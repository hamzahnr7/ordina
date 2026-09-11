<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Repository\Contracts\ProductStockRepositoryInterface;
use PDO;

final class MysqlProductStockRepository implements ProductStockRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findByProductId(int $productId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT ps.warehouse_id, w.name AS warehouse_name, ps.quantity
             FROM product_stocks ps
             JOIN warehouses w ON w.id = ps.warehouse_id
             WHERE ps.product_id = :product_id
             ORDER BY w.name'
        );
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    public function incrementQuantity(int $productId, int $warehouseId, int $delta): void
    {
        $existing = $this->connection->prepare(
            'SELECT id FROM product_stocks WHERE product_id = :product_id AND warehouse_id = :warehouse_id LIMIT 1'
        );
        $existing->execute(['product_id' => $productId, 'warehouse_id' => $warehouseId]);
        $row = $existing->fetch();

        if ($row === false) {
            $insert = $this->connection->prepare(
                'INSERT INTO product_stocks (product_id, warehouse_id, quantity) VALUES (:product_id, :warehouse_id, :quantity)'
            );
            $insert->execute(['product_id' => $productId, 'warehouse_id' => $warehouseId, 'quantity' => $delta]);

            return;
        }

        $update = $this->connection->prepare('UPDATE product_stocks SET quantity = quantity + :delta WHERE id = :id');
        $update->execute(['delta' => $delta, 'id' => $row['id']]);
    }

    public function decrementIfAvailable(int $productId, int $warehouseId, int $qty): bool
    {
        $stmt = $this->connection->prepare(
            'UPDATE product_stocks
             SET quantity = quantity - :qty
             WHERE product_id = :product_id AND warehouse_id = :warehouse_id AND quantity >= :qty_check'
        );
        $stmt->execute([
            'qty' => $qty,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'qty_check' => $qty,
        ]);

        return $stmt->rowCount() > 0;
    }
}
