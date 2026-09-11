<a href="/dashboard" class="back-link">&larr; Kembali ke dashboard</a>

<div class="toolbar">
    <h1 style="margin:0;">Kategori Produk</h1>
    <a href="/categories/create" class="btn">Tambah Kategori</a>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<?php endif; ?>

<div class="card">
<div class="table-scroll">
<table>
    <thead>
        <tr><th>Nama</th><th>Deskripsi</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if ($categories === []): ?>
        <tr class="empty-row"><td colspan="3">Belum ada kategori.</td></tr>
    <?php endif; ?>
    <?php foreach ($categories as $category): ?>
        <tr>
            <td data-label="Nama"><?= htmlspecialchars($category->name, ENT_QUOTES) ?></td>
            <td data-label="Deskripsi"><?= htmlspecialchars($category->description ?? '-', ENT_QUOTES) ?></td>
            <td data-label="Aksi"><a href="/categories/<?= (int) $category->id ?>/edit" class="btn btn-secondary">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
