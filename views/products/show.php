<?php
/**
 * @var \App\Entity\Product $product
 * @var string $categoryName
 * @var list<array{warehouse_id:int, warehouse_name:string, quantity:int}> $stocks
 * @var int $totalStock
 * @var bool $isLowStock
 */
?>
<h1><?= htmlspecialchars($product->name, ENT_QUOTES) ?></h1>
<p><a href="/products">&larr; Kembali ke daftar produk</a></p>

<div class="card" style="display:flex;gap:1.5rem;flex-wrap:wrap;">
    <?php if ($product->imagePath !== null): ?>
        <img src="/<?= htmlspecialchars($product->imagePath, ENT_QUOTES) ?>" alt="" style="max-width:200px;">
    <?php endif; ?>
    <dl>
        <dt>SKU</dt><dd><?= htmlspecialchars($product->sku, ENT_QUOTES) ?></dd>
        <dt>Kategori</dt><dd><?= htmlspecialchars($categoryName, ENT_QUOTES) ?></dd>
        <dt>Unit</dt><dd><?= htmlspecialchars($product->unit, ENT_QUOTES) ?></dd>
        <dt>Harga Beli</dt><dd><?= number_format($product->buyPrice, 0, ',', '.') ?></dd>
        <dt>Harga Jual</dt><dd><?= number_format($product->sellPrice, 0, ',', '.') ?></dd>
        <dt>Reorder Point</dt><dd><?= $product->reorderPoint ?></dd>
        <dt>Status</dt><dd><?= $product->isActive ? 'Aktif' : 'Nonaktif' ?></dd>
    </dl>
</div>

<h2>Stok per Gudang (WH-01)</h2>
<div class="card">
    <table>
        <thead><tr><th>Gudang</th><th>Quantity</th></tr></thead>
        <tbody>
        <?php if ($stocks === []): ?>
            <tr><td colspan="2">Belum ada catatan stok untuk produk ini di gudang manapun.</td></tr>
        <?php endif; ?>
        <?php foreach ($stocks as $stock): ?>
            <tr>
                <td data-label="Gudang"><?= htmlspecialchars($stock['warehouse_name'], ENT_QUOTES) ?></td>
                <td data-label="Quantity"><?= (int) $stock['quantity'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th style="<?= $isLowStock ? 'color:var(--color-danger);' : '' ?>">
                    <?= $totalStock ?><?= $isLowStock ? ' (di bawah reorder point)' : '' ?>
                </th>
            </tr>
        </tfoot>
    </table>
</div>
