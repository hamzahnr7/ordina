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
}
