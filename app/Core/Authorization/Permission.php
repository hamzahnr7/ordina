<?php

declare(strict_types=1);

namespace App\Core\Authorization;

/**
 * One case per protected action from the brief's §1.2 responsibility table.
 * Controllers guard endpoints with `$this->authorize(Permission::X)`;
 * Gate::allows() is the single place that decides which roles have which
 * permission. Menus in config/menus.php reference these same cases so menu
 * visibility and endpoint authorization can never drift apart.
 */
enum Permission: string
{
    case ManageUsers = 'users.manage';
    case ManageMasterData = 'master-data.manage';
    case ViewCatalog = 'master-data.view-catalog';
    case ViewProductStock = 'master-data.view-stock';

    case CreateSalesOrder = 'sales-order.create';
    case ApproveSalesOrder = 'sales-order.approve';
    case ProcessGoodsIssue = 'goods-issue.process';

    case CreatePurchaseOrder = 'purchase-order.create';
    case ProposePurchaseOrder = 'purchase-order.propose';
    case ProcessGoodsReceipt = 'goods-receipt.process';

    case ViewDashboardAll = 'dashboard.view-all';
    case ViewDashboardOwnOrders = 'dashboard.view-own-orders';
    case ViewDashboardStockQueue = 'dashboard.view-stock-queue';

    case DownloadReportAll = 'report.download-all';
    case DownloadReportOwnOrders = 'report.download-own-orders';
    case DownloadReportStock = 'report.download-stock';
}
