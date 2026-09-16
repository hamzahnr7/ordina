<?php

declare(strict_types=1);

use App\Core\Authorization\Permission;

/**
 * Single source of truth for the main navigation. Each entry's `permission`
 * (null|Permission|list<Permission>) is checked against the signed-in
 * user's role via Gate::allows() - see App\Core\Authorization\MenuRegistry.
 *
 * `group` clusters related items under a labeled section in the sidebar
 * (see MenuRegistry::GROUP_LABELS below for display order/labels); `icon`
 * picks a glyph from views/layouts/app.php's inline SVG set.
 *
 * To add a new menu item: add a row here, add the route in app/routes.php,
 * and guard the target Controller action with $this->authorize(...) using
 * the same Permission(s). See docs/architecture/rbac-and-menu-access.md.
 */
return [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/dashboard', 'permission' => null, 'group' => 'top', 'icon' => 'dashboard'],

    [
        'key' => 'products',
        'label' => 'Produk',
        'route' => '/products',
        'permission' => [Permission::ManageMasterData, Permission::ViewCatalog, Permission::ViewProductStock],
        'group' => 'master-data',
        'icon' => 'product',
    ],
    ['key' => 'categories', 'label' => 'Kategori Produk', 'route' => '/categories', 'permission' => Permission::ManageMasterData, 'group' => 'master-data', 'icon' => 'category'],
    ['key' => 'warehouses', 'label' => 'Gudang', 'route' => '/warehouses', 'permission' => Permission::ManageMasterData, 'group' => 'master-data', 'icon' => 'warehouse'],
    ['key' => 'suppliers', 'label' => 'Supplier', 'route' => '/suppliers', 'permission' => Permission::ManageMasterData, 'group' => 'master-data', 'icon' => 'supplier'],
    ['key' => 'customers', 'label' => 'Customer', 'route' => '/customers', 'permission' => Permission::ManageMasterData, 'group' => 'master-data', 'icon' => 'customer'],

    [
        'key' => 'purchase-orders',
        'label' => 'Purchase Order',
        'route' => '/purchase-orders',
        'permission' => [Permission::CreatePurchaseOrder, Permission::ProposePurchaseOrder, Permission::ProcessGoodsReceipt],
        'group' => 'transactions',
        'icon' => 'purchase-order',
    ],
    [
        'key' => 'sales-orders',
        'label' => 'Sales Order',
        'route' => '/sales-orders',
        'permission' => [Permission::CreateSalesOrder, Permission::ApproveSalesOrder, Permission::ProcessGoodsIssue],
        'group' => 'transactions',
        'icon' => 'sales-order',
    ],

    [
        'key' => 'reports',
        'label' => 'Laporan',
        'route' => '/reports',
        'permission' => [Permission::DownloadReportAll, Permission::DownloadReportOwnOrders, Permission::DownloadReportStock],
        'group' => 'reports',
        'icon' => 'report',
    ],

    ['key' => 'users', 'label' => 'Manajemen User', 'route' => '/users', 'permission' => Permission::ManageUsers, 'group' => 'admin', 'icon' => 'users'],
];
