<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renders a view inside the shared layout. Used by Controller::view() for
 * normal pages, and directly by public/index.php's central error handler
 * and Router's 404 fallback - so 403/404/500 get the same header/nav/CSS
 * as every other page instead of an unstyled fragment.
 */
final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);

        extract($data);

        ob_start();
        require __DIR__ . "/../../views/{$view}.php";
        $content = ob_get_clean();

        $title = $data['title'] ?? 'Ordina Inventory & Order Management';

        require __DIR__ . '/../../views/layouts/app.php';
    }
}
