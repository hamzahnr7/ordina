<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Authorization\Gate;
use App\Core\Authorization\Permission;
use App\Core\Http\Exceptions\UnauthenticatedException;
use App\Core\Http\Exceptions\AuthorizationException;
use App\Domain\Role;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        ob_start();
        require __DIR__ . "/../../views/{$view}.php";
        $content = ob_get_clean();

        $title = $data['title'] ?? 'Ordina Inventory & Order Management';

        require __DIR__ . '/../../views/layouts/app.php';
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function redirect(string $path): never
    {
        header("Location: {$path}");
        exit;
    }

    /** @return array{id:int, name:string, email:string, role:string}|null */
    protected function currentUser(): ?array
    {
        return Session::get('user');
    }

    protected function currentRole(): ?Role
    {
        $user = $this->currentUser();

        return $user !== null ? Role::from($user['role']) : null;
    }

    /**
     * Server-side authorization guard (ARCH-01/SO-01: never trust the UI
     * alone). Throws instead of rendering directly so every 401/403 response
     * is produced in exactly one place - the central handler in
     * public/index.php (ERR-01).
     */
    protected function authorize(Permission $permission): void
    {
        $role = $this->currentRole();

        if ($role === null) {
            throw new UnauthenticatedException();
        }

        if (!Gate::allows($role, $permission)) {
            throw new AuthorizationException();
        }
    }

    /**
     * Same enforcement as authorize(), for pages several roles reach for
     * different reasons (e.g. the product catalog: Admin manages it, Sales
     * views it, Warehouse Staff checks stock on it) - matches the OR
     * semantics MenuRegistry already uses for such menu entries.
     */
    protected function authorizeAny(Permission ...$permissions): void
    {
        $role = $this->currentRole();

        if ($role === null) {
            throw new UnauthenticatedException();
        }

        foreach ($permissions as $permission) {
            if (Gate::allows($role, $permission)) {
                return;
            }
        }

        throw new AuthorizationException();
    }

    protected function requireLogin(): void
    {
        if (!Session::has('user')) {
            throw new UnauthenticatedException();
        }
    }
}
