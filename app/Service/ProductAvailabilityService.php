<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Contracts\ProductRepositoryInterface;
use App\Repository\Contracts\ProductStockRepositoryInterface;

/**
 * Reference implementation of the Controller -> Service -> Repository
 * pattern (ARCH-01), backing the API-01 endpoint. Both dependencies are
 * interfaces injected via the constructor - no `new PDO()` and no direct
 * MySQL coupling here.
 */
final class ProductAvailabilityService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductStockRepositoryInterface $stocks,
    ) {
    }

    /** @return array{sku:string, name:string, warehouses:list<array{warehouse_id:int, warehouse_name:string, quantity:int}>}|null */
    public function availabilityBySku(string $sku): ?array
    {
        $product = $this->products->findBySku($sku);

        if ($product === null || $product->id === null) {
            return null;
        }

        return [
            'sku' => $product->sku,
            'name' => $product->name,
            'warehouses' => $this->stocks->findByProductId($product->id),
        ];
    }
}
