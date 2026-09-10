<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Role;
use App\Entity\User;
use App\Repository\InMemory\InMemoryUserRepository;
use App\Service\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private function repositoryWithUser(bool $isActive = true): InMemoryUserRepository
    {
        $repository = new InMemoryUserRepository();
        $repository->save(new User(
            id: null,
            name: 'Sales Satu',
            email: 'sales1@ordina.test',
            passwordHash: password_hash('CorrectPassword1', PASSWORD_DEFAULT),
            role: Role::Sales,
            isActive: $isActive,
        ));

        return $repository;
    }

    public function test_valid_credentials_return_the_user(): void
    {
        $service = new AuthService($this->repositoryWithUser());

        $user = $service->attempt('sales1@ordina.test', 'CorrectPassword1');

        self::assertNotNull($user);
        self::assertSame('sales1@ordina.test', $user->email);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $service = new AuthService($this->repositoryWithUser());

        self::assertNull($service->attempt('sales1@ordina.test', 'WrongPassword'));
    }

    public function test_unknown_email_is_rejected(): void
    {
        $service = new AuthService($this->repositoryWithUser());

        self::assertNull($service->attempt('nobody@ordina.test', 'CorrectPassword1'));
    }

    public function test_inactive_user_cannot_login_even_with_correct_password(): void
    {
        $service = new AuthService($this->repositoryWithUser(isActive: false));

        self::assertNull($service->attempt('sales1@ordina.test', 'CorrectPassword1'));
    }
}
