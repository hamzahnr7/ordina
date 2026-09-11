<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Entity\PurchaseOrderItem;
use App\Repository\Contracts\PurchaseOrderItemRepositoryInterface;
use PDO;

final class MysqlPurchaseOrderItemRepository implements PurchaseOrderItemRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findByPurchaseOrderId(int $purchaseOrderId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM purchase_order_items WHERE purchase_order_id = :purchase_order_id ORDER BY id'
        );
        $stmt->execute(['purchase_order_id' => $purchaseOrderId]);

        return array_map(PurchaseOrderItem::fromArray(...), $stmt->fetchAll());
    }

    public function findById(int $id): ?PurchaseOrderItem
    {
        $stmt = $this->connection->prepare('SELECT * FROM purchase_order_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : PurchaseOrderItem::fromArray($row);
    }

    public function insertMany(int $purchaseOrderId, array $items): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO purchase_order_items (purchase_order_id, product_id, qty_ordered, buy_price)
             VALUES (:purchase_order_id, :product_id, :qty_ordered, :buy_price)'
        );

        foreach ($items as $item) {
            $stmt->execute([
                'purchase_order_id' => $purchaseOrderId,
                'product_id' => $item['product_id'],
                'qty_ordered' => $item['qty_ordered'],
                'buy_price' => $item['buy_price'],
            ]);
        }
    }

    public function incrementReceivedQty(int $itemId, int $qty): void
    {
        $stmt = $this->connection->prepare('UPDATE purchase_order_items SET qty_received = qty_received + :qty WHERE id = :id');
        $stmt->execute(['qty' => $qty, 'id' => $itemId]);
    }
}
