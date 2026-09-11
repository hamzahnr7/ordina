<div class="auth-shell">
    <div class="card auth-card">
        <h1>Ordina</h1>
        <p style="text-align:center;color:var(--color-text-muted);margin-top:calc(-1 * var(--space-2));">
            Inventory &amp; Order Management System
        </p>

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
