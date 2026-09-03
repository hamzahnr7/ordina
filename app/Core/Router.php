<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, list<array{pattern: string, handler: array{0: class-string, 1: string}}>> */
    private array $routes = [];

    /** @param array{0: class-string, 1: string} $handler */
    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** @param array{0: class-string, 1: string} $handler */
    private function add(string $method, string $path, array $handler): void
    {
        $this->routes[$method][] = [
            'pattern' => $this->toPattern($path),
            'handler' => $handler,
        ];
    }

    private function toPattern(string $path): string
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);

        return '#^' . $pattern . '$#';
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) === 1) {
                $params = array_filter($matches, fn ($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
                [$controllerClass, $action] = $route['handler'];
                (new $controllerClass())->$action($params);

                return;
            }
        }

        http_response_code(404);
        require __DIR__ . '/../../views/errors/404.php';
    }
}
