<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\MenuRegistry;
use App\Core\Controller;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $role = $this->currentRole();

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $this->currentUser(),
            'menus' => MenuRegistry::forRole($role),
        ]);
    }
}
