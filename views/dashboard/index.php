<h1>Halo, <?= htmlspecialchars($user['name'], ENT_QUOTES) ?></h1>
<p>Role: <strong><?= htmlspecialchars($user['role'], ENT_QUOTES) ?></strong></p>

<nav class="card" aria-label="Menu utama">
    <ul>
        <?php foreach ($menus as $menu): ?>
            <li><a href="<?= htmlspecialchars($menu['route'], ENT_QUOTES) ?>"><?= htmlspecialchars($menu['label'], ENT_QUOTES) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>

<form method="post" action="/logout" style="margin-top:1rem;">
    <button type="submit">Logout</button>
</form>

<p style="margin-top:2rem;color:#666;">
    Ringkasan angka per role (nilai inventori, order pending, antrean stok - DASH-01)
    belum diimplementasikan. Nav di atas membuktikan menu mengikuti hak akses role
    (lihat <code>docs/architecture/rbac-and-menu-access.md</code>).
</p>
