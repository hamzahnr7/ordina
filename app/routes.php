<?php

declare(strict_types=1);

use App\Controller\Api\ProductAvailabilityController;
use App\Controller\AuthController;
use App\Controller\CategoryController;
use App\Controller\CustomerController;
use App\Controller\DashboardController;
use App\Controller\ProductController;
use App\Controller\SupplierController;
use App\Controller\UserController;
use App\Controller\WarehouseController;
use App\Core\Router;

/** @var Router $router */

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);

// USR-01 - Admin only (enforced server-side via UserController::authorize()).
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/{id}/edit', [UserController::class, 'edit']);
$router->post('/users/{id}', [UserController::class, 'update']);
$router->post('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

// Master data (§2.2) - Admin-only CRUD; literal paths ("create") must be
// registered before the "{id}" pattern so the router doesn't capture them
// as an id first (see App\Core\Router::dispatch - first match wins).
$router->get('/categories', [CategoryController::class, 'index']);
$router->get('/categories/create', [CategoryController::class, 'create']);
$router->post('/categories', [CategoryController::class, 'store']);
$router->get('/categories/{id}/edit', [CategoryController::class, 'edit']);
$router->post('/categories/{id}', [CategoryController::class, 'update']);

$router->get('/warehouses', [WarehouseController::class, 'index']);
$router->get('/warehouses/create', [WarehouseController::class, 'create']);
$router->post('/warehouses', [WarehouseController::class, 'store']);
$router->get('/warehouses/{id}/edit', [WarehouseController::class, 'edit']);
$router->post('/warehouses/{id}', [WarehouseController::class, 'update']);
$router->post('/warehouses/{id}/toggle-active', [WarehouseController::class, 'toggleActive']);

$router->get('/suppliers', [SupplierController::class, 'index']);
$router->get('/suppliers/create', [SupplierController::class, 'create']);
$router->post('/suppliers', [SupplierController::class, 'store']);
$router->get('/suppliers/{id}/edit', [SupplierController::class, 'edit']);
$router->post('/suppliers/{id}', [SupplierController::class, 'update']);
$router->post('/suppliers/{id}/toggle-active', [SupplierController::class, 'toggleActive']);

$router->get('/customers', [CustomerController::class, 'index']);
$router->get('/customers/create', [CustomerController::class, 'create']);
$router->post('/customers', [CustomerController::class, 'store']);
$router->get('/customers/{id}/edit', [CustomerController::class, 'edit']);
$router->post('/customers/{id}', [CustomerController::class, 'update']);
$router->post('/customers/{id}/toggle-active', [CustomerController::class, 'toggleActive']);

// Products (PRD-01/FIND-01/WH-01) - index()/show() readable by Admin/Sales/
// Warehouse Staff alike; create/edit/toggle stay Admin-only.
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/create', [ProductController::class, 'create']);
$router->post('/products', [ProductController::class, 'store']);
$router->get('/products/{id}/edit', [ProductController::class, 'edit']);
$router->post('/products/{id}/toggle-active', [ProductController::class, 'toggleActive']);
$router->post('/products/{id}', [ProductController::class, 'update']);
$router->get('/products/{id}', [ProductController::class, 'show']);

$router->get('/api/products/{sku}/availability', [ProductAvailabilityController::class, 'show']);

// TODO: register remaining routes as each vertical slice is built
// (purchase-orders, sales-orders, reports).
