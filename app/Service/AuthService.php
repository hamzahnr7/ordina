<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\Contracts\UserRepositoryInterface;

/**
 * AUTH-01: credential check + active-user check. Deliberately returns null
 * on every failure path (unknown email, wrong password, inactive account)
 * so the Controller can show one generic message - never revealing which
 * part was wrong.
 */
final class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function attempt(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail(trim($email));

        if ($user === null || !$user->isActive) {
            return null;
        }

        if (!password_verify($password, $user->passwordHash)) {
            return null;
        }

        return $user;
    }
}
