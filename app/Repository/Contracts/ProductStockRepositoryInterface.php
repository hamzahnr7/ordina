<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

interface ProductStockRepositoryInterface
{
    /** @return list<array{warehouse_id:int, warehouse_name:string, quantity:int}> */
    public function findByProductId(int $productId): array;

    /**
     * Creates the product+warehouse row if it doesn't exist yet, otherwise
     * adds $delta to its quantity. Used by goods receipt (PO-01) - always
     * called from inside a TransactionManagerInterface::transactional()
     * block alongside a StockLedgerRepositoryInterface::record() call
     * (ARCH-02).
     */
    public function incrementQuantity(int $productId, int $warehouseId, int $delta): void;

    /**
     * ARCH-02: atomically decrements quantity only if at least $qty is
     * available - the check and the write are the same SQL statement, so
     * InnoDB's row lock during the UPDATE makes this race-free (see
     * docs/architecture/adr-0002-concurrency-safe-stock.md). Returns false
     * (no row affected) if the product+warehouse row doesn't exist or
     * doesn't have enough stock - the caller must treat that as rejected,
     * never retry-and-ignore.
     */
    public function decrementIfAvailable(int $productId, int $warehouseId, int $qty): bool;
}
