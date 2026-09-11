<a href="/categories" class="back-link">&larr; Kembali ke daftar kategori</a>
<h1>Tambah Kategori</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width:420px;">
    <form method="post" action="/categories">
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>

        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES) ?></textarea>

        <button type="submit">Simpan</button>
    </form>
</div>
