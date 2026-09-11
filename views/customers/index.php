<a href="/dashboard" class="back-link">&larr; Kembali ke dashboard</a>

<div class="toolbar">
    <h1 style="margin:0;">Customer</h1>
    <a href="/customers/create" class="btn">Tambah Customer</a>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<?php endif; ?>

<div class="card">
<div class="table-scroll">
<table>
    <thead>
        <tr><th>Nama</th><th>Kontak</th><th>Alamat</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if ($customers === []): ?>
        <tr class="empty-row"><td colspan="5">Belum ada customer.</td></tr>
    <?php endif; ?>
    <?php foreach ($customers as $customer): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($customer->name, ENT_QUOTES) ?></td>
            <td data-label="Kontak"><?= htmlspecialchars($customer->contact ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Alamat"><?= htmlspecialchars($customer->address ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Status">
                <span class="badge <?= $customer->isActive ? 'badge-success' : 'badge-muted' ?>">
                    <?= $customer->isActive ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </td>
            <td data-label="Aksi">
                <a href="/customers/<?= (int) $customer->id ?>/edit" class="btn btn-secondary">Edit</a>
                <form method="post" action="/customers/<?= (int) $customer->id ?>/toggle-active" style="display:inline;">
                    <button type="submit" class="btn-secondary"><?= $customer->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
