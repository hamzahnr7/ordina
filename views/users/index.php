<a href="/dashboard" class="back-link">&larr; Kembali ke dashboard</a>

<div class="toolbar">
    <h1 style="margin:0;">Manajemen User</h1>
    <a href="/users/create" class="btn">Tambah User</a>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<?php endif; ?>

<div class="card">
<div class="table-scroll">
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
        <tr class="empty-row"><td colspan="5">Belum ada akun Sales/Warehouse Staff.</td></tr>
    <?php endif; ?>
    <?php foreach ($users as $u): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($u->name, ENT_QUOTES) ?></td>
            <td data-label="Email"><?= htmlspecialchars($u->email, ENT_QUOTES) ?></td>
            <td data-label="Role"><?= htmlspecialchars($u->role->label(), ENT_QUOTES) ?></td>
            <td data-label="Status">
                <span class="badge <?= $u->isActive ? 'badge-success' : 'badge-muted' ?>">
                    <?= $u->isActive ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </td>
            <td data-label="Aksi">
                <a href="/users/<?= (int) $u->id ?>/edit" class="btn btn-secondary">Edit</a>
                <form method="post" action="/users/<?= (int) $u->id ?>/toggle-active" style="display:inline;">
                    <button type="submit" class="btn-secondary"><?= $u->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
