<?php

declare(strict_types=1);

namespace App\Core\Transaction;

/** Test double: runs the callback directly, no real transaction - InMemory repositories have nothing to commit/roll back. */
final class NullTransactionManager implements TransactionManagerInterface
{
    public function transactional(callable $callback): mixed
    {
        return $callback();
    }
}
