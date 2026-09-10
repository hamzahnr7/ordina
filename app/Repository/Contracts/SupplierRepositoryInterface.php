<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\Supplier;

interface SupplierRepositoryInterface
{
    public function findById(int $id): ?Supplier;

    /** @return list<Supplier> */
    public function findAll(): array;

    /** @return list<Supplier> */
    public function findActive(): array;

    public function save(Supplier $supplier): Supplier;

    public function setActive(int $id, bool $active): void;
}
