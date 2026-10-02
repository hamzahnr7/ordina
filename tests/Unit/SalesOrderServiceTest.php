<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Transaction\NullTransactionManager;
use App\Domain\SalesOrderStatus;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\SalesOrder;
use App\Entity\Warehouse;
use App\Repository\InMemory\InMemoryCustomerRepository;
use App\Repository\InMemory\InMemoryProductRepository;
use App\Repository\InMemory\InMemoryProductStockRepository;
use App\Repository\InMemory\InMemorySalesOrderItemRepository;
use App\Repository\InMemory\InMemorySalesOrderRepository;
use App\Repository\InMemory\InMemoryStockLedgerRepository;
use App\Repository\InMemory\InMemoryWarehouseRepository;
use App\Service\Exception\ForbiddenOperationException;
use App\Service\Exception\ValidationException;
use App\Service\SalesOrderService;
use PHPUnit\Framework\TestCase;

/**
 * SO-01 lifecycle + the SoD self-approval rule + ARCH-02's actual
 * load-bearing scenario: goods issue must reject once stock is exhausted,
 * not oversell - entirely against InMemory fakes, no MySQL needed.
 */
final class SalesOrderServiceTest extends TestCase
{
    private const CREATOR_ID = 10;
    private const APPROVER_ID = 20;

    private InMemorySalesOrderRepository $salesOrders;
    private InMemorySalesOrderItemRepository $items;
    private InMemoryProductStockRepository $stocks;
    private InMemoryStockLedgerRepository $ledger;
    private SalesOrderService $service;
    private int $customerId;
    private int $warehouseId;
    private int $productId;

    protected function setUp(): void
    {
        $customers = new InMemoryCustomerRepository();
        $customer = $customers->save(new Customer(null, 'Customer Test', null, null, true));

        $warehouses = new InMemoryWarehouseRepository();
        $warehouse = $warehouses->save(new Warehouse(null, 'Gudang Test', 'Jakarta', true));

        $products = new InMemoryProductRepository();
        $product = $products->save(new Product(
            id: null,
            sku: 'SKU-SO-TEST',
            name: 'Produk SO Test',
            categoryId: 1,
            unit: 'pcs',
            buyPrice: 1000,
            sellPrice: 2000,
            reorderPoint: 5,
        ));

        $this->salesOrders = new InMemorySalesOrderRepository();
        $this->items = new InMemorySalesOrderItemRepository();
        $this->stocks = new InMemoryProductStockRepository();
        $this->stocks->seed($product->id, [
            ['warehouse_id' => $warehouse->id, 'warehouse_name' => $warehouse->name, 'quantity' => 10],
        ]);
        $this->ledger = new InMemoryStockLedgerRepository();

        $this->service = new SalesOrderService(
            $this->salesOrders,
            $this->items,
            $this->stocks,
            $this->ledger,
            $customers,
            $warehouses,
            $products,
            new NullTransactionManager(),
        );

        $this->customerId = $customer->id;
        $this->warehouseId = $warehouse->id;
        $this->productId = $product->id;
    }

    private function createDraftSo(int $qty = 4): int
    {
        $so = $this->service->create(
            ['customer_id' => $this->customerId, 'warehouse_id' => $this->warehouseId],
            [['product_id' => $this->productId, 'qty' => $qty, 'sell_price' => 2000]],
            self::CREATOR_ID,
        );

        return $so->id;
    }

    public function test_create_rejects_empty_item_list(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(
            ['customer_id' => $this->customerId, 'warehouse_id' => $this->warehouseId],
            [],
            self::CREATOR_ID,
        );
    }

    public function test_create_rejects_unknown_customer(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(
            ['customer_id' => 999, 'warehouse_id' => $this->warehouseId],
            [['product_id' => $this->productId, 'qty' => 1, 'sell_price' => 2000]],
            self::CREATOR_ID,
        );
    }

    /** Sell price may only be raised from the product's own price, never lowered. */
    public function test_create_rejects_sell_price_below_product_price(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(
            ['customer_id' => $this->customerId, 'warehouse_id' => $this->warehouseId],
            [['product_id' => $this->productId, 'qty' => 1, 'sell_price' => 1999]], // product's own price is 2000
            self::CREATOR_ID,
        );
    }

