<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\Supplier;
use App\Repository\Contracts\SupplierRepositoryInterface;

final class InMemorySupplierRepository implements SupplierRepositoryInterface
{
    /** @var array<int, Supplier> */
    private array $byId = [];

    private int $nextId = 1;

    public function findById(int $id): ?Supplier
    {
        return $this->byId[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->byId);
    }

    public function findActive(): array
    {
        return array_values(array_filter($this->byId, static fn (Supplier $s): bool => $s->isActive));
    }

    public function save(Supplier $supplier): Supplier
    {
        $id = $supplier->id ?? $this->nextId++;
        $saved = new Supplier($id, $supplier->name, $supplier->contact, $supplier->address, $supplier->isActive);
        $this->byId[$id] = $saved;

        return $saved;
    }

    public function setActive(int $id, bool $active): void
    {
        $s = $this->byId[$id];
        $this->byId[$id] = new Supplier($s->id, $s->name, $s->contact, $s->address, $active);
    }
}
