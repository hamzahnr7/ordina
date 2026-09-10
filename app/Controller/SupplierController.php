<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Repository\Mysql\MysqlSupplierRepository;
use App\Service\Exception\ValidationException;
use App\Service\SupplierService;

/** Admin-only master data (§1.2), feeds Purchase Order's supplier dropdown. */
final class SupplierController extends Controller
{
    public function index(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('suppliers/index', [
            'title' => 'Supplier',
            'suppliers' => $this->service()->list(),
            'success' => Session::pullFlash('success'),
        ]);
    }

    public function create(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('suppliers/create', [
            'title' => 'Tambah Supplier',
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function store(): void
    {
        $this->authorize(Permission::ManageMasterData);

        try {
            $this->service()->create([
                'name' => $_POST['name'] ?? '',
                'contact' => $_POST['contact'] ?? '',
                'address' => $_POST['address'] ?? '',
            ]);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $_POST);
            $this->redirect('/suppliers/create');
        }

        Session::flash('success', 'Supplier berhasil dibuat.');
        $this->redirect('/suppliers');
    }

    /** @param array<string, string> $params */
    public function edit(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $supplier = $this->service()->find((int) $params['id']);

        if ($supplier === null) {
            throw new NotFoundException();
        }

        $this->view('suppliers/edit', [
            'title' => 'Edit Supplier',
            'supplier' => $supplier,
            'errors' => Session::pullFlash('errors', []),
        ]);
    }

    /** @param array<string, string> $params */
    public function update(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $id = (int) $params['id'];

        try {
            $this->service()->update($id, [
                'name' => $_POST['name'] ?? '',
                'contact' => $_POST['contact'] ?? '',
                'address' => $_POST['address'] ?? '',
            ]);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            $this->redirect("/suppliers/{$id}/edit");
        }

        Session::flash('success', 'Supplier berhasil diperbarui.');
        $this->redirect('/suppliers');
    }

    /** @param array<string, string> $params */
    public function toggleActive(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $id = (int) $params['id'];
        $supplier = $this->service()->find($id);

        if ($supplier === null) {
            throw new NotFoundException();
        }

        $this->service()->setActive($id, !$supplier->isActive);
        $this->redirect('/suppliers');
    }

    private function service(): SupplierService
    {
        return new SupplierService(new MysqlSupplierRepository(Database::connection()));
    }
}
