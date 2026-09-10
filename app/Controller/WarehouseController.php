<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Repository\Mysql\MysqlWarehouseRepository;
use App\Service\Exception\ValidationException;
use App\Service\WarehouseService;

/** WH-01: Admin-only gudang management. */
final class WarehouseController extends Controller
{
    public function index(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('warehouses/index', [
            'title' => 'Gudang',
            'warehouses' => $this->service()->list(),
            'success' => Session::pullFlash('success'),
        ]);
    }

    public function create(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('warehouses/create', [
            'title' => 'Tambah Gudang',
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function store(): void
    {
        $this->authorize(Permission::ManageMasterData);

        try {
            $this->service()->create(['name' => $_POST['name'] ?? '', 'location' => $_POST['location'] ?? '']);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $_POST);
            $this->redirect('/warehouses/create');
        }

        Session::flash('success', 'Gudang berhasil dibuat.');
        $this->redirect('/warehouses');
    }

    /** @param array<string, string> $params */
    public function edit(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $warehouse = $this->service()->find((int) $params['id']);

        if ($warehouse === null) {
            throw new NotFoundException();
        }

        $this->view('warehouses/edit', [
            'title' => 'Edit Gudang',
            'warehouse' => $warehouse,
            'errors' => Session::pullFlash('errors', []),
        ]);
    }

    /** @param array<string, string> $params */
    public function update(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $id = (int) $params['id'];

        try {
            $this->service()->update($id, ['name' => $_POST['name'] ?? '', 'location' => $_POST['location'] ?? '']);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            $this->redirect("/warehouses/{$id}/edit");
        }

        Session::flash('success', 'Gudang berhasil diperbarui.');
        $this->redirect('/warehouses');
    }

    /** @param array<string, string> $params */
    public function toggleActive(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $id = (int) $params['id'];
        $warehouse = $this->service()->find($id);

        if ($warehouse === null) {
            throw new NotFoundException();
        }

        $this->service()->setActive($id, !$warehouse->isActive);
        $this->redirect('/warehouses');
    }

    private function service(): WarehouseService
    {
        return new WarehouseService(new MysqlWarehouseRepository(Database::connection()));
    }
}
