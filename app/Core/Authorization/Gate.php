<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Domain\Role;

/**
 * Role -> Permission map. This is the server-side enforcement point required
 * by the brief (§1.2, SO-01): approve/goods-issue/goods-receipt checks must
 * live here, not only be hidden in the UI.
 */
final class Gate
{
    /** @var array<string, list<Permission>> */
    private const ROLE_PERMISSIONS = [
        'Admin' => [
            Permission::ManageUsers,
            Permission::ManageMasterData,
            Permission::CreateSalesOrder,
            Permission::ApproveSalesOrder,
            Permission::ProcessGoodsIssue,
            Permission::CreatePurchaseOrder,
            Permission::ProcessGoodsReceipt,
            Permission::ViewDashboardAll,
            Permission::DownloadReportAll,
        ],
        'Sales' => [
            Permission::ViewCatalog,
            Permission::CreateSalesOrder,
            Permission::ViewDashboardOwnOrders,
            Permission::DownloadReportOwnOrders,
        ],
        'WarehouseStaff' => [
            Permission::ViewProductStock,
            Permission::ProposePurchaseOrder,
            Permission::ProcessGoodsReceipt,
            Permission::ProcessGoodsIssue,
            Permission::ViewDashboardStockQueue,
            Permission::DownloadReportStock,
        ],
    ];

    public static function allows(Role $role, Permission $permission): bool
    {
        return in_array($permission, self::ROLE_PERMISSIONS[$role->value] ?? [], true);
    }

    /** @return list<Permission> */
    public static function permissionsFor(Role $role): array
    {
        return self::ROLE_PERMISSIONS[$role->value] ?? [];
    }
}
