<?php

declare(strict_types=1);

namespace App\Entity;

final class Category
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $description,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            description: $row['description'] !== null ? (string) $row['description'] : null,
        );
    }
}
