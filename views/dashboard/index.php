<?php
/**
 * @var \App\Domain\Role $role
 * @var array<string, mixed> $stats
 */
use App\Domain\PurchaseOrderStatus;
use App\Domain\Role;
use App\Domain\SalesOrderStatus;

$rupiah = static fn (float $value): string => 'Rp' . number_format($value, 0, ',', '.');
?>
<h1>Halo, <?= htmlspecialchars($user['name'], ENT_QUOTES) ?></h1>

<div class="card">
    <p style="margin:0;">
        Anda masuk sebagai <span class="badge badge-success"><?= htmlspecialchars($user['role'], ENT_QUOTES) ?></span>.
        Gunakan menu di bagian atas halaman untuk berpindah antar fitur.
    </p>
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

    <h2>Purchase Order per Status</h2>
    <div class="card">
        <div class="status-counts">
            <?php foreach ($stats['purchaseOrderStatusCounts'] as $statusValue => $count): ?>
                <span class="badge badge-muted"><?= htmlspecialchars(PurchaseOrderStatus::from($statusValue)->label(), ENT_QUOTES) ?>: <?= $count ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <h2>Sales Order per Status</h2>
    <div class="card">
        <div class="status-counts">
            <?php foreach ($stats['salesOrderStatusCounts'] as $statusValue => $count): ?>
                <span class="badge badge-muted"><?= htmlspecialchars(SalesOrderStatus::from($statusValue)->label(), ENT_QUOTES) ?>: <?= $count ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($stats['lowStockProducts'] !== []): ?>
        <h2>Produk Perlu Restock</h2>
        <div class="card">
        <div class="table-scroll">
            <table>
                <thead><tr><th>SKU</th><th>Produk</th><th>Stok</th><th>Reorder Point</th></tr></thead>
                <tbody>
                <?php foreach ($stats['lowStockProducts'] as $product): ?>
                    <tr>
                        <td data-label="SKU"><?= htmlspecialchars($product['sku'], ENT_QUOTES) ?></td>
                        <td data-label="Produk"><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                        <td data-label="Stok"><?= (int) $product['total_stock'] ?></td>
                        <td data-label="Reorder Point"><?= (int) $product['reorder_point'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($role === Role::Sales): ?>
    <h2>Ringkasan Sales Order Saya</h2>
    <div class="card">
        <div class="status-counts">
            <?php foreach ($stats['salesOrderStatusCounts'] as $statusValue => $count): ?>
                <span class="badge badge-muted"><?= htmlspecialchars(SalesOrderStatus::from($statusValue)->label(), ENT_QUOTES) ?>: <?= $count ?></span>
            <?php endforeach; ?>
        </div>
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
        <h2>Produk Low Stock</h2>
        <div class="card">
        <div class="table-scroll">
            <table>
                <thead><tr><th>SKU</th><th>Produk</th><th>Stok</th><th>Reorder Point</th></tr></thead>
                <tbody>
                <?php foreach ($stats['lowStockProducts'] as $product): ?>
                    <tr>
                        <td data-label="SKU"><?= htmlspecialchars($product['sku'], ENT_QUOTES) ?></td>
                        <td data-label="Produk"><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                        <td data-label="Stok"><?= (int) $product['total_stock'] ?></td>
                        <td data-label="Reorder Point"><?= (int) $product['reorder_point'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
