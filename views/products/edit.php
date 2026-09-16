<div class="page-head">
    <div class="page-head-text">
        <h1>Edit Produk</h1>
        <p class="page-head-meta">Perbarui detail produk ini.</p>
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

<div class="detail-layout">
    <div class="detail-main card">
    <form method="post" action="/products/<?= (int) $product->id ?>" enctype="multipart/form-data">
        <div class="form-grid">
            <div>
                <label>SKU</label>
                <input type="text" value="<?= htmlspecialchars($product->sku, ENT_QUOTES) ?>" disabled>
            </div>

            <div>
                <label for="category_id">Kategori<span class="required-mark">*</span></label>
                <select id="category_id" name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category->id ?>" <?= $product->categoryId === $category->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category->name, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-full">
                <label for="name">Nama<span class="required-mark">*</span></label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($product->name, ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="unit">Unit<span class="required-mark">*</span></label>
                <input type="text" id="unit" name="unit" value="<?= htmlspecialchars($product->unit, ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="reorder_point">Reorder Point<span class="required-mark">*</span></label>
                <input type="number" id="reorder_point" name="reorder_point" min="0" step="1" value="<?= htmlspecialchars((string) $product->reorderPoint, ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="buy_price">Harga Beli<span class="required-mark">*</span></label>
                <div class="input-prefix">
                    <span>Rp</span>
                    <input type="number" id="buy_price" name="buy_price" min="0" step="1" value="<?= htmlspecialchars((string) $product->buyPrice, ENT_QUOTES) ?>" required>
                </div>
            </div>

            <div>
                <label for="sell_price">Harga Jual<span class="required-mark">*</span></label>
                <div class="input-prefix">
                    <span>Rp</span>
                    <input type="number" id="sell_price" name="sell_price" min="0" step="1" value="<?= htmlspecialchars((string) $product->sellPrice, ENT_QUOTES) ?>" required>
                </div>
            </div>

            <div class="field-full">
                <label for="image">Ganti Gambar (opsional, JPG/PNG/WEBP, maks 2MB)</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <div class="form-actions">
            <a href="/products" class="btn btn-secondary">Batal</a>
            <button type="submit">Simpan</button>
        </div>
    </form>
    </div>

    <div class="detail-side">
        <div class="panel">
            <p class="panel-title">Pratinjau Gambar</p>
            <div class="image-preview">
                <?php if ($product->imagePath !== null): ?>
                    <img id="image-preview-img" src="/<?= htmlspecialchars($product->imagePath, ENT_QUOTES) ?>" alt="Pratinjau gambar produk">
                <?php else: ?>
                    <img id="image-preview-img" alt="Pratinjau gambar produk" hidden>
                    <span id="image-preview-empty" class="image-preview-empty">Belum ada gambar dipilih</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
