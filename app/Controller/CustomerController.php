<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Repository\Mysql\MysqlCustomerRepository;
use App\Service\CustomerService;
use App\Service\Exception\ValidationException;

/** Admin-only master data (§1.2), feeds Sales Order's customer dropdown. */
final class CustomerController extends Controller
{
    public function index(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('customers/index', [
            'title' => 'Customer',
            'customers' => $this->service()->list(),
            'success' => Session::pullFlash('success'),
        ]);
    }

    public function create(): void
    {
        $this->authorize(Permission::ManageMasterData);

        $this->view('customers/create', [
            'title' => 'Tambah Customer',
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
            $this->redirect('/customers/create');
        }

        Session::flash('success', 'Customer berhasil dibuat.');
        $this->redirect('/customers');
    }

    /** @param array<string, string> $params */
    public function edit(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $customer = $this->service()->find((int) $params['id']);

        if ($customer === null) {
            throw new NotFoundException();
        }

        $this->view('customers/edit', [
            'title' => 'Edit Customer',
            'customer' => $customer,
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
            $this->redirect("/customers/{$id}/edit");
        }

        Session::flash('success', 'Customer berhasil diperbarui.');
        $this->redirect('/customers');
    }

    /** @param array<string, string> $params */
    public function toggleActive(array $params): void
    {
        $this->authorize(Permission::ManageMasterData);

        $id = (int) $params['id'];
        $customer = $this->service()->find($id);

        if ($customer === null) {
            throw new NotFoundException();
        }

        $this->service()->setActive($id, !$customer->isActive);
        $this->redirect('/customers');
    }

    private function service(): CustomerService
    {
        return new CustomerService(new MysqlCustomerRepository(Database::connection()));
    }
}
