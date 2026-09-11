<a href="/dashboard" class="back-link">&larr; Kembali ke dashboard</a>

<div class="toolbar">
    <h1 style="margin:0;">Gudang</h1>
    <a href="/warehouses/create" class="btn">Tambah Gudang</a>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<?php endif; ?>

<div class="card">
<div class="table-scroll">
<table>
    <thead>
        <tr><th>Nama</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if ($warehouses === []): ?>
        <tr class="empty-row"><td colspan="4">Belum ada gudang.</td></tr>
    <?php endif; ?>
    <?php foreach ($warehouses as $warehouse): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($warehouse->name, ENT_QUOTES) ?></td>
            <td data-label="Lokasi"><?= htmlspecialchars($warehouse->location, ENT_QUOTES) ?></td>
            <td data-label="Status">
                <span class="badge <?= $warehouse->isActive ? 'badge-success' : 'badge-muted' ?>">
                    <?= $warehouse->isActive ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </td>
            <td data-label="Aksi">
                <a href="/warehouses/<?= (int) $warehouse->id ?>/edit" class="btn btn-secondary">Edit</a>
                <form method="post" action="/warehouses/<?= (int) $warehouse->id ?>/toggle-active" style="display:inline;">
                    <button type="submit" class="btn-secondary"><?= $warehouse->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
