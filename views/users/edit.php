<div class="page-head">
    <div class="page-head-text">
        <h1>Edit User</h1>
        <p class="page-head-meta">Perbarui data atau role akun ini.</p>
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
    <form method="post" action="/users/<?= (int) $user->id ?>">
        <div class="form-grid">
            <div>
                <label for="name">Nama<span class="required-mark">*</span></label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user->name, ENT_QUOTES) ?>" required>
            </div>

            <div>
                <label for="email">Email<span class="required-mark">*</span></label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user->email, ENT_QUOTES) ?>" required>
            </div>

            <div class="field-full">
                <label for="role">Role<span class="required-mark">*</span></label>
                <select id="role" name="role" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role->value ?>" <?= $role === $user->role ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role->label(), ENT_QUOTES) ?>
                        </option>
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
