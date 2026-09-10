<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Role;
use App\Entity\User;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Service\Exception\ForbiddenOperationException;
use App\Service\Exception\ValidationException;

/**
 * USR-01: Admin-only management of Sales/Warehouse Staff accounts. Admin
 * accounts are intentionally excluded from every method here - see
 * Role::manageable() and docs/architecture/rbac-and-menu-access.md.
 */
final class UserService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /** @return list<User> */
    public function list(): array
    {
        return array_values(array_filter(
            $this->users->findAll(),
            static fn (User $user): bool => $user->role !== Role::Admin
        ));
    }

    public function find(int $id): ?User
    {
        $user = $this->users->findById($id);

        return ($user !== null && $user->role !== Role::Admin) ? $user : null;
    }

    /** @param array{name?:string, email?:string, password?:string, role?:string} $input */
    public function create(array $input): User
    {
        $errors = $this->validate($input, requirePassword: true);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $user = new User(
            id: null,
            name: trim($input['name']),
            email: strtolower(trim($input['email'])),
            passwordHash: password_hash($input['password'], PASSWORD_DEFAULT),
            role: Role::from($input['role']),
            isActive: true,
        );

        return $this->users->save($user);
    }

    /** @param array{name?:string, email?:string, role?:string} $input */
    public function update(int $id, array $input): User
    {
        $existing = $this->find($id);

        if ($existing === null) {
            throw new ForbiddenOperationException('User is not manageable through this screen.');
        }

        $errors = $this->validate($input, requirePassword: false, excludingId: $id);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $updated = new User(
            id: $existing->id,
            name: trim($input['name']),
            email: strtolower(trim($input['email'])),
            passwordHash: $existing->passwordHash,
            role: Role::from($input['role']),
            isActive: $existing->isActive,
        );

        return $this->users->save($updated);
    }

    public function setActive(int $id, bool $active): void
    {
        if ($this->find($id) === null) {
            throw new ForbiddenOperationException('User is not manageable through this screen.');
        }

        $this->users->setActive($id, $active);
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function validate(array $input, bool $requirePassword, ?int $excludingId = null): array
    {
        $errors = [];

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Nama wajib diisi.';
        }

        $email = trim((string) ($input['email'] ?? ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email tidak valid.';
        } elseif ($this->users->emailExists(strtolower($email), $excludingId)) {
            $errors['email'] = 'Email sudah digunakan.';
        }

        if ($requirePassword && strlen((string) ($input['password'] ?? '')) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        }

        $role = Role::tryFrom((string) ($input['role'] ?? ''));

        if ($role === null || !in_array($role, Role::manageable(), true)) {
            $errors['role'] = 'Role harus Sales atau Warehouse Staff.';
        }

        return $errors;
    }
}
