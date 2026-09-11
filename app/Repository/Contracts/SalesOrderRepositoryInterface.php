<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Domain\SalesOrderStatus;
use App\Entity\SalesOrder;

interface SalesOrderRepositoryInterface
{
    public function findById(int $id): ?SalesOrder;

    /**
     * FIND-01: search by SO number or customer name, filter by status, sort
     * by created_at, paginated. $ownerId scopes the list to one creator
     * (Sales sees only their own orders - §1.2); null means unscoped (Admin/
     * Warehouse Staff see all). Returns denormalized rows (customer_name,
     * warehouse_name joined in) for the listing view.
     *
     * @param array{search?:string, status?:string} $filters
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginateForListing(array $filters, ?int $ownerId, string $sortDir, int $page, int $perPage): array;

    /** Inserts when $salesOrder->id is null, otherwise updates customer/warehouse (not status/approved_by - see updateStatus()/approve()). */
    public function save(SalesOrder $salesOrder): SalesOrder;

    public function updateStatus(int $id, SalesOrderStatus $status): void;

    public function approve(int $id, int $approvedBy): void;

    /** REPORT-01: SO status report within [from, to] on created_at's date (inclusive, 'Y-m-d'). $ownerId scopes to one creator (Sales), null = all (Admin). @return list<array{id:int, date:string, customer_name:string, status:string}> */
    public function findForReport(string $from, string $to, ?int $ownerId): array;
}
