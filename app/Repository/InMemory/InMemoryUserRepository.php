<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\User;
use App\Repository\Contracts\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<int, User> */
    private array $usersById = [];

    private int $nextId = 1;

    public function findById(int $id): ?User
    {
        return $this->usersById[$id] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->usersById as $user) {
            if (strcasecmp($user->email, $email) === 0) {
                return $user;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return array_values($this->usersById);
    }

    public function emailExists(string $email, ?int $excludingId = null): bool
    {
        foreach ($this->usersById as $user) {
            if (strcasecmp($user->email, $email) === 0 && $user->id !== $excludingId) {
                return true;
            }
        }

        return false;
    }

    public function save(User $user): User
    {
        $id = $user->id ?? $this->nextId++;
        $saved = new User($id, $user->name, $user->email, $user->passwordHash, $user->role, $user->isActive);
        $this->usersById[$id] = $saved;

        return $saved;
    }

    public function setActive(int $id, bool $active): void
    {
        $user = $this->usersById[$id];
        $this->usersById[$id] = new User($user->id, $user->name, $user->email, $user->passwordHash, $user->role, $active);
    }
}
