<?php
/**
 * @var \App\Entity\User $user
 * @var array<string, string> $errors
 * @var string|null $success
 */
?>
<div class="page-head">
    <div class="page-head-text">
        <h1>Profil Saya</h1>
        <p class="page-head-meta">Data akun Anda dan penggantian password.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="detail-layout">
    <div class="detail-main card">
        <h2>Ganti Password</h2>
        <form method="post" action="/profile/password">
            <label for="current_password">Password saat ini<span class="required-mark">*</span></label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>

            <div class="form-grid">
                <div>
                    <label for="new_password">Password baru<span class="required-mark">*</span></label>
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
                </div>
                <div>
                    <label for="password_confirmation">Ulangi password baru<span class="required-mark">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                </div>
            </div>
            <p class="field-hint">Minimal 8 karakter. Setelah diganti, sesi Anda tetap aktif.</p>

            <div class="form-actions">
                <a href="/dashboard" class="btn btn-secondary">Batal</a>
                <button type="submit">Simpan Password</button>
            </div>
        </form>
    </div>

    <div class="detail-side">
        <div class="panel">
            <p class="panel-title">Akun</p>
            <dl>
                <dt>Nama</dt><dd><?= htmlspecialchars($user->name, ENT_QUOTES) ?></dd>
                <dt>Email</dt><dd><?= htmlspecialchars($user->email, ENT_QUOTES) ?></dd>
                <dt>Role</dt><dd><?= htmlspecialchars($user->role->label(), ENT_QUOTES) ?></dd>
                <dt>Status</dt><dd><span class="badge <?= $user->isActive ? 'badge-success' : 'badge-muted' ?>"><?= $user->isActive ? 'Aktif' : 'Nonaktif' ?></span></dd>
            </dl>
            <?php if ($user->role !== \App\Domain\Role::Admin): ?>
                <p class="field-hint" style="margin-top:var(--space-3);margin-bottom:0;">Perubahan nama, email, atau role dilakukan oleh Admin.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
