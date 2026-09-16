<div class="auth-shell">
    <div class="auth-visual">
        <div class="auth-visual-content">
            <span class="app-brand">Ordina</span>
            <h2>Setiap barang, tercatat.</h2>
            <p>Stok multi-gudang, purchase order, dan sales order dalam satu sistem.</p>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="card auth-card">
            <h1>Masuk ke Ordina</h1>
            <p class="form-subtitle">Gunakan akun yang diberikan admin untuk mengakses sistem.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <form method="post" action="/login">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES) ?>" required autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit" style="width:100%;justify-content:center;">Login</button>
            </form>
        </div>
    </div>
</div>
