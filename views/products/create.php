<h1>Tambah Produk</h1>
<p><a href="/products">&larr; Kembali ke daftar produk</a></p>

<?php if (!empty($errors)): ?>
    <div class="card" style="border-color:var(--color-danger);">
        <ul>
            <?php foreach ($errors as $message): ?>
                <li style="color:var(--color-danger);"><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width:480px;">
    <form method="post" action="/products" enctype="multipart/form-data">
        <label for="sku">SKU</label>
        <input type="text" id="sku" name="sku" value="<?= htmlspecialchars($old['sku'] ?? '', ENT_QUOTES) ?>" required>

        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>

        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id" required>
            <option value="">Pilih kategori</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category->id ?>" <?= (string) ($old['category_id'] ?? '') === (string) $category->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category->name, ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="unit">Unit</label>
        <input type="text" id="unit" name="unit" placeholder="pcs, box, ream, ..." value="<?= htmlspecialchars($old['unit'] ?? '', ENT_QUOTES) ?>" required>

        <label for="buy_price">Harga Beli</label>
        <input type="number" id="buy_price" name="buy_price" min="0" step="1" value="<?= htmlspecialchars($old['buy_price'] ?? '0', ENT_QUOTES) ?>" required>

        <label for="sell_price">Harga Jual</label>
        <input type="number" id="sell_price" name="sell_price" min="0" step="1" value="<?= htmlspecialchars($old['sell_price'] ?? '0', ENT_QUOTES) ?>" required>

        <label for="reorder_point">Reorder Point</label>
        <input type="number" id="reorder_point" name="reorder_point" min="0" step="1" value="<?= htmlspecialchars($old['reorder_point'] ?? '0', ENT_QUOTES) ?>" required>

        <label for="image">Gambar (opsional, JPG/PNG/WEBP, maks 2MB)</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">

        <button type="submit">Simpan</button>
    </form>
</div>
