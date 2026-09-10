<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Entity\Category;
use App\Repository\Contracts\CategoryRepositoryInterface;

final class InMemoryCategoryRepository implements CategoryRepositoryInterface
{
    /** @var array<int, Category> */
    private array $categoriesById = [];

    private int $nextId = 1;

    public function findById(int $id): ?Category
    {
        return $this->categoriesById[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->categoriesById);
    }

    public function save(Category $category): Category
    {
        $id = $category->id ?? $this->nextId++;
        $saved = new Category($id, $category->name, $category->description);
        $this->categoriesById[$id] = $saved;

        return $saved;
    }
}
