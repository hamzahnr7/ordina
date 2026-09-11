<a href="/warehouses" class="back-link">&larr; Kembali ke daftar gudang</a>
<h1>Tambah Gudang</h1>

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
    <form method="post" action="/warehouses">
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>

        <label for="location">Lokasi</label>
        <input type="text" id="location" name="location" value="<?= htmlspecialchars($old['location'] ?? '', ENT_QUOTES) ?>" required>

        <button type="submit">Simpan</button>
    </form>
</div>
