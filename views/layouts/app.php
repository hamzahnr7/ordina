<?php

declare(strict_types=1);

use App\Core\Authorization\MenuRegistry;
use App\Core\Session;
use App\Domain\Role;

$navUser = Session::get('user');
$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Cache-busting: append each asset's last-modified time so browsers refetch
// it the moment the file actually changes, instead of serving a stale copy
// from cache until a hard-refresh (Ctrl+Shift+R).
$assetVersion = static function (string $relativePath): string {
    $path = __DIR__ . '/../../public/' . $relativePath;
    $mtime = @filemtime($path);

    return $relativePath . '?v=' . ($mtime !== false ? $mtime : time());
};

// Small hand-drawn icon set for the sidebar (one glyph per menu key, see
// config/menus.php's `icon` field) - 20x20, single stroke, no external
// icon library per the brief's "no framework" rule.
$navIcon = static function (string $key): string {
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'product' => '<path d="M12 3 20 7.5v9L12 21 4 16.5v-9Z"/><path d="M4 7.5 12 12l8-4.5M12 12v9"/>',
        'category' => '<path d="M11 3 3 11v0L11 19l8-8v0Z" transform="rotate(45 11 11)"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'warehouse' => '<path d="M3 10 12 4l9 6v9H3Z"/><path d="M9 19v-6h6v6"/>',
        'supplier' => '<rect x="3" y="8" width="12" height="8" rx="1"/><path d="M15 11h3l3 3v2h-6Z"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="17" cy="18.5" r="1.5"/>',
        'customer' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-6 7-6s7 2.1 7 6"/>',
        'purchase-order' => '<path d="M5 3h11l3 3v15H5Z"/><path d="M9 9h7M9 13h7M9 17h4"/>',
        'sales-order' => '<path d="M4 4h2l2 12h11l2-8H8"/><circle cx="9" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
        'report' => '<path d="M4 20V10M11 20V4M18 20v-7"/><path d="M2 20h20"/>',
        'users' => '<circle cx="9" cy="7" r="3"/><path d="M2.5 19c0-3.3 2.9-5.5 6.5-5.5s6.5 2.2 6.5 5.5"/><circle cx="17.5" cy="8" r="2.3"/><path d="M15 13.7c2.6.4 4.5 2.2 4.5 5.3"/>',
    ];

    $path = $paths[$key] ?? '<circle cx="12" cy="12" r="8"/>';

    return '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Ordina Inventory & Order Management', ENT_QUOTES) ?></title>
    <link rel="stylesheet" href="/<?= htmlspecialchars($assetVersion('assets/css/app.css'), ENT_QUOTES) ?>">
</head>
<body>
    <?php if ($navUser !== null): ?>
        <div class="app-shell">
            <button type="button" class="sidebar-overlay" id="sidebar-overlay" aria-label="Tutup menu" tabindex="-1"></button>

            <aside class="sidebar" id="app-sidebar">
                <div class="sidebar-brand">
                    <a href="/dashboard" class="app-brand">Ordina</a>
                    <button type="button" class="sidebar-close" id="sidebar-close" aria-label="Tutup menu">&times;</button>
                </div>

                <nav class="sidebar-nav" aria-label="Menu utama">
                    <?php foreach (MenuRegistry::forRole(Role::from($navUser['role'])) as $group): ?>
                        <?php if ($group['label'] !== null): ?>
                            <p class="nav-group-label"><?= htmlspecialchars($group['label'], ENT_QUOTES) ?></p>
                        <?php endif; ?>
                        <?php foreach ($group['items'] as $menu): ?>
                            <?php $isActive = $currentPath === $menu['route'] || str_starts_with($currentPath, $menu['route'] . '/'); ?>
                            <a href="<?= htmlspecialchars($menu['route'], ENT_QUOTES) ?>" class="nav-link" <?= $isActive ? 'aria-current="page"' : '' ?>>
                                <?= $navIcon($menu['icon']) ?>
                                <span><?= htmlspecialchars($menu['label'], ENT_QUOTES) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </nav>

                <div class="sidebar-user">
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name"><?= htmlspecialchars($navUser['name'], ENT_QUOTES) ?></span>
                        <span class="role-badge"><?= htmlspecialchars($navUser['role'], ENT_QUOTES) ?></span>
                    </div>
                    <form method="post" action="/logout">
                        <button type="submit" class="btn-secondary">Logout</button>
                    </form>
                </div>
            </aside>

            <div class="app-main">
                <header class="app-topbar">
                    <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="app-sidebar">
                        <span class="nav-toggle-bars" aria-hidden="true"></span>
                        Menu
                    </button>
                    <a href="/dashboard" class="app-brand app-brand-mobile">Ordina</a>
                </header>

                <div class="container">
                    <?= $content ?? '' ?>
                </div>
            </div>
        </div>
    <?php elseif (!empty($bleed)): ?>
        <?= $content ?? '' ?>
    <?php else: ?>
        <div class="container">
            <?= $content ?? '' ?>
        </div>
    <?php endif; ?>

    <script src="/<?= htmlspecialchars($assetVersion('assets/js/app.js'), ENT_QUOTES) ?>"></script>
</body>
</html>
