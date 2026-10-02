<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\PurchaseOrderStatus;
use App\Domain\Role;
use App\Domain\SalesOrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The status enums are the single source of truth for which transition is
 * allowed from which state (brief §1.3) - every case is pinned here so a
 * changed rule shows up as a failing row, not a silent behavior change.
 */
final class StatusEnumTest extends TestCase
{
    /** @return iterable<string, array{PurchaseOrderStatus, string, string, bool, bool}> */
    public static function purchaseOrderStatuses(): iterable
    {
        // status, label, badge, canBeCancelled, canReceiveGoods
        yield 'Draft' => [PurchaseOrderStatus::Draft, 'Draft', 'badge-muted', true, false];
        yield 'Ordered' => [PurchaseOrderStatus::Ordered, 'Ordered', 'badge-success', true, true];
        yield 'PartiallyReceived' => [PurchaseOrderStatus::PartiallyReceived, 'Partially Received', 'badge-success', true, true];
        yield 'Received' => [PurchaseOrderStatus::Received, 'Received', 'badge-success', false, false];
        yield 'Cancelled' => [PurchaseOrderStatus::Cancelled, 'Cancelled', 'badge-danger', false, false];
    }

    #[DataProvider('purchaseOrderStatuses')]
    public function test_purchase_order_status_rules(
        PurchaseOrderStatus $status,
        string $label,
        string $badge,
        bool $canBeCancelled,
        bool $canReceiveGoods,
    ): void {
        self::assertSame($label, $status->label());
        self::assertSame($badge, $status->badgeClass());
        self::assertSame($canBeCancelled, $status->canBeCancelled());
        self::assertSame($canReceiveGoods, $status->canReceiveGoods());
    }

    /** @return iterable<string, array{SalesOrderStatus, string, string, bool, bool, bool, bool}> */
    public static function salesOrderStatuses(): iterable
    {
        // status, label, badge, canBeSubmitted, canBeDecided, canBeCancelled, canIssueGoods
        yield 'Draft' => [SalesOrderStatus::Draft, 'Draft', 'badge-muted', true, false, true, false];
        yield 'PendingApproval' => [SalesOrderStatus::PendingApproval, 'Pending Approval', 'badge-success', false, true, true, false];
        yield 'Approved' => [SalesOrderStatus::Approved, 'Approved', 'badge-success', false, false, true, true];
        yield 'Fulfilled' => [SalesOrderStatus::Fulfilled, 'Fulfilled', 'badge-success', false, false, false, false];
        yield 'Cancelled' => [SalesOrderStatus::Cancelled, 'Cancelled', 'badge-danger', false, false, false, false];
    }

    #[DataProvider('salesOrderStatuses')]
    public function test_sales_order_status_rules(
        SalesOrderStatus $status,
        string $label,
        string $badge,
        bool $canBeSubmitted,
        bool $canBeDecided,
        bool $canBeCancelled,
        bool $canIssueGoods,
    ): void {
        self::assertSame($label, $status->label());
        self::assertSame($badge, $status->badgeClass());
        self::assertSame($canBeSubmitted, $status->canBeSubmitted());
        self::assertSame($canBeDecided, $status->canBeDecided());
        self::assertSame($canBeCancelled, $status->canBeCancelled());
        self::assertSame($canIssueGoods, $status->canIssueGoods());
    }

    public function test_role_labels(): void
    {
        self::assertSame('Admin', Role::Admin->label());
        self::assertSame('Sales', Role::Sales->label());
        self::assertSame('Warehouse Staff', Role::WarehouseStaff->label());
    }

    public function test_admin_is_never_a_manageable_role(): void
    {
        self::assertSame([Role::Sales, Role::WarehouseStaff], Role::manageable());
    }
}
