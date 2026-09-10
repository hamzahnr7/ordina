<?php

declare(strict_types=1);

namespace App\Core;

use Redis;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (Config::get('REDIS_ENABLED', 'false') === 'true' && extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect(Config::get('REDIS_HOST', 'redis'), (int) Config::get('REDIS_PORT', '6379'));
            session_set_save_handler(new RedisSessionHandler($redis), true);
        }

        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** One-request-lifetime values (validation errors, form re-fill, flash messages). */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }
}
