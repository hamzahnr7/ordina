<div class="page-head">
    <div class="page-head-text">
        <h1>Laporan</h1>
        <p class="page-head-meta">Unduh rekap stok dan order untuk rentang tanggal tertentu</p>
    </div>
</div>

<div class="detail-layout">
    <div class="detail-main card">
        <form method="get">
            <div class="form-grid">
                <div>
                    <label for="from">Dari Tanggal<span class="required-mark">*</span></label>
                    <input type="date" id="from" name="from" value="<?= htmlspecialchars($defaultFrom, ENT_QUOTES) ?>" required>
                </div>

                <div>
                    <label for="to">Sampai Tanggal<span class="required-mark">*</span></label>
                    <input type="date" id="to" name="to" value="<?= htmlspecialchars($defaultTo, ENT_QUOTES) ?>" required>
                </div>
            </div>

            <div class="form-actions">
                <?php if ($canOrders): ?>
                    <button type="submit" formaction="/reports/orders.csv" class="btn-secondary">Unduh Laporan Order (CSV)</button>
                <?php endif; ?>
                <?php if ($canStock): ?>
                    <button type="submit" formaction="/reports/stock-ledger.csv">Unduh Laporan Stok (CSV)</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="detail-side">
        <div class="panel">
            <p class="panel-title">Cakupan Laporan</p>
            <dl>
                <dt>Laporan Stok</dt>
                <dd>Seluruh pergerakan Stock Ledger (Receipt/Issue) pada rentang tanggal terpilih.</dd>
                <dt>Laporan Order</dt>
                <dd>Status Purchase Order/Sales Order pada rentang yang sama - isinya mengikuti hak akses Anda.</dd>
            </dl>
        </div>
    </div>
</div>
