<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Repository\Contracts\StockLedgerRepositoryInterface;
use PDO;

final class MysqlStockLedgerRepository implements StockLedgerRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function record(
        int $productId,
        int $warehouseId,
        string $movementType,
        int $quantity,
        string $referenceType,
        ?int $referenceId,
        int $performedBy,
    ): void {
        $stmt = $this->connection->prepare(
            'INSERT INTO stock_ledger (product_id, warehouse_id, movement_type, quantity, reference_type, reference_id, performed_by)
             VALUES (:product_id, :warehouse_id, :movement_type, :quantity, :reference_type, :reference_id, :performed_by)'
        );
        $stmt->execute([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by' => $performedBy,
        ]);
    }

    public function findByReference(string $referenceType, int $referenceId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT sl.*, p.name AS product_name, p.sku, w.name AS warehouse_name, u.name AS performed_by_name
             FROM stock_ledger sl
             JOIN products p ON p.id = sl.product_id
             JOIN warehouses w ON w.id = sl.warehouse_id
             JOIN users u ON u.id = sl.performed_by
             WHERE sl.reference_type = :reference_type AND sl.reference_id = :reference_id
             ORDER BY sl.id'
        );
        $stmt->execute(['reference_type' => $referenceType, 'reference_id' => $referenceId]);

        return $stmt->fetchAll();
    }

    public function findByDateRange(string $from, string $to): array
    {
        $stmt = $this->connection->prepare(
            'SELECT sl.*, p.name AS product_name, p.sku, w.name AS warehouse_name, u.name AS performed_by_name
             FROM stock_ledger sl
             JOIN products p ON p.id = sl.product_id
             JOIN warehouses w ON w.id = sl.warehouse_id
             JOIN users u ON u.id = sl.performed_by
             WHERE DATE(sl.created_at) BETWEEN :from AND :to
             ORDER BY sl.created_at'
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        return $stmt->fetchAll();
    }
}
