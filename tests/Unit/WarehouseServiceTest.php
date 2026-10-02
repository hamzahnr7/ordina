<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repository\InMemory\InMemoryWarehouseRepository;
use App\Service\Exception\ValidationException;
use App\Service\WarehouseService;
use PHPUnit\Framework\TestCase;

final class WarehouseServiceTest extends TestCase
{
    private WarehouseService $service;

    protected function setUp(): void
    {
        $this->service = new WarehouseService(new InMemoryWarehouseRepository());
    }

    public function test_create_trims_fields_and_starts_active(): void
    {
        $warehouse = $this->service->create(['name' => ' Jakarta ', 'location' => ' Jl. Gudang 1 ']);

        self::assertSame('Jakarta', $warehouse->name);
        self::assertSame('Jl. Gudang 1', $warehouse->location);
        self::assertTrue($warehouse->isActive);
    }

    public function test_create_requires_both_name_and_location(): void
    {
        try {
            $this->service->create(['name' => '', 'location' => ' ']);
            self::fail('Blank name and location should be rejected.');
        } catch (ValidationException $e) {
            self::assertSame(['name', 'location'], array_keys($e->errors()));
        }
    }

    public function test_update_keeps_active_flag_and_replaces_fields(): void
    {
        $warehouse = $this->service->create(['name' => 'Gudang', 'location' => 'Lama']);
        $this->service->setActive((int) $warehouse->id, false);

        $updated = $this->service->update((int) $warehouse->id, ['name' => 'Gudang Surabaya', 'location' => 'Baru']);

        self::assertSame('Gudang Surabaya', $updated->name);
        self::assertSame('Baru', $updated->location);
        self::assertFalse($updated->isActive);
    }

    public function test_update_rejects_unknown_warehouse(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->update(999, ['name' => 'X', 'location' => 'Y']);
    }

    public function test_update_rejects_blank_location(): void
    {
        $warehouse = $this->service->create(['name' => 'Gudang', 'location' => 'Jakarta']);

        $this->expectException(ValidationException::class);

        $this->service->update((int) $warehouse->id, ['name' => 'Gudang', 'location' => '']);
    }

    public function test_list_active_hides_deactivated_warehouses(): void
    {
        $active = $this->service->create(['name' => 'Aktif', 'location' => 'A']);
        $inactive = $this->service->create(['name' => 'Nonaktif', 'location' => 'B']);
        $this->service->setActive((int) $inactive->id, false);

        self::assertCount(2, $this->service->list());
        self::assertSame([$active->id], array_map(static fn ($w) => $w->id, $this->service->listActive()));
        self::assertFalse($this->service->find((int) $inactive->id)?->isActive);
    }
}
