<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findBySku(string $sku): ?Product;

    public function skuExists(string $sku, ?int $excludingId = null): bool;

    /** @return list<Product> */
    public function findAll(): array;

    /** For dropdowns on other forms (e.g. Purchase Order items) - active products only. */
    public function findActive(): array;

    /**
     * FIND-01: search by name/SKU, filter by category and stock status,
     * paginated. Returns denormalized rows (category_name, total_stock
     * across all warehouses joined in) for the listing view - a read model,
     * not the Product entity, since single-record reads/writes
     * (findById/save) don't need those joins.
     *
     * @param array{search?:string, category_id?:int, stock_status?:'low'|'normal'} $filters
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginateForListing(array $filters, int $page, int $perPage): array;

    /** Inserts when $product->id is null, otherwise updates every column except sku. */
    public function save(Product $product): Product;

    public function setActive(int $id, bool $active): void;
}
