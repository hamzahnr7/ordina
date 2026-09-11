<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\Customer;
use App\Repository\Contracts\CustomerRepositoryInterface;

final class InMemoryCustomerRepository implements CustomerRepositoryInterface
{
    /** @var array<int, Customer> */
    private array $byId = [];

    private int $nextId = 1;

    public function findById(int $id): ?Customer
    {
        return $this->byId[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->byId);
    }

    public function findActive(): array
    {
        return array_values(array_filter($this->byId, static fn (Customer $c): bool => $c->isActive));
    }

    public function save(Customer $customer): Customer
    {
        $id = $customer->id ?? $this->nextId++;
        $saved = new Customer($id, $customer->name, $customer->contact, $customer->address, $customer->isActive);
        $this->byId[$id] = $saved;

        return $saved;
    }

    public function setActive(int $id, bool $active): void
    {
        $c = $this->byId[$id];
        $this->byId[$id] = new Customer($c->id, $c->name, $c->contact, $c->address, $active);
    }
}
