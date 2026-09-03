<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . "/../../views/{$view}.php";
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

    protected function forbidden(): void
    {
        http_response_code(403);
        require __DIR__ . '/../../views/errors/403.php';
    }

    protected function requireLogin(): void
    {
        if (!Session::has('user')) {
            $this->redirect('/login');
        }
    }
}
