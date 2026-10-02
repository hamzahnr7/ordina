<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\SalesOrderStatus;
use App\Service\Exception\ValidationException;

/**
 * ARCH-02 on real MySQL: the conditional UPDATE (quantity >= qty) is the
 * oversell guard, and PdoTransactionManager must roll back every write made
 * earlier in the same goods issue when a later item fails.
 */
final class GoodsIssueIntegrationTest extends DatabaseTestCase
{
    public function test_second_goods_issue_is_rejected_once_first_exhausts_stock(): void
    {
        $this->givenStock(self::PRODUCT_ID, 10);
        $first = $this->approvedSalesOrder([['product_id' => self::PRODUCT_ID, 'qty' => 8]]);
        $second = $this->approvedSalesOrder([['product_id' => self::PRODUCT_ID, 'qty' => 5]]);
        $service = $this->salesOrderService();

        $service->processGoodsIssue($first, self::WAREHOUSE_STAFF_ID);

        try {
            $service->processGoodsIssue($second, self::WAREHOUSE_STAFF_ID);
            self::fail('Second goods issue should have been rejected - only 2 left, 5 requested.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('stock', $e->errors());
        }

        self::assertSame(2, $this->stockOf(self::PRODUCT_ID));
        self::assertSame(SalesOrderStatus::Fulfilled, $service->find($first)?->status);
        self::assertSame(SalesOrderStatus::Approved, $service->find($second)?->status);
        self::assertSame(0, $this->ledgerRowCount('SO', $second));
        self::assertSame($this->stockOf(self::PRODUCT_ID), $this->ledgerBalanceOf(self::PRODUCT_ID));
    }

    public function test_failed_item_rolls_back_earlier_items_in_same_goods_issue(): void
    {
        $this->givenStock(self::PRODUCT_ID, 10);
        $this->givenStock(self::OTHER_PRODUCT_ID, 1);
        $soId = $this->approvedSalesOrder([
            ['product_id' => self::PRODUCT_ID, 'qty' => 4],       // would succeed on its own
            ['product_id' => self::OTHER_PRODUCT_ID, 'qty' => 3], // insufficient -> whole issue fails
        ]);
        $service = $this->salesOrderService();

        try {
            $service->processGoodsIssue($soId, self::WAREHOUSE_STAFF_ID);
            self::fail('Goods issue should have been rejected for insufficient stock on the second item.');
        } catch (ValidationException) {
        }

        self::assertSame(10, $this->stockOf(self::PRODUCT_ID), 'First item decrement must be rolled back.');
        self::assertSame(1, $this->stockOf(self::OTHER_PRODUCT_ID));
        self::assertSame(0, $this->ledgerRowCount('SO', $soId), 'No ledger row may survive the rollback.');
        self::assertSame(SalesOrderStatus::Approved, $service->find($soId)?->status);
    }
}
