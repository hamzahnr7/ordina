<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Entity\User;
use App\Repository\Contracts\UserRepositoryInterface;
use PDO;

final class MysqlUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromArray($row);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromArray($row);
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM users ORDER BY name');

        return array_map(User::fromArray(...), $stmt->fetchAll());
    }

    public function emailExists(string $email, ?int $excludingId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($excludingId !== null) {
            $sql .= ' AND id <> :excluding_id';
            $params['excluding_id'] = $excludingId;
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function save(User $user): User
    {
        if ($user->id === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO users (name, email, password_hash, role, is_active)
                 VALUES (:name, :email, :password_hash, :role, :is_active)'
            );
            $stmt->execute([
                'name' => $user->name,
                'email' => $user->email,
                'password_hash' => $user->passwordHash,
                'role' => $user->role->value,
                'is_active' => $user->isActive ? 1 : 0,
            ]);

            return $this->findById((int) $this->connection->lastInsertId());
        }

        $stmt = $this->connection->prepare(
            'UPDATE users SET name = :name, email = :email, role = :role, is_active = :is_active WHERE id = :id'
        );
        $stmt->execute([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->isActive ? 1 : 0,
            'id' => $user->id,
        ]);

        return $this->findById($user->id);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->connection->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
        $stmt->execute(['is_active' => $active ? 1 : 0, 'id' => $id]);
    }
}
