<?php

declare(strict_types=1);

use App\Controller\Api\ProductAvailabilityController;
use App\Controller\AuthController;
use App\Core\Router;

/** @var Router $router */

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/api/products/{sku}/availability', [ProductAvailabilityController::class, 'show']);

// TODO: register remaining routes as each vertical slice is built
// (dashboard, users, products, warehouses, purchase-orders, sales-orders, reports).
