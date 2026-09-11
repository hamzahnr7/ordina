<a href="/dashboard" class="back-link">&larr; Kembali ke dashboard</a>

<div class="toolbar">
    <h1 style="margin:0;">Supplier</h1>
    <a href="/suppliers/create" class="btn">Tambah Supplier</a>
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
    <?php if ($suppliers === []): ?>
        <tr class="empty-row"><td colspan="5">Belum ada supplier.</td></tr>
    <?php endif; ?>
    <?php foreach ($suppliers as $supplier): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($supplier->name, ENT_QUOTES) ?></td>
            <td data-label="Kontak"><?= htmlspecialchars($supplier->contact ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Alamat"><?= htmlspecialchars($supplier->address ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Status">
                <span class="badge <?= $supplier->isActive ? 'badge-success' : 'badge-muted' ?>">
                    <?= $supplier->isActive ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </td>
            <td data-label="Aksi">
                <a href="/suppliers/<?= (int) $supplier->id ?>/edit" class="btn btn-secondary">Edit</a>
                <form method="post" action="/suppliers/<?= (int) $supplier->id ?>/toggle-active" style="display:inline;">
                    <button type="submit" class="btn-secondary"><?= $supplier->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
