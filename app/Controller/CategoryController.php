<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Repository\Mysql\MysqlCategoryRepository;
use App\Service\CategoryService;
use App\Service\Exception\ValidationException;

/** PRD-01 support: categories back the Product form's dropdown; Admin-only (§1.2 master data). */
final class CategoryController extends Controller
{
    public function index(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('categories/index', [
            'title' => 'Kategori Produk',
            'categories' => $this->service()->list(),
            'success' => Session::pullFlash('success'),
        ]);
    }

    public function create(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('categories/create', [
            'title' => 'Tambah Kategori',
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function store(): void
    {
        $this->authorize(Permission::ManageMasterData);

        try {
            $this->service()->create(['name' => $_POST['name'] ?? '', 'description' => $_POST['description'] ?? '']);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $_POST);
            $this->redirect('/categories/create');
        }

        Session::flash('success', 'Kategori berhasil dibuat.');
        $this->redirect('/categories');
    }

    /** @param array<string, string> $params */
    public function edit(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $category = $this->service()->find((int) $params['id']);

        if ($category === null) {
            throw new NotFoundException();
        }

        $this->view('categories/edit', [
            'title' => 'Edit Kategori',
            'category' => $category,
            'errors' => Session::pullFlash('errors', []),
        ]);
    }

    /** @param array<string, string> $params */
    public function update(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $id = (int) $params['id'];

        try {
            $this->service()->update($id, ['name' => $_POST['name'] ?? '', 'description' => $_POST['description'] ?? '']);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            $this->redirect("/categories/{$id}/edit");
        }

        Session::flash('success', 'Kategori berhasil diperbarui.');
        $this->redirect('/categories');
    }

    private function service(): CategoryService
    {
        return new CategoryService(new MysqlCategoryRepository(Database::connection()));
    }
}
