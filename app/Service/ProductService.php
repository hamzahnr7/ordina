<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Repository\Contracts\CategoryRepositoryInterface;
use App\Repository\Contracts\ProductRepositoryInterface;
use App\Repository\Contracts\ProductStockRepositoryInterface;
use App\Service\Exception\ValidationException;

/** PRD-01 (catalog CRUD) + FIND-01 (search/filter/pagination) + WH-01 (per-warehouse stock on the detail page). */
final class ProductService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CategoryRepositoryInterface $categories,
        private readonly ProductStockRepositoryInterface $stocks,
        private readonly ProductImageUploader $images,
    ) {
    }

    /**
     * Filters are expected pre-normalized by the caller (trimmed search
     * string, int-or-absent category_id, validated stock_status enum) -
     * see ProductController::index().
     *
     * @param array{search?:string, category_id?:int, stock_status?:string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function paginate(array $filters, int $page): array
    {
        $page = max(1, $page);
        $result = $this->products->paginateForListing($filters, $page, self::PER_PAGE);
        $totalPages = max(1, (int) ceil($result['total'] / self::PER_PAGE));

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'totalPages' => $totalPages,
        ];
    }

    public function find(int $id): ?Product
    {
        return $this->products->findById($id);
    }

    /** For dropdowns on other forms (e.g. Purchase Order items). */
    public function listActive(): array
    {
        return $this->products->findActive();
    }

    /**
     * @return array{product: Product, categoryName: string, stocks: list<array{warehouse_id:int, warehouse_name:string, quantity:int}>, totalStock: int, isLowStock: bool}|null
     */
    public function detail(int $id): ?array
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            return null;
        }

        $category = $this->categories->findById($product->categoryId);
        $stocks = $this->stocks->findByProductId($id);
        $totalStock = array_sum(array_column($stocks, 'quantity'));

        return [
            'product' => $product,
            'categoryName' => $category?->name ?? '-',
            'stocks' => $stocks,
            'totalStock' => $totalStock,
            'isLowStock' => self::isLowStock($totalStock, $product->reorderPoint),
        ];
    }

    /**
     * The low-stock rule (§2.5 DASH-01/FIND-01): total stock across all
     * warehouses below reorder_point. Kept as a pure, trivially unit-tested
     * function - see tests/Unit/ProductServiceTest.php - and mirrored (not
     * called from, since it runs in SQL) by the stock_status condition in
     * MysqlProductRepository::paginateForListing().
     */
    public static function isLowStock(int $totalStock, int $reorderPoint): bool
    {
        return $totalStock < $reorderPoint;
    }

    /**
     * @param array{sku?:string, name?:string, category_id?:string|int, unit?:string, buy_price?:string|float, sell_price?:string|float, reorder_point?:string|int} $input
     * @param array{name?:string, type?:string, tmp_name?:string, error?:int, size?:int}|null $imageFile
     */
    public function create(array $input, ?array $imageFile): Product
    {
        $errors = $this->validate($input, excludingId: null);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $imagePath = $this->images->upload($imageFile);

        return $this->products->save(new Product(
            id: null,
            sku: trim($input['sku']),
            name: trim($input['name']),
            categoryId: (int) $input['category_id'],
            unit: trim($input['unit']),
            buyPrice: (float) $input['buy_price'],
            sellPrice: (float) $input['sell_price'],
            reorderPoint: (int) $input['reorder_point'],
            imagePath: $imagePath,
            isActive: true,
        ));
    }

    /**
     * @param array{name?:string, category_id?:string|int, unit?:string, buy_price?:string|float, sell_price?:string|float, reorder_point?:string|int} $input
     * @param array{name?:string, type?:string, tmp_name?:string, error?:int, size?:int}|null $imageFile
     */
    public function update(int $id, array $input, ?array $imageFile): Product
    {
        $existing = $this->products->findById($id);

        if ($existing === null) {
            throw new ValidationException(['name' => 'Produk tidak ditemukan.']);
        }

        // SKU is immutable after creation - not part of $input, so it's excluded from validation here.
        $errors = $this->validate(['sku' => $existing->sku, ...$input], excludingId: $id);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $imagePath = $this->images->upload($imageFile) ?? $existing->imagePath;

        return $this->products->save(new Product(
            id: $existing->id,
            sku: $existing->sku,
            name: trim($input['name']),
            categoryId: (int) $input['category_id'],
            unit: trim($input['unit']),
            buyPrice: (float) $input['buy_price'],
            sellPrice: (float) $input['sell_price'],
            reorderPoint: (int) $input['reorder_point'],
            imagePath: $imagePath,
            isActive: $existing->isActive,
        ));
    }

    public function setActive(int $id, bool $active): void
    {
        $this->products->setActive($id, $active);
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function validate(array $input, ?int $excludingId): array
    {
        $errors = [];

        $sku = trim((string) ($input['sku'] ?? ''));

        if ($sku === '') {
            $errors['sku'] = 'SKU wajib diisi.';
        } elseif ($this->products->skuExists($sku, $excludingId)) {
            $errors['sku'] = 'SKU sudah digunakan.';
        }

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Nama produk wajib diisi.';
        }

        if (trim((string) ($input['unit'] ?? '')) === '') {
            $errors['unit'] = 'Unit wajib diisi.';
        }

        $categoryId = (int) ($input['category_id'] ?? 0);

        if ($categoryId <= 0 || $this->categories->findById($categoryId) === null) {
            $errors['category_id'] = 'Kategori tidak valid.';
        }

        foreach (['buy_price' => 'Harga beli', 'sell_price' => 'Harga jual', 'reorder_point' => 'Reorder point'] as $field => $label) {
            if (!is_numeric($input[$field] ?? null) || (float) $input[$field] < 0) {
                $errors[$field] = "{$label} harus berupa angka >= 0.";
            }
        }

        return $errors;
    }
}
