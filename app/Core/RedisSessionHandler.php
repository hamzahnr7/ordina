<?php

declare(strict_types=1);

namespace App\Core;

use Redis;
use SessionHandlerInterface;

/**
 * Optional session store (see docs/architecture/adr-0003-redis-session-store.md).
 * Holds ephemeral session data only - MySQL/StockLedger remain the sole
 * system-of-record for inventory data, so this does not act as "NoSQL
 * primary storage" under §4's technology rules.
 */
final class RedisSessionHandler implements SessionHandlerInterface
{
    private const TTL_SECONDS = 1800;

    public function __construct(private readonly Redis $redis)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $data = $this->redis->get($this->key($id));

        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool
    {
        return (bool) $this->redis->setex($this->key($id), self::TTL_SECONDS, $data);
    }

    public function destroy(string $id): bool
    {
        $this->redis->del($this->key($id));

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    private function key(string $id): string
    {
        return "session:{$id}";
    }
}
