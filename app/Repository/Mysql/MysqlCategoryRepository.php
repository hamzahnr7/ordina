<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Entity\Category;
use App\Repository\Contracts\CategoryRepositoryInterface;
use PDO;

final class MysqlCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->connection->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : Category::fromArray($row);
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM categories ORDER BY name');

        return array_map(Category::fromArray(...), $stmt->fetchAll());
    }

    public function save(Category $category): Category
    {
        if ($category->id === null) {
            $stmt = $this->connection->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
            $stmt->execute(['name' => $category->name, 'description' => $category->description]);

            return $this->findById((int) $this->connection->lastInsertId());
        }

        $stmt = $this->connection->prepare('UPDATE categories SET name = :name, description = :description WHERE id = :id');
        $stmt->execute(['name' => $category->name, 'description' => $category->description, 'id' => $category->id]);

        return $this->findById($category->id);
    }
}
