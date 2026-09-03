<?php

declare(strict_types=1);

// Standalone script (JOB-01) - run via:
//   docker compose exec web php scripts/check-low-stock.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;

Config::load(__DIR__ . '/../.env');

$pdo = Database::connection();

$stmt = $pdo->query(
    'SELECT p.sku, p.name, w.name AS warehouse_name, ps.quantity, p.reorder_point
     FROM product_stocks ps
     JOIN products p ON p.id = ps.product_id
     JOIN warehouses w ON w.id = ps.warehouse_id
     WHERE ps.quantity <= p.reorder_point AND p.is_active = 1
     ORDER BY p.name, w.name'
);

$rows = $stmt->fetchAll();

if ($rows === []) {
    echo "No products below reorder point.\n";
    exit(0);
}

printf("%-12s %-30s %-20s %8s %8s\n", 'SKU', 'Product', 'Warehouse', 'Qty', 'Reorder');

foreach ($rows as $row) {
    printf(
        "%-12s %-30s %-20s %8d %8d\n",
        $row['sku'],
        $row['name'],
        $row['warehouse_name'],
        (int) $row['quantity'],
        (int) $row['reorder_point']
    );
}
