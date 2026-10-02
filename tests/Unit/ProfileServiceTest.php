<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Role;
use App\Entity\User;
use App\Repository\InMemory\InMemoryUserRepository;
use App\Service\Exception\ValidationException;
use App\Service\ProfileService;
use PHPUnit\Framework\TestCase;

/** §1.2 "profil sendiri": any role - Admin included - views its own profile and changes its own password. */
final class ProfileServiceTest extends TestCase
{
    private InMemoryUserRepository $users;
    private ProfileService $service;
    private int $userId;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->service = new ProfileService($this->users);

        $admin = $this->users->save(new User(
            id: null,
            name: 'Admin Utama',
            email: 'admin@ordina.test',
            passwordHash: password_hash('OldPassword1', PASSWORD_DEFAULT),
            role: Role::Admin,
            isActive: true,
        ));
        $this->userId = $admin->id;
    }

    public function test_changes_own_password_when_current_password_is_correct(): void
    {
        $this->service->changePassword($this->userId, 'OldPassword1', 'NewPassword2', 'NewPassword2');

        $hash = $this->users->findById($this->userId)->passwordHash;
        self::assertTrue(password_verify('NewPassword2', $hash));
        self::assertFalse(password_verify('OldPassword1', $hash));
    }

    public function test_rejects_wrong_current_password_and_keeps_the_old_one(): void
    {
        try {
            $this->service->changePassword($this->userId, 'WrongPassword', 'NewPassword2', 'NewPassword2');
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('current_password', $e->errors());
        }

        self::assertTrue(password_verify('OldPassword1', $this->users->findById($this->userId)->passwordHash));
    }

    public function test_rejects_new_password_shorter_than_eight_characters(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->changePassword($this->userId, 'OldPassword1', 'short', 'short');
    }

    public function test_rejects_when_confirmation_does_not_match(): void
    {
        try {
            $this->service->changePassword($this->userId, 'OldPassword1', 'NewPassword2', 'Different3');
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('password_confirmation', $e->errors());
        }
    }

    public function test_find_returns_own_account_and_null_for_unknown_id(): void
    {
        self::assertSame('admin@ordina.test', $this->service->find($this->userId)?->email);
        self::assertNull($this->service->find(999));
    }

    public function test_unknown_user_cannot_change_a_password(): void
    {
        try {
            $this->service->changePassword(999, 'OldPassword1', 'NewPassword2', 'NewPassword2');
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(['current_password'], array_keys($e->errors()));
        }
    }
}
