<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

/**
 * DASH-01: every number here comes from a live aggregation query, never a
 * static value. REPORT-01 is expected to reuse these same queries for the
 * CSV export ("dihasilkan dari query agregasi/rekap yang sama dengan
 * dashboard") - keep that in mind before adding a one-off query elsewhere
 * for the report instead of a method here.
 */
interface DashboardRepositoryInterface
{
    /** Total value of all stock on hand, at cost (buy_price). */
    public function inventoryValue(): float;

    public function lowStockProductCount(): int;

    /** @return list<array{sku:string, name:string, reorder_point:int, total_stock:int}> */
    public function lowStockProducts(int $limit): array;

    /** @return array<string, int> PurchaseOrderStatus::value => count, every status present even if 0 */
    public function purchaseOrderStatusCounts(): array;

    /** @param int|null $ownerId scope to one creator (Sales), or null for all (Admin) @return array<string, int> SalesOrderStatus::value => count */
    public function salesOrderStatusCounts(?int $ownerId): array;

    /** Purchase Orders in Ordered/PartiallyReceived - awaiting goods receipt. */
    public function pendingGoodsReceiptCount(): int;

    /** Sales Orders Approved - awaiting goods issue. */
    public function pendingGoodsIssueCount(): int;
}
