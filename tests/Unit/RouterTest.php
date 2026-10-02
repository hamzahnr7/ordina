<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Router;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Fixtures\RouterSpyController;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        RouterSpyController::$calls = [];

        $this->router = new Router();
        $this->router->get('/purchase-orders', [RouterSpyController::class, 'index']);
        $this->router->post('/purchase-orders/{id}/receive', [RouterSpyController::class, 'store']);
    }

    public function test_dispatches_get_route_to_its_action(): void
    {
        $this->router->dispatch('GET', '/purchase-orders');

        self::assertSame([['action' => 'index', 'params' => []]], RouterSpyController::$calls);
    }

    public function test_passes_named_segments_as_params(): void
    {
        $this->router->dispatch('POST', '/purchase-orders/42/receive');

        self::assertSame([['action' => 'store', 'params' => ['id' => '42']]], RouterSpyController::$calls);
    }

    public function test_wrong_method_falls_through_to_404_page(): void
    {
        $this->expectOutputRegex('/Tidak ditemukan/');

        $this->router->dispatch('GET', '/purchase-orders/42/receive');

        self::assertSame([], RouterSpyController::$calls);
    }

    public function test_unknown_path_renders_404_page(): void
    {
        $this->expectOutputRegex('/<title>404 - Tidak Ditemukan<\/title>/');

        $this->router->dispatch('GET', '/does-not-exist');
    }

    public function test_segment_placeholder_does_not_match_across_slashes(): void
    {
        $this->expectOutputRegex('/Tidak ditemukan/');

        $this->router->dispatch('POST', '/purchase-orders/4/2/receive');

        self::assertSame([], RouterSpyController::$calls);
    }
}
