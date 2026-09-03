<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Ordina Inventory & Order Management', ENT_QUOTES) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="container">
        <?= $content ?? '' ?>
    </div>
    <script src="/assets/js/app.js"></script>
</body>
</html>
