<?php

declare(strict_types=1);

namespace App\Entity;

final class Customer
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $contact,
        public readonly ?string $address,
        public readonly bool $isActive,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            contact: $row['contact'] !== null ? (string) $row['contact'] : null,
            address: $row['address'] !== null ? (string) $row['address'] : null,
            isActive: (bool) $row['is_active'],
        );
    }
}
