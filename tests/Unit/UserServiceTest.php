<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Role;
use App\Entity\User;
use App\Repository\InMemory\InMemoryUserRepository;
use App\Service\Exception\ForbiddenOperationException;
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

    /** @return array{0: UserService, 1: int} service + id of a seeded Admin account */
    private function serviceWithAdmin(): array
    {
        $repository = new InMemoryUserRepository();
        $admin = $repository->save(new User(null, 'Admin Utama', 'admin@ordina.test', 'hash', Role::Admin, true));

        return [new UserService($repository), (int) $admin->id];
    }

    public function test_admin_accounts_are_invisible_to_list_and_find(): void
    {
        [$service, $adminId] = $this->serviceWithAdmin();
        $sales = $service->create(['name' => 'Sales', 'email' => 's@ordina.test', 'password' => 'SecurePass1', 'role' => 'Sales']);

        self::assertSame([$sales->id], array_map(static fn (User $u) => $u->id, $service->list()));
        self::assertNull($service->find($adminId));
    }

    public function test_update_changes_profile_but_keeps_password_and_active_flag(): void
    {
        $service = new UserService(new InMemoryUserRepository());
        $user = $service->create(['name' => 'Sales Lama', 'email' => 'lama@ordina.test', 'password' => 'SecurePass1', 'role' => 'Sales']);
        $service->setActive((int) $user->id, false);

        $updated = $service->update((int) $user->id, ['name' => ' Gudang Baru ', 'email' => ' BARU@Ordina.Test ', 'role' => 'WarehouseStaff']);

        self::assertSame('Gudang Baru', $updated->name);
        self::assertSame('baru@ordina.test', $updated->email);
        self::assertSame(Role::WarehouseStaff, $updated->role);
        self::assertTrue(password_verify('SecurePass1', $updated->passwordHash));
        self::assertFalse($updated->isActive);
    }

    public function test_update_may_keep_its_own_email(): void
    {
        $service = new UserService(new InMemoryUserRepository());
        $user = $service->create(['name' => 'Sales', 'email' => 'same@ordina.test', 'password' => 'SecurePass1', 'role' => 'Sales']);

        self::assertSame('Sales Dua', $service->update((int) $user->id, ['name' => 'Sales Dua', 'email' => 'same@ordina.test', 'role' => 'Sales'])->name);
    }

    public function test_update_rejects_invalid_input(): void
    {
        $service = new UserService(new InMemoryUserRepository());
        $user = $service->create(['name' => 'Sales', 'email' => 'ok@ordina.test', 'password' => 'SecurePass1', 'role' => 'Sales']);

        try {
            $service->update((int) $user->id, ['name' => '', 'email' => 'bukan-email', 'role' => 'Admin']);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(['name', 'email', 'role'], array_keys($e->errors()));
        }
    }

    public function test_update_and_set_active_refuse_admin_accounts(): void
    {
        [$service, $adminId] = $this->serviceWithAdmin();

        try {
            $service->update($adminId, ['name' => 'X', 'email' => 'x@ordina.test', 'role' => 'Sales']);
            self::fail('Admin must not be editable here.');
        } catch (ForbiddenOperationException) {
        }

        $this->expectException(ForbiddenOperationException::class);

        $service->setActive($adminId, false);
    }

    public function test_create_rejects_blank_name_invalid_email_short_password_and_unknown_role(): void
    {
        $service = new UserService(new InMemoryUserRepository());

        try {
            $service->create(['name' => ' ', 'email' => '', 'password' => 'short', 'role' => 'Manager']);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(['name', 'email', 'password', 'role'], array_keys($e->errors()));
        }
    }
}
