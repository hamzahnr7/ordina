<?php

declare(strict_types=1);

namespace App\Entity;

final class Product
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $sku,
        public readonly string $name,
        public readonly int $categoryId,
        public readonly string $unit,
        public readonly float $buyPrice,
        public readonly float $sellPrice,
        public readonly int $reorderPoint,
        public readonly bool $isActive = true,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            sku: (string) $row['sku'],
            name: (string) $row['name'],
            categoryId: (int) $row['category_id'],
            unit: (string) $row['unit'],
            buyPrice: (float) $row['buy_price'],
            sellPrice: (float) $row['sell_price'],
            reorderPoint: (int) $row['reorder_point'],
            isActive: (bool) $row['is_active'],
        );
    }
}
