<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Gate;
use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Repository\Mysql\MysqlCategoryRepository;
use App\Repository\Mysql\MysqlProductRepository;
use App\Repository\Mysql\MysqlProductStockRepository;
use App\Service\CategoryService;
use App\Service\Exception\ValidationException;
use App\Service\ProductImageUploader;
use App\Service\ProductService;

/**
 * PRD-01 (catalog CRUD, Admin-only) + FIND-01 (search/filter/pagination) +
 * WH-01 (per-warehouse stock on the detail page). index()/show() are shared
 * by all three roles for different reasons - see config/menus.php and
 * docs/architecture/rbac-and-menu-access.md.
 */
final class ProductController extends Controller
{
    public function index(): void
    {
        $this->authorizeAny(Permission::ManageMasterData, Permission::ViewCatalog, Permission::ViewProductStock);

        $filters = array_filter([
            'search' => trim((string) ($_GET['search'] ?? '')),
            'category_id' => isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null,
            'stock_status' => in_array($_GET['stock_status'] ?? '', ['low', 'normal'], true) ? $_GET['stock_status'] : null,
        ]);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->service()->paginate($filters, $page);

        $this->view('products/index', [
            'title' => 'Produk',
            'result' => $result,
            'filters' => $filters,
            'categories' => $this->categoryService()->list(),
            'canManage' => $this->currentRole() !== null && Gate::allows($this->currentRole(), Permission::ManageMasterData),
            'success' => Session::pullFlash('success'),
        ]);
    }

    /** @param array<string, string> $params */
    public function show(array $params): void
    {
        $this->authorizeAny(Permission::ManageMasterData, Permission::ViewCatalog, Permission::ViewProductStock);

        $detail = $this->service()->detail((int) $params['id']);

        if ($detail === null) {
            throw new NotFoundException();
        }

        $this->view('products/show', ['title' => 'Detail Produk', ...$detail]);
    }

    public function create(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('products/create', [
            'title' => 'Tambah Produk',
            'categories' => $this->categoryService()->list(),
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function store(): void
    {
        $this->authorize(Permission::ManageMasterData);

        try {
            $this->service()->create($_POST, $_FILES['image'] ?? null);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $_POST);
            $this->redirect('/products/create');
        }

        Session::flash('success', 'Produk berhasil dibuat.');
        $this->redirect('/products');
    }

    /** @param array<string, string> $params */
    public function edit(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $product = $this->service()->find((int) $params['id']);

        if ($product === null) {
            throw new NotFoundException();
        }

        $this->view('products/edit', [
            'title' => 'Edit Produk',
            'product' => $product,
            'categories' => $this->categoryService()->list(),
            'errors' => Session::pullFlash('errors', []),
        ]);
    }

    /** @param array<string, string> $params */
    public function update(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $id = (int) $params['id'];

        try {
            $this->service()->update($id, $_POST, $_FILES['image'] ?? null);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            $this->redirect("/products/{$id}/edit");
        }

        Session::flash('success', 'Produk berhasil diperbarui.');
        $this->redirect('/products');
    }

    /** @param array<string, string> $params */
    public function toggleActive(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $id = (int) $params['id'];
        $product = $this->service()->find($id);

        if ($product === null) {
            throw new NotFoundException();
        }

        $this->service()->setActive($id, !$product->isActive);
        $this->redirect('/products');
    }

    private function service(): ProductService
    {
        $pdo = Database::connection();

        return new ProductService(
            new MysqlProductRepository($pdo),
            new MysqlCategoryRepository($pdo),
            new MysqlProductStockRepository($pdo),
            new ProductImageUploader(uploadDir: __DIR__ . '/../../public/uploads/products')
        );
    }

    private function categoryService(): CategoryService
    {
        return new CategoryService(new MysqlCategoryRepository(Database::connection()));
    }
}
