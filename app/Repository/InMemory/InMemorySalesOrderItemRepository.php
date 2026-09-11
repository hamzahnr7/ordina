<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\SalesOrderItem;
use App\Repository\Contracts\SalesOrderItemRepositoryInterface;

final class InMemorySalesOrderItemRepository implements SalesOrderItemRepositoryInterface
{
    /** @var array<int, SalesOrderItem> */
    private array $byId = [];

    private int $nextId = 1;

    public function findBySalesOrderId(int $salesOrderId): array
    {
        return array_values(array_filter(
            $this->byId,
            static fn (SalesOrderItem $item): bool => $item->salesOrderId === $salesOrderId
        ));
    }

    public function insertMany(int $salesOrderId, array $items): void
    {
        foreach ($items as $item) {
            $id = $this->nextId++;
            $this->byId[$id] = new SalesOrderItem(
                $id,
                $salesOrderId,
                (int) $item['product_id'],
                (int) $item['qty'],
                (float) $item['sell_price'],
            );
        }
    }
}
