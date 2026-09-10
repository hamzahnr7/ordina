<h1>Edit Gudang</h1>
<p><a href="/warehouses">&larr; Kembali ke daftar gudang</a></p>

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
    <form method="post" action="/warehouses/<?= (int) $warehouse->id ?>">
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($warehouse->name, ENT_QUOTES) ?>" required>

        <label for="location">Lokasi</label>
        <input type="text" id="location" name="location" value="<?= htmlspecialchars($warehouse->location, ENT_QUOTES) ?>" required>

        <button type="submit">Simpan</button>
    </form>
</div>
