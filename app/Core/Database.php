<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::connect(
                Config::get('DB_HOST', 'mysql'),
                Config::get('DB_PORT', '3306'),
                Config::get('DB_DATABASE', 'ordina'),
                Config::get('DB_USERNAME', 'ordina'),
                Config::get('DB_PASSWORD', '')
            );
        }

        return self::$connection;
    }

    public static function connect(string $host, string $port, string $database, ?string $username, ?string $password): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Never leak DSN/credentials or the driver's stack trace to the client (ERR-01).
            throw new RuntimeException('Database connection failed.', previous: $e);
        }
    }
}
