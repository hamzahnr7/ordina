<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Domain\Role;
use App\Repository\Mysql\MysqlUserRepository;
use App\Service\Exception\ValidationException;
use App\Service\UserService;

/** USR-01: Admin manages Sales/Warehouse Staff accounts only (see UserService, Role::manageable()). */
final class UserController extends Controller
{
    public function index(): void
    {
        $this->authorize(Permission::ManageUsers);

        $service = $this->service();

        $this->view('users/index', [
            'title' => 'Manajemen User',
            'users' => $service->list(),
            'success' => Session::pullFlash('success'),
        ]);
    }

    public function create(): void
    {
        $this->authorize(Permission::ManageUsers);

        $this->view('users/create', [
            'title' => 'Tambah User',
            'roles' => Role::manageable(),
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function store(): void
    {
        $this->authorize(Permission::ManageUsers);

        try {
            $this->service()->create([
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? '',
            ]);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $_POST);
            $this->redirect('/users/create');
        }

        Session::flash('success', 'User berhasil dibuat.');
        $this->redirect('/users');
    }

    /** @param array<string, string> $params */
    public function edit(array $params): void
    {
        $this->authorize(Permission::ManageUsers);

        $user = $this->service()->find((int) $params['id']);

        if ($user === null) {
            throw new NotFoundException();
        }

        $this->view('users/edit', [
            'title' => 'Edit User',
            'user' => $user,
            'roles' => Role::manageable(),
            'errors' => Session::pullFlash('errors', []),
        ]);
    }

    /** @param array<string, string> $params */
    public function update(array $params): void
    {
        $this->authorize(Permission::ManageUsers);

        $id = (int) $params['id'];

        try {
            $this->service()->update($id, [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'role' => $_POST['role'] ?? '',
            ]);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            $this->redirect("/users/{$id}/edit");
        }

        Session::flash('success', 'User berhasil diperbarui.');
        $this->redirect('/users');
    }

    /** @param array<string, string> $params */
    public function toggleActive(array $params): void
    {
        $this->authorize(Permission::ManageUsers);

        $id = (int) $params['id'];
        $user = $this->service()->find($id);

        if ($user === null) {
            throw new NotFoundException();
        }

        $this->service()->setActive($id, !$user->isActive);
        $this->redirect('/users');
    }

    private function service(): UserService
    {
        return new UserService(new MysqlUserRepository(Database::connection()));
    }
}
