<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entity\Category;
use App\Repository\InMemory\InMemoryCategoryRepository;
use App\Repository\InMemory\InMemoryProductRepository;
use App\Repository\InMemory\InMemoryProductStockRepository;
use App\Service\Exception\ValidationException;
use App\Service\ProductImageUploader;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;

final class ProductServiceTest extends TestCase
{
    public function test_total_stock_below_reorder_point_is_low_stock(): void
    {
        self::assertTrue(ProductService::isLowStock(totalStock: 5, reorderPoint: 20));
    }

    public function test_total_stock_at_or_above_reorder_point_is_not_low_stock(): void
    {
        self::assertFalse(ProductService::isLowStock(totalStock: 20, reorderPoint: 20));
        self::assertFalse(ProductService::isLowStock(totalStock: 25, reorderPoint: 20));
    }

    private function service(): ProductService
    {
        $categories = new InMemoryCategoryRepository();
        $categories->save(new Category(id: 1, name: 'Electronics', description: null));

        return new ProductService(
            new InMemoryProductRepository(),
            $categories,
            new InMemoryProductStockRepository(),
            new ProductImageUploader(uploadDir: sys_get_temp_dir())
        );
    }

    public function test_create_rejects_duplicate_sku(): void
    {
        $service = $this->service();
        $service->create([
            'sku' => 'SKU-DUP', 'name' => 'Product A', 'category_id' => 1,
            'unit' => 'pcs', 'buy_price' => 1000, 'sell_price' => 2000, 'reorder_point' => 5,
        ], null);

        $this->expectException(ValidationException::class);

        $service->create([
            'sku' => 'SKU-DUP', 'name' => 'Product B', 'category_id' => 1,
            'unit' => 'pcs', 'buy_price' => 1000, 'sell_price' => 2000, 'reorder_point' => 5,
        ], null);
    }

    public function test_create_rejects_unknown_category(): void
    {
        $service = $this->service();

        $this->expectException(ValidationException::class);

        $service->create([
            'sku' => 'SKU-X', 'name' => 'Product X', 'category_id' => 999,
            'unit' => 'pcs', 'buy_price' => 1000, 'sell_price' => 2000, 'reorder_point' => 5,
        ], null);
    }

    public function test_create_rejects_negative_reorder_point(): void
    {
        $service = $this->service();

        $this->expectException(ValidationException::class);

        $service->create([
            'sku' => 'SKU-Y', 'name' => 'Product Y', 'category_id' => 1,
            'unit' => 'pcs', 'buy_price' => 1000, 'sell_price' => 2000, 'reorder_point' => -1,
        ], null);
    }
}
