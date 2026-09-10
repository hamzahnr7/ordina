<?php
/** @var array{items: list<array<string,mixed>>, total:int, page:int, perPage:int, totalPages:int} $result */
$queryWithout = static function (array $overrides) use ($filters, $result): string {
    return '?' . http_build_query(array_filter([...$filters, ...$overrides]));
};
?>
<h1>Produk</h1>
<p><a href="/dashboard">&larr; Kembali ke dashboard</a></p>

<?php if (!empty($success)): ?>
    <p style="color:green;"><?= htmlspecialchars($success, ENT_QUOTES) ?></p>
<?php endif; ?>

<?php if ($canManage): ?>
    <p><a href="/products/create"><button type="button">Tambah Produk</button></a></p>
<?php endif; ?>

<form method="get" action="/products" class="card">
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label for="search">Cari (nama/SKU)</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div style="min-width:160px;">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id">
                <option value="">Semua</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category->id ?>" <?= (int) ($filters['category_id'] ?? 0) === $category->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category->name, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:160px;">
            <label for="stock_status">Status Stok</label>
            <select id="stock_status" name="stock_status">
                <option value="">Semua</option>
                <option value="low" <?= ($filters['stock_status'] ?? '') === 'low' ? 'selected' : '' ?>>Low stock</option>
                <option value="normal" <?= ($filters['stock_status'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal</option>
            </select>
        </div>
        <div>
            <button type="submit">Filter</button>
        </div>
    </div>
</form>

<div class="card">
<table>
    <thead>
        <tr>
            <th>SKU</th><th>Nama</th><th>Kategori</th><th>Unit</th><th>Harga Jual</th><th>Stok</th><th>Status</th>
            <?php if ($canManage): ?><th>Aksi</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php if ($result['items'] === []): ?>
        <tr><td colspan="8">Tidak ada produk yang cocok dengan filter ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($result['items'] as $item): ?>
        <?php $isLow = \App\Service\ProductService::isLowStock((int) $item['total_stock'], (int) $item['reorder_point']); ?>
        <tr>
            <td data-label="SKU"><?= htmlspecialchars($item['sku'], ENT_QUOTES) ?></td>
            <td data-label="Nama"><a href="/products/<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></a></td>
            <td data-label="Kategori"><?= htmlspecialchars($item['category_name'], ENT_QUOTES) ?></td>
            <td data-label="Unit"><?= htmlspecialchars($item['unit'], ENT_QUOTES) ?></td>
            <td data-label="Harga Jual"><?= number_format((float) $item['sell_price'], 0, ',', '.') ?></td>
            <td data-label="Stok" style="<?= $isLow ? 'color:var(--color-danger);font-weight:600;' : '' ?>">
                <?= (int) $item['total_stock'] ?><?= $isLow ? ' (low)' : '' ?>
            </td>
            <td data-label="Status"><?= ((int) $item['is_active']) ? 'Aktif' : 'Nonaktif' ?></td>
            <?php if ($canManage): ?>
                <td data-label="Aksi">
                    <a href="/products/<?= (int) $item['id'] ?>/edit">Edit</a>
                    &nbsp;
                    <form method="post" action="/products/<?= (int) $item['id'] ?>/toggle-active" style="display:inline;">
                        <button type="submit"><?= ((int) $item['is_active']) ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                    </form>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if ($result['totalPages'] > 1): ?>
    <nav aria-label="Pagination" style="margin-top:1rem;display:flex;gap:.5rem;">
        <?php for ($p = 1; $p <= $result['totalPages']; $p++): ?>
            <?php if ($p === $result['page']): ?>
                <strong><?= $p ?></strong>
            <?php else: ?>
                <a href="<?= htmlspecialchars($queryWithout(['page' => $p]), ENT_QUOTES) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
