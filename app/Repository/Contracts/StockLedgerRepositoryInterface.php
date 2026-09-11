<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

interface StockLedgerRepositoryInterface
{
    /** Writes one append-only movement row (§1.3, ARCH-02). $movementType: Receipt|Issue|Adjustment. $referenceType: PO|SO|Adjustment. */
    public function record(
        int $productId,
        int $warehouseId,
        string $movementType,
        int $quantity,
        string $referenceType,
        ?int $referenceId,
        int $performedBy,
    ): void;

    /** @return list<array<string, mixed>> */
    public function findByReference(string $referenceType, int $referenceId): array;

    /** REPORT-01: all movements within [from, to] (inclusive, 'Y-m-d'), joined with product/warehouse/user names for the CSV export. @return list<array<string, mixed>> */
    public function findByDateRange(string $from, string $to): array;
}
