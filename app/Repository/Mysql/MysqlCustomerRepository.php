<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Entity\Customer;
use App\Repository\Contracts\CustomerRepositoryInterface;
use PDO;

final class MysqlCustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?Customer
    {
        $stmt = $this->connection->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : Customer::fromArray($row);
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM customers ORDER BY name');

        return array_map(Customer::fromArray(...), $stmt->fetchAll());
    }

    public function findActive(): array
    {
        $stmt = $this->connection->query('SELECT * FROM customers WHERE is_active = 1 ORDER BY name');

        return array_map(Customer::fromArray(...), $stmt->fetchAll());
    }

    public function save(Customer $customer): Customer
    {
        if ($customer->id === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO customers (name, contact, address, is_active) VALUES (:name, :contact, :address, :is_active)'
            );
            $stmt->execute([
                'name' => $customer->name,
                'contact' => $customer->contact,
                'address' => $customer->address,
                'is_active' => $customer->isActive ? 1 : 0,
            ]);

            return $this->findById((int) $this->connection->lastInsertId());
        }

        $stmt = $this->connection->prepare(
            'UPDATE customers SET name = :name, contact = :contact, address = :address, is_active = :is_active WHERE id = :id'
        );
        $stmt->execute([
            'name' => $customer->name,
            'contact' => $customer->contact,
            'address' => $customer->address,
            'is_active' => $customer->isActive ? 1 : 0,
            'id' => $customer->id,
        ]);

        return $this->findById($customer->id);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->connection->prepare('UPDATE customers SET is_active = :is_active WHERE id = :id');
        $stmt->execute(['is_active' => $active ? 1 : 0, 'id' => $id]);
    }
}
