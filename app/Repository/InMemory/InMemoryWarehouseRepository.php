<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\Warehouse;
use App\Repository\Contracts\WarehouseRepositoryInterface;

final class InMemoryWarehouseRepository implements WarehouseRepositoryInterface
{
    /** @var array<int, Warehouse> */
    private array $byId = [];

    private int $nextId = 1;

    public function findById(int $id): ?Warehouse
    {
        return $this->byId[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->byId);
    }

    public function findActive(): array
    {
        return array_values(array_filter($this->byId, static fn (Warehouse $w): bool => $w->isActive));
    }

    public function save(Warehouse $warehouse): Warehouse
    {
        $id = $warehouse->id ?? $this->nextId++;
        $saved = new Warehouse($id, $warehouse->name, $warehouse->location, $warehouse->isActive);
        $this->byId[$id] = $saved;

        return $saved;
    }

    public function setActive(int $id, bool $active): void
    {
        $w = $this->byId[$id];
        $this->byId[$id] = new Warehouse($w->id, $w->name, $w->location, $active);
    }
}
