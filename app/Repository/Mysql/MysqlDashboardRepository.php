<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Domain\PurchaseOrderStatus;
use App\Domain\SalesOrderStatus;
use App\Repository\Contracts\DashboardRepositoryInterface;
use PDO;

final class MysqlDashboardRepository implements DashboardRepositoryInterface
{
    /** Shared by lowStockProductCount()/lowStockProducts() - pre-aggregates stock per product, same subquery shape as MysqlProductRepository::paginateForListing() (no GROUP BY/HAVING ambiguity). */
    private const LOW_STOCK_BASE = "
        FROM products p
        LEFT JOIN (
            SELECT product_id, SUM(quantity) AS total_stock
            FROM product_stocks
            GROUP BY product_id
        ) s ON s.product_id = p.id
        WHERE p.is_active = 1 AND COALESCE(s.total_stock, 0) < p.reorder_point
    ";

    public function __construct(private readonly PDO $connection)
    {
    }

    public function inventoryValue(): float
    {
        $stmt = $this->connection->query(
            'SELECT COALESCE(SUM(ps.quantity * p.buy_price), 0) AS value
             FROM product_stocks ps
             JOIN products p ON p.id = ps.product_id'
        );

        return (float) $stmt->fetchColumn();
    }

    public function lowStockProductCount(): int
    {
        $stmt = $this->connection->query('SELECT COUNT(*) ' . self::LOW_STOCK_BASE);

        return (int) $stmt->fetchColumn();
    }

    public function lowStockProducts(int $limit): array
    {
        $stmt = $this->connection->prepare(
            'SELECT p.sku, p.name, p.reorder_point, COALESCE(s.total_stock, 0) AS total_stock '
            . self::LOW_STOCK_BASE
            . ' ORDER BY (p.reorder_point - COALESCE(s.total_stock, 0)) DESC LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function purchaseOrderStatusCounts(): array
    {
        $stmt = $this->connection->query('SELECT status, COUNT(*) AS total FROM purchase_orders GROUP BY status');

        return $this->fillStatusCounts($stmt->fetchAll(), PurchaseOrderStatus::cases());
    }

    public function salesOrderStatusCounts(?int $ownerId): array
    {
        if ($ownerId !== null) {
            $stmt = $this->connection->prepare('SELECT status, COUNT(*) AS total FROM sales_orders WHERE created_by = :owner_id GROUP BY status');
            $stmt->execute(['owner_id' => $ownerId]);
        } else {
            $stmt = $this->connection->query('SELECT status, COUNT(*) AS total FROM sales_orders GROUP BY status');
        }

        return $this->fillStatusCounts($stmt->fetchAll(), SalesOrderStatus::cases());
    }

    public function pendingGoodsReceiptCount(): int
    {
        $stmt = $this->connection->query("SELECT COUNT(*) FROM purchase_orders WHERE status IN ('Ordered', 'PartiallyReceived')");

        return (int) $stmt->fetchColumn();
    }

    public function pendingGoodsIssueCount(): int
    {
        $stmt = $this->connection->query("SELECT COUNT(*) FROM sales_orders WHERE status = 'Approved'");

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param list<array{status:string, total:string|int}> $rows
     * @param list<PurchaseOrderStatus>|list<SalesOrderStatus> $allStatuses
     * @return array<string, int>
     */
    private function fillStatusCounts(array $rows, array $allStatuses): array
    {
        $counts = [];

        foreach ($allStatuses as $status) {
            $counts[$status->value] = 0;
        }

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
