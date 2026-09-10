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

    public function findById(int $id): ?Product
    {
        $stmt = $this->connection->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : Product::fromArray($row);
    }

    public function findBySku(string $sku): ?Product
    {
        $stmt = $this->connection->prepare('SELECT * FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $row = $stmt->fetch();

        return $row === false ? null : Product::fromArray($row);
    }

    public function skuExists(string $sku, ?int $excludingId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE sku = :sku';
        $params = ['sku' => $sku];

        if ($excludingId !== null) {
            $sql .= ' AND id <> :excluding_id';
            $params['excluding_id'] = $excludingId;
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM products ORDER BY name');

        return array_map(Product::fromArray(...), $stmt->fetchAll());
    }

    public function paginateForListing(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(p.name LIKE :search OR p.sku LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = 'p.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        // Mirrors ProductService::isLowStock() - keep both in sync (see
        // ProductServiceTest for the pure-logic version of this comparison).
        $having = match ($filters['stock_status'] ?? null) {
            'low' => 'HAVING total_stock < p.reorder_point',
            'normal' => 'HAVING total_stock >= p.reorder_point',
            default => '',
        };

        $base = "FROM products p
                  JOIN categories c ON c.id = p.category_id
                  LEFT JOIN product_stocks ps ON ps.product_id = p.id
                  {$where}
                  GROUP BY p.id
                  {$having}";

        // total_stock must be selected here too (not just p.id) - HAVING
        // references the alias, and an alias only resolves within the same
        // query's SELECT list.
        $countStmt = $this->connection->prepare(
            "SELECT COUNT(*) FROM (SELECT p.id, COALESCE(SUM(ps.quantity), 0) AS total_stock {$base}) AS filtered"
        );
        $this->bindFilterParams($countStmt, $params);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $itemsStmt = $this->connection->prepare(
            "SELECT p.*, c.name AS category_name, COALESCE(SUM(ps.quantity), 0) AS total_stock
             {$base}
             ORDER BY p.name
             LIMIT :limit OFFSET :offset"
        );
        $this->bindFilterParams($itemsStmt, $params);
        $itemsStmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $itemsStmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $itemsStmt->execute();

        return ['items' => $itemsStmt->fetchAll(), 'total' => $total];
    }

    /** @param array<string, mixed> $params */
    private function bindFilterParams(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }

    public function save(Product $product): Product
    {
        if ($product->id === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO products (sku, name, category_id, unit, buy_price, sell_price, reorder_point, image_path, is_active)
                 VALUES (:sku, :name, :category_id, :unit, :buy_price, :sell_price, :reorder_point, :image_path, :is_active)'
            );
            $stmt->execute([
                'sku' => $product->sku,
                'name' => $product->name,
                'category_id' => $product->categoryId,
                'unit' => $product->unit,
                'buy_price' => $product->buyPrice,
                'sell_price' => $product->sellPrice,
                'reorder_point' => $product->reorderPoint,
                'image_path' => $product->imagePath,
                'is_active' => $product->isActive ? 1 : 0,
            ]);

            return $this->findById((int) $this->connection->lastInsertId());
        }

        $stmt = $this->connection->prepare(
            'UPDATE products SET
                name = :name,
                category_id = :category_id,
                unit = :unit,
                buy_price = :buy_price,
                sell_price = :sell_price,
                reorder_point = :reorder_point,
                image_path = :image_path,
                is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $product->name,
            'category_id' => $product->categoryId,
            'unit' => $product->unit,
            'buy_price' => $product->buyPrice,
            'sell_price' => $product->sellPrice,
            'reorder_point' => $product->reorderPoint,
            'image_path' => $product->imagePath,
            'is_active' => $product->isActive ? 1 : 0,
            'id' => $product->id,
        ]);

        return $this->findById($product->id);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->connection->prepare('UPDATE products SET is_active = :is_active WHERE id = :id');
        $stmt->execute(['is_active' => $active ? 1 : 0, 'id' => $id]);
    }
}
