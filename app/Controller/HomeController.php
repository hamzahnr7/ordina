<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Session;

/** Base URL ("/"): send the visitor to wherever they actually belong. */
final class HomeController extends Controller
{
    public function index(): void
    {
        $this->redirect(Session::has('user') ? '/dashboard' : '/login');
    }
}
