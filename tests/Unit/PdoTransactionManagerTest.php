<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Transaction\NullTransactionManager;
use App\Core\Transaction\PdoTransactionManager;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ARCH-02's atomicity rests on this class: commit only when the callback
 * finishes, roll back and rethrow on any failure. The PDO here is a mock -
 * the real-MySQL rollback is proven in tests/Integration/GoodsIssueIntegrationTest.
 */
final class PdoTransactionManagerTest extends TestCase
{
    public function test_commits_and_returns_callback_result(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('commit')->willReturn(true);
        $pdo->expects(self::never())->method('rollBack');

        $result = (new PdoTransactionManager($pdo))->transactional(static fn (): string => 'done');

        self::assertSame('done', $result);
    }

    public function test_rolls_back_and_rethrows_when_callback_fails(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::never())->method('commit');
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stok tidak cukup');

        (new PdoTransactionManager($pdo))->transactional(static function (): never {
            throw new RuntimeException('stok tidak cukup');
        });
    }

    public function test_null_manager_just_runs_the_callback(): void
    {
        self::assertSame(42, (new NullTransactionManager())->transactional(static fn (): int => 42));
    }
}
