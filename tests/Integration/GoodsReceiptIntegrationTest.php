<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\PurchaseOrderStatus;

/**
 * PO-01 goods receipt end-to-end on real MySQL: ProductStock, StockLedger,
 * qty_received and the PO status must all move together in one transaction.
 */
final class GoodsReceiptIntegrationTest extends DatabaseTestCase
{
    public function test_partial_then_full_receipt_updates_stock_ledger_and_status(): void
    {
        $this->givenStock(self::PRODUCT_ID, 5);
        $service = $this->purchaseOrderService();

        $po = $service->create(
            ['supplier_id' => 1, 'warehouse_id' => self::WAREHOUSE_ID, 'order_date' => '2026-10-01'],
            [['product_id' => self::PRODUCT_ID, 'qty_ordered' => 10, 'buy_price' => 10000]],
            self::ADMIN_ID
        );
        $poId = (int) $po->id;
        $service->markOrdered($poId);
        $itemId = (int) $service->detail($poId)['items'][0]['item']->id;

        $service->receiveGoods($poId, [$itemId => 4], self::WAREHOUSE_STAFF_ID);

        self::assertSame(9, $this->stockOf(self::PRODUCT_ID));
        self::assertSame(PurchaseOrderStatus::PartiallyReceived, $service->find($poId)?->status);
        self::assertSame(1, $this->ledgerRowCount('PO', $poId));

        $service->receiveGoods($poId, [$itemId => 6], self::WAREHOUSE_STAFF_ID);

        self::assertSame(15, $this->stockOf(self::PRODUCT_ID));
        self::assertSame(PurchaseOrderStatus::Received, $service->find($poId)?->status);
        self::assertSame(2, $this->ledgerRowCount('PO', $poId));
        self::assertSame(10, $service->detail($poId)['items'][0]['item']->qtyReceived);
        self::assertSame($this->stockOf(self::PRODUCT_ID), $this->ledgerBalanceOf(self::PRODUCT_ID));
    }

    public function test_receipt_creates_stock_row_for_product_never_stocked_in_that_warehouse(): void
    {
        $service = $this->purchaseOrderService();
        $po = $service->create(
            ['supplier_id' => 1, 'warehouse_id' => self::WAREHOUSE_ID, 'order_date' => '2026-10-01'],
            [['product_id' => self::OTHER_PRODUCT_ID, 'qty_ordered' => 3, 'buy_price' => 20000]],
            self::ADMIN_ID
        );
        $poId = (int) $po->id;
        $service->markOrdered($poId);
        $itemId = (int) $service->detail($poId)['items'][0]['item']->id;

        self::assertNull($this->stockOf(self::OTHER_PRODUCT_ID));

        $service->receiveGoods($poId, [$itemId => 3], self::WAREHOUSE_STAFF_ID);

        self::assertSame(3, $this->stockOf(self::OTHER_PRODUCT_ID));
        self::assertSame(3, $this->ledgerBalanceOf(self::OTHER_PRODUCT_ID));
    }
}
