<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

interface ProductStockRepositoryInterface
{
    /** @return list<array{warehouse_id:int, warehouse_name:string, quantity:int}> */
    public function findByProductId(int $productId): array;
}
