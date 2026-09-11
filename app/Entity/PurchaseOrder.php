<?php

declare(strict_types=1);

namespace App\Entity;

use App\Domain\PurchaseOrderStatus;

final class PurchaseOrder
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $supplierId,
        public readonly int $warehouseId,
        public readonly PurchaseOrderStatus $status,
        public readonly string $orderDate,
        public readonly int $createdBy,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            supplierId: (int) $row['supplier_id'],
            warehouseId: (int) $row['warehouse_id'],
            status: PurchaseOrderStatus::from((string) $row['status']),
            orderDate: (string) $row['order_date'],
            createdBy: (int) $row['created_by'],
        );
    }
}
