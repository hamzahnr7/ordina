<?php

declare(strict_types=1);

namespace App\Repository\Mysql;

use App\Entity\Product;
use App\Repository\Contracts\ProductRepositoryInterface;
use PDO;

final class MysqlProductRepository implements ProductRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findBySku(string $sku): ?Product
    {
        $stmt = $this->connection->prepare('SELECT * FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $row = $stmt->fetch();

        return $row === false ? null : Product::fromArray($row);
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM products ORDER BY name');

        return array_map(Product::fromArray(...), $stmt->fetchAll());
    }

    public function save(Product $product): void
    {
        // TODO: implement insert/update once PRD-01 validation rules are wired up.
    }
}
