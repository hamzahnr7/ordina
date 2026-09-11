<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Gate;
use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Domain\Role;
use App\Repository\Mysql\MysqlPurchaseOrderRepository;
use App\Repository\Mysql\MysqlSalesOrderRepository;
use App\Repository\Mysql\MysqlStockLedgerRepository;
use App\Service\ReportService;
use DateTimeImmutable;

/**
 * REPORT-01. Access mirrors §1.2's "Mengunduh laporan" row exactly:
 * Admin = both reports, all data; Sales = order report, own orders only;
 * Warehouse Staff = stock report only.
 */
final class ReportController extends Controller
{
    public function index(): void
    {
        $this->authorizeAny(Permission::DownloadReportAll, Permission::DownloadReportOwnOrders, Permission::DownloadReportStock);

        $role = $this->currentRole();

        $this->view('reports/index', [
            'title' => 'Laporan',
            'canStock' => $role !== null && (Gate::allows($role, Permission::DownloadReportAll) || Gate::allows($role, Permission::DownloadReportStock)),
            'canOrders' => $role !== null && (Gate::allows($role, Permission::DownloadReportAll) || Gate::allows($role, Permission::DownloadReportOwnOrders)),
            'defaultFrom' => date('Y-m-01'),
            'defaultTo' => date('Y-m-d'),
        ]);
    }

    public function stockLedgerCsv(): void
    {
        $this->authorizeAny(Permission::DownloadReportAll, Permission::DownloadReportStock);

        [$from, $to] = $this->dateRangeFromQuery();
        $rows = $this->service()->stockLedgerReport($from, $to);

        $this->streamCsv(
            "stock-ledger_{$from}_to_{$to}.csv",
            ['Waktu', 'SKU', 'Produk', 'Gudang', 'Tipe', 'Qty', 'Referensi', 'Oleh'],
            array_map(static fn (array $r): array => [
                $r['created_at'],
                $r['sku'],
                $r['product_name'],
                $r['warehouse_name'],
                $r['movement_type'],
                $r['quantity'],
                "{$r['reference_type']}-{$r['reference_id']}",
                $r['performed_by_name'],
            ], $rows)
        );
    }

    public function ordersCsv(): void
    {
        $this->authorizeAny(Permission::DownloadReportAll, Permission::DownloadReportOwnOrders);

        [$from, $to] = $this->dateRangeFromQuery();
        $role = $this->currentRole();
        $includePurchaseOrders = $role !== null && Gate::allows($role, Permission::DownloadReportAll);
        $ownerId = $role === Role::Sales ? (int) $this->currentUser()['id'] : null;

        $rows = $this->service()->ordersReport($from, $to, $includePurchaseOrders, $ownerId);

        $this->streamCsv(
            "orders_{$from}_to_{$to}.csv",
            ['Tipe', 'No. Order', 'Tanggal', 'Pihak Terkait', 'Status'],
            array_map(static fn (array $r): array => [
                $r['order_type'],
                $r['id'],
                $r['date'],
                $r['party_name'],
                $r['status'],
            ], $rows)
        );
    }

    /** @return array{0: string, 1: string} [from, to] as 'Y-m-d', defaulting to month-to-date on anything invalid. */
    private function dateRangeFromQuery(): array
    {
        $from = (string) ($_GET['from'] ?? '');
        $to = (string) ($_GET['to'] ?? '');

        if (DateTimeImmutable::createFromFormat('Y-m-d', $from) === false) {
            $from = date('Y-m-01');
        }

        if (DateTimeImmutable::createFromFormat('Y-m-d', $to) === false) {
            $to = date('Y-m-d');
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @param list<string> $header
     * @param list<list<mixed>> $rows
     */
    private function streamCsv(string $filename, array $header, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $out = fopen('php://output', 'wb');
        fputcsv($out, $header);

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        fclose($out);
    }

    private function service(): ReportService
    {
        $pdo = Database::connection();

        return new ReportService(
            new MysqlStockLedgerRepository($pdo),
            new MysqlPurchaseOrderRepository($pdo),
            new MysqlSalesOrderRepository($pdo),
        );
    }
}
