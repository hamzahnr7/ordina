<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Entity\SalesOrderItem;
use App\Repository\Contracts\SalesOrderItemRepositoryInterface;
use PDO;

final class MysqlSalesOrderItemRepository implements SalesOrderItemRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findBySalesOrderId(int $salesOrderId): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM sales_order_items WHERE sales_order_id = :sales_order_id ORDER BY id');
        $stmt->execute(['sales_order_id' => $salesOrderId]);

        return array_map(SalesOrderItem::fromArray(...), $stmt->fetchAll());
    }

    public function insertMany(int $salesOrderId, array $items): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO sales_order_items (sales_order_id, product_id, qty, sell_price)
             VALUES (:sales_order_id, :product_id, :qty, :sell_price)'
        );

        foreach ($items as $item) {
            $stmt->execute([
                'sales_order_id' => $salesOrderId,
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'sell_price' => $item['sell_price'],
            ]);
        }
    }
}
