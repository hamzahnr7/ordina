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

    public function findActive(): array
    {
        $stmt = $this->connection->query('SELECT * FROM products WHERE is_active = 1 ORDER BY name');

        return array_map(Product::fromArray(...), $stmt->fetchAll());
    }

    public function paginateForListing(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            // Native prepared statements (Database::connect() disables
            // emulation) don't support binding one value to a named
            // placeholder used twice - each occurrence needs its own name.
            $conditions[] = '(p.name LIKE :search_name OR p.sku LIKE :search_sku)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_sku'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = 'p.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }

        // Mirrors ProductService::isLowStock() - keep both in sync (see
        // ProductServiceTest for the pure-logic version of this comparison).
        // Compared directly in WHERE (not HAVING) against the pre-aggregated
        // `stock` subquery below, so neither query here needs a GROUP BY at
        // all - avoids MySQL's ONLY_FULL_GROUP_BY functional-dependency
        // rules entirely rather than relying on them.
        if (($filters['stock_status'] ?? null) === 'low') {
            $conditions[] = 'COALESCE(stock.total_stock, 0) < p.reorder_point';
        } elseif (($filters['stock_status'] ?? null) === 'normal') {
            $conditions[] = 'COALESCE(stock.total_stock, 0) >= p.reorder_point';
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $base = "FROM products p
                  JOIN categories c ON c.id = p.category_id
                  LEFT JOIN (
                      SELECT product_id, SUM(quantity) AS total_stock
                      FROM product_stocks
                      GROUP BY product_id
                  ) stock ON stock.product_id = p.id
                  {$where}";

        $countStmt = $this->connection->prepare("SELECT COUNT(*) {$base}");
        $this->bindFilterParams($countStmt, $params);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $itemsStmt = $this->connection->prepare(
            "SELECT p.*, c.name AS category_name, COALESCE(stock.total_stock, 0) AS total_stock
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
