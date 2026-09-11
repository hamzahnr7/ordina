<a href="/dashboard" class="back-link">&larr; Kembali ke dashboard</a>
<h1>Laporan</h1>

<div class="card" style="max-width:520px;">
    <form method="get">
        <label for="from">Dari Tanggal</label>
        <input type="date" id="from" name="from" value="<?= htmlspecialchars($defaultFrom, ENT_QUOTES) ?>" required>

        <label for="to">Sampai Tanggal</label>
        <input type="date" id="to" name="to" value="<?= htmlspecialchars($defaultTo, ENT_QUOTES) ?>" required>

        <?php if ($canStock): ?>
            <button type="submit" formaction="/reports/stock-ledger.csv">Unduh Laporan Stok (CSV)</button>
        <?php endif; ?>
        <?php if ($canOrders): ?>
            <button type="submit" formaction="/reports/orders.csv" class="btn-secondary">Unduh Laporan Order (CSV)</button>
        <?php endif; ?>
    </form>
</div>

<p class="field-hint">
    Laporan stok berisi seluruh pergerakan Stock Ledger (Receipt/Issue) pada
    rentang tanggal terpilih. Laporan order berisi status Purchase
    Order/Sales Order pada rentang tanggal yang sama - isinya mengikuti hak
    akses Anda (lihat <code>docs/architecture/rbac-and-menu-access.md</code>).
</p>
