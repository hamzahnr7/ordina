<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Domain\SalesOrderStatus;
use App\Entity\SalesOrder;
use App\Repository\Contracts\SalesOrderRepositoryInterface;
use PDO;
use PDOStatement;

final class MysqlSalesOrderRepository implements SalesOrderRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?SalesOrder
    {
        $stmt = $this->connection->prepare('SELECT * FROM sales_orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : SalesOrder::fromArray($row);
    }

    public function paginateForListing(array $filters, ?int $ownerId, string $sortDir, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(c.name LIKE :search_name OR so.id = :search_id)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_id'] = ctype_digit((string) $filters['search']) ? (int) $filters['search'] : 0;
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'so.status = :status';
            $params['status'] = $filters['status'];
        }

        if ($ownerId !== null) {
            $conditions[] = 'so.created_by = :owner_id';
            $params['owner_id'] = $ownerId;
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $base = "FROM sales_orders so
                  JOIN customers c ON c.id = so.customer_id
                  JOIN warehouses w ON w.id = so.warehouse_id
                  {$where}";

        $countStmt = $this->connection->prepare("SELECT COUNT(*) {$base}");
        $this->bindFilterParams($countStmt, $params);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $itemsStmt = $this->connection->prepare(
            "SELECT so.*, c.name AS customer_name, w.name AS warehouse_name
             {$base}
             ORDER BY so.created_at {$direction}, so.id {$direction}
             LIMIT :limit OFFSET :offset"
        );
        $this->bindFilterParams($itemsStmt, $params);
        $itemsStmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $itemsStmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $itemsStmt->execute();

        return ['items' => $itemsStmt->fetchAll(), 'total' => $total];
    }

    /** @param array<string, mixed> $params */
    private function bindFilterParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }

    public function save(SalesOrder $salesOrder): SalesOrder
    {
        if ($salesOrder->id === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO sales_orders (customer_id, warehouse_id, status, created_by)
                 VALUES (:customer_id, :warehouse_id, :status, :created_by)'
            );
            $stmt->execute([
                'customer_id' => $salesOrder->customerId,
                'warehouse_id' => $salesOrder->warehouseId,
                'status' => $salesOrder->status->value,
                'created_by' => $salesOrder->createdBy,
            ]);

            return $this->findById((int) $this->connection->lastInsertId());
        }

        $stmt = $this->connection->prepare(
            'UPDATE sales_orders SET customer_id = :customer_id, warehouse_id = :warehouse_id WHERE id = :id'
        );
        $stmt->execute([
            'customer_id' => $salesOrder->customerId,
            'warehouse_id' => $salesOrder->warehouseId,
            'id' => $salesOrder->id,
        ]);

        return $this->findById($salesOrder->id);
    }

    public function updateStatus(int $id, SalesOrderStatus $status): void
    {
        $stmt = $this->connection->prepare('UPDATE sales_orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status->value, 'id' => $id]);
    }

    public function approve(int $id, int $approvedBy): void
    {
        $stmt = $this->connection->prepare(
            "UPDATE sales_orders SET status = 'Approved', approved_by = :approved_by WHERE id = :id"
        );
        $stmt->execute(['approved_by' => $approvedBy, 'id' => $id]);
    }

    public function findForReport(string $from, string $to, ?int $ownerId): array
    {
        $where = 'WHERE DATE(so.created_at) BETWEEN :from AND :to';
        $params = ['from' => $from, 'to' => $to];

        if ($ownerId !== null) {
            $where .= ' AND so.created_by = :owner_id';
            $params['owner_id'] = $ownerId;
        }

        $stmt = $this->connection->prepare(
            "SELECT so.id, DATE(so.created_at) AS date, c.name AS customer_name, so.status
             FROM sales_orders so
             JOIN customers c ON c.id = so.customer_id
             {$where}
             ORDER BY so.created_at"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
