<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    /** @return list<User> */
    public function findAll(): array;

    public function emailExists(string $email, ?int $excludingId = null): bool;

    /** Inserts when $user->id is null, otherwise updates name/email/role/is_active. */
    public function save(User $user): User;

    public function setActive(int $id, bool $active): void;
}
