<?php

declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Repository\Contracts\StockLedgerRepositoryInterface;

final class InMemoryStockLedgerRepository implements StockLedgerRepositoryInterface
{
    /** @var list<array<string, mixed>> */
    private array $rows = [];

    public function record(
        int $productId,
        int $warehouseId,
        string $movementType,
        int $quantity,
        string $referenceType,
        ?int $referenceId,
        int $performedBy,
    ): void {
        $this->rows[] = [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by' => $performedBy,
        ];
    }

    public function findByReference(string $referenceType, int $referenceId): array
    {
        return array_values(array_filter(
            $this->rows,
            static fn (array $row): bool => $row['reference_type'] === $referenceType && $row['reference_id'] === $referenceId
        ));
    }

    /** Test helper - all rows recorded so far, in order. @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->rows;
    }

    public function findByDateRange(string $from, string $to): array
    {
        // No timestamp is recorded on the in-memory rows (only the real
        // schema auto-generates created_at) - no test exercises date
        // filtering against this fake, so it just returns everything.
        return $this->rows;
    }
}
