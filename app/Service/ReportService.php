<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Contracts\PurchaseOrderRepositoryInterface;
use App\Repository\Contracts\SalesOrderRepositoryInterface;
use App\Repository\Contracts\StockLedgerRepositoryInterface;

/**
 * REPORT-01. Reuses the same repositories DASH-01 reads from (per the
 * brief's "dihasilkan dari query agregasi/rekap yang sama dengan
 * dashboard") rather than writing separate one-off report queries.
 */
final class ReportService
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $ledger,
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly SalesOrderRepositoryInterface $salesOrders,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function stockLedgerReport(string $from, string $to): array
    {
        return $this->ledger->findByDateRange($from, $to);
    }

    /**
     * @return list<array{order_type:string, id:int, date:string, party_name:string, status:string}>
     */
    public function ordersReport(string $from, string $to, bool $includePurchaseOrders, ?int $ownerId): array
    {
        $rows = [];

        if ($includePurchaseOrders) {
            foreach ($this->purchaseOrders->findForReport($from, $to) as $po) {
                $rows[] = [
                    'order_type' => 'PO',
                    'id' => $po['id'],
                    'date' => $po['order_date'],
                    'party_name' => $po['supplier_name'],
                    'status' => $po['status'],
                ];
            }
        }

        foreach ($this->salesOrders->findForReport($from, $to, $ownerId) as $so) {
            $rows[] = [
                'order_type' => 'SO',
                'id' => $so['id'],
                'date' => $so['date'],
                'party_name' => $so['customer_name'],
                'status' => $so['status'],
            ];
        }

        usort($rows, static fn (array $a, array $b) => $a['date'] <=> $b['date']);

        return $rows;
    }
}
