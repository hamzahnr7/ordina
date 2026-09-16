<?php
/**
 * @var \App\Domain\Role $role
 * @var array<string, mixed> $stats
 */
use App\Domain\PurchaseOrderStatus;
use App\Domain\Role;
use App\Domain\SalesOrderStatus;

$rupiah = static fn (float $value): string => 'Rp' . number_format($value, 0, ',', '.');
$maxOf = static fn (array $counts): int => max([1, ...array_values($counts)]);

/** Renders one status => count breakdown as proportional horizontal bars instead of a flat badge row. */
$statusBars = static function (array $counts, callable $labelFor) use ($maxOf): void {
    $max = $maxOf($counts);
    echo '<div class="status-bar-list">';
    foreach ($counts as $statusValue => $count) {
        $pct = $count / $max * 100;
        echo '<div class="status-bar-row">';
        echo '<span class="status-bar-label">' . htmlspecialchars($labelFor($statusValue), ENT_QUOTES) . '</span>';
        echo '<span class="status-bar-track"><span class="status-bar-fill" style="width:' . $pct . '%;"></span></span>';
        echo '<span class="status-bar-count">' . $count . '</span>';
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
                <p class="panel-title">Purchase Order per Status</p>
                <?php $statusBars($stats['purchaseOrderStatusCounts'], static fn (string $v): string => PurchaseOrderStatus::from($v)->label()); ?>
            </div>
            <div class="panel">
                <p class="panel-title">Sales Order per Status</p>
                <?php $statusBars($stats['salesOrderStatusCounts'], static fn (string $v): string => SalesOrderStatus::from($v)->label()); ?>
            </div>
        </div>

        <div class="panel <?= $stats['lowStockProducts'] !== [] ? 'panel-attention' : '' ?>">
            <p class="panel-title">Produk Perlu Restock</p>
            <?php if ($stats['lowStockProducts'] === []): ?>
                <p class="field-hint" style="margin:0;">Tidak ada produk di bawah reorder point saat ini.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>SKU</th><th>Produk</th><th>Stok</th><th>ROP</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['lowStockProducts'] as $product): ?>
                            <tr>
                                <td data-label="SKU"><?= htmlspecialchars($product['sku'], ENT_QUOTES) ?></td>
                                <td data-label="Produk"><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                                <td data-label="Stok"><?= (int) $product['total_stock'] ?></td>
                                <td data-label="ROP"><?= (int) $product['reorder_point'] ?></td>
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
        <?php $statusBars($stats['salesOrderStatusCounts'], static fn (string $v): string => SalesOrderStatus::from($v)->label()); ?>
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
                    <thead><tr><th>SKU</th><th>Produk</th><th>Stok</th><th>ROP</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats['lowStockProducts'] as $product): ?>
                        <tr>
                            <td data-label="SKU"><?= htmlspecialchars($product['sku'], ENT_QUOTES) ?></td>
                            <td data-label="Produk"><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                            <td data-label="Stok"><?= (int) $product['total_stock'] ?></td>
                            <td data-label="ROP"><?= (int) $product['reorder_point'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
