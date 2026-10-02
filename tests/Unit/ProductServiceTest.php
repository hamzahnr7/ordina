<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entity\Category;
use App\Entity\Product;
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

    /** @return array<string, mixed> */
    private static function validInput(string $sku = 'SKU-OK'): array
    {
        return [
            'sku' => $sku, 'name' => 'Wireless Mouse', 'category_id' => 1,
            'unit' => 'pcs', 'buy_price' => '45000', 'sell_price' => '75000', 'reorder_point' => '20',
        ];
    }

    public function test_create_reports_every_missing_required_field_at_once(): void
    {
        try {
            $this->service()->create(['sku' => ' ', 'name' => '', 'unit' => '', 'category_id' => 0, 'buy_price' => 'abc'], null);
            self::fail('Empty product should be rejected.');
        } catch (ValidationException $e) {
            self::assertSame(
                ['sku', 'name', 'unit', 'category_id', 'buy_price', 'sell_price', 'reorder_point'],
                array_keys($e->errors())
            );
        }
    }

    public function test_create_trims_and_casts_input_and_starts_active(): void
    {
        $product = $this->service()->create(['sku' => ' SKU-1 ', 'name' => ' Mouse ', 'unit' => ' pcs '] + self::validInput(), null);

        self::assertSame('SKU-1', $product->sku);
        self::assertSame('Mouse', $product->name);
        self::assertSame('pcs', $product->unit);
        self::assertSame(45000.0, $product->buyPrice);
        self::assertSame(20, $product->reorderPoint);
        self::assertNull($product->imagePath);
        self::assertTrue($product->isActive);
    }

    public function test_update_keeps_sku_image_and_active_flag(): void
    {
        $products = new InMemoryProductRepository();
        $categories = new InMemoryCategoryRepository();
        $categories->save(new Category(id: 1, name: 'Electronics', description: null));
        $service = new ProductService($products, $categories, new InMemoryProductStockRepository(), new ProductImageUploader(sys_get_temp_dir()));

        $existing = $products->save(new Product(null, 'SKU-KEEP', 'Lama', 1, 'pcs', 1, 2, 3, 'uploads/products/old.png', false));

        // Even if the form posts a different SKU, it must be ignored (SKU is immutable).
        $updated = $service->update((int) $existing->id, ['sku' => 'SKU-HACK'] + self::validInput(), null);

        self::assertSame('SKU-KEEP', $updated->sku);
        self::assertSame('Wireless Mouse', $updated->name);
        self::assertSame(75000.0, $updated->sellPrice);
        self::assertSame('uploads/products/old.png', $updated->imagePath);
        self::assertFalse($updated->isActive);
    }

    public function test_update_rejects_unknown_product(): void
    {
        $this->expectException(ValidationException::class);

        $this->service()->update(999, self::validInput(), null);
    }

    public function test_update_rejects_invalid_input(): void
    {
        $service = $this->service();
        $product = $service->create(self::validInput(), null);

        $this->expectException(ValidationException::class);

        $service->update((int) $product->id, ['name' => ''] + self::validInput(), null);
    }

    public function test_update_may_keep_its_own_sku_without_tripping_the_duplicate_check(): void
    {
        $service = $this->service();
        $product = $service->create(self::validInput('SKU-SELF'), null);

        self::assertSame('Baru', $service->update((int) $product->id, ['name' => 'Baru'] + self::validInput(), null)->name);
    }

    public function test_detail_sums_stock_across_warehouses_and_flags_low_stock(): void
    {
        $products = new InMemoryProductRepository();
        $categories = new InMemoryCategoryRepository();
        $categories->save(new Category(id: 1, name: 'Electronics', description: null));
        $stocks = new InMemoryProductStockRepository();
        $service = new ProductService($products, $categories, $stocks, new ProductImageUploader(sys_get_temp_dir()));

        $product = $service->create(self::validInput(), null); // reorder point 20
        $stocks->seed((int) $product->id, [
            ['warehouse_id' => 1, 'warehouse_name' => 'Jakarta', 'quantity' => 8],
            ['warehouse_id' => 2, 'warehouse_name' => 'Surabaya', 'quantity' => 7],
        ]);

        $detail = $service->detail((int) $product->id);

        self::assertNotNull($detail);
        self::assertSame('Electronics', $detail['categoryName']);
        self::assertSame(15, $detail['totalStock']);
        self::assertTrue($detail['isLowStock']);
        self::assertCount(2, $detail['stocks']);
    }

    public function test_detail_of_product_with_missing_category_and_no_stock(): void
    {
        $products = new InMemoryProductRepository();
        $service = new ProductService($products, new InMemoryCategoryRepository(), new InMemoryProductStockRepository(), new ProductImageUploader(sys_get_temp_dir()));
        $product = $products->save(new Product(null, 'SKU-ORPHAN', 'Yatim', 99, 'pcs', 1, 2, 0));

        $detail = $service->detail((int) $product->id);

        self::assertNotNull($detail);
        self::assertSame('-', $detail['categoryName']);
        self::assertSame(0, $detail['totalStock']);
        self::assertFalse($detail['isLowStock']);
    }

    public function test_detail_of_unknown_product_is_null(): void
    {
        self::assertNull($this->service()->detail(999));
    }

    public function test_paginate_clamps_page_and_computes_total_pages(): void
    {
        $service = $this->service();
        foreach (['A', 'B', 'C'] as $letter) {
            $service->create(['name' => "Produk {$letter}"] + self::validInput("SKU-{$letter}"), null);
        }

        $page = $service->paginate([], page: 0, perPage: 2);

        self::assertSame(1, $page['page']);
        self::assertSame(2, $page['perPage']);
        self::assertSame(3, $page['total']);
        self::assertSame(2, $page['totalPages']);
        self::assertSame(['Produk A', 'Produk B'], array_column($page['items'], 'name'));
    }

    public function test_paginate_with_no_results_still_has_one_page(): void
    {
        $page = $this->service()->paginate(['search' => 'tidak-ada'], page: 1);

        self::assertSame(0, $page['total']);
        self::assertSame(1, $page['totalPages']);
        self::assertSame(10, $page['perPage']);
    }

    public function test_set_active_and_list_active(): void
    {
        $service = $this->service();
        $kept = $service->create(self::validInput('SKU-KEPT'), null);
        $dropped = $service->create(self::validInput('SKU-DROP'), null);

        $service->setActive((int) $dropped->id, false);

        self::assertFalse($service->find((int) $dropped->id)?->isActive);
        self::assertSame([$kept->id], array_map(static fn ($p) => $p->id, $service->listActive()));
    }
}
