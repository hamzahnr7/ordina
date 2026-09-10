<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Entity\Supplier;
use App\Repository\Contracts\SupplierRepositoryInterface;
use PDO;

final class MysqlSupplierRepository implements SupplierRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?Supplier
    {
        $stmt = $this->connection->prepare('SELECT * FROM suppliers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : Supplier::fromArray($row);
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM suppliers ORDER BY name');

        return array_map(Supplier::fromArray(...), $stmt->fetchAll());
    }

    public function findActive(): array
    {
        $stmt = $this->connection->query('SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name');

        return array_map(Supplier::fromArray(...), $stmt->fetchAll());
    }

    public function save(Supplier $supplier): Supplier
    {
        if ($supplier->id === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO suppliers (name, contact, address, is_active) VALUES (:name, :contact, :address, :is_active)'
            );
            $stmt->execute([
                'name' => $supplier->name,
                'contact' => $supplier->contact,
                'address' => $supplier->address,
                'is_active' => $supplier->isActive ? 1 : 0,
            ]);

            return $this->findById((int) $this->connection->lastInsertId());
        }

        $stmt = $this->connection->prepare(
            'UPDATE suppliers SET name = :name, contact = :contact, address = :address, is_active = :is_active WHERE id = :id'
        );
        $stmt->execute([
            'name' => $supplier->name,
            'contact' => $supplier->contact,
            'address' => $supplier->address,
            'is_active' => $supplier->isActive ? 1 : 0,
            'id' => $supplier->id,
        ]);

        return $this->findById($supplier->id);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->connection->prepare('UPDATE suppliers SET is_active = :is_active WHERE id = :id');
        $stmt->execute(['is_active' => $active ? 1 : 0, 'id' => $id]);
    }
}
