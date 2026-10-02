<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\PurchaseOrderStatus;
use App\Domain\SalesOrderStatus;
use App\Entity\PurchaseOrder;
use App\Entity\SalesOrder;
use App\Repository\InMemory\InMemoryPurchaseOrderRepository;
use App\Repository\InMemory\InMemorySalesOrderRepository;
use App\Repository\InMemory\InMemoryStockLedgerRepository;
use App\Service\ReportService;
use PHPUnit\Framework\TestCase;

/**
 * REPORT-01's merge logic: PO + SO rows into one date-sorted list, with PO
 * rows only for roles allowed to see them and SO rows scoped to the owner.
 * (The InMemory SO fake stamps every row with the range's start date - it
 * has no created_at - and the real date filtering is SQL, see tech-debt #18.)
 */
final class ReportServiceTest extends TestCase
{
    private const SALES_A = 2;
    private const SALES_B = 3;

    private InMemoryStockLedgerRepository $ledger;
    private ReportService $service;

    protected function setUp(): void
    {
        $purchaseOrders = new InMemoryPurchaseOrderRepository();
        $purchaseOrders->save(new PurchaseOrder(null, 1, 1, PurchaseOrderStatus::Received, '2026-08-20', 1));
        $purchaseOrders->save(new PurchaseOrder(null, 2, 1, PurchaseOrderStatus::Draft, '2026-07-01', 1)); // outside range

        $salesOrders = new InMemorySalesOrderRepository();
        $salesOrders->save(new SalesOrder(null, 1, 1, SalesOrderStatus::Fulfilled, self::SALES_A, 1));
        $salesOrders->save(new SalesOrder(null, 2, 1, SalesOrderStatus::Draft, self::SALES_B, null));

        $this->ledger = new InMemoryStockLedgerRepository();
        $this->service = new ReportService($this->ledger, $purchaseOrders, $salesOrders);
    }

    public function test_orders_report_merges_po_and_so_sorted_by_date(): void
    {
        $rows = $this->service->ordersReport('2026-08-01', '2026-08-31', includePurchaseOrders: true, ownerId: null);

        self::assertSame(['SO', 'SO', 'PO'], array_column($rows, 'order_type'));
        self::assertSame([
            'order_type' => 'PO',
            'id' => 1,
            'date' => '2026-08-20',
            'party_name' => 'Supplier #1',
            'status' => 'Received',
        ], $rows[2]);
        self::assertSame('Customer #1', $rows[0]['party_name']);
    }

    public function test_orders_report_for_sales_has_no_po_rows_and_only_own_orders(): void
    {
        $rows = $this->service->ordersReport('2026-08-01', '2026-08-31', includePurchaseOrders: false, ownerId: self::SALES_B);

        self::assertCount(1, $rows);
        self::assertSame('SO', $rows[0]['order_type']);
        self::assertSame(2, $rows[0]['id']);
        self::assertSame('Draft', $rows[0]['status']);
    }

    public function test_stock_ledger_report_returns_ledger_rows(): void
    {
        $this->ledger->record(1, 1, 'Receipt', 10, 'PO', 1, 4);
        $this->ledger->record(1, 1, 'Issue', 3, 'SO', 1, 4);

        $rows = $this->service->stockLedgerReport('2026-08-01', '2026-08-31');

        self::assertSame(['Receipt', 'Issue'], array_column($rows, 'movement_type'));
    }
}
