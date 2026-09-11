<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Contracts\DashboardRepositoryInterface;

/** DASH-01: one method per role, matching exactly the numbers §2.5 asks that role to see. */
final class DashboardService
{
    private const LOW_STOCK_PREVIEW_LIMIT = 10;

    public function __construct(private readonly DashboardRepositoryInterface $dashboard)
    {
    }

    /** @return array{inventoryValue:float, lowStockCount:int, lowStockProducts:list<array<string,mixed>>, purchaseOrderStatusCounts:array<string,int>, salesOrderStatusCounts:array<string,int>} */
    public function forAdmin(): array
    {
        return [
            'inventoryValue' => $this->dashboard->inventoryValue(),
            'lowStockCount' => $this->dashboard->lowStockProductCount(),
            'lowStockProducts' => $this->dashboard->lowStockProducts(self::LOW_STOCK_PREVIEW_LIMIT),
            'purchaseOrderStatusCounts' => $this->dashboard->purchaseOrderStatusCounts(),
            'salesOrderStatusCounts' => $this->dashboard->salesOrderStatusCounts(null),
        ];
    }

    /** @return array{salesOrderStatusCounts: array<string,int>} */
    public function forSales(int $userId): array
    {
        return [
            'salesOrderStatusCounts' => $this->dashboard->salesOrderStatusCounts($userId),
        ];
    }

    /** @return array{pendingGoodsReceiptCount:int, pendingGoodsIssueCount:int, lowStockCount:int, lowStockProducts:list<array<string,mixed>>} */
    public function forWarehouseStaff(): array
    {
        return [
            'pendingGoodsReceiptCount' => $this->dashboard->pendingGoodsReceiptCount(),
            'pendingGoodsIssueCount' => $this->dashboard->pendingGoodsIssueCount(),
            'lowStockCount' => $this->dashboard->lowStockProductCount(),
            'lowStockProducts' => $this->dashboard->lowStockProducts(self::LOW_STOCK_PREVIEW_LIMIT),
        ];
    }
}
