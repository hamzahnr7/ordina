<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Authorization\Gate;
use App\Core\Authorization\Permission;
use App\Domain\Role;
use PHPUnit\Framework\TestCase;

/**
 * Encodes the brief's §1.2 segregation-of-duties table as executable
 * assertions, so a future edit to Gate::ROLE_PERMISSIONS that accidentally
 * grants Sales approval rights (or similar) fails a test, not just a review.
 */
final class GateTest extends TestCase
{
    public function test_sales_cannot_approve_sales_orders(): void
    {
        self::assertFalse(Gate::allows(Role::Sales, Permission::ApproveSalesOrder));
    }

    public function test_admin_can_approve_sales_orders(): void
    {
        self::assertTrue(Gate::allows(Role::Admin, Permission::ApproveSalesOrder));
    }

    public function test_only_admin_can_manage_users(): void
    {
        self::assertTrue(Gate::allows(Role::Admin, Permission::ManageUsers));
        self::assertFalse(Gate::allows(Role::Sales, Permission::ManageUsers));
        self::assertFalse(Gate::allows(Role::WarehouseStaff, Permission::ManageUsers));
    }

    public function test_sales_has_no_warehouse_permissions(): void
    {
        self::assertFalse(Gate::allows(Role::Sales, Permission::ProcessGoodsIssue));
        self::assertFalse(Gate::allows(Role::Sales, Permission::ProcessGoodsReceipt));
    }
}
