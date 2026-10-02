<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Authorization\MenuRegistry;
use App\Domain\Role;
use PHPUnit\Framework\TestCase;

/**
 * Sidebar menus are derived from config/menus.php + Gate, never hard-coded
 * per role - these pin what each role actually sees and in which order.
 */
final class MenuRegistryTest extends TestCase
{
    /** @param list<array{label:?string, items:list<array{key:string}>}> $groups @return array<string, list<string>> */
    private static function keysByGroup(array $groups): array
    {
        $result = [];

        foreach ($groups as $group) {
            $result[$group['label'] ?? '(top)'] = array_column($group['items'], 'key');
        }

        return $result;
    }

    public function test_admin_sees_every_group_in_display_order(): void
    {
        self::assertSame([
            '(top)' => ['dashboard'],
            'Master Data' => ['products', 'categories', 'warehouses', 'suppliers', 'customers'],
            'Transaksi' => ['purchase-orders', 'sales-orders'],
            'Laporan' => ['reports'],
            'Administrasi' => ['users'],
        ], self::keysByGroup(MenuRegistry::forRole(Role::Admin)));
    }

    public function test_sales_sees_no_master_data_admin_or_purchase_orders(): void
    {
        self::assertSame([
            '(top)' => ['dashboard'],
            'Master Data' => ['products'],
            'Transaksi' => ['sales-orders'],
            'Laporan' => ['reports'],
        ], self::keysByGroup(MenuRegistry::forRole(Role::Sales)));
    }

    public function test_warehouse_staff_reaches_products_through_a_later_permission_in_the_list(): void
    {
        // products requires any of [ManageMasterData, ViewCatalog, ViewProductStock];
        // Warehouse Staff only holds the last one.
        self::assertSame([
            '(top)' => ['dashboard'],
            'Master Data' => ['products'],
            'Transaksi' => ['purchase-orders', 'sales-orders'],
            'Laporan' => ['reports'],
        ], self::keysByGroup(MenuRegistry::forRole(Role::WarehouseStaff)));
    }

    public function test_menu_items_expose_only_display_fields(): void
    {
        $item = MenuRegistry::forRole(Role::Admin)[0]['items'][0];

        self::assertSame(['key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'dashboard'], $item);
    }
}
