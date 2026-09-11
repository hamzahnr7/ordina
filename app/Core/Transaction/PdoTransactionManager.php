<?php

declare(strict_types=1);

namespace App\Core\Transaction;

use PDO;
use Throwable;

final class PdoTransactionManager implements TransactionManagerInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function transactional(callable $callback): mixed
    {
        $this->connection->beginTransaction();

        try {
            $result = $callback();
            $this->connection->commit();

            return $result;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }
}
