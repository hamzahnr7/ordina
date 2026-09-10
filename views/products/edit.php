<h1>Edit Produk</h1>
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
    <form method="post" action="/products/<?= (int) $product->id ?>" enctype="multipart/form-data">
        <label>SKU</label>
        <input type="text" value="<?= htmlspecialchars($product->sku, ENT_QUOTES) ?>" disabled>

        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($product->name, ENT_QUOTES) ?>" required>

        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category->id ?>" <?= $product->categoryId === $category->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category->name, ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="unit">Unit</label>
        <input type="text" id="unit" name="unit" value="<?= htmlspecialchars($product->unit, ENT_QUOTES) ?>" required>

        <label for="buy_price">Harga Beli</label>
        <input type="number" id="buy_price" name="buy_price" min="0" step="1" value="<?= htmlspecialchars((string) $product->buyPrice, ENT_QUOTES) ?>" required>

        <label for="sell_price">Harga Jual</label>
        <input type="number" id="sell_price" name="sell_price" min="0" step="1" value="<?= htmlspecialchars((string) $product->sellPrice, ENT_QUOTES) ?>" required>

        <label for="reorder_point">Reorder Point</label>
        <input type="number" id="reorder_point" name="reorder_point" min="0" step="1" value="<?= htmlspecialchars((string) $product->reorderPoint, ENT_QUOTES) ?>" required>

        <?php if ($product->imagePath !== null): ?>
            <label>Gambar saat ini</label>
            <img src="/<?= htmlspecialchars($product->imagePath, ENT_QUOTES) ?>" alt="" style="max-width:120px;display:block;margin-bottom:1rem;">
        <?php endif; ?>

        <label for="image">Ganti Gambar (opsional, JPG/PNG/WEBP, maks 2MB)</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">

        <button type="submit">Simpan</button>
    </form>
</div>
