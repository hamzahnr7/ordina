<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Session;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login');
    }

    public function login(): void
    {
        // TODO: delegate to an AuthService (validate via password_verify(),
        // check `is_active`), then Session::regenerate() + Session::put('user', ...).
        // On failure, re-render the form with a generic error (AUTH-01).
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
