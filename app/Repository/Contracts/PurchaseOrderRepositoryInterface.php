<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Domain\PurchaseOrderStatus;
use App\Entity\PurchaseOrder;

interface PurchaseOrderRepositoryInterface
{
    public function findById(int $id): ?PurchaseOrder;

    /**
     * FIND-01: search by PO number or supplier name, filter by status, sort
     * by order_date, paginated. Returns denormalized rows (supplier_name,
     * warehouse_name joined in) for the listing view.
     *
     * @param array{search?:string, status?:string} $filters
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginateForListing(array $filters, string $sortDir, int $page, int $perPage): array;

    /** Inserts when $purchaseOrder->id is null, otherwise updates supplier/warehouse/order_date (not status - see updateStatus()). */
    public function save(PurchaseOrder $purchaseOrder): PurchaseOrder;

    public function updateStatus(int $id, PurchaseOrderStatus $status): void;

    /** REPORT-01: PO status report within [from, to] on order_date (inclusive, 'Y-m-d'). @return list<array{id:int, order_date:string, supplier_name:string, status:string}> */
    public function findForReport(string $from, string $to): array;
}
