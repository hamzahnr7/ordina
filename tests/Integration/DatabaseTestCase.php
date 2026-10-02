<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Config;
use App\Core\Database;
use App\Core\Transaction\PdoTransactionManager;
use App\Repository\Mysql\MysqlCustomerRepository;
use App\Repository\Mysql\MysqlProductRepository;
use App\Repository\Mysql\MysqlProductStockRepository;
use App\Repository\Mysql\MysqlPurchaseOrderItemRepository;
use App\Repository\Mysql\MysqlPurchaseOrderRepository;
use App\Repository\Mysql\MysqlSalesOrderItemRepository;
use App\Repository\Mysql\MysqlSalesOrderRepository;
use App\Repository\Mysql\MysqlStockLedgerRepository;
use App\Repository\Mysql\MysqlSupplierRepository;
use App\Repository\Mysql\MysqlWarehouseRepository;
use App\Service\PurchaseOrderService;
use App\Service\SalesOrderService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that hit real MySQL in Docker (TEST-02). Uses a
 * dedicated DB_TEST_DATABASE so integration tests never touch dev/demo data.
 * Run inside the web container: docker compose exec web vendor/bin/phpunit --testsuite Integration
 *
 * Every test starts from empty tables plus the same small fixture set
 * (FIRST: Independent + Repeatable), and wires the real Mysql repositories
 * and PdoTransactionManager - unlike the unit tests' InMemory fakes, so
 * transactions, CHECK constraints and the conditional UPDATE are real.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected const ADMIN_ID = 1;
    protected const SALES_ID = 2;
    protected const WAREHOUSE_STAFF_ID = 3;
    protected const WAREHOUSE_ID = 1;
    protected const PRODUCT_ID = 1;
    protected const OTHER_PRODUCT_ID = 2;

    private const TABLES = [
        'stock_ledger', 'sales_order_items', 'sales_orders', 'purchase_order_items', 'purchase_orders',
        'product_stocks', 'products', 'categories', 'customers', 'suppliers', 'warehouses', 'users',
    ];

    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Database::connect(
            Config::get('DB_TEST_HOST', 'localhost'),
            Config::get('DB_TEST_PORT', '3306'),
            Config::get('DB_TEST_DATABASE', 'ordina_test'),
            Config::get('DB_TEST_USERNAME', 'tester'),
            Config::get('DB_TEST_PASSWORD', 'testing123')
        );

        $this->resetDatabase();
        $this->seedFixtures();
    }

    private function resetDatabase(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach (self::TABLES as $table) {
            $this->pdo->exec("TRUNCATE TABLE {$table}");
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function seedFixtures(): void
    {
        $hash = password_hash('password123', PASSWORD_DEFAULT);

        $users = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)');
        foreach ([['Admin', 'admin@test.local', 'Admin'], ['Sales', 'sales@test.local', 'Sales'], ['Gudang', 'gudang@test.local', 'WarehouseStaff']] as [$name, $email, $role]) {
            $users->execute(['name' => $name, 'email' => $email, 'hash' => $hash, 'role' => $role]);
        }

        $this->pdo->exec("INSERT INTO warehouses (name, location) VALUES ('Gudang Test', 'Jakarta')");
        $this->pdo->exec("INSERT INTO categories (name) VALUES ('Test Category')");
        $this->pdo->exec("INSERT INTO suppliers (name) VALUES ('Supplier Test')");
        $this->pdo->exec("INSERT INTO customers (name) VALUES ('Customer Test')");
        $this->pdo->exec(
            "INSERT INTO products (sku, name, category_id, unit, buy_price, sell_price, reorder_point) VALUES
                ('IT-0001', 'Produk Satu', 1, 'pcs', 10000, 15000, 5),
                ('IT-0002', 'Produk Dua', 1, 'pcs', 20000, 30000, 5)"
        );
    }

    /** Opening stock written the same way the seed does it: ledger row first, stock row matching it. */
    protected function givenStock(int $productId, int $quantity): void
    {
        $this->pdo->prepare(
            "INSERT INTO stock_ledger (product_id, warehouse_id, movement_type, quantity, reference_type, reference_id, performed_by)
             VALUES (:product_id, :warehouse_id, 'Adjustment', :quantity, 'Adjustment', NULL, :performed_by)"
        )->execute(['product_id' => $productId, 'warehouse_id' => self::WAREHOUSE_ID, 'quantity' => $quantity, 'performed_by' => self::ADMIN_ID]);

        $this->pdo->prepare('INSERT INTO product_stocks (product_id, warehouse_id, quantity) VALUES (:product_id, :warehouse_id, :quantity)')
            ->execute(['product_id' => $productId, 'warehouse_id' => self::WAREHOUSE_ID, 'quantity' => $quantity]);
    }

    /** Returns null when no product_stocks row exists yet (distinct from 0). */
    protected function stockOf(int $productId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT quantity FROM product_stocks WHERE product_id = :product_id AND warehouse_id = :warehouse_id');
        $stmt->execute(['product_id' => $productId, 'warehouse_id' => self::WAREHOUSE_ID]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : (int) $value;
    }

    /** Stock as the ledger says it should be: Receipt + Adjustment - Issue. */
    protected function ledgerBalanceOf(int $productId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN movement_type = 'Issue' THEN -quantity ELSE quantity END), 0)
             FROM stock_ledger WHERE product_id = :product_id AND warehouse_id = :warehouse_id"
        );
        $stmt->execute(['product_id' => $productId, 'warehouse_id' => self::WAREHOUSE_ID]);

        return (int) $stmt->fetchColumn();
    }

    protected function ledgerRowCount(string $referenceType, int $referenceId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM stock_ledger WHERE reference_type = :type AND reference_id = :id');
        $stmt->execute(['type' => $referenceType, 'id' => $referenceId]);

        return (int) $stmt->fetchColumn();
    }

    protected function purchaseOrderService(): PurchaseOrderService
    {
        return new PurchaseOrderService(
            new MysqlPurchaseOrderRepository($this->pdo),
            new MysqlPurchaseOrderItemRepository($this->pdo),
            new MysqlProductStockRepository($this->pdo),
            new MysqlStockLedgerRepository($this->pdo),
            new MysqlSupplierRepository($this->pdo),
            new MysqlWarehouseRepository($this->pdo),
            new MysqlProductRepository($this->pdo),
            new PdoTransactionManager($this->pdo),
        );
    }

    protected function salesOrderService(): SalesOrderService
    {
        return new SalesOrderService(
            new MysqlSalesOrderRepository($this->pdo),
            new MysqlSalesOrderItemRepository($this->pdo),
            new MysqlProductStockRepository($this->pdo),
            new MysqlStockLedgerRepository($this->pdo),
            new MysqlCustomerRepository($this->pdo),
            new MysqlWarehouseRepository($this->pdo),
            new MysqlProductRepository($this->pdo),
            new PdoTransactionManager($this->pdo),
        );
    }

    /**
     * Create -> submit -> approve (by Admin, never the creator), ready for goods issue.
     *
     * @param list<array{product_id:int, qty:int}> $items
     */
    protected function approvedSalesOrder(array $items): int
    {
        $service = $this->salesOrderService();

        $so = $service->create(
            ['customer_id' => 1, 'warehouse_id' => self::WAREHOUSE_ID],
            array_map(static fn (array $item): array => $item + ['sell_price' => 30000], $items),
            self::SALES_ID
        );

        $service->submit((int) $so->id);
        $service->approve((int) $so->id, self::ADMIN_ID);

        return (int) $so->id;
    }
}
