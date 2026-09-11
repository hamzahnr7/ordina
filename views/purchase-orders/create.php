<a href="/purchase-orders" class="back-link">&larr; Kembali ke daftar Purchase Order</a>
<h1>Buat Purchase Order</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width:720px;">
    <form method="post" action="/purchase-orders">
        <label for="supplier_id">Supplier</label>
        <select id="supplier_id" name="supplier_id" required>
            <option value="">Pilih supplier</option>
            <?php foreach ($suppliers as $supplier): ?>
                <option value="<?= (int) $supplier->id ?>" <?= (string) ($old['supplier_id'] ?? '') === (string) $supplier->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($supplier->name, ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="warehouse_id">Gudang Tujuan</label>
        <select id="warehouse_id" name="warehouse_id" required>
            <option value="">Pilih gudang</option>
            <?php foreach ($warehouses as $warehouse): ?>
                <option value="<?= (int) $warehouse->id ?>" <?= (string) ($old['warehouse_id'] ?? '') === (string) $warehouse->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($warehouse->name, ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="order_date">Tanggal Order</label>
        <input type="date" id="order_date" name="order_date" value="<?= htmlspecialchars($old['order_date'] ?? date('Y-m-d'), ENT_QUOTES) ?>" required>

        <label>Item Produk</label>
        <div class="table-scroll">
        <table id="po-items-table">
            <thead>
                <tr><th>Produk</th><th style="width:110px;">Qty</th><th style="width:160px;">Harga Beli</th><th></th></tr>
            </thead>
            <tbody id="po-items-body">
                <tr class="po-item-row">
                    <td data-label="Produk">
                        <select name="items[product_id][]" required>
                            <option value="">Pilih produk</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= (int) $product->id ?>"><?= htmlspecialchars("{$product->sku} - {$product->name}", ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td data-label="Qty"><input type="number" name="items[qty_ordered][]" min="1" step="1" required></td>
                    <td data-label="Harga Beli"><input type="number" name="items[buy_price][]" min="0" step="1" required></td>
                    <td><button type="button" class="btn-secondary remove-item-row">Hapus</button></td>
                </tr>
            </tbody>
        </table>
        </div>
        <p><button type="button" class="btn-secondary" id="add-item-row">+ Tambah Item</button></p>

        <button type="submit">Simpan sebagai Draft</button>
    </form>
</div>

<template id="po-item-row-template">
    <tr class="po-item-row">
        <td data-label="Produk">
            <select name="items[product_id][]" required>
                <option value="">Pilih produk</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product->id ?>"><?= htmlspecialchars("{$product->sku} - {$product->name}", ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td data-label="Qty"><input type="number" name="items[qty_ordered][]" min="1" step="1" required></td>
        <td data-label="Harga Beli"><input type="number" name="items[buy_price][]" min="0" step="1" required></td>
        <td><button type="button" class="btn-secondary remove-item-row">Hapus</button></td>
    </tr>
</template>
