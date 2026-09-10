<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Http\Exceptions\AuthorizationException;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Http\Exceptions\UnauthenticatedException;
use App\Core\Router;
use App\Core\Session;
use App\Service\Exception\ForbiddenOperationException;

Config::load(__DIR__ . '/../.env');

Session::start();

$router = new Router();
require __DIR__ . '/../app/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Central error handling (ERR-01): every Controller signals failure by
// throwing instead of rendering directly, so there is exactly one place
// that decides what the client sees - and database/stack-trace details
// never reach the response body.
try {
    $router->dispatch($method, $path);
} catch (UnauthenticatedException) {
    header('Location: /login');
} catch (AuthorizationException | ForbiddenOperationException) {
    http_response_code(403);
    require __DIR__ . '/../views/errors/403.php';
} catch (NotFoundException) {
    http_response_code(404);
    require __DIR__ . '/../views/errors/404.php';
} catch (\Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    require __DIR__ . '/../views/errors/500.php';
}
