<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\SalesOrderItem;

interface SalesOrderItemRepositoryInterface
{
    /** @return list<SalesOrderItem> */
    public function findBySalesOrderId(int $salesOrderId): array;

    /** @param list<array{product_id:int, qty:int, sell_price:float}> $items */
    public function insertMany(int $salesOrderId, array $items): void;
}
