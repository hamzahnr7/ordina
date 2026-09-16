<div class="page-head">
    <div class="page-head-text">
        <h1>Tambah User</h1>
        <p class="page-head-meta">Buat akun baru untuk Sales atau Warehouse Staff.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card form-card">
    <form method="post" action="/users">
        <div class="form-grid">
            <div>
                <label for="name">Nama<span class="required-mark">*</span></label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="email">Email<span class="required-mark">*</span></label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="password">Password<span class="required-mark">*</span></label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>

            <div>
                <label for="role">Role<span class="required-mark">*</span></label>
                <select id="role" name="role" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role->value ?>"><?= htmlspecialchars($role->label(), ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a href="/users" class="btn btn-secondary">Batal</a>
            <button type="submit">Simpan</button>
        </div>
    </form>
</div>
