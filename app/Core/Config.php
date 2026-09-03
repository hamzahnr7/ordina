<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $envFile): void
    {
        if (!is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            self::$values[trim($key)] = trim($value);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $fromEnv = getenv($key);

        return $fromEnv !== false ? $fromEnv : $default;
    }
}
