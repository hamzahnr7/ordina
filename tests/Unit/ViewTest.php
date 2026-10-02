<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\View;
use PHPUnit\Framework\TestCase;

/** Renders a real view + the shared layout with no session (logged-out branch). */
final class ViewTest extends TestCase
{
    public function test_renders_view_inside_layout_with_given_title(): void
    {
        $html = $this->capture(static fn () => View::render('errors/404', ['title' => 'Judul Uji'], 404));

        self::assertStringContainsString('<title>Judul Uji</title>', $html);
        self::assertStringContainsString('<div class="error-code">404</div>', $html);
        self::assertStringContainsString('</html>', $html);
    }

    public function test_falls_back_to_default_title(): void
    {
        $html = $this->capture(static fn () => View::render('errors/404'));

        self::assertStringContainsString('<title>Ordina Inventory &amp; Order Management</title>', $html);
    }

    public function test_view_data_is_escaped_where_the_layout_prints_it(): void
    {
        $html = $this->capture(static fn () => View::render('errors/404', ['title' => '<script>x</script>']));

        self::assertStringNotContainsString('<script>x</script>', $html);
        self::assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
    }

    private function capture(callable $render): string
    {
        ob_start();

        try {
            $render();
        } finally {
            $html = (string) ob_get_clean();
        }

        return $html;
    }
}
