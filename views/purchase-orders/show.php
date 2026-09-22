<?php
/**
 * @var \App\Entity\PurchaseOrder $purchaseOrder
 * @var string $supplierName
 * @var string $warehouseName
 * @var list<array{item: \App\Entity\PurchaseOrderItem, productName: string, productSku: string}> $items
 * @var list<array<string, mixed>> $ledger
 * @var bool $canManage
 * @var bool $canReceive
 */
$statusBadge = match ($purchaseOrder->status->value) {
    'Draft' => 'badge-muted',
    'Cancelled' => 'badge-danger',
    default => 'badge-success',
};
?>
<div class="page-head">
    <div class="page-head-text">
        <div class="page-title-row">
            <a href="/purchase-orders" class="icon-btn back-btn" aria-label="Kembali ke daftar Purchase Order">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6 9 12l6 6"/></svg>
            </a>
            <h1>PO-<?= str_pad((string) $purchaseOrder->id, 5, '0', STR_PAD_LEFT) ?> <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($purchaseOrder->status->label(), ENT_QUOTES) ?></span></h1>
        </div>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<?php endif; ?>
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
    <div class="detail-main">
        <h2>Item</h2>
        <div class="card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr><th>SKU</th><th>Produk</th><th>Qty Dipesan</th><th>Diterima</th><th>Sisa</th><th>Harga Beli</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $row): ?>
                    <?php $item = $row['item']; ?>
                    <tr>
                        <td data-label="SKU"><?= htmlspecialchars($row['productSku'], ENT_QUOTES) ?></td>
                        <td data-label="Produk"><?= htmlspecialchars($row['productName'], ENT_QUOTES) ?></td>
                        <td data-label="Qty Dipesan"><?= $item->qtyOrdered ?></td>
                        <td data-label="Diterima"><?= $item->qtyReceived ?></td>
                        <td data-label="Sisa"><?= $item->remaining() ?></td>
                        <td data-label="Harga Beli"><?= number_format($item->buyPrice, 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>

        <?php if ($canReceive && $purchaseOrder->status->canReceiveGoods()): ?>
            <h2>Goods Receipt</h2>
            <div class="card">
                <form method="post" action="/purchase-orders/<?= (int) $purchaseOrder->id ?>/receive">
                    <div class="table-scroll">
                    <table>
                        <thead><tr><th>Produk</th><th>Sisa</th><th style="width:140px;">Terima Sekarang</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $row): $item = $row['item']; if ($item->remaining() <= 0) { continue; } ?>
                            <tr>
                                <td data-label="Produk"><?= htmlspecialchars("{$row['productSku']} - {$row['productName']}", ENT_QUOTES) ?></td>
                                <td data-label="Sisa"><?= $item->remaining() ?></td>
                                <td data-label="Terima Sekarang">
                                    <input type="number" name="receive[<?= (int) $item->id ?>]" min="0" max="<?= $item->remaining() ?>" value="0" style="margin-bottom:0;">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <p class="field-hint">Isi qty yang benar-benar diterima secara fisik. Penerimaan sebagian (partial) diperbolehkan - sisa akan tetap tercatat untuk diterima lain waktu.</p>
                    <button type="submit">Proses Goods Receipt</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($ledger !== []): ?>
            <h2>Stock Ledger Terkait</h2>
            <div class="card">
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Waktu</th><th>Produk</th><th>Gudang</th><th>Tipe</th><th>Qty</th><th>Oleh</th></tr></thead>
                    <tbody>
                    <?php foreach ($ledger as $entry): ?>
                        <tr>
                            <td data-label="Waktu"><?= htmlspecialchars($entry['created_at'], ENT_QUOTES) ?></td>
                            <td data-label="Produk"><?= htmlspecialchars("{$entry['sku']} - {$entry['product_name']}", ENT_QUOTES) ?></td>
                            <td data-label="Gudang"><?= htmlspecialchars($entry['warehouse_name'], ENT_QUOTES) ?></td>
                            <td data-label="Tipe"><span class="badge badge-success"><?= htmlspecialchars($entry['movement_type'], ENT_QUOTES) ?></span></td>
                            <td data-label="Qty">+<?= (int) $entry['quantity'] ?></td>
                            <td data-label="Oleh"><?= htmlspecialchars($entry['performed_by_name'], ENT_QUOTES) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-side">
        <div class="panel">
            <p class="panel-title">Ringkasan</p>
            <dl>
                <dt>Supplier</dt><dd><?= htmlspecialchars($supplierName, ENT_QUOTES) ?></dd>
                <dt>Gudang Tujuan</dt><dd><?= htmlspecialchars($warehouseName, ENT_QUOTES) ?></dd>
                <dt>Tanggal Order</dt><dd><?= htmlspecialchars($purchaseOrder->orderDate, ENT_QUOTES) ?></dd>
            </dl>
        </div>

        <?php if ($canManage && $purchaseOrder->status->value === 'Draft'): ?>
            <div class="panel">
                <p class="panel-title">Aksi</p>
                <div class="detail-actions">
                    <form method="post" action="/purchase-orders/<?= (int) $purchaseOrder->id ?>/mark-ordered">
                        <button type="submit">Kirim ke Supplier</button>
                    </form>
                    <form method="post" action="/purchase-orders/<?= (int) $purchaseOrder->id ?>/cancel">
                        <button type="submit" class="btn-danger">Batalkan</button>
                    </form>
                </div>
            </div>
        <?php elseif ($canManage && $purchaseOrder->status->canBeCancelled()): ?>
            <div class="panel">
                <p class="panel-title">Aksi</p>
                <div class="detail-actions">
                    <form method="post" action="/purchase-orders/<?= (int) $purchaseOrder->id ?>/cancel">
                        <button type="submit" class="btn-danger">Batalkan Purchase Order</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
