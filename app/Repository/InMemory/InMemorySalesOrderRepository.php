<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Domain\SalesOrderStatus;
use App\Entity\SalesOrder;
use App\Repository\Contracts\SalesOrderRepositoryInterface;

final class InMemorySalesOrderRepository implements SalesOrderRepositoryInterface
{
    /** @var array<int, SalesOrder> */
    private array $byId = [];

    private int $nextId = 1;

    public function findById(int $id): ?SalesOrder
    {
        return $this->byId[$id] ?? null;
    }

    public function paginateForListing(array $filters, ?int $ownerId, string $sortDir, int $page, int $perPage): array
    {
        $items = array_values($this->byId);

        if ($ownerId !== null) {
            $items = array_filter($items, static fn (SalesOrder $so): bool => $so->createdBy === $ownerId);
        }

        if (!empty($filters['status'])) {
            $items = array_filter($items, static fn (SalesOrder $so): bool => $so->status->value === $filters['status']);
        }

        $items = array_values($items);
        usort($items, static fn (SalesOrder $a, SalesOrder $b) => strtoupper($sortDir) === 'ASC'
            ? $a->id <=> $b->id
            : $b->id <=> $a->id);

        $total = count($items);
        $offset = max(0, ($page - 1) * $perPage);

        return ['items' => array_slice($items, $offset, $perPage), 'total' => $total];
    }

    public function save(SalesOrder $salesOrder): SalesOrder
    {
        $id = $salesOrder->id ?? $this->nextId++;
        $saved = new SalesOrder($id, $salesOrder->customerId, $salesOrder->warehouseId, $salesOrder->status, $salesOrder->createdBy, $salesOrder->approvedBy);
        $this->byId[$id] = $saved;

        return $saved;
    }

    public function updateStatus(int $id, SalesOrderStatus $status): void
    {
        $so = $this->byId[$id];
        $this->byId[$id] = new SalesOrder($so->id, $so->customerId, $so->warehouseId, $status, $so->createdBy, $so->approvedBy);
    }

    public function approve(int $id, int $approvedBy): void
    {
        $so = $this->byId[$id];
        $this->byId[$id] = new SalesOrder($so->id, $so->customerId, $so->warehouseId, SalesOrderStatus::Approved, $so->createdBy, $approvedBy);
    }

    public function findForReport(string $from, string $to, ?int $ownerId): array
    {
        // No timestamp is recorded on the in-memory entity (only the real
        // schema auto-generates created_at) - date range is a no-op here;
        // no test exercises date filtering against this fake.
        $items = array_values($this->byId);

        if ($ownerId !== null) {
            $items = array_filter($items, static fn (SalesOrder $so): bool => $so->createdBy === $ownerId);
        }

        return array_values(array_map(
            static fn (SalesOrder $so): array => [
                'id' => $so->id,
                'date' => $from,
                'customer_name' => "Customer #{$so->customerId}",
                'status' => $so->status->value,
            ],
            $items
        ));
    }
}
