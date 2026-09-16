<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Domain\Role;

/**
 * Reads config/menus.php and filters it down to what a given Role may see,
 * grouped into labeled sidebar sections in a fixed display order. Purely
 * presentational (nav rendering) - the actual access control for each route
 * still happens via Controller::authorize() + Gate, so a menu being hidden
 * is a convenience, never the security boundary.
 */
final class MenuRegistry
{
    /** Display order and label for each group key used in config/menus.php. Null label = no section header (e.g. Dashboard). */
    private const GROUP_LABELS = [
        'top' => null,
        'master-data' => 'Master Data',
        'transactions' => 'Transaksi',
        'reports' => 'Laporan',
        'admin' => 'Administrasi',
    ];

    /** @return list<array{label:?string,items:list<array{key:string,label:string,route:string,icon:string}>}> */
    public static function forRole(Role $role): array
    {
        /** @var list<array{key:string,label:string,route:string,permission:Permission|list<Permission>|null,group:string,icon:string}> $menus */
        $menus = require dirname(__DIR__, 3) . '/config/menus.php';

        $visible = array_values(array_filter($menus, static function (array $menu) use ($role): bool {
            if ($menu['permission'] === null) {
                return true;
            }

            $required = is_array($menu['permission']) ? $menu['permission'] : [$menu['permission']];

            foreach ($required as $permission) {
                if (Gate::allows($role, $permission)) {
                    return true;
                }
            }

            return false;
        }));

        $groups = [];

        foreach (self::GROUP_LABELS as $groupKey => $label) {
            $items = array_values(array_filter($visible, static fn (array $menu): bool => $menu['group'] === $groupKey));

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'label' => $label,
                'items' => array_map(
                    static fn (array $menu) => ['key' => $menu['key'], 'label' => $menu['label'], 'route' => $menu['route'], 'icon' => $menu['icon']],
                    $items
                ),
            ];
        }

        return $groups;
    }
}
