<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repository\InMemory\InMemoryCustomerRepository;
use App\Service\CustomerService;
use App\Service\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class CustomerServiceTest extends TestCase
{
    private CustomerService $service;

    protected function setUp(): void
    {
        $this->service = new CustomerService(new InMemoryCustomerRepository());
    }

    public function test_create_trims_fields_and_starts_active(): void
    {
        $customer = $this->service->create(['name' => ' Toko Maju ', 'contact' => ' 0812 ', 'address' => '']);

        self::assertSame('Toko Maju', $customer->name);
        self::assertSame('0812', $customer->contact);
        self::assertNull($customer->address);
        self::assertTrue($customer->isActive);
    }

    public function test_create_rejects_blank_name(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(['name' => ' ']);
    }

    public function test_update_keeps_active_flag_and_replaces_fields(): void
    {
        $customer = $this->service->create(['name' => 'Toko Lama']);
        $this->service->setActive((int) $customer->id, false);

        $updated = $this->service->update((int) $customer->id, ['name' => 'Toko Baru', 'address' => 'Surabaya']);

        self::assertSame('Toko Baru', $updated->name);
        self::assertSame('Surabaya', $updated->address);
        self::assertNull($updated->contact);
        self::assertFalse($updated->isActive, 'Editing must not silently re-activate a customer.');
    }

    public function test_update_rejects_unknown_customer(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->update(999, ['name' => 'Siapa']);
    }

    public function test_update_rejects_blank_name(): void
    {
        $customer = $this->service->create(['name' => 'Toko']);

        $this->expectException(ValidationException::class);

        $this->service->update((int) $customer->id, ['name' => '']);
    }

    public function test_list_active_hides_deactivated_customers(): void
    {
        $active = $this->service->create(['name' => 'Aktif']);
        $inactive = $this->service->create(['name' => 'Nonaktif']);
        $this->service->setActive((int) $inactive->id, false);

        self::assertCount(2, $this->service->list());
        self::assertSame([$active->id], array_map(static fn ($c) => $c->id, $this->service->listActive()));
        self::assertFalse($this->service->find((int) $inactive->id)?->isActive);
    }
}
