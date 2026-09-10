<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Mirrors the `users.role` ENUM in database/schema-and-seed.sql exactly.
 * There are only ever these three values - see
 * docs/architecture/rbac-and-menu-access.md for why new roles are added
 * here + in a migration, not through a runtime "create role" UI.
 */
enum Role: string
{
    case Admin = 'Admin';
    case Sales = 'Sales';
    case WarehouseStaff = 'WarehouseStaff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Sales => 'Sales',
            self::WarehouseStaff => 'Warehouse Staff',
        };
    }

    /**
     * Roles USR-01 allows an Admin to create/edit/deactivate from the User
     * Management screen. Admin accounts are seeded, not self-service.
     *
     * @return list<self>
     */
    public static function manageable(): array
    {
        return [self::Sales, self::WarehouseStaff];
    }
}
