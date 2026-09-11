<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\PurchaseOrderItem;
use App\Repository\Contracts\PurchaseOrderItemRepositoryInterface;

final class InMemoryPurchaseOrderItemRepository implements PurchaseOrderItemRepositoryInterface
{
    /** @var array<int, PurchaseOrderItem> */
    private array $byId = [];

    private int $nextId = 1;

    public function findByPurchaseOrderId(int $purchaseOrderId): array
    {
        return array_values(array_filter(
            $this->byId,
            static fn (PurchaseOrderItem $item): bool => $item->purchaseOrderId === $purchaseOrderId
        ));
    }

    public function findById(int $id): ?PurchaseOrderItem
    {
        return $this->byId[$id] ?? null;
    }

    public function insertMany(int $purchaseOrderId, array $items): void
    {
        foreach ($items as $item) {
            $id = $this->nextId++;
            $this->byId[$id] = new PurchaseOrderItem(
                $id,
                $purchaseOrderId,
                (int) $item['product_id'],
                (int) $item['qty_ordered'],
                0,
                (float) $item['buy_price'],
            );
        }
    }

    public function incrementReceivedQty(int $itemId, int $qty): void
    {
        $item = $this->byId[$itemId];
        $this->byId[$itemId] = new PurchaseOrderItem(
            $item->id,
            $item->purchaseOrderId,
            $item->productId,
            $item->qtyOrdered,
            $item->qtyReceived + $qty,
            $item->buyPrice,
        );
    }
}
