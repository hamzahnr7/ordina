<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function findBySku(string $sku): ?Product;

    /** @return Product[] */
    public function findAll(): array;

    public function save(Product $product): void;
}
