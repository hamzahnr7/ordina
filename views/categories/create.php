<div class="page-head">
    <div class="page-head-text">
        <h1>Tambah Kategori</h1>
        <p class="page-head-meta">Lengkapi nama dan deskripsi kategori baru.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card form-card">
    <form method="post" action="/categories">
        <label for="name">Nama<span class="required-mark">*</span></label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>

        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES) ?></textarea>

        <div class="form-actions">
            <a href="/categories" class="btn btn-secondary">Batal</a>
            <button type="submit">Simpan</button>
        </div>
    </form>
</div>
