<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Role;
use App\Repository\InMemory\InMemoryUserRepository;
use App\Service\Exception\ValidationException;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function test_creates_a_sales_user_with_a_hashed_password(): void
    {
        $service = new UserService(new InMemoryUserRepository());

        $user = $service->create([
            'name' => 'Sales Baru',
            'email' => 'sales.baru@ordina.test',
            'password' => 'SecurePass1',
            'role' => Role::Sales->value,
        ]);

        self::assertSame('sales.baru@ordina.test', $user->email);
        self::assertTrue(password_verify('SecurePass1', $user->passwordHash));
        self::assertTrue($user->isActive);
    }

    public function test_rejects_duplicate_email(): void
    {
        $service = new UserService(new InMemoryUserRepository());
        $service->create([
            'name' => 'Sales A',
            'email' => 'dup@ordina.test',
            'password' => 'SecurePass1',
            'role' => Role::Sales->value,
        ]);

        $this->expectException(ValidationException::class);

        $service->create([
            'name' => 'Sales B',
            'email' => 'dup@ordina.test',
            'password' => 'SecurePass1',
            'role' => Role::WarehouseStaff->value,
        ]);
    }

    public function test_rejects_admin_role_from_the_user_management_screen(): void
    {
        $service = new UserService(new InMemoryUserRepository());

        $this->expectException(ValidationException::class);

        $service->create([
            'name' => 'Sneaky Admin',
            'email' => 'sneaky@ordina.test',
            'password' => 'SecurePass1',
            'role' => Role::Admin->value,
        ]);
    }

    public function test_set_active_toggles_the_account(): void
    {
        $repository = new InMemoryUserRepository();
        $service = new UserService($repository);

        $user = $service->create([
            'name' => 'Gudang Satu',
            'email' => 'wh1@ordina.test',
            'password' => 'SecurePass1',
            'role' => Role::WarehouseStaff->value,
        ]);

        $service->setActive($user->id, false);

        self::assertFalse($service->find($user->id)->isActive);
    }
}
