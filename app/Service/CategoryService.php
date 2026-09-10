<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Repository\Contracts\CategoryRepositoryInterface;
use App\Service\Exception\ValidationException;

final class CategoryService
{
    public function __construct(private readonly CategoryRepositoryInterface $categories)
    {
    }

    /** @return list<Category> */
    public function list(): array
    {
        return $this->categories->findAll();
    }

    public function find(int $id): ?Category
    {
        return $this->categories->findById($id);
    }

    /** @param array{name?:string, description?:string} $input */
    public function create(array $input): Category
    {
        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->categories->save(new Category(
            id: null,
            name: trim($input['name']),
            description: $this->nullableTrim($input['description'] ?? null),
        ));
    }

    /** @param array{name?:string, description?:string} $input */
    public function update(int $id, array $input): Category
    {
        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->categories->save(new Category(
            id: $id,
            name: trim($input['name']),
            description: $this->nullableTrim($input['description'] ?? null),
        ));
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function validate(array $input): array
    {
        $errors = [];

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Nama kategori wajib diisi.';
        }

        return $errors;
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
