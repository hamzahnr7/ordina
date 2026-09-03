<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\Product;
use App\Repository\Contracts\ProductRepositoryInterface;

/**
 * Fake used by unit tests (ARCH-01) so Service logic can be verified without
 * a real MySQL connection.
 */
final class InMemoryProductRepository implements ProductRepositoryInterface
{
    /** @var array<string, Product> */
    private array $products = [];

    public function findBySku(string $sku): ?Product
    {
        return $this->products[$sku] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->products);
    }

    public function save(Product $product): void
    {
        $this->products[$product->sku] = $product;
    }
}
