<a href="/customers" class="back-link">&larr; Kembali ke daftar customer</a>
<h1>Edit Customer</h1>

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
    <form method="post" action="/customers/<?= (int) $customer->id ?>">
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($customer->name, ENT_QUOTES) ?>" required>

        <label for="contact">Kontak</label>
        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($customer->contact ?? '', ENT_QUOTES) ?>">

        <label for="address">Alamat</label>
        <textarea id="address" name="address" rows="2"><?= htmlspecialchars($customer->address ?? '', ENT_QUOTES) ?></textarea>

        <button type="submit">Simpan</button>
    </form>
</div>
