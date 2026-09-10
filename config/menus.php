<?php

declare(strict_types=1);

use App\Core\Authorization\Permission;

/**
 * Single source of truth for the main navigation. Each entry's `permission`
 * (null|Permission|list<Permission>) is checked against the signed-in
 * user's role via Gate::allows() - see App\Core\Authorization\MenuRegistry.
 *
 * To add a new menu item: add a row here, add the route in app/routes.php,
 * and guard the target Controller action with $this->authorize(...) using
 * the same Permission(s). See docs/architecture/rbac-and-menu-access.md.
 */
return [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/dashboard', 'permission' => null],

    ['key' => 'users', 'label' => 'Manajemen User', 'route' => '/users', 'permission' => Permission::ManageUsers],

    [
        'key' => 'products',
        'label' => 'Produk',
        'route' => '/products',
        'permission' => [Permission::ManageMasterData, Permission::ViewCatalog, Permission::ViewProductStock],
    ],

    ['key' => 'categories', 'label' => 'Kategori Produk', 'route' => '/categories', 'permission' => Permission::ManageMasterData],

    ['key' => 'warehouses', 'label' => 'Gudang', 'route' => '/warehouses', 'permission' => Permission::ManageMasterData],

    ['key' => 'suppliers', 'label' => 'Supplier', 'route' => '/suppliers', 'permission' => Permission::ManageMasterData],

    ['key' => 'customers', 'label' => 'Customer', 'route' => '/customers', 'permission' => Permission::ManageMasterData],

    [
        'key' => 'purchase-orders',
        'label' => 'Purchase Order',
        'route' => '/purchase-orders',
        'permission' => [Permission::CreatePurchaseOrder, Permission::ProposePurchaseOrder, Permission::ProcessGoodsReceipt],
    ],

    [
        'key' => 'sales-orders',
        'label' => 'Sales Order',
        'route' => '/sales-orders',
        'permission' => [Permission::CreateSalesOrder, Permission::ApproveSalesOrder, Permission::ProcessGoodsIssue],
    ],

    [
        'key' => 'reports',
        'label' => 'Laporan',
        'route' => '/reports',
        'permission' => [Permission::DownloadReportAll, Permission::DownloadReportOwnOrders, Permission::DownloadReportStock],
    ],
];
