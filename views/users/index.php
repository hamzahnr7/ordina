<h1>Manajemen User</h1>
<p><a href="/dashboard">&larr; Kembali ke dashboard</a></p>

<?php if (!empty($success)): ?>
    <p style="color:green;"><?= htmlspecialchars($success, ENT_QUOTES) ?></p>
<?php endif; ?>

<p><a href="/users/create"><button type="button">Tambah User</button></a></p>

<div class="card">
<table>
    <thead>
        <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($users === []): ?>
        <tr><td colspan="5">Belum ada akun Sales/Warehouse Staff.</td></tr>
    <?php endif; ?>
    <?php foreach ($users as $u): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($u->name, ENT_QUOTES) ?></td>
            <td data-label="Email"><?= htmlspecialchars($u->email, ENT_QUOTES) ?></td>
            <td data-label="Role"><?= htmlspecialchars($u->role->label(), ENT_QUOTES) ?></td>
            <td data-label="Status"><?= $u->isActive ? 'Aktif' : 'Nonaktif' ?></td>
            <td data-label="Aksi">
                <a href="/users/<?= (int) $u->id ?>/edit">Edit</a>
                &nbsp;
                <form method="post" action="/users/<?= (int) $u->id ?>/toggle-active" style="display:inline;">
                    <button type="submit"><?= $u->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
