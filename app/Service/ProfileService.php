<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Service\Exception\ValidationException;

/**
 * §1.2 "profil sendiri": the signed-in user's own account, for every role.
 * Kept apart from UserService on purpose - that one is Admin managing
 * *other* Sales/Warehouse accounts and deliberately refuses Admin accounts.
 */
final class ProfileService
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function find(int $userId): ?User
    {
        return $this->users->findById($userId);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmation): void
    {
        $user = $this->users->findById($userId);
        $errors = [];

        if ($user === null || !password_verify($currentPassword, $user->passwordHash)) {
            $errors['current_password'] = 'Password saat ini salah.';
        }

        if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
            $errors['new_password'] = 'Password baru minimal ' . self::MIN_PASSWORD_LENGTH . ' karakter.';
        } elseif ($newPassword !== $confirmation) {
            $errors['password_confirmation'] = 'Konfirmasi password baru tidak sama.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
    }
}
