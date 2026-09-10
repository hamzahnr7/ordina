<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\Warehouse;

interface WarehouseRepositoryInterface
{
    public function findById(int $id): ?Warehouse;

    /** @return list<Warehouse> */
    public function findAll(): array;

    /** @return list<Warehouse> */
    public function findActive(): array;

    public function save(Warehouse $warehouse): Warehouse;

    public function setActive(int $id, bool $active): void;
}
