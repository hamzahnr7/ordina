<?php
/** @var array{items: list<array<string,mixed>>, total:int, page:int, perPage:int, totalPages:int} $result */
$queryWithout = static function (array $overrides) use ($filters): string {
    return '?' . http_build_query(array_filter([...$filters, ...$overrides]));
};
?>
<div class="page-head">
    <div class="page-head-text">
        <h1>Produk</h1>
        <p class="page-head-meta"><?= (int) $result['total'] ?> produk cocok dengan filter saat ini</p>
    </div>
    <?php if ($canManage): ?>
        <div class="page-head-actions">
            <a href="/products/create" class="btn">Tambah Produk</a>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<?php endif; ?>

<form method="get" action="/products" class="card filter-bar">
    <div class="filter-row">
        <div class="filter-field search-field">
            <label for="search">Cari (nama/SKU)</label>
            <div class="search-input-wrap">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m20 20-4.8-4.8"/></svg>
                <input type="text" id="search" name="search" placeholder="Nama atau SKU produk" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES) ?>">
            </div>
        </div>
        <div class="filter-field">
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
        <div class="filter-field">
            <label for="stock_status">Status Stok</label>
            <select id="stock_status" name="stock_status">
                <option value="">Semua</option>
                <option value="low" <?= ($filters['stock_status'] ?? '') === 'low' ? 'selected' : '' ?>>Low stock</option>
                <option value="normal" <?= ($filters['stock_status'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit">Filter</button>
            <?php if (array_filter($filters) !== []): ?>
                <a href="/products" class="filter-reset">Reset filter</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="card">
<div class="table-scroll">
<table>
    <thead>
        <tr>
            <th>SKU</th><th>Nama</th><th>Kategori</th><th>Unit</th><th>Harga Jual</th><th>Stok</th><th>Status</th>
            <?php if ($canManage): ?><th>Aksi</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php if ($result['items'] === []): ?>
        <tr class="empty-row"><td colspan="8">Tidak ada produk yang cocok dengan filter ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($result['items'] as $item): ?>
        <?php $isLow = \App\Service\ProductService::isLowStock((int) $item['total_stock'], (int) $item['reorder_point']); ?>
        <tr>
            <td data-label="SKU"><?= htmlspecialchars($item['sku'], ENT_QUOTES) ?></td>
            <td data-label="Nama"><a href="/products/<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></a></td>
            <td data-label="Kategori"><?= htmlspecialchars($item['category_name'], ENT_QUOTES) ?></td>
            <td data-label="Unit"><?= htmlspecialchars($item['unit'], ENT_QUOTES) ?></td>
            <td data-label="Harga Jual"><?= number_format((float) $item['sell_price'], 0, ',', '.') ?></td>
            <td data-label="Stok">
                <?= (int) $item['total_stock'] ?>
                <?php if ($isLow): ?><span class="badge badge-danger">low</span><?php endif; ?>
            </td>
            <td data-label="Status">
                <span class="badge <?= ((int) $item['is_active']) ? 'badge-success' : 'badge-muted' ?>">
                    <?= ((int) $item['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </td>
            <?php if ($canManage): ?>
                <td data-label="Aksi">
                    <a href="/products/<?= (int) $item['id'] ?>/edit" class="btn btn-secondary">Edit</a>
                    <form method="post" action="/products/<?= (int) $item['id'] ?>/toggle-active" style="display:inline;">
                        <button type="submit" class="<?= ((int) $item['is_active']) ? 'btn-danger' : 'btn-secondary' ?>"><?= ((int) $item['is_active']) ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                    </form>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php if ($result['totalPages'] > 1): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php for ($p = 1; $p <= $result['totalPages']; $p++): ?>
            <?php if ($p === $result['page']): ?>
                <strong><?= $p ?></strong>
            <?php else: ?>
                <a href="<?= htmlspecialchars($queryWithout(['page' => $p]), ENT_QUOTES) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