    public function test_create_accepts_sell_price_at_or_above_product_price(): void
    {
        $so = $this->service->create(
            ['customer_id' => $this->customerId, 'warehouse_id' => $this->warehouseId],
            [['product_id' => $this->productId, 'qty' => 1, 'sell_price' => 2500]], // raised above 2000
            self::CREATOR_ID,
        );

        self::assertNotNull($so->id);
    }

    public function test_submit_moves_draft_to_pending_approval(): void
    {
        $soId = $this->createDraftSo();
        $this->service->submit($soId);

        self::assertSame(SalesOrderStatus::PendingApproval, $this->service->find($soId)->status);
    }

    public function test_submit_rejected_once_already_submitted(): void
    {
        $soId = $this->createDraftSo();
        $this->service->submit($soId);

        $this->expectException(ForbiddenOperationException::class);
        $this->service->submit($soId);
    }

    public function test_creator_cannot_approve_their_own_order(): void
    {
        $soId = $this->createDraftSo();
        $this->service->submit($soId);

        $this->expectException(ForbiddenOperationException::class);
        $this->service->approve($soId, self::CREATOR_ID);
    }

    public function test_different_user_can_approve(): void
    {
        $soId = $this->createDraftSo();
        $this->service->submit($soId);
        $this->service->approve($soId, self::APPROVER_ID);

        $so = $this->service->find($soId);
        self::assertSame(SalesOrderStatus::Approved, $so->status);
        self::assertSame(self::APPROVER_ID, $so->approvedBy);
    }

    public function test_reject_moves_pending_approval_to_cancelled(): void
    {
        $soId = $this->createDraftSo();
        $this->service->submit($soId);
        $this->service->reject($soId);

        self::assertSame(SalesOrderStatus::Cancelled, $this->service->find($soId)->status);
    }

    public function test_cannot_cancel_once_fulfilled(): void
    {
        $soId = $this->createDraftSo(qty: 3);
        $this->service->submit($soId);
        $this->service->approve($soId, self::APPROVER_ID);
        $this->service->processGoodsIssue($soId, self::APPROVER_ID);

        self::assertSame(SalesOrderStatus::Fulfilled, $this->service->find($soId)->status);

        $this->expectException(ForbiddenOperationException::class);
        $this->service->cancel($soId);
    }

    public function test_goods_issue_rejected_unless_approved(): void
    {
        $soId = $this->createDraftSo();

        $this->expectException(ForbiddenOperationException::class);
        $this->service->processGoodsIssue($soId, self::APPROVER_ID);
    }

    /** ARCH-02: the actual load-bearing scenario for this whole mechanism. */
    public function test_goods_issue_decrements_stock_and_writes_ledger(): void
    {
        $soId = $this->createDraftSo(qty: 4);
        $this->service->submit($soId);
        $this->service->approve($soId, self::APPROVER_ID);

        $this->service->processGoodsIssue($soId, self::APPROVER_ID);

        self::assertSame(SalesOrderStatus::Fulfilled, $this->service->find($soId)->status);
        self::assertSame(6, $this->stocks->findByProductId($this->productId)[0]['quantity']); // 10 - 4
        self::assertCount(1, $this->ledger->findByReference('SO', $soId));
    }

    /**
     * The scenario ARCH-02 exists for: a second Sales Order draining the
     * same product+warehouse must be rejected once the first has exhausted
     * it - not oversold, and left Approved (not silently Fulfilled) so the
     * failure is visible.
     */
    public function test_second_goods_issue_rejected_once_stock_exhausted_by_the_first(): void
    {
        $firstSoId = $this->createDraftSo(qty: 10); // exactly all available stock
        $this->service->submit($firstSoId);
        $this->service->approve($firstSoId, self::APPROVER_ID);
        $this->service->processGoodsIssue($firstSoId, self::APPROVER_ID);

        self::assertSame(0, $this->stocks->findByProductId($this->productId)[0]['quantity']);

        $secondSoId = $this->createDraftSo(qty: 1); // nothing left
        $this->service->submit($secondSoId);
        $this->service->approve($secondSoId, self::APPROVER_ID);

        $this->expectException(ValidationException::class);

        try {
            $this->service->processGoodsIssue($secondSoId, self::APPROVER_ID);
        } finally {
            // Rejected, not silently fulfilled or half-issued.
            self::assertSame(SalesOrderStatus::Approved, $this->service->find($secondSoId)->status);
            self::assertSame(0, $this->stocks->findByProductId($this->productId)[0]['quantity']);
            self::assertCount(0, $this->ledger->findByReference('SO', $secondSoId));
        }
    }

