<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Domain\PurchaseOrderStatus;
use App\Entity\PurchaseOrder;
use App\Repository\Contracts\PurchaseOrderRepositoryInterface;
use PDO;

final class MysqlPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?PurchaseOrder
    {
        $stmt = $this->connection->prepare('SELECT * FROM purchase_orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : PurchaseOrder::fromArray($row);
    }

    public function paginateForListing(array $filters, string $sortDir, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(s.name LIKE :search_name OR po.id = :search_id)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_id'] = ctype_digit((string) $filters['search']) ? (int) $filters['search'] : 0;
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'po.status = :status';
            $params['status'] = $filters['status'];
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $base = "FROM purchase_orders po
                  JOIN suppliers s ON s.id = po.supplier_id
                  JOIN warehouses w ON w.id = po.warehouse_id
                  {$where}";

        $countStmt = $this->connection->prepare("SELECT COUNT(*) {$base}");
        $this->bindFilterParams($countStmt, $params);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $itemsStmt = $this->connection->prepare(
            "SELECT po.*, s.name AS supplier_name, w.name AS warehouse_name
             {$base}
             ORDER BY po.order_date {$direction}, po.id {$direction}
             LIMIT :limit OFFSET :offset"
        );
        $this->bindFilterParams($itemsStmt, $params);
        $itemsStmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $itemsStmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $itemsStmt->execute();

        return ['items' => $itemsStmt->fetchAll(), 'total' => $total];
    }

    /** @param array<string, mixed> $params */
    private function bindFilterParams(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }

    public function save(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if ($purchaseOrder->id === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO purchase_orders (supplier_id, warehouse_id, status, order_date, created_by)
                 VALUES (:supplier_id, :warehouse_id, :status, :order_date, :created_by)'
            );
            $stmt->execute([
                'supplier_id' => $purchaseOrder->supplierId,
                'warehouse_id' => $purchaseOrder->warehouseId,
                'status' => $purchaseOrder->status->value,
                'order_date' => $purchaseOrder->orderDate,
                'created_by' => $purchaseOrder->createdBy,
            ]);

            return $this->findById((int) $this->connection->lastInsertId());
        }

        $stmt = $this->connection->prepare(
            'UPDATE purchase_orders SET supplier_id = :supplier_id, warehouse_id = :warehouse_id, order_date = :order_date WHERE id = :id'
        );
        $stmt->execute([
            'supplier_id' => $purchaseOrder->supplierId,
            'warehouse_id' => $purchaseOrder->warehouseId,
            'order_date' => $purchaseOrder->orderDate,
            'id' => $purchaseOrder->id,
        ]);

        return $this->findById($purchaseOrder->id);
    }

    public function updateStatus(int $id, PurchaseOrderStatus $status): void
    {
        $stmt = $this->connection->prepare('UPDATE purchase_orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status->value, 'id' => $id]);
    }

    public function findForReport(string $from, string $to): array
    {
        $stmt = $this->connection->prepare(
            'SELECT po.id, po.order_date, s.name AS supplier_name, po.status
             FROM purchase_orders po
             JOIN suppliers s ON s.id = po.supplier_id
             WHERE po.order_date BETWEEN :from AND :to
             ORDER BY po.order_date'
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        return $stmt->fetchAll();
    }
}
