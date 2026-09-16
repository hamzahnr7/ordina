<div class="page-head">
    <div class="page-head-text">
        <h1>Tambah Gudang</h1>
        <p class="page-head-meta">Lengkapi nama dan lokasi gudang baru.</p>
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
    <form method="post" action="/warehouses">
        <label for="name">Nama<span class="required-mark">*</span></label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>

        <label for="location">Lokasi<span class="required-mark">*</span></label>
        <input type="text" id="location" name="location" value="<?= htmlspecialchars($old['location'] ?? '', ENT_QUOTES) ?>" required>

        <div class="form-actions">
            <a href="/warehouses" class="btn btn-secondary">Batal</a>
            <button type="submit">Simpan</button>
        </div>
    </form>
</div>
