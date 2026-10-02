<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repository\InMemory\InMemoryCategoryRepository;
use App\Service\CategoryService;
use App\Service\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class CategoryServiceTest extends TestCase
{
    private CategoryService $service;

    protected function setUp(): void
    {
        $this->service = new CategoryService(new InMemoryCategoryRepository());
    }

    public function test_create_trims_input_and_blank_description_becomes_null(): void
    {
        $category = $this->service->create(['name' => '  Electronics  ', 'description' => '   ']);

        self::assertSame('Electronics', $category->name);
        self::assertNull($category->description);
        self::assertSame($category->id, $this->service->find((int) $category->id)?->id);
    }

    public function test_create_keeps_a_real_description_trimmed(): void
    {
        self::assertSame('Alat tulis', $this->service->create(['name' => 'Stationery', 'description' => ' Alat tulis '])->description);
    }

    public function test_create_without_description_key_stores_null(): void
    {
        self::assertNull($this->service->create(['name' => 'Tanpa Deskripsi'])->description);
    }

    public function test_create_rejects_blank_name(): void
    {
        try {
            $this->service->create(['name' => '   ']);
            self::fail('Blank name should be rejected.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('name', $e->errors());
        }

        self::assertSame([], $this->service->list());
    }

    public function test_update_changes_the_existing_category(): void
    {
        $category = $this->service->create(['name' => 'Elektronik']);

        $this->service->update((int) $category->id, ['name' => 'Electronics', 'description' => 'Gadget']);

        $stored = $this->service->find((int) $category->id);
        self::assertNotNull($stored);
        self::assertSame('Electronics', $stored->name);
        self::assertSame('Gadget', $stored->description);
        self::assertCount(1, $this->service->list());
    }

    public function test_update_rejects_blank_name(): void
    {
        $category = $this->service->create(['name' => 'Elektronik']);

        $this->expectException(ValidationException::class);

        $this->service->update((int) $category->id, ['name' => '']);
    }
}
