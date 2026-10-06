<?php

declare(strict_types=1);

// HTTP smoke test for the running app - the Auto-HTTP cases RPT-* and ERR-*
// in docs/testing/test-scenarios.md. Logs in as each role over real HTTP and
// checks status codes, redirects, CSV row counts and API JSON.
//
//   docker compose exec web php scripts/smoke-test.php           (inside the container)
//   php scripts/smoke-test.php http://localhost:8080             (from the host)
//
// Read-only: it logs in/out, downloads reports and makes one approve attempt
// that must be refused - no data is changed. The RPT-* numbers come from
// database/schema-and-seed.sql, so they only hold on a fresh seed
// (`docker compose down -v && docker compose up -d`).

const PASSWORD = 'admin123';
const RANGE_A = 'from=2026-08-01&to=2026-08-15';
const RANGE_B = 'from=2026-08-16&to=2026-09-30';

$base = rtrim($argv[1] ?? 'http://localhost', '/');
$results = ['pass' => 0, 'fail' => 0];

/**
 * @param array<string, string> $form
 * @return array{status: int, location: string, body: string}
 */
function request(string $base, string $method, string $path, ?string $cookieJar = null, array $form = []): array
{
    $ch = curl_init($base . $path);
    if ($ch === false) {
        throw new RuntimeException('curl_init failed');
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
    ]);

    if ($cookieJar !== null) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form));
    }

    $raw = curl_exec($ch);
    if (!is_string($raw)) {
        throw new RuntimeException("Cannot reach {$base}{$path}: " . curl_error($ch));
    }

    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    unset($ch); // writes the cookie jar

    $headers = substr($raw, 0, $headerSize);
    $location = preg_match('/^Location:\s*(\S+)/mi', $headers, $m) === 1 ? $m[1] : '';

    return ['status' => $status, 'location' => $location, 'body' => substr($raw, $headerSize)];
}

/** Logs in and returns the cookie-jar file holding that session. */
function login(string $base, string $email, string $password = PASSWORD): string
{
    $jar = (string) tempnam(sys_get_temp_dir(), 'ordina-smoke-');
    request($base, 'POST', '/login', $jar, ['email' => $email, 'password' => $password]);

    return $jar;
}

/** @return list<list<string>> data rows only (header dropped) */
function csvRows(string $body): array
{
    $stream = fopen('php://memory', 'r+');
    if ($stream === false) {
        throw new RuntimeException('Cannot open memory stream');
    }
    fwrite($stream, $body);
    rewind($stream);

    $rows = [];
    while (($row = fgetcsv($stream, null, ',', '"', '\\')) !== false) {
        if ($row !== [null]) {
            $rows[] = array_map('strval', $row);
        }
    }
    fclose($stream);

    return array_slice($rows, 1);
}

/**
 * @param list<list<string>> $rows
 * @return array<string, int>
 */
function countBy(array $rows, int $column): array
{
    $counts = [];
    foreach ($rows as $row) {
        $counts[$row[$column]] = ($counts[$row[$column]] ?? 0) + 1;
    }
    ksort($counts);

    return $counts;
}

/** @param array<string, int> $counts */
function describe(array $counts): string
{
    return implode(', ', array_map(static fn (string $k, int $v): string => "{$k} {$v}", array_keys($counts), $counts));
}

/** @param array{pass: int, fail: int} $results */
function check(array &$results, string $id, string $label, bool $ok, string $detail): void
{
    $results[$ok ? 'pass' : 'fail']++;
    printf("%s  %-7s %s -> %s\n", $ok ? 'PASS' : 'FAIL', $id, $label, $detail);
}

echo "Ordina smoke test -> {$base}\n\n";

