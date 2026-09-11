<?php

declare(strict_types=1);

namespace App\Entity;

use App\Domain\SalesOrderStatus;

final class SalesOrder
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $customerId,
        public readonly int $warehouseId,
        public readonly SalesOrderStatus $status,
        public readonly int $createdBy,
        public readonly ?int $approvedBy,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            customerId: (int) $row['customer_id'],
            warehouseId: (int) $row['warehouse_id'],
            status: SalesOrderStatus::from((string) $row['status']),
            createdBy: (int) $row['created_by'],
            approvedBy: $row['approved_by'] !== null ? (int) $row['approved_by'] : null,
        );
    }
}
