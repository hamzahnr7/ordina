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
        <header class="app-header" id="app-header">
            <div class="app-header-bar">
                <a href="/dashboard" class="app-brand">Ordina</a>

                <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="app-nav">
                    Menu
                </button>

                <nav class="app-nav" id="app-nav" aria-label="Menu utama">
                    <?php foreach (MenuRegistry::forRole(Role::from($navUser['role'])) as $menu): ?>
                        <?php $isActive = $currentPath === $menu['route'] || str_starts_with($currentPath, $menu['route'] . '/'); ?>
                        <a href="<?= htmlspecialchars($menu['route'], ENT_QUOTES) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <?= htmlspecialchars($menu['label'], ENT_QUOTES) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="app-user">
                    <span><?= htmlspecialchars($navUser['name'], ENT_QUOTES) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($navUser['role'], ENT_QUOTES) ?></span>
                    <form method="post" action="/logout">
                        <button type="submit">Logout</button>
                    </form>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <div class="container">
        <?= $content ?? '' ?>
    </div>

    <script src="/<?= htmlspecialchars($assetVersion('assets/js/app.js'), ENT_QUOTES) ?>"></script>
</body>
</html>
