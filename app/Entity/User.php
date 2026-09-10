<?php

declare(strict_types=1);

namespace App\Entity;

use App\Domain\Role;

final class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly Role $role,
        public readonly bool $isActive,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            role: Role::from((string) $row['role']),
            isActive: (bool) $row['is_active'],
        );
    }

    /**
     * What gets stored in the session after login - never the password hash.
     * See docs/architecture/rbac-and-menu-access.md for the full property list.
     *
     * @return array{id:int, name:string, email:string, role:string}
     */
    public function toSessionArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
        ];
    }
}
