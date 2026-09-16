<div class="page-head">
    <div class="page-head-text">
        <h1>Edit Supplier</h1>
        <p class="page-head-meta">Perbarui data kontak supplier ini.</p>
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
    <form method="post" action="/suppliers/<?= (int) $supplier->id ?>">
        <label for="name">Nama<span class="required-mark">*</span></label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($supplier->name, ENT_QUOTES) ?>" required>

        <label for="contact">Kontak</label>
        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($supplier->contact ?? '', ENT_QUOTES) ?>">

        <label for="address">Alamat</label>
        <textarea id="address" name="address" rows="2"><?= htmlspecialchars($supplier->address ?? '', ENT_QUOTES) ?></textarea>

        <div class="form-actions">
            <a href="/suppliers" class="btn btn-secondary">Batal</a>
            <button type="submit">Simpan</button>
        </div>
    </form>
</div>
