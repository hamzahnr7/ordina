<?php

declare(strict_types=1);

use App\Core\Config;

// Central place to translate raw .env values into typed application settings.
// Controllers/Services should depend on this array (or small wrapper objects),
// never read getenv()/$_ENV directly - keeps ARCH-01's "no superglobals in
// business logic" rule honest.
return [
    'app' => [
        'env' => Config::get('APP_ENV', 'local'),
        'url' => Config::get('APP_URL', 'http://localhost:8080'),
    ],
    'db' => [
        'host' => Config::get('DB_HOST', 'mysql'),
        'port' => Config::get('DB_PORT', '3306'),
        'database' => Config::get('DB_DATABASE', 'ordina'),
        'username' => Config::get('DB_USERNAME', 'ordina'),
        'password' => Config::get('DB_PASSWORD', ''),
    ],
    'session' => [
        'redis_enabled' => Config::get('REDIS_ENABLED', 'false') === 'true',
        'redis_host' => Config::get('REDIS_HOST', 'redis'),
        'redis_port' => (int) Config::get('REDIS_PORT', '6379'),
    ],
];
