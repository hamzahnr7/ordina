<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Repository\Mysql\MysqlUserRepository;
use App\Service\Exception\ValidationException;
use App\Service\ProfileService;

/** §1.2 "profil sendiri" - every signed-in role, no Permission beyond being logged in. */
final class ProfileController extends Controller
{
    public function show(): void
    {
        $this->requireLogin();

        $user = $this->service()->find((int) $this->currentUser()['id']);

        if ($user === null) {
            throw new NotFoundException();
        }

        $this->view('profile/show', [
            'title' => 'Profil Saya',
            'user' => $user,
            'errors' => Session::pullFlash('errors', []),
            'success' => Session::pullFlash('success'),
        ]);
    }

    public function updatePassword(): void
    {
        $this->requireLogin();

        try {
            $this->service()->changePassword(
                (int) $this->currentUser()['id'],
                (string) ($_POST['current_password'] ?? ''),
                (string) ($_POST['new_password'] ?? ''),
                (string) ($_POST['password_confirmation'] ?? ''),
            );
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            $this->redirect('/profile');
        }

        // Same rule as login (AUTH-01): a credential change gets a fresh session ID.
        Session::regenerate();
        Session::flash('success', 'Password berhasil diubah.');
        $this->redirect('/profile');
    }

    private function service(): ProfileService
    {
        return new ProfileService(new MysqlUserRepository(Database::connection()));
    }
}
