<a href="/users" class="back-link">&larr; Kembali ke daftar user</a>
<h1>Tambah User</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width:420px;">
    <form method="post" action="/users">
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES) ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8">

        <label for="role">Role</label>
        <select id="role" name="role" required>
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role->value ?>"><?= htmlspecialchars($role->label(), ENT_QUOTES) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Simpan</button>
    </form>
</div>
