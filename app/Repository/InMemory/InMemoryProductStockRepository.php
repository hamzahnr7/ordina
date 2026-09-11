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

    public function incrementQuantity(int $productId, int $warehouseId, int $delta): void
    {
        $rows = $this->stocks[$productId] ?? [];

        foreach ($rows as $index => $row) {
            if ($row['warehouse_id'] === $warehouseId) {
                $rows[$index]['quantity'] += $delta;
                $this->stocks[$productId] = $rows;

                return;
            }
        }

        $rows[] = ['warehouse_id' => $warehouseId, 'warehouse_name' => "Warehouse #{$warehouseId}", 'quantity' => $delta];
        $this->stocks[$productId] = $rows;
    }

    public function decrementIfAvailable(int $productId, int $warehouseId, int $qty): bool
    {
        $rows = $this->stocks[$productId] ?? [];

        foreach ($rows as $index => $row) {
            if ($row['warehouse_id'] === $warehouseId) {
                if ($row['quantity'] < $qty) {
                    return false;
                }

                $rows[$index]['quantity'] -= $qty;
                $this->stocks[$productId] = $rows;

                return true;
            }
        }

        return false;
    }
}
