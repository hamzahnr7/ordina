<h1>Tambah Customer</h1>
<p><a href="/customers">&larr; Kembali ke daftar customer</a></p>

<?php if (!empty($errors)): ?>
    <div class="card" style="border-color:var(--color-danger);">
        <ul>
            <?php foreach ($errors as $message): ?>
                <li style="color:var(--color-danger);"><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width:420px;">
    <form method="post" action="/customers">
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>

        <label for="contact">Kontak</label>
        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($old['contact'] ?? '', ENT_QUOTES) ?>">

        <label for="address">Alamat</label>
        <textarea id="address" name="address" rows="2"><?= htmlspecialchars($old['address'] ?? '', ENT_QUOTES) ?></textarea>

        <button type="submit">Simpan</button>
    </form>
</div>
