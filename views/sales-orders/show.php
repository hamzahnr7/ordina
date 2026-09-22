<?php
/**
 * @var \App\Entity\SalesOrder $salesOrder
 * @var string $customerName
 * @var string $warehouseName
 * @var list<array{item: \App\Entity\SalesOrderItem, productName: string, productSku: string}> $items
 * @var list<array<string, mixed>> $ledger
 * @var bool $canSubmit
 * @var bool $canApproveOrReject
 * @var bool $canCancel
 * @var bool $canIssue
 */
$statusBadge = match ($salesOrder->status->value) {
    'Draft' => 'badge-muted',
    'Cancelled' => 'badge-danger',
    default => 'badge-success',
};
?>
<div class="page-head">
    <div class="page-head-text">
        <div class="page-title-row">
            <a href="/sales-orders" class="icon-btn back-btn" aria-label="Kembali ke daftar Sales Order">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6 9 12l6 6"/></svg>
            </a>
            <h1>SO-<?= str_pad((string) $salesOrder->id, 5, '0', STR_PAD_LEFT) ?> <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($salesOrder->status->label(), ENT_QUOTES) ?></span></h1>
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
                <thead><tr><th>SKU</th><th>Produk</th><th>Qty</th><th>Harga Jual</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php $grandTotal = 0; ?>
                <?php foreach ($items as $row): $item = $row['item']; $subtotal = $item->qty * $item->sellPrice; $grandTotal += $subtotal; ?>
                    <tr>
                        <td data-label="SKU"><?= htmlspecialchars($row['productSku'], ENT_QUOTES) ?></td>
                        <td data-label="Produk"><?= htmlspecialchars($row['productName'], ENT_QUOTES) ?></td>
                        <td data-label="Qty"><?= $item->qty ?></td>
                        <td data-label="Harga Jual"><?= number_format($item->sellPrice, 0, ',', '.') ?></td>
                        <td data-label="Subtotal"><?= number_format($subtotal, 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><th colspan="4">Total</th><th><?= number_format($grandTotal, 0, ',', '.') ?></th></tr>
                </tfoot>
            </table>
        </div>
        </div>

        <?php if ($canIssue): ?>
            <h2>Goods Issue</h2>
            <div class="card">
                <p class="field-hint">
                    Memproses goods issue akan mengurangi stok untuk setiap item di atas dari
                    gudang <?= htmlspecialchars($warehouseName, ENT_QUOTES) ?>. Ditolak otomatis
                    jika stok tidak mencukupi untuk salah satu item (ARCH-02) - tidak ada
                    perubahan stok sebagian.
                </p>
                <form method="post" action="/sales-orders/<?= (int) $salesOrder->id ?>/issue">
                    <button type="submit">Proses Goods Issue</button>
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
                            <td data-label="Tipe"><span class="badge badge-danger"><?= htmlspecialchars($entry['movement_type'], ENT_QUOTES) ?></span></td>
                            <td data-label="Qty">-<?= (int) $entry['quantity'] ?></td>
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
                <dt>Customer</dt><dd><?= htmlspecialchars($customerName, ENT_QUOTES) ?></dd>
                <dt>Gudang Asal</dt><dd><?= htmlspecialchars($warehouseName, ENT_QUOTES) ?></dd>
            </dl>
        </div>

        <?php if ($canSubmit || $canApproveOrReject || $canCancel): ?>
            <div class="panel">
                <p class="panel-title">Aksi</p>
                <div class="detail-actions">
                    <?php if ($canSubmit): ?>
                        <form method="post" action="/sales-orders/<?= (int) $salesOrder->id ?>/submit">
                            <button type="submit">Ajukan untuk Approval</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($canApproveOrReject): ?>
                        <form method="post" action="/sales-orders/<?= (int) $salesOrder->id ?>/approve">
                            <button type="submit">Setujui</button>
                        </form>
                        <form method="post" action="/sales-orders/<?= (int) $salesOrder->id ?>/reject">
                            <button type="submit" class="btn-danger">Tolak</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                        <form method="post" action="/sales-orders/<?= (int) $salesOrder->id ?>/cancel">
                            <button type="submit" class="btn-danger">Batalkan</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
