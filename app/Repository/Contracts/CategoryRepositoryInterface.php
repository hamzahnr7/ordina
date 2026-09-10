<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Entity\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    /** @return list<Category> */
    public function findAll(): array;

    /** Inserts when $category->id is null, otherwise updates name/description. */
    public function save(Category $category): Category;
}
