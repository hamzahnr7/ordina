<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Warehouse;
use App\Repository\Contracts\WarehouseRepositoryInterface;
use App\Service\Exception\ValidationException;

final class WarehouseService
{
    public function __construct(private readonly WarehouseRepositoryInterface $warehouses)
    {
    }

    /** @return list<Warehouse> */
    public function list(): array
    {
        return $this->warehouses->findAll();
    }

    /** For dropdowns on other forms (e.g. Purchase Order). */
    public function listActive(): array
    {
        return $this->warehouses->findActive();
    }

    public function find(int $id): ?Warehouse
    {
        return $this->warehouses->findById($id);
    }

    /** @param array{name?:string, location?:string} $input */
    public function create(array $input): Warehouse
    {
        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->warehouses->save(new Warehouse(
            id: null,
            name: trim($input['name']),
            location: trim($input['location']),
            isActive: true,
        ));
    }

    /** @param array{name?:string, location?:string} $input */
    public function update(int $id, array $input): Warehouse
    {
        $existing = $this->warehouses->findById($id);

        if ($existing === null) {
            throw new ValidationException(['name' => 'Gudang tidak ditemukan.']);
        }

        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->warehouses->save(new Warehouse(
            id: $existing->id,
            name: trim($input['name']),
            location: trim($input['location']),
            isActive: $existing->isActive,
        ));
    }

    public function setActive(int $id, bool $active): void
    {
        $this->warehouses->setActive($id, $active);
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function validate(array $input): array
    {
        $errors = [];

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Nama gudang wajib diisi.';
        }

        if (trim((string) ($input['location'] ?? '')) === '') {
            $errors['location'] = 'Lokasi wajib diisi.';
        }

        return $errors;
    }
}
