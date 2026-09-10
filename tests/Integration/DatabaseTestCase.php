<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Config;
use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that hit real MySQL in Docker (TEST-02). Uses a
 * dedicated DB_TEST_DATABASE so integration tests never touch dev/demo data.
 * Run inside the web container: docker compose exec web vendor/bin/phpunit --testsuite Integration
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Database::connect(
            Config::get('DB_TEST_HOST', 'localhost'),
            Config::get('DB_TEST_PORT', '3306'),
            Config::get('DB_TEST_DATABASE', 'ordina_test'),
            Config::get('DB_TEST_USERNAME', 'tester'),
            Config::get('DB_TEST_PASSWORD', 'testing123')
        );
    }
}
