<h1>Supplier</h1>
<p><a href="/dashboard">&larr; Kembali ke dashboard</a></p>

<?php if (!empty($success)): ?>
    <p style="color:green;"><?= htmlspecialchars($success, ENT_QUOTES) ?></p>
<?php endif; ?>

<p><a href="/suppliers/create"><button type="button">Tambah Supplier</button></a></p>

<div class="card">
<table>
    <thead>
        <tr><th>Nama</th><th>Kontak</th><th>Alamat</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if ($suppliers === []): ?>
        <tr><td colspan="5">Belum ada supplier.</td></tr>
    <?php endif; ?>
    <?php foreach ($suppliers as $supplier): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($supplier->name, ENT_QUOTES) ?></td>
            <td data-label="Kontak"><?= htmlspecialchars($supplier->contact ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Alamat"><?= htmlspecialchars($supplier->address ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Status"><?= $supplier->isActive ? 'Aktif' : 'Nonaktif' ?></td>
            <td data-label="Aksi">
                <a href="/suppliers/<?= (int) $supplier->id ?>/edit">Edit</a>
                &nbsp;
                <form method="post" action="/suppliers/<?= (int) $supplier->id ?>/toggle-active" style="display:inline;">
                    <button type="submit"><?= $supplier->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
