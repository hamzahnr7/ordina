<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repository\InMemory\InMemorySupplierRepository;
use App\Service\Exception\ValidationException;
use App\Service\SupplierService;
use PHPUnit\Framework\TestCase;

final class SupplierServiceTest extends TestCase
{
    private SupplierService $service;

    protected function setUp(): void
    {
        $this->service = new SupplierService(new InMemorySupplierRepository());
    }

    public function test_create_trims_fields_and_starts_active(): void
    {
        $supplier = $this->service->create(['name' => ' PT Kertas ', 'contact' => '', 'address' => ' Bandung ']);

        self::assertSame('PT Kertas', $supplier->name);
        self::assertNull($supplier->contact);
        self::assertSame('Bandung', $supplier->address);
        self::assertTrue($supplier->isActive);
    }

    public function test_create_rejects_blank_name(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(['name' => '']);
    }

    public function test_update_keeps_active_flag_and_replaces_fields(): void
    {
        $supplier = $this->service->create(['name' => 'Supplier Lama', 'contact' => 'lama@x.test']);
        $this->service->setActive((int) $supplier->id, false);

        $updated = $this->service->update((int) $supplier->id, ['name' => 'Supplier Baru', 'contact' => 'baru@x.test']);

        self::assertSame('Supplier Baru', $updated->name);
        self::assertSame('baru@x.test', $updated->contact);
        self::assertNull($updated->address);
        self::assertFalse($updated->isActive);
    }

    public function test_update_rejects_unknown_supplier(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->update(999, ['name' => 'Siapa']);
    }

    public function test_update_rejects_blank_name(): void
    {
        $supplier = $this->service->create(['name' => 'Supplier']);

        $this->expectException(ValidationException::class);

        $this->service->update((int) $supplier->id, ['name' => '  ']);
    }

    public function test_list_active_hides_deactivated_suppliers(): void
    {
        $active = $this->service->create(['name' => 'Aktif']);
        $inactive = $this->service->create(['name' => 'Nonaktif']);
        $this->service->setActive((int) $inactive->id, false);

        self::assertCount(2, $this->service->list());
        self::assertSame([$active->id], array_map(static fn ($s) => $s->id, $this->service->listActive()));
        self::assertFalse($this->service->find((int) $inactive->id)?->isActive);
    }
}
