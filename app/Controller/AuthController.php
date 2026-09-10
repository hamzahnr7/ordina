<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Repository\Mysql\MysqlUserRepository;
use App\Service\AuthService;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Session::has('user')) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login', [
            'error' => Session::pullFlash('error'),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function login(): void
    {
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $service = new AuthService(new MysqlUserRepository(Database::connection()));
        $user = $service->attempt($email, $password);

        if ($user === null) {
            // AUTH-01: one generic message regardless of which part was wrong
            // (unknown email, wrong password, or inactive account).
            Session::flash('error', 'Email atau password salah.');
            Session::flash('old', ['email' => $email]);
            $this->redirect('/login');
        }

        // New session id on privilege change (AUTH-01), then store only the
        // safe, display-oriented fields - never the password hash.
        Session::regenerate();
        Session::put('user', $user->toSessionArray());

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
