<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\MenuRegistry;
use App\Core\Controller;
use App\Core\Database;
use App\Domain\Role;
use App\Repository\Mysql\MysqlDashboardRepository;
use App\Service\DashboardService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $role = $this->currentRole();
        $service = new DashboardService(new MysqlDashboardRepository(Database::connection()));

        $stats = match ($role) {
            Role::Admin => $service->forAdmin(),
            Role::Sales => $service->forSales((int) $this->currentUser()['id']),
            Role::WarehouseStaff => $service->forWarehouseStaff(),
            default => [],
        };

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $this->currentUser(),
            'menus' => MenuRegistry::forRole($role),
            'role' => $role,
            'stats' => $stats,
        ]);
    }
}
