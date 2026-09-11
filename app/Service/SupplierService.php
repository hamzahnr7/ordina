<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Supplier;
use App\Repository\Contracts\SupplierRepositoryInterface;
use App\Service\Exception\ValidationException;

final class SupplierService
{
    public function __construct(private readonly SupplierRepositoryInterface $suppliers)
    {
    }

    /** @return list<Supplier> */
    public function list(): array
    {
        return $this->suppliers->findAll();
    }

    /** For dropdowns on other forms (e.g. Purchase Order). */
    public function listActive(): array
    {
        return $this->suppliers->findActive();
    }

    public function find(int $id): ?Supplier
    {
        return $this->suppliers->findById($id);
    }

    /** @param array{name?:string, contact?:string, address?:string} $input */
    public function create(array $input): Supplier
    {
        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->suppliers->save(new Supplier(
            id: null,
            name: trim($input['name']),
            contact: $this->nullableTrim($input['contact'] ?? null),
            address: $this->nullableTrim($input['address'] ?? null),
            isActive: true,
        ));
    }

    /** @param array{name?:string, contact?:string, address?:string} $input */
    public function update(int $id, array $input): Supplier
    {
        $existing = $this->suppliers->findById($id);

        if ($existing === null) {
            throw new ValidationException(['name' => 'Supplier tidak ditemukan.']);
        }

        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->suppliers->save(new Supplier(
            id: $existing->id,
            name: trim($input['name']),
            contact: $this->nullableTrim($input['contact'] ?? null),
            address: $this->nullableTrim($input['address'] ?? null),
            isActive: $existing->isActive,
        ));
    }

    public function setActive(int $id, bool $active): void
    {
        $this->suppliers->setActive($id, $active);
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function validate(array $input): array
    {
        $errors = [];

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Nama supplier wajib diisi.';
        }

        return $errors;
    }

    private function nullableTrim(?string $value): ?string
    {
        return ($value === null || trim($value) === '') ? null : trim($value);
    }
}
