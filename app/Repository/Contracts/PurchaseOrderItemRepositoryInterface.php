<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\PurchaseOrderItem;

interface PurchaseOrderItemRepositoryInterface
{
    /** @return list<PurchaseOrderItem> */
    public function findByPurchaseOrderId(int $purchaseOrderId): array;

    public function findById(int $id): ?PurchaseOrderItem;

    /**
     * @param list<array{product_id:int, qty_ordered:int, buy_price:float}> $items
     */
    public function insertMany(int $purchaseOrderId, array $items): void;

    public function incrementReceivedQty(int $itemId, int $qty): void;
}
