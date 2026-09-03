<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Repository\Mysql\MysqlProductRepository;
use App\Repository\Mysql\MysqlProductStockRepository;
use App\Service\ProductAvailabilityService;

final class ProductAvailabilityController extends Controller
{
    /** @param array<string, string> $params */
    public function show(array $params): void
    {
        if (!Session::has('user')) {
            $this->json(['error' => 'Unauthenticated'], 401);

            return;
        }

        $pdo = Database::connection();
        $service = new ProductAvailabilityService(
            new MysqlProductRepository($pdo),
            new MysqlProductStockRepository($pdo)
        );

        $availability = $service->availabilityBySku($params['sku']);

        if ($availability === null) {
            $this->json(['error' => 'Product not found'], 404);

            return;
        }

        $this->json($availability, 200);
    }
}
