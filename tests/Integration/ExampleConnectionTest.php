<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * Placeholder proving the integration harness reaches real MySQL.
 * Replace with the required scenarios once goods-receipt/goods-issue exist,
 * e.g.:
 *  - goods receipt increases ProductStock + writes a Receipt StockLedger row end-to-end.
 *  - a second concurrent goods issue is rejected once stock is exhausted by the first (ARCH-02).
 */
final class ExampleConnectionTest extends DatabaseTestCase
{
    public function test_can_query_test_database(): void
    {
        $result = $this->pdo->query('SELECT 1 AS ok')->fetch();

        self::assertSame(1, (int) $result['ok']);
    }
}
