<?php
/**
 * @var \App\Entity\Product $product
 * @var string $categoryName
 * @var list<array{warehouse_id:int, warehouse_name:string, quantity:int}> $stocks
 * @var int $totalStock
 * @var bool $isLowStock
 */
?>
<div class="page-head">
    <div class="page-head-text">
        <a href="/products" class="page-back">&larr; Kembali ke daftar produk</a>
        <h1><?= htmlspecialchars($product->name, ENT_QUOTES) ?> <span class="badge <?= $product->isActive ? 'badge-success' : 'badge-muted' ?>"><?= $product->isActive ? 'Aktif' : 'Nonaktif' ?></span></h1>
    </div>
</div>

<div class="detail-layout">
    <div class="detail-main">
        <h2>Stok per Gudang (WH-01)</h2>
        <div class="card">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Gudang</th><th>Quantity</th></tr></thead>
                <tbody>
                <?php if ($stocks === []): ?>
                    <tr class="empty-row"><td colspan="2">Belum ada catatan stok untuk produk ini di gudang manapun.</td></tr>
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
                        <th>
                            <?= $totalStock ?>
                            <?php if ($isLowStock): ?><span class="badge badge-danger">di bawah reorder point</span><?php endif; ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        </div>
    </div>

    <div class="detail-side">
        <div class="panel">
            <p class="panel-title">Detail Produk</p>
            <?php if ($product->imagePath !== null): ?>
                <img src="/<?= htmlspecialchars($product->imagePath, ENT_QUOTES) ?>" alt="" style="width:100%;border-radius:var(--radius-sm);margin-bottom:var(--space-3);">
            <?php endif; ?>
            <dl>
                <dt>SKU</dt><dd><?= htmlspecialchars($product->sku, ENT_QUOTES) ?></dd>
                <dt>Kategori</dt><dd><?= htmlspecialchars($categoryName, ENT_QUOTES) ?></dd>
                <dt>Unit</dt><dd><?= htmlspecialchars($product->unit, ENT_QUOTES) ?></dd>
                <dt>Harga Beli</dt><dd><?= number_format($product->buyPrice, 0, ',', '.') ?></dd>
                <dt>Harga Jual</dt><dd><?= number_format($product->sellPrice, 0, ',', '.') ?></dd>
                <dt>Reorder Point</dt><dd><?= $product->reorderPoint ?></dd>
            </dl>
        </div>
    </div>
</div>
