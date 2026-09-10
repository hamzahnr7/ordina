<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Entity\Warehouse;
use App\Repository\Contracts\WarehouseRepositoryInterface;
use PDO;

final class MysqlWarehouseRepository implements WarehouseRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?Warehouse
    {
        $stmt = $this->connection->prepare('SELECT * FROM warehouses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : Warehouse::fromArray($row);
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM warehouses ORDER BY name');

        return array_map(Warehouse::fromArray(...), $stmt->fetchAll());
    }

    public function findActive(): array
    {
        $stmt = $this->connection->query('SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name');

        return array_map(Warehouse::fromArray(...), $stmt->fetchAll());
    }

    public function save(Warehouse $warehouse): Warehouse
    {
        if ($warehouse->id === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO warehouses (name, location, is_active) VALUES (:name, :location, :is_active)'
            );
            $stmt->execute([
                'name' => $warehouse->name,
                'location' => $warehouse->location,
                'is_active' => $warehouse->isActive ? 1 : 0,
            ]);

            return $this->findById((int) $this->connection->lastInsertId());
        }

        $stmt = $this->connection->prepare(
            'UPDATE warehouses SET name = :name, location = :location, is_active = :is_active WHERE id = :id'
        );
        $stmt->execute([
            'name' => $warehouse->name,
            'location' => $warehouse->location,
            'is_active' => $warehouse->isActive ? 1 : 0,
            'id' => $warehouse->id,
        ]);

        return $this->findById($warehouse->id);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->connection->prepare('UPDATE warehouses SET is_active = :is_active WHERE id = :id');
        $stmt->execute(['is_active' => $active ? 1 : 0, 'id' => $id]);
    }
}
