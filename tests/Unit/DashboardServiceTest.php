<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repository\Contracts\DashboardRepositoryInterface;
use App\Service\DashboardService;
use PHPUnit\Framework\TestCase;

/**
 * DASH-01: each role gets exactly its own §2.5 numbers. The aggregation SQL
 * itself lives in MysqlDashboardRepository (no InMemory fake), so the
 * repository is a mock here - this pins what each role is shown and that
 * Sales counts are always scoped to the signed-in user.
 */
final class DashboardServiceTest extends TestCase
{
    private const LOW_STOCK_ROWS = [['sku' => 'SKU-0006', 'name' => 'Webcam', 'total_stock' => 7, 'reorder_point' => 10]];

    public function test_admin_sees_inventory_value_low_stock_and_all_order_counts(): void
    {
        $repository = $this->createMock(DashboardRepositoryInterface::class);
        $repository->method('inventoryValue')->willReturn(1250000.0);
        $repository->method('lowStockProductCount')->willReturn(6);
        $repository->expects(self::once())->method('lowStockProducts')->with(10)->willReturn(self::LOW_STOCK_ROWS);
        $repository->method('purchaseOrderStatusCounts')->willReturn(['Draft' => 3]);
        $repository->expects(self::once())->method('salesOrderStatusCounts')->with(null)->willReturn(['Approved' => 2]);

        self::assertSame([
            'inventoryValue' => 1250000.0,
            'lowStockCount' => 6,
            'lowStockProducts' => self::LOW_STOCK_ROWS,
            'purchaseOrderStatusCounts' => ['Draft' => 3],
            'salesOrderStatusCounts' => ['Approved' => 2],
        ], (new DashboardService($repository))->forAdmin());
    }

    public function test_sales_sees_only_their_own_order_counts(): void
    {
        $repository = $this->createMock(DashboardRepositoryInterface::class);
        $repository->expects(self::once())->method('salesOrderStatusCounts')->with(2)->willReturn(['Draft' => 1]);
        $repository->expects(self::never())->method('inventoryValue');
        $repository->expects(self::never())->method('purchaseOrderStatusCounts');

        self::assertSame(['salesOrderStatusCounts' => ['Draft' => 1]], (new DashboardService($repository))->forSales(2));
    }

    public function test_warehouse_staff_sees_pending_queues_and_low_stock(): void
    {
        $repository = $this->createMock(DashboardRepositoryInterface::class);
        $repository->method('pendingGoodsReceiptCount')->willReturn(5);
        $repository->method('pendingGoodsIssueCount')->willReturn(2);
        $repository->method('lowStockProductCount')->willReturn(6);
        $repository->expects(self::once())->method('lowStockProducts')->with(10)->willReturn(self::LOW_STOCK_ROWS);
        $repository->expects(self::never())->method('inventoryValue');

        self::assertSame([
            'pendingGoodsReceiptCount' => 5,
            'pendingGoodsIssueCount' => 2,
            'lowStockCount' => 6,
            'lowStockProducts' => self::LOW_STOCK_ROWS,
        ], (new DashboardService($repository))->forWarehouseStaff());
    }
}
