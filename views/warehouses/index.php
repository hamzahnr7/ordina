<h1>Gudang</h1>
<p><a href="/dashboard">&larr; Kembali ke dashboard</a></p>

<?php if (!empty($success)): ?>
    <p style="color:green;"><?= htmlspecialchars($success, ENT_QUOTES) ?></p>
<?php endif; ?>

<p><a href="/warehouses/create"><button type="button">Tambah Gudang</button></a></p>

<div class="card">
<table>
    <thead>
        <tr><th>Nama</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if ($warehouses === []): ?>
        <tr><td colspan="4">Belum ada gudang.</td></tr>
    <?php endif; ?>
    <?php foreach ($warehouses as $warehouse): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($warehouse->name, ENT_QUOTES) ?></td>
            <td data-label="Lokasi"><?= htmlspecialchars($warehouse->location, ENT_QUOTES) ?></td>
            <td data-label="Status"><?= $warehouse->isActive ? 'Aktif' : 'Nonaktif' ?></td>
            <td data-label="Aksi">
                <a href="/warehouses/<?= (int) $warehouse->id ?>/edit">Edit</a>
                &nbsp;
                <form method="post" action="/warehouses/<?= (int) $warehouse->id ?>/toggle-active" style="display:inline;">
                    <button type="submit"><?= $warehouse->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
