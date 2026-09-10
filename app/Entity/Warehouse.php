<?php

declare(strict_types=1);

namespace App\Entity;

final class Warehouse
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $location,
        public readonly bool $isActive,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            location: (string) $row['location'],
            isActive: (bool) $row['is_active'],
        );
    }
}
