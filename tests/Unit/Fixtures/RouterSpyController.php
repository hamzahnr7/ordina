<?php

declare(strict_types=1);

namespace Tests\Unit\Fixtures;

/** Stand-in controller for RouterTest - records which action ran with which route params. */
final class RouterSpyController
{
    /** @var list<array{action:string, params:array<string, string>}> */
    public static array $calls = [];

    /** @param array<string, string> $params */
    public function index(array $params): void
    {
        self::$calls[] = ['action' => 'index', 'params' => $params];
    }

    /** @param array<string, string> $params */
    public function store(array $params): void
    {
        self::$calls[] = ['action' => 'store', 'params' => $params];
    }
}
