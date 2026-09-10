<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Domain\Role;

/**
 * Reads config/menus.php and filters it down to what a given Role may see.
 * Purely presentational (nav rendering) - the actual access control for
 * each route still happens via Controller::authorize() + Gate, so a menu
 * being hidden is a convenience, never the security boundary.
 */
final class MenuRegistry
{
    /** @return list<array{key:string,label:string,route:string}> */
    public static function forRole(Role $role): array
    {
        /** @var list<array{key:string,label:string,route:string,permission:Permission|list<Permission>|null}> $menus */
        $menus = require dirname(__DIR__, 3) . '/config/menus.php';

        $visible = array_filter($menus, static function (array $menu) use ($role): bool {
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
        });

        return array_map(
            static fn (array $menu) => ['key' => $menu['key'], 'label' => $menu['label'], 'route' => $menu['route']],
            array_values($visible)
        );
    }
}
