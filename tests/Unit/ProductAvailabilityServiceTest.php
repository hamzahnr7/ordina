<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entity\Product;
use App\Repository\InMemory\InMemoryProductRepository;
use App\Repository\InMemory\InMemoryProductStockRepository;
use App\Service\ProductAvailabilityService;
use PHPUnit\Framework\TestCase;

/**
 * Demonstrates ARCH-01: Service logic tested against the in-memory fakes,
 * with no PDO/session/database involved. Replace/extend once PO/SO status
 * transitions and stock-calculation rules are implemented (TEST-01 needs
 * >=6 cases across >=3 logic areas).
 */
final class ProductAvailabilityServiceTest extends TestCase
{
    public function test_returns_stock_per_warehouse_for_known_sku(): void
    {
        $products = new InMemoryProductRepository();
        $products->save(new Product(
            id: 1,
            sku: 'SKU-0001',
            name: 'Wireless Mouse',
            categoryId: 1,
            unit: 'pcs',
            buyPrice: 45000,
            sellPrice: 75000,
            reorderPoint: 20,
        ));

        $stocks = new InMemoryProductStockRepository();
        $stocks->seed(1, [
            ['warehouse_id' => 1, 'warehouse_name' => 'Warehouse Jakarta', 'quantity' => 15],
            ['warehouse_id' => 2, 'warehouse_name' => 'Warehouse Surabaya', 'quantity' => 40],
        ]);

        $service = new ProductAvailabilityService($products, $stocks);

        $result = $service->availabilityBySku('SKU-0001');

        self::assertNotNull($result);
        self::assertSame('SKU-0001', $result['sku']);
        self::assertCount(2, $result['warehouses']);
    }

    public function test_returns_null_for_unknown_sku(): void
    {
        $service = new ProductAvailabilityService(
            new InMemoryProductRepository(),
            new InMemoryProductStockRepository()
        );

        self::assertNull($service->availabilityBySku('DOES-NOT-EXIST'));
    }
}