try {
    if (request($base, 'GET', '/login')['status'] !== 200) {
        fwrite(STDERR, "The app is not answering at {$base}/login. Is `docker compose up -d` running?\n");
        exit(2);
    }

    $admin = login($base, 'admin@ordina.test');
    $sales1 = login($base, 'sales1@ordina.test');
    $sales2 = login($base, 'sales2@ordina.test');
    $warehouse = login($base, 'wh1@ordina.test');

    // ---- REPORT-01 --------------------------------------------------------
    $r = request($base, 'GET', '/reports/stock-ledger.csv?' . RANGE_A, $admin);
    $rows = csvRows($r['body']);
    $byType = countBy($rows, 4);
    check($results, 'RPT-01', 'Admin stock-ledger, range A', $r['status'] === 200 && count($rows) === 65
        && $byType === ['Adjustment' => 58, 'Issue' => 3, 'Receipt' => 4], count($rows) . ' rows (' . describe($byType) . ')');

    $r = request($base, 'GET', '/reports/stock-ledger.csv?' . RANGE_B, $admin);
    $rows = csvRows($r['body']);
    $byType = countBy($rows, 4);
    check($results, 'RPT-02', 'Admin stock-ledger, range B', $r['status'] === 200 && count($rows) === 5
        && $byType === ['Issue' => 2, 'Receipt' => 3], count($rows) . ' rows (' . describe($byType) . ')');

    $r = request($base, 'GET', '/reports/orders.csv?' . RANGE_A, $admin);
    $rows = csvRows($r['body']);
    $byType = countBy($rows, 0);
    check($results, 'RPT-03', 'Admin orders, range A', $r['status'] === 200 && $byType === ['PO' => 6, 'SO' => 4],
        count($rows) . ' rows (' . describe($byType) . ')');

    $r = request($base, 'GET', '/reports/orders.csv?' . RANGE_B, $admin);
    $rows = csvRows($r['body']);
    $byType = countBy($rows, 0);
    check($results, 'RPT-04', 'Admin orders, range B', $r['status'] === 200 && $byType === ['PO' => 7, 'SO' => 9],
        count($rows) . ' rows (' . describe($byType) . ')');

    $r = request($base, 'GET', '/reports/orders.csv?' . RANGE_B, $sales1);
    $rows = csvRows($r['body']);
    $ids = array_column($rows, 1);
    check($results, 'RPT-05', 'Sales Satu orders, range B', $r['status'] === 200 && $ids === ['5', '7', '10', '11', '13']
        && countBy($rows, 0) === ['SO' => 5], 'SO ' . implode(', ', $ids) . ', types: ' . describe(countBy($rows, 0)));

    $r = request($base, 'GET', '/reports/orders.csv?' . RANGE_B, $sales2);
    $rows = csvRows($r['body']);
    $ids = array_column($rows, 1);
    check($results, 'RPT-06', 'Sales Dua orders, range B', $r['status'] === 200 && $ids === ['6', '8', '9', '12']
        && countBy($rows, 0) === ['SO' => 4], 'SO ' . implode(', ', $ids) . ', types: ' . describe(countBy($rows, 0)));

    $r = request($base, 'GET', '/reports/stock-ledger.csv?from=2026-09-30&to=2026-08-16', $admin);
    check($results, 'RPT-07', 'Reversed dates are swapped', $r['status'] === 200 && count(csvRows($r['body'])) === 5,
        count(csvRows($r['body'])) . ' rows');

    $r = request($base, 'GET', '/reports/orders.csv?' . RANGE_B, $warehouse);
    check($results, 'RPT-09', 'Warehouse Staff cannot download orders', $r['status'] === 403, "HTTP {$r['status']}");

    $r = request($base, 'GET', '/reports/stock-ledger.csv?' . RANGE_B, $sales1);
    check($results, 'RPT-10', 'Sales cannot download stock ledger', $r['status'] === 403, "HTTP {$r['status']}");

    // ---- AUTH / API / ERR-01 ----------------------------------------------
    $r = request($base, 'GET', '/dashboard');
    check($results, 'ERR-01', 'No session -> login', $r['status'] === 302 && $r['location'] === '/login',
        "HTTP {$r['status']} -> {$r['location']}");

    $jar = (string) tempnam(sys_get_temp_dir(), 'ordina-smoke-');
    $r = request($base, 'POST', '/login', $jar, ['email' => 'admin@ordina.test', 'password' => 'salah123']);
    $page = request($base, 'GET', '/login', $jar);
    check($results, 'ERR-02', 'Wrong password is refused', $r['location'] === '/login'
        && str_contains($page['body'], 'Email atau password salah.'), "HTTP {$r['status']} -> {$r['location']}, generic message shown");
    @unlink($jar);

    $jar = login($base, 'admin@ordina.test');
    request($base, 'POST', '/logout', $jar);
    $r = request($base, 'GET', '/dashboard', $jar);
    check($results, 'ERR-04', 'After logout -> login', $r['status'] === 302 && $r['location'] === '/login',
        "HTTP {$r['status']} -> {$r['location']}");
    @unlink($jar);

    $r = request($base, 'GET', '/api/products/SKU-0001/availability');
    check($results, 'ERR-05', 'API without session', $r['status'] === 401
        && (json_decode($r['body'], true)['error'] ?? null) === 'Unauthenticated', "HTTP {$r['status']} {$r['body']}");

    $r = request($base, 'GET', '/api/products/SKU-9999/availability', $admin);
    check($results, 'ERR-06', 'API unknown SKU', $r['status'] === 404
        && (json_decode($r['body'], true)['error'] ?? null) === 'Product not found', "HTTP {$r['status']} {$r['body']}");

    $r = request($base, 'GET', '/api/products/SKU-0001/availability', $admin);
    $json = json_decode($r['body'], true);
    $stock = is_array($json) ? array_column($json['warehouses'] ?? [], 'quantity', 'warehouse_name') : [];
    ksort($stock);
    check($results, 'ERR-07', 'API known SKU', $r['status'] === 200
        && $stock == ['Warehouse Jakarta' => 15, 'Warehouse Surabaya' => 50], "HTTP {$r['status']} " . json_encode($stock));

    $r = request($base, 'POST', '/sales-orders/7/approve', $sales1);
    $after = request($base, 'GET', '/sales-orders/7', $admin);
    check($results, 'ERR-08', 'Sales cannot approve own SO-7', $r['status'] === 403
        && str_contains($after['body'], 'Pending Approval'), "HTTP {$r['status']}, SO-7 still Pending Approval");

    $r = request($base, 'GET', '/users', $warehouse);
    check($results, 'ERR-09', 'Warehouse Staff cannot open /users', $r['status'] === 403, "HTTP {$r['status']}");

    $r = request($base, 'GET', '/halaman-tidak-ada', $admin);
    $leaks = preg_match('/Stack trace|\.php on line|\/var\/www/i', $r['body']) === 1;
    check($results, 'ERR-10', 'Unknown page -> 404 without internals', $r['status'] === 404 && !$leaks,
        "HTTP {$r['status']}" . ($leaks ? ', LEAKS internals' : ', no stack trace or path'));

    foreach ([$admin, $sales1, $sales2, $warehouse] as $file) {
        @unlink($file);
    }
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(2);
}

$total = $results['pass'] + $results['fail'];
echo "\n{$total} checks: {$results['pass']} passed, {$results['fail']} failed\n";

if ($results['fail'] > 0) {
    echo "If only RPT-* failed, the data probably changed since seeding: run `docker compose down -v && docker compose up -d` and retry.\n";
    exit(1);
}
