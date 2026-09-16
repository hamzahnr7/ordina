<div class="page-head">
    <div class="page-head-text">
        <h1>Buat Purchase Order</h1>
        <p class="page-head-meta">Pilih supplier, gudang tujuan, dan item yang dipesan.</p>
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
    <form method="post" action="/purchase-orders">
        <div class="form-grid">
            <div>
                <label for="supplier_id">Supplier<span class="required-mark">*</span></label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Pilih supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= (int) $supplier->id ?>" <?= (string) ($old['supplier_id'] ?? '') === (string) $supplier->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($supplier->name, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="warehouse_id">Gudang Tujuan<span class="required-mark">*</span></label>
                <select id="warehouse_id" name="warehouse_id" required>
                    <option value="">Pilih gudang</option>
                    <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?= (int) $warehouse->id ?>" <?= (string) ($old['warehouse_id'] ?? '') === (string) $warehouse->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($warehouse->name, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-full">
                <label for="order_date">Tanggal Order<span class="required-mark">*</span></label>
                <input type="date" id="order_date" name="order_date" value="<?= htmlspecialchars($old['order_date'] ?? date('Y-m-d'), ENT_QUOTES) ?>" required>
            </div>
        </div>

        <label>Item Produk</label>
        <div class="table-scroll">
        <table id="po-items-table">
            <thead>
                <tr><th>Produk</th><th style="width:100px;">Qty</th><th style="width:170px;">Harga Beli</th><th style="width:130px;">Subtotal</th><th></th></tr>
            </thead>
            <tbody id="po-items-body">
                <tr class="po-item-row">
                    <td data-label="Produk">
                        <select name="items[product_id][]" class="row-product" required style="margin-bottom:0;">
                            <option value="">Pilih produk</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= (int) $product->id ?>" data-buy-price="<?= $product->buyPrice ?>"><?= htmlspecialchars("{$product->sku} - {$product->name}", ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td data-label="Qty"><input type="number" name="items[qty_ordered][]" class="row-qty" min="1" step="1" required style="margin-bottom:0;"></td>
                    <td data-label="Harga Beli">
                        <div class="input-prefix">
                            <span>Rp</span>
                            <input type="number" class="row-price" min="0" step="1" disabled style="margin-bottom:0;">
                        </div>
                        <input type="hidden" name="items[buy_price][]" class="row-price-value">
                    </td>
                    <td data-label="Subtotal"><span class="row-subtotal">Rp0</span></td>
                    <td><button type="button" class="icon-btn icon-btn-danger remove-item-row" aria-label="Hapus item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg></button></td>
                </tr>
            </tbody>
        </table>
        </div>
        <p><button type="button" class="btn-secondary" id="add-item-row">+ Tambah Item</button></p>

        <div class="form-actions">
            <a href="/purchase-orders" class="btn btn-secondary">Batal</a>
            <button type="submit">Simpan sebagai Draft</button>
        </div>
    </form>
    </div>

    <div class="detail-side">
        <div class="panel">
            <p class="panel-title">Ringkasan Order</p>
            <dl>
                <dt>Jumlah Item</dt><dd id="order-summary-count">0</dd>
                <dt>Total Qty</dt><dd id="order-summary-qty">0</dd>
                <dt>Perkiraan Total</dt><dd id="order-summary-total">Rp0</dd>
            </dl>
            <p class="field-hint" style="margin-top:var(--space-3);margin-bottom:0;">Dihitung otomatis dari qty &times; harga beli tiap item.</p>
        </div>
    </div>
</div>

<template id="po-item-row-template">
    <tr class="po-item-row">
        <td data-label="Produk">
            <select name="items[product_id][]" class="row-product" required style="margin-bottom:0;">
                <option value="">Pilih produk</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product->id ?>" data-buy-price="<?= $product->buyPrice ?>"><?= htmlspecialchars("{$product->sku} - {$product->name}", ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td data-label="Qty"><input type="number" name="items[qty_ordered][]" class="row-qty" min="1" step="1" required style="margin-bottom:0;"></td>
        <td data-label="Harga Beli">
            <div class="input-prefix">
                <span>Rp</span>
                <input type="number" class="row-price" min="0" step="1" disabled style="margin-bottom:0;">
            </div>
            <input type="hidden" name="items[buy_price][]" class="row-price-value">
        </td>
        <td data-label="Subtotal"><span class="row-subtotal">Rp0</span></td>
        <td><button type="button" class="icon-btn icon-btn-danger remove-item-row" aria-label="Hapus item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg></button></td>
    </tr>
</template>
