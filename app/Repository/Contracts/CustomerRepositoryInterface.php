<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\Customer;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;

    /** @return list<Customer> */
    public function findAll(): array;

    /** @return list<Customer> */
    public function findActive(): array;

    public function save(Customer $customer): Customer;

    public function setActive(int $id, bool $active): void;
}
