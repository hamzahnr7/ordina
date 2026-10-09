<?php
/**
 * @var \App\Domain\Role $role
 * @var array<string, mixed> $stats
 */
use App\Domain\PurchaseOrderStatus;
use App\Domain\Role;
use App\Domain\SalesOrderStatus;

$rupiah = static fn (float $value): string => 'Rp' . number_format($value, 0, ',', '.');

// Icon paths (24x24, stroked) and tone per status: muted = not started,
// primary = in progress, success = done, danger = cancelled.
$icons = [
    'draft' => '<path d="M4 20h4L19 9l-4-4L4 16Z"/><path d="m13.5 6.5 4 4"/>',
    'send' => '<path d="M21 3 3 10.5l7 3 3 7Z"/><path d="M21 3 10 13.5"/>',
    'truck' => '<path d="M3 6h11v10H3Z"/><path d="M14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/>',
    'package' => '<path d="M12 3 20 7.5v9L12 21 4 16.5v-9Z"/><path d="M4 7.5 12 12l8-4.5M12 12v9"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.5 2.5L16 9.5"/>',
    'cancel' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
];
$poVisuals = [
    'Draft' => ['draft', 'muted'],
    'Ordered' => ['send', 'primary'],
    'PartiallyReceived' => ['truck', 'primary'],
    'Received' => ['package', 'success'],
    'Cancelled' => ['cancel', 'danger'],
];
$soVisuals = [
    'Draft' => ['draft', 'muted'],
    'PendingApproval' => ['clock', 'primary'],
    'Approved' => ['check', 'primary'],
    'Fulfilled' => ['package', 'success'],
    'Cancelled' => ['cancel', 'danger'],
];

/** Renders one status => count breakdown as a grid of cards: icon, status, count. */
$statusCards = static function (array $counts, callable $labelFor, array $visuals) use ($icons): void {
    echo '<div class="status-card-grid">';
    foreach ($counts as $statusValue => $count) {
        [$icon, $tone] = $visuals[$statusValue] ?? ['draft', 'muted'];
        echo '<div class="status-card status-tone-' . $tone . ($count === 0 ? ' status-card-empty' : '') . '">';
        echo '<span class="status-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$icon] . '</svg></span>';
        echo '<span class="status-card-label">' . htmlspecialchars($labelFor($statusValue), ENT_QUOTES) . '</span>';
        echo '<span class="status-card-count">' . (int) $count . '</span>';
        echo '</div>';
    }
    echo '</div>';
};
?>
<div class="page-head">
    <div class="page-head-text">
        <h1>Halo, <?= htmlspecialchars($user['name'], ENT_QUOTES) ?></h1>
        <p class="page-head-meta">Masuk sebagai <span class="badge badge-success"><?= htmlspecialchars($user['role'], ENT_QUOTES) ?></span></p>
    </div>
</div>

<?php if ($role === Role::Admin): ?>
    <div class="stat-grid">
        <div class="stat-tile">
            <span class="stat-label">Nilai Inventori</span>
            <span class="stat-value"><?= $rupiah($stats['inventoryValue']) ?></span>
        </div>
        <div class="stat-tile <?= $stats['lowStockCount'] > 0 ? 'stat-warning' : '' ?>">
            <span class="stat-label">Produk di Bawah Reorder Point</span>
            <span class="stat-value"><?= $stats['lowStockCount'] ?></span>
        </div>
    </div>

    <div class="dashboard-grid">
        <div>
            <div class="panel">
                <p class="panel-title">Dashboard Purchase Order</p>
                <?php $statusCards($stats['purchaseOrderStatusCounts'], static fn (string $v): string => PurchaseOrderStatus::from($v)->label(), $poVisuals); ?>
            </div>
            <div class="panel">
                <p class="panel-title">Dashboard Sales Order</p>
                <?php $statusCards($stats['salesOrderStatusCounts'], static fn (string $v): string => SalesOrderStatus::from($v)->label(), $soVisuals); ?>
            </div>
        </div>

        <div class="panel <?= $stats['lowStockProducts'] !== [] ? 'panel-attention' : '' ?>">
            <p class="panel-title">Produk Perlu Restock</p>
            <?php if ($stats['lowStockProducts'] === []): ?>
                <p class="field-hint" style="margin:0;">Tidak ada produk di bawah reorder point saat ini.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>SKU</th><th>Produk</th><th>Stok</th><th>ROP</th><th>PO</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['lowStockProducts'] as $product): ?>
                            <tr>
                                <td data-label="SKU"><?= htmlspecialchars($product['sku'], ENT_QUOTES) ?></td>
                                <td data-label="Produk"><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                                <td data-label="Stok"><?= (int) $product['total_stock'] ?></td>
                                <td data-label="ROP"><?= (int) $product['reorder_point'] ?></td>
                                <td data-label="PO"><a href="/purchase-orders/create?product_id=<?= (int) $product['id'] ?>" class="icon-btn icon-btn-primary" title="Buat PO untuk restock <?= htmlspecialchars($product['name'], ENT_QUOTES) ?>" aria-label="Buat PO untuk restock <?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8Z"/><path d="M14 3v5h5"/><path d="M12 11v6M9 14h6"/></svg></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($role === Role::Sales): ?>
    <div class="panel">
        <p class="panel-title">Ringkasan Sales Order Saya</p>
        <?php $statusCards($stats['salesOrderStatusCounts'], static fn (string $v): string => SalesOrderStatus::from($v)->label(), $soVisuals); ?>
    </div>
<?php endif; ?>

<?php if ($role === Role::WarehouseStaff): ?>
    <div class="stat-grid">
        <div class="stat-tile">
            <span class="stat-label">Antrean Goods Receipt</span>
            <span class="stat-value"><?= $stats['pendingGoodsReceiptCount'] ?></span>
        </div>
        <div class="stat-tile">
            <span class="stat-label">Antrean Goods Issue</span>
            <span class="stat-value"><?= $stats['pendingGoodsIssueCount'] ?></span>
        </div>
        <div class="stat-tile <?= $stats['lowStockCount'] > 0 ? 'stat-warning' : '' ?>">
            <span class="stat-label">Produk Low Stock</span>
            <span class="stat-value"><?= $stats['lowStockCount'] ?></span>
        </div>
    </div>

    <?php if ($stats['lowStockProducts'] !== []): ?>
        <div class="panel panel-attention">
            <p class="panel-title">Produk Low Stock</p>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>SKU</th><th>Produk</th><th>Stok</th><th>ROP</th><th>PO</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats['lowStockProducts'] as $product): ?>
                        <tr>
                            <td data-label="SKU"><?= htmlspecialchars($product['sku'], ENT_QUOTES) ?></td>
                            <td data-label="Produk"><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                            <td data-label="Stok"><?= (int) $product['total_stock'] ?></td>
                            <td data-label="ROP"><?= (int) $product['reorder_point'] ?></td>
                            <td data-label="PO"><a href="/purchase-orders/create?product_id=<?= (int) $product['id'] ?>" class="icon-btn icon-btn-primary" title="Buat PO untuk restock <?= htmlspecialchars($product['name'], ENT_QUOTES) ?>" aria-label="Buat PO untuk restock <?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8Z"/><path d="M14 3v5h5"/><path d="M12 11v6M9 14h6"/></svg></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
