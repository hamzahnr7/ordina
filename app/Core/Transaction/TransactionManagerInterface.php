<?php

declare(strict_types=1);

namespace App\Core\Transaction;

/**
 * ARCH-02: the multi-table write in goods receipt/issue (ProductStock +
 * StockLedger, +status recompute) must be atomic. Abstracted behind an
 * interface - like Repository boundaries - so the Service that orchestrates
 * it (e.g. PurchaseOrderService::receiveGoods()) can be unit-tested with
 * NullTransactionManager + InMemory repositories, with no real database.
 */
interface TransactionManagerInterface
{
    /**
     * Runs $callback inside beginTransaction()/commit(); rolls back and
     * rethrows on any Throwable.
     */
    public function transactional(callable $callback): mixed;
}
