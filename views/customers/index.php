<h1>Customer</h1>
<p><a href="/dashboard">&larr; Kembali ke dashboard</a></p>

<?php if (!empty($success)): ?>
    <p style="color:green;"><?= htmlspecialchars($success, ENT_QUOTES) ?></p>
<?php endif; ?>

<p><a href="/customers/create"><button type="button">Tambah Customer</button></a></p>

<div class="card">
<table>
    <thead>
        <tr><th>Nama</th><th>Kontak</th><th>Alamat</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if ($customers === []): ?>
        <tr><td colspan="5">Belum ada customer.</td></tr>
    <?php endif; ?>
    <?php foreach ($customers as $customer): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($customer->name, ENT_QUOTES) ?></td>
            <td data-label="Kontak"><?= htmlspecialchars($customer->contact ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Alamat"><?= htmlspecialchars($customer->address ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Status"><?= $customer->isActive ? 'Aktif' : 'Nonaktif' ?></td>
            <td data-label="Aksi">
                <a href="/customers/<?= (int) $customer->id ?>/edit">Edit</a>
                &nbsp;
                <form method="post" action="/customers/<?= (int) $customer->id ?>/toggle-active" style="display:inline;">
                    <button type="submit"><?= $customer->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
