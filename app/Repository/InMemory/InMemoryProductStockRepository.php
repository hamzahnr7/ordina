<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Repository\Contracts\ProductStockRepositoryInterface;

final class InMemoryProductStockRepository implements ProductStockRepositoryInterface
{
    /** @var array<int, list<array{warehouse_id:int, warehouse_name:string, quantity:int}>> */
    private array $stocks = [];

    /** @param list<array{warehouse_id:int, warehouse_name:string, quantity:int}> $rows */
    public function seed(int $productId, array $rows): void
    {
        $this->stocks[$productId] = $rows;
    }

    public function findByProductId(int $productId): array
    {
        return $this->stocks[$productId] ?? [];
    }
}
