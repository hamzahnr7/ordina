<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer;
use App\Repository\Contracts\CustomerRepositoryInterface;
use App\Service\Exception\ValidationException;

final class CustomerService
{
    public function __construct(private readonly CustomerRepositoryInterface $customers)
    {
    }

    /** @return list<Customer> */
    public function list(): array
    {
        return $this->customers->findAll();
    }

    public function find(int $id): ?Customer
    {
        return $this->customers->findById($id);
    }

    /** @param array{name?:string, contact?:string, address?:string} $input */
    public function create(array $input): Customer
    {
        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->customers->save(new Customer(
            id: null,
            name: trim($input['name']),
            contact: $this->nullableTrim($input['contact'] ?? null),
            address: $this->nullableTrim($input['address'] ?? null),
            isActive: true,
        ));
    }

    /** @param array{name?:string, contact?:string, address?:string} $input */
    public function update(int $id, array $input): Customer
    {
        $existing = $this->customers->findById($id);

        if ($existing === null) {
            throw new ValidationException(['name' => 'Customer tidak ditemukan.']);
        }

        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->customers->save(new Customer(
            id: $existing->id,
            name: trim($input['name']),
            contact: $this->nullableTrim($input['contact'] ?? null),
            address: $this->nullableTrim($input['address'] ?? null),
            isActive: $existing->isActive,
        ));
    }

    public function setActive(int $id, bool $active): void
    {
        $this->customers->setActive($id, $active);
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function validate(array $input): array
    {
        $errors = [];

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Nama customer wajib diisi.';
        }

        return $errors;
    }

    private function nullableTrim(?string $value): ?string
    {
        return ($value === null || trim($value) === '') ? null : trim($value);
    }
}