    public function test_approve_rejected_unless_pending_approval(): void
    {
        $soId = $this->createDraftSo();

        $this->expectException(ForbiddenOperationException::class);

        $this->service->approve($soId, self::APPROVER_ID);
    }

    public function test_reject_rejected_unless_pending_approval(): void
    {
        $soId = $this->createDraftSo();

        $this->expectException(ForbiddenOperationException::class);

        $this->service->reject($soId);
    }

    public function test_cancel_moves_a_draft_so_to_cancelled(): void
    {
        $soId = $this->createDraftSo();

        $this->service->cancel($soId);

        self::assertSame(SalesOrderStatus::Cancelled, $this->service->find($soId)->status);
    }

    public function test_goods_issue_of_an_approved_order_without_items_is_refused(): void
    {
        $so = $this->salesOrders->save(new SalesOrder(null, $this->customerId, $this->warehouseId, SalesOrderStatus::Approved, self::CREATOR_ID, self::APPROVER_ID));

        $this->expectException(ForbiddenOperationException::class);
        $this->expectExceptionMessage('tidak memiliki item');

        $this->service->processGoodsIssue((int) $so->id, self::APPROVER_ID);
    }

    public function test_create_reports_every_invalid_header_and_item_field(): void
    {
        try {
            $this->service->create(
                ['customer_id' => 0, 'warehouse_id' => 999],
                [['product_id' => 999, 'qty' => 'abc', 'sell_price' => 'gratis']],
                self::CREATOR_ID,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(
                ['customer_id', 'warehouse_id', 'item_0_product', 'item_0_qty', 'item_0_price'],
                array_keys($e->errors())
            );
        }
    }

    public function test_detail_resolves_names_and_includes_ledger(): void
    {
        $soId = $this->createDraftSo(qty: 2);
        $this->service->submit($soId);
        $this->service->approve($soId, self::APPROVER_ID);
        $this->service->processGoodsIssue($soId, self::APPROVER_ID);

        $detail = $this->service->detail($soId);

        self::assertNotNull($detail);
        self::assertSame('Customer Test', $detail['customerName']);
        self::assertSame('Gudang Test', $detail['warehouseName']);
        self::assertSame('Produk SO Test', $detail['items'][0]['productName']);
        self::assertSame('SKU-SO-TEST', $detail['items'][0]['productSku']);
        self::assertCount(1, $detail['ledger']);
    }

    public function test_detail_falls_back_to_dash_for_missing_references(): void
    {
        $so = $this->salesOrders->save(new SalesOrder(null, 999, 999, SalesOrderStatus::Draft, self::CREATOR_ID, null));
        $this->items->insertMany((int) $so->id, [['product_id' => 999, 'qty' => 1, 'sell_price' => 1]]);

        $detail = $this->service->detail((int) $so->id);

        self::assertNotNull($detail);
        self::assertSame('-', $detail['customerName']);
        self::assertSame('-', $detail['warehouseName']);
        self::assertSame('-', $detail['items'][0]['productName']);
        self::assertSame('-', $detail['items'][0]['productSku']);
    }

    public function test_detail_of_unknown_so_is_null(): void
    {
        self::assertNull($this->service->detail(999));
    }

    public function test_paginate_scopes_to_owner_and_normalizes_paging(): void
    {
        $mine = $this->createDraftSo();
        $this->service->create(
            ['customer_id' => $this->customerId, 'warehouse_id' => $this->warehouseId],
            [['product_id' => $this->productId, 'qty' => 1, 'sell_price' => 2000]],
            self::APPROVER_ID, // someone else's order
        );

        $own = $this->service->paginate([], self::CREATOR_ID, 'ASC', page: 0, perPage: 25);
        self::assertSame(1, $own['page']);
        self::assertSame(25, $own['perPage']);
        self::assertSame('asc', $own['sortDir']);
        self::assertSame([$mine], array_column($own['items'], 'id'));

        $everyone = $this->service->paginate(['status' => 'Draft'], null, 'desc', page: 1, perPage: 1);
        self::assertSame(2, $everyone['total']);
        self::assertSame(2, $everyone['totalPages']);
        self::assertSame('desc', $everyone['sortDir']);
    }
}
