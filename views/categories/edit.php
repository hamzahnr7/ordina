<h1>Edit Kategori</h1>
<p><a href="/categories">&larr; Kembali ke daftar kategori</a></p>

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
    <form method="post" action="/categories/<?= (int) $category->id ?>">
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($category->name, ENT_QUOTES) ?>" required>

        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($category->description ?? '', ENT_QUOTES) ?></textarea>

        <button type="submit">Simpan</button>
    </form>
</div>
