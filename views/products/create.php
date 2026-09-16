<div class="page-head">
    <div class="page-head-text">
        <h1>Tambah Produk</h1>
        <p class="page-head-meta">Lengkapi detail produk baru, termasuk harga dan reorder point.</p>
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
    <form method="post" action="/products" enctype="multipart/form-data">
        <div class="form-grid">
            <div>
                <label for="sku">SKU<span class="required-mark">*</span></label>
                <input type="text" id="sku" name="sku" value="<?= htmlspecialchars($old['sku'] ?? '', ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="category_id">Kategori<span class="required-mark">*</span></label>
                <select id="category_id" name="category_id" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category->id ?>" <?= (string) ($old['category_id'] ?? '') === (string) $category->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category->name, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-full">
                <label for="name">Nama<span class="required-mark">*</span></label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="unit">Unit<span class="required-mark">*</span></label>
                <input type="text" id="unit" name="unit" placeholder="pcs, box, ream, ..." value="<?= htmlspecialchars($old['unit'] ?? '', ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="reorder_point">Reorder Point<span class="required-mark">*</span></label>
                <input type="number" id="reorder_point" name="reorder_point" min="0" step="1" value="<?= htmlspecialchars($old['reorder_point'] ?? '0', ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="buy_price">Harga Beli<span class="required-mark">*</span></label>
                <div class="input-prefix">
                    <span>Rp</span>
                    <input type="number" id="buy_price" name="buy_price" min="0" step="1" value="<?= htmlspecialchars($old['buy_price'] ?? '0', ENT_QUOTES) ?>" required>
                </div>
            </div>

            <div>
                <label for="sell_price">Harga Jual<span class="required-mark">*</span></label>
                <div class="input-prefix">
                    <span>Rp</span>
                    <input type="number" id="sell_price" name="sell_price" min="0" step="1" value="<?= htmlspecialchars($old['sell_price'] ?? '0', ENT_QUOTES) ?>" required>
                </div>
            </div>

            <div class="field-full">
                <label for="image">Gambar (opsional, JPG/PNG/WEBP, maks 2MB)</label>
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
                <img id="image-preview-img" alt="Pratinjau gambar produk" hidden>
                <span id="image-preview-empty" class="image-preview-empty">Belum ada gambar dipilih</span>
            </div>
        </div>
    </div>
</div>
