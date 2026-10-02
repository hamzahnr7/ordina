<?php

declare(strict_types=1);

// Standalone script (JOB-01) - outside the web request cycle, the way a cron
// job would run in production. Run via:
//   docker compose exec web php scripts/check-low-stock.php
//
// Uses the same repository as the dashboard (MysqlDashboardRepository), so the
// "low stock" rule here can never drift from what DASH-01 and the FIND-01
// filter show: total stock across all warehouses < reorder_point, active
// products only, products with no stock row at all counted as 0.

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Repository\Mysql\MysqlDashboardRepository;

Config::load(__DIR__ . '/../.env');

$dashboard = new MysqlDashboardRepository(Database::connection());
$count = $dashboard->lowStockProductCount();

echo 'Low-stock check - ' . date('Y-m-d H:i:s') . PHP_EOL;

if ($count === 0) {
    echo 'No products below reorder point.' . PHP_EOL;
    exit(0);
}

echo "{$count} product(s) below reorder point:" . PHP_EOL . PHP_EOL;
printf("%-10s %-32s %8s %8s %9s\n", 'SKU', 'Product', 'Stock', 'Reorder', 'Shortfall');
echo str_repeat('-', 71) . PHP_EOL;

foreach ($dashboard->lowStockProducts($count) as $row) {
    $stock = (int) $row['total_stock'];
    $reorder = (int) $row['reorder_point'];

    printf(
        "%-10s %-32s %8d %8d %9d\n",
        $row['sku'],
        mb_strimwidth((string) $row['name'], 0, 32, '…'),
        $stock,
        $reorder,
        $reorder - $stock
    );
}
