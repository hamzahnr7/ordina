<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Domain\PurchaseOrderStatus;
use App\Entity\PurchaseOrder;
use App\Repository\Contracts\PurchaseOrderRepositoryInterface;

final class InMemoryPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    /** @var array<int, PurchaseOrder> */
    private array $byId = [];

    private int $nextId = 1;

    public function findById(int $id): ?PurchaseOrder
    {
        return $this->byId[$id] ?? null;
    }

    public function paginateForListing(array $filters, string $sortDir, int $page, int $perPage): array
    {
        $items = array_values($this->byId);

        if (!empty($filters['status'])) {
            $items = array_filter($items, static fn (PurchaseOrder $po): bool => $po->status->value === $filters['status']);
        }

        usort($items, static fn (PurchaseOrder $a, PurchaseOrder $b) => strtoupper($sortDir) === 'ASC'
            ? $a->orderDate <=> $b->orderDate
            : $b->orderDate <=> $a->orderDate);

        $total = count($items);
        $offset = max(0, ($page - 1) * $perPage);

        return ['items' => array_slice(array_values($items), $offset, $perPage), 'total' => $total];
    }

    public function save(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $id = $purchaseOrder->id ?? $this->nextId++;
        $saved = new PurchaseOrder(
            $id,
            $purchaseOrder->supplierId,
            $purchaseOrder->warehouseId,
            $purchaseOrder->status,
            $purchaseOrder->orderDate,
            $purchaseOrder->createdBy,
        );
        $this->byId[$id] = $saved;

        return $saved;
    }

    public function updateStatus(int $id, PurchaseOrderStatus $status): void
    {
        $po = $this->byId[$id];
        $this->byId[$id] = new PurchaseOrder($po->id, $po->supplierId, $po->warehouseId, $status, $po->orderDate, $po->createdBy);
    }

    public function findForReport(string $from, string $to): array
    {
        $matches = array_filter(
            $this->byId,
            static fn (PurchaseOrder $po): bool => $po->orderDate >= $from && $po->orderDate <= $to
        );

        return array_values(array_map(
            static fn (PurchaseOrder $po): array => [
                'id' => $po->id,
                'order_date' => $po->orderDate,
                'supplier_name' => "Supplier #{$po->supplierId}",
                'status' => $po->status->value,
            ],
            $matches
        ));
    }
}
