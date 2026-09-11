<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Transaction\NullTransactionManager;
use App\Domain\PurchaseOrderStatus;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\Warehouse;
use App\Repository\InMemory\InMemoryProductRepository;
use App\Repository\InMemory\InMemoryProductStockRepository;
use App\Repository\InMemory\InMemoryPurchaseOrderItemRepository;
use App\Repository\InMemory\InMemoryPurchaseOrderRepository;
use App\Repository\InMemory\InMemoryStockLedgerRepository;
use App\Repository\InMemory\InMemorySupplierRepository;
use App\Repository\InMemory\InMemoryWarehouseRepository;
use App\Service\Exception\ForbiddenOperationException;
use App\Service\Exception\ValidationException;
use App\Service\PurchaseOrderService;
use PHPUnit\Framework\TestCase;

/**
 * Exercises PO-01's full lifecycle plus the ARCH-02-relevant path
 * (receiveGoods() writing ProductStock + StockLedger together) entirely
 * against InMemory fakes - no MySQL needed. Mirrors what
 * MysqlPurchaseOrderItemRepository/MysqlProductStockRepository do for real,
 * see docs/architecture/adr-0002-concurrency-safe-stock.md.
 */
final class PurchaseOrderServiceTest extends TestCase
{
    private InMemoryPurchaseOrderRepository $purchaseOrders;
    private InMemoryPurchaseOrderItemRepository $items;
    private InMemoryProductStockRepository $stocks;
    private InMemoryStockLedgerRepository $ledger;
    private PurchaseOrderService $service;
    private int $supplierId;
    private int $warehouseId;
    private int $productId;

    protected function setUp(): void
    {
        $suppliers = new InMemorySupplierRepository();
        $supplier = $suppliers->save(new Supplier(null, 'Supplier Test', null, null, true));

        $warehouses = new InMemoryWarehouseRepository();
        $warehouse = $warehouses->save(new Warehouse(null, 'Gudang Test', 'Jakarta', true));

        $products = new InMemoryProductRepository();
        $product = $products->save(new Product(
            id: null,
            sku: 'SKU-TEST',
            name: 'Produk Test',
            categoryId: 1,
            unit: 'pcs',
            buyPrice: 1000,
            sellPrice: 2000,
            reorderPoint: 5,
        ));

        $this->purchaseOrders = new InMemoryPurchaseOrderRepository();
        $this->items = new InMemoryPurchaseOrderItemRepository();
        $this->stocks = new InMemoryProductStockRepository();
        $this->ledger = new InMemoryStockLedgerRepository();

        $this->service = new PurchaseOrderService(
            $this->purchaseOrders,
            $this->items,
            $this->stocks,
            $this->ledger,
            $suppliers,
            $warehouses,
            $products,
            new NullTransactionManager(),
        );

        $this->supplierId = $supplier->id;
        $this->warehouseId = $warehouse->id;
        $this->productId = $product->id;
    }

    private function createDraftPo(int $qtyOrdered = 10): int
    {
        $po = $this->service->create(
            [
                'supplier_id' => $this->supplierId,
                'warehouse_id' => $this->warehouseId,
                'order_date' => '2026-01-15',
            ],
            [['product_id' => $this->productId, 'qty_ordered' => $qtyOrdered, 'buy_price' => 1000]],
            createdBy: 1,
        );

        return $po->id;
    }

    public function test_create_rejects_empty_item_list(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(
            ['supplier_id' => $this->supplierId, 'warehouse_id' => $this->warehouseId, 'order_date' => '2026-01-15'],
            [],
            1,
        );
    }

    public function test_create_rejects_unknown_supplier(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(
            ['supplier_id' => 999, 'warehouse_id' => $this->warehouseId, 'order_date' => '2026-01-15'],
            [['product_id' => $this->productId, 'qty_ordered' => 5, 'buy_price' => 1000]],
            1,
        );
    }

    public function test_create_starts_as_draft(): void
    {
        $poId = $this->createDraftPo();

        self::assertSame(PurchaseOrderStatus::Draft, $this->service->find($poId)->status);
    }

    public function test_mark_ordered_requires_draft_status(): void
    {
        $poId = $this->createDraftPo();
        $this->service->markOrdered($poId);

        self::assertSame(PurchaseOrderStatus::Ordered, $this->service->find($poId)->status);

        $this->expectException(ForbiddenOperationException::class);
        $this->service->markOrdered($poId);
    }

    public function test_receive_goods_rejected_before_po_is_ordered(): void
    {
        $poId = $this->createDraftPo();

        $this->expectException(ForbiddenOperationException::class);
        $this->service->receiveGoods($poId, [], 1);
    }

    public function test_partial_receipt_updates_stock_ledger_and_status(): void
    {
        $poId = $this->createDraftPo(qtyOrdered: 10);
        $this->service->markOrdered($poId);

        $items = $this->items->findByPurchaseOrderId($poId);
        $itemId = $items[0]->id;

        $this->service->receiveGoods($poId, [$itemId => 4], performedBy: 1);

        self::assertSame(PurchaseOrderStatus::PartiallyReceived, $this->service->find($poId)->status);
        self::assertSame(4, $this->items->findById($itemId)->qtyReceived);

        $stock = $this->stocks->findByProductId($this->productId);
        self::assertSame(4, $stock[0]['quantity']);
        self::assertCount(1, $this->ledger->findByReference('PO', $poId));

        // Receive the remainder - PO should now be fully Received.
        $this->service->receiveGoods($poId, [$itemId => 6], performedBy: 1);

        self::assertSame(PurchaseOrderStatus::Received, $this->service->find($poId)->status);
        self::assertSame(10, $this->stocks->findByProductId($this->productId)[0]['quantity']);
        self::assertCount(2, $this->ledger->findByReference('PO', $poId));
    }

    public function test_cannot_receive_more_than_remaining_quantity(): void
    {
        $poId = $this->createDraftPo(qtyOrdered: 5);
        $this->service->markOrdered($poId);

        $itemId = $this->items->findByPurchaseOrderId($poId)[0]->id;
        $this->service->receiveGoods($poId, [$itemId => 3], performedBy: 1); // remaining: 2

        // PO is still PartiallyReceived (receivable) - the qty itself is
        // what's invalid here, not the PO's status.
        $this->expectException(ValidationException::class);
        $this->service->receiveGoods($poId, [$itemId => 3], performedBy: 1);
    }

    public function test_cannot_receive_goods_once_po_is_fully_received(): void
    {
        $poId = $this->createDraftPo(qtyOrdered: 5);
        $this->service->markOrdered($poId);

        $itemId = $this->items->findByPurchaseOrderId($poId)[0]->id;
        $this->service->receiveGoods($poId, [$itemId => 5], performedBy: 1);

        self::assertSame(PurchaseOrderStatus::Received, $this->service->find($poId)->status);

        // The PO is fully Received now - a further receipt must be rejected
        // by the status guard, not silently over-fulfill the item.
        $this->expectException(ForbiddenOperationException::class);
        $this->service->receiveGoods($poId, [$itemId => 1], performedBy: 1);
    }

    public function test_cancel_not_allowed_once_fully_received(): void
    {
        $poId = $this->createDraftPo(qtyOrdered: 2);
        $this->service->markOrdered($poId);
        $itemId = $this->items->findByPurchaseOrderId($poId)[0]->id;
        $this->service->receiveGoods($poId, [$itemId => 2], performedBy: 1);

        $this->expectException(ForbiddenOperationException::class);
        $this->service->cancel($poId);
    }
}
