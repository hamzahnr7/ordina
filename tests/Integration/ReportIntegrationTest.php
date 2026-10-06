<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repository\Mysql\MysqlPurchaseOrderRepository;
use App\Repository\Mysql\MysqlSalesOrderRepository;
use App\Repository\Mysql\MysqlStockLedgerRepository;
use App\Service\ReportService;

/**
 * REPORT-01 on real MySQL: the date-range SQL (inclusive on both days), the
 * PO/SO merge, and Sales seeing only their own orders. Fixtures sit exactly
 * on the range edges (00:00:00 / 23:59:59), where an exclusive or
 * datetime-vs-date comparison bug would show up.
 *
 * Range A = 2026-08-01..2026-08-15, Range B = 2026-08-16..2026-09-30 -
 * the same ranges docs/testing/test-scenarios.md uses for RPT-01..RPT-06.
 */
final class ReportIntegrationTest extends DatabaseTestCase
{
    private const OTHER_SALES_ID = 4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo->exec(
            "INSERT INTO users (name, email, password_hash, role) VALUES ('Sales Dua', 'sales2@test.local', 'x', 'Sales')"
        );

        $ledger = $this->pdo->prepare(
            "INSERT INTO stock_ledger (product_id, warehouse_id, movement_type, quantity, reference_type, reference_id, performed_by, created_at)
             VALUES (:product_id, 1, :type, :qty, :ref_type, :ref_id, :by, :at)"
        );
        foreach ([
            [self::PRODUCT_ID, 'Adjustment', 10, 'Adjustment', null, '2026-07-31 23:59:59'], // day before range A
            [self::PRODUCT_ID, 'Adjustment', 20, 'Adjustment', null, '2026-08-01 00:00:00'], // first second of range A
            [self::PRODUCT_ID, 'Receipt', 5, 'PO', 1, '2026-08-15 23:59:59'],                // last second of range A
            [self::OTHER_PRODUCT_ID, 'Issue', 2, 'SO', 1, '2026-08-16 00:00:00'],            // first second of range B
            [self::PRODUCT_ID, 'Issue', 1, 'SO', 2, '2026-10-01 00:00:00'],                  // after range B
        ] as [$productId, $type, $qty, $refType, $refId, $at]) {
            $ledger->execute([
                'product_id' => $productId, 'type' => $type, 'qty' => $qty, 'ref_type' => $refType,
                'ref_id' => $refId, 'by' => self::WAREHOUSE_STAFF_ID, 'at' => $at,
            ]);
        }

        $po = $this->pdo->prepare(
            "INSERT INTO purchase_orders (supplier_id, warehouse_id, status, order_date, created_by) VALUES (1, 1, :status, :date, 1)"
        );
        foreach ([['Received', '2026-08-01'], ['Ordered', '2026-08-15'], ['Draft', '2026-08-16']] as [$status, $date]) {
            $po->execute(['status' => $status, 'date' => $date]);
        }

        $so = $this->pdo->prepare(
            "INSERT INTO sales_orders (customer_id, warehouse_id, status, created_by, created_at) VALUES (1, 1, :status, :by, :at)"
        );
        foreach ([
            ['Fulfilled', self::SALES_ID, '2026-08-15 23:59:59'],
            ['PendingApproval', self::SALES_ID, '2026-08-16 00:00:01'],
            ['Draft', self::OTHER_SALES_ID, '2026-08-20 10:00:00'],
        ] as [$status, $createdBy, $at]) {
            $so->execute(['status' => $status, 'by' => $createdBy, 'at' => $at]);
        }
    }

    private function service(): ReportService
    {
        return new ReportService(
            new MysqlStockLedgerRepository($this->pdo),
            new MysqlPurchaseOrderRepository($this->pdo),
            new MysqlSalesOrderRepository($this->pdo),
        );
    }

    public function test_stock_ledger_range_includes_both_edge_days_and_nothing_outside(): void
    {
        $rangeA = $this->service()->stockLedgerReport('2026-08-01', '2026-08-15');

        self::assertSame(['2026-08-01 00:00:00', '2026-08-15 23:59:59'], array_column($rangeA, 'created_at'));
        self::assertSame(['Adjustment', 'Receipt'], array_column($rangeA, 'movement_type'));
    }

    public function test_stock_ledger_rows_carry_the_joined_names_the_csv_prints(): void
    {
        $row = $this->service()->stockLedgerReport('2026-08-16', '2026-09-30')[0];

        self::assertSame('IT-0002', $row['sku']);
        self::assertSame('Produk Dua', $row['product_name']);
        self::assertSame('Gudang Test', $row['warehouse_name']);
        self::assertSame('Gudang', $row['performed_by_name']);
        self::assertSame('Issue', $row['movement_type']);
    }

    public function test_two_ranges_return_different_rows(): void
    {
        $service = $this->service();

        self::assertCount(2, $service->stockLedgerReport('2026-08-01', '2026-08-15'));
        self::assertCount(1, $service->stockLedgerReport('2026-08-16', '2026-09-30'));
    }

    public function test_admin_orders_report_merges_po_and_so_sorted_by_date(): void
    {
        $rows = $this->service()->ordersReport('2026-08-01', '2026-08-15', includePurchaseOrders: true, ownerId: null);

        self::assertSame(
            [['PO', '2026-08-01'], ['PO', '2026-08-15'], ['SO', '2026-08-15']],
            array_map(static fn (array $r): array => [$r['order_type'], (string) $r['date']], $rows)
        );
        self::assertSame('Supplier Test', $rows[0]['party_name']);
        self::assertSame('Customer Test', $rows[2]['party_name']);
    }

    public function test_admin_orders_report_range_b_counts_all_creators(): void
    {
        $rows = $this->service()->ordersReport('2026-08-16', '2026-09-30', includePurchaseOrders: true, ownerId: null);

        self::assertSame(['PO', 'SO', 'SO'], array_column($rows, 'order_type'));
    }

    public function test_sales_orders_report_has_only_own_sales_orders_and_no_po(): void
    {
        $service = $this->service();

        $salesSatu = $service->ordersReport('2026-08-16', '2026-09-30', includePurchaseOrders: false, ownerId: self::SALES_ID);
        $salesDua = $service->ordersReport('2026-08-16', '2026-09-30', includePurchaseOrders: false, ownerId: self::OTHER_SALES_ID);

        self::assertSame(['SO'], array_values(array_unique(array_column($salesSatu, 'order_type'))));
        self::assertSame(['PendingApproval'], array_column($salesSatu, 'status'));
        self::assertSame(['Draft'], array_column($salesDua, 'status'));
        self::assertNotEquals(array_column($salesSatu, 'id'), array_column($salesDua, 'id'));
    }
}
