<?php
/** @var array{items: list<array<string,mixed>>, total:int, page:int, perPage:int, totalPages:int, sortDir:string} $result */
$queryWithout = static function (array $overrides) use ($filters, $sortDir): string {
    return '?' . http_build_query(array_filter(['sort' => $sortDir, ...$filters, ...$overrides]));
};
$statusBadge = static fn (string $status): string => match ($status) {
    'Draft' => 'badge-muted',
    'Cancelled' => 'badge-danger',
    default => 'badge-success',
};
?>
<a href="/dashboard" class="back-link">&larr; Kembali ke dashboard</a>

<div class="toolbar">
    <h1 style="margin:0;">Sales Order</h1>
    <?php if ($canCreate): ?>
        <a href="/sales-orders/create" class="btn">Buat Sales Order</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<?php endif; ?>

<form method="get" action="/sales-orders" class="card filter-bar">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sortDir, ENT_QUOTES) ?>">
    <div class="filter-row">
        <div class="filter-field">
            <label for="search">Cari (No. SO/Customer)</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= $status->value ?>" <?= ($filters['status'] ?? '') === $status->value ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status->label(), ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit">Filter</button>
        </div>
    </div>
</form>

<div class="card">
<div class="table-scroll">
<table>
    <thead>
        <tr>
            <th>No. SO</th>
            <th>Customer</th>
            <th>Gudang</th>
            <th>
                <a href="<?= htmlspecialchars($queryWithout(['sort' => $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1]), ENT_QUOTES) ?>">
                    Tanggal <?= $sortDir === 'asc' ? '&uarr;' : '&darr;' ?>
                </a>
            </th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($result['items'] === []): ?>
        <tr class="empty-row"><td colspan="5">Belum ada Sales Order yang cocok dengan filter ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($result['items'] as $so): ?>
        <tr>
            <td data-label="No. SO"><a href="/sales-orders/<?= (int) $so['id'] ?>">SO-<?= str_pad((string) $so['id'], 5, '0', STR_PAD_LEFT) ?></a></td>
            <td data-label="Customer"><?= htmlspecialchars($so['customer_name'], ENT_QUOTES) ?></td>
            <td data-label="Gudang"><?= htmlspecialchars($so['warehouse_name'], ENT_QUOTES) ?></td>
            <td data-label="Tanggal"><?= htmlspecialchars($so['created_at'], ENT_QUOTES) ?></td>
            <td data-label="Status">
                <span class="badge <?= $statusBadge($so['status']) ?>"><?= htmlspecialchars($so['status'], ENT_QUOTES) ?></span>
            </td>
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
