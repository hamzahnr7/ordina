<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\Product;
use App\Repository\Contracts\ProductRepositoryInterface;

/**
 * Fake used by unit tests (ARCH-01) so Service logic can be verified without
 * a real MySQL connection. paginateForListing() here only covers
 * search/category filtering + pagination mechanics - it has no
 * product_stocks to join, so `total_stock` is always 0 and `stock_status`
 * filtering is ignored. Stock-aware listing is covered by tests/Integration
 * (TEST-02) against real MySQL instead.
 */
final class InMemoryProductRepository implements ProductRepositoryInterface
{
    /** @var array<int, Product> */
    private array $productsById = [];

    private int $nextId = 1;

    public function findById(int $id): ?Product
    {
        return $this->productsById[$id] ?? null;
    }

    public function findBySku(string $sku): ?Product
    {
        foreach ($this->productsById as $product) {
            if ($product->sku === $sku) {
                return $product;
            }
        }

        return null;
    }

    public function skuExists(string $sku, ?int $excludingId = null): bool
    {
        foreach ($this->productsById as $product) {
            if ($product->sku === $sku && $product->id !== $excludingId) {
                return true;
            }
        }

        return false;
    }

    public function findAll(): array
    {
        return array_values($this->productsById);
    }

    public function findActive(): array
    {
        return array_values(array_filter($this->productsById, static fn (Product $p): bool => $p->isActive));
    }

    public function paginateForListing(array $filters, int $page, int $perPage): array
    {
        $matches = array_values(array_filter($this->productsById, function (Product $product) use ($filters): bool {
            if (!empty($filters['search'])) {
                $needle = mb_strtolower($filters['search']);
                $haystack = mb_strtolower($product->name . ' ' . $product->sku);

                if (!str_contains($haystack, $needle)) {
                    return false;
                }
            }

            if (!empty($filters['category_id']) && $product->categoryId !== $filters['category_id']) {
                return false;
            }

            return true;
        }));

        usort($matches, static fn (Product $a, Product $b) => $a->name <=> $b->name);

        $total = count($matches);
        $offset = max(0, ($page - 1) * $perPage);

        $items = array_map(
            static fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category_id' => $p->categoryId,
                'unit' => $p->unit,
                'buy_price' => $p->buyPrice,
                'sell_price' => $p->sellPrice,
                'reorder_point' => $p->reorderPoint,
                'image_path' => $p->imagePath,
                'is_active' => $p->isActive,
                'total_stock' => 0,
            ],
            array_slice($matches, $offset, $perPage)
        );

        return ['items' => $items, 'total' => $total];
    }

    public function save(Product $product): Product
    {
        $id = $product->id ?? $this->nextId++;
        $saved = new Product(
            id: $id,
            sku: $product->sku,
            name: $product->name,
            categoryId: $product->categoryId,
            unit: $product->unit,
            buyPrice: $product->buyPrice,
            sellPrice: $product->sellPrice,
            reorderPoint: $product->reorderPoint,
            imagePath: $product->imagePath,
            isActive: $product->isActive,
        );
        $this->productsById[$id] = $saved;

        return $saved;
    }

    public function setActive(int $id, bool $active): void
    {
        $product = $this->productsById[$id];
        $this->productsById[$id] = new Product(
            id: $product->id,
            sku: $product->sku,
            name: $product->name,
            categoryId: $product->categoryId,
            unit: $product->unit,
            buyPrice: $product->buyPrice,
            sellPrice: $product->sellPrice,
            reorderPoint: $product->reorderPoint,
            imagePath: $product->imagePath,
            isActive: $active,
        );
    }
}
