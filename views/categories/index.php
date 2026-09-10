<h1>Kategori Produk</h1>
<p><a href="/dashboard">&larr; Kembali ke dashboard</a></p>

<?php if (!empty($success)): ?>
    <p style="color:green;"><?= htmlspecialchars($success, ENT_QUOTES) ?></p>
<?php endif; ?>

<p><a href="/categories/create"><button type="button">Tambah Kategori</button></a></p>

<div class="card">
<table>
    <thead>
        <tr><th>Nama</th><th>Deskripsi</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if ($categories === []): ?>
        <tr><td colspan="3">Belum ada kategori.</td></tr>
    <?php endif; ?>
    <?php foreach ($categories as $category): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($category->name, ENT_QUOTES) ?></td>
            <td data-label="Deskripsi"><?= htmlspecialchars($category->description ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Aksi"><a href="/categories/<?= (int) $category->id ?>/edit">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
