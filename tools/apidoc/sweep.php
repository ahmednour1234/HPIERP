<?php
/**
 * Hit every GET endpoint on v1 and v2 and report what came back.
 *
 * GETs are called with no parameters, so a 4xx for a missing id is expected
 * and recorded, not treated as a failure. Only 5xx and transport errors are
 * failures — those are the ones that mean the endpoint is broken.
 *
 * Usage: php tools/apidoc/sweep.php <token> [base]
 */
$token = $argv[1] ?? '';
$base  = rtrim($argv[2] ?? 'http://127.0.0.1:8000', '/');

$routes = json_decode(file_get_contents(__DIR__ . '/../../storage/app/r.json'), true);

// Endpoints that mutate or stream rather than answering JSON.
$skip = [
    'api/v1/stocks/confirm', 'api/v2/stocks/confirm',
    'api/v1/product/export', 'api/v1/transaction/transfer/export',
    'api/v1/product/download/excel/sample', 'api/v1/product/barcode/generate',
];

$ids = ['id' => '800300', 'seller_id' => '800050', 'type' => '4', 'customer_id' => '800300'];

$targets = [];
foreach ($routes as $r) {
    $uri = $r['uri'];
    if (!str_starts_with($uri, 'api/v1/') && !str_starts_with($uri, 'api/v2/')) continue;
    if (in_array($uri, $skip, true)) continue;
    if (!in_array('GET', explode('|', $r['method']), true)) continue;

    $path = preg_replace_callback('/\{([a-zA-Z_]+)\??\}/', fn($m) => $ids[$m[1]] ?? '1', $uri);
    $targets[$path] = $uri;
}
ksort($targets);

$rows = [];
foreach ($targets as $path => $uri) {
    $ch = curl_init("$base/$path");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', "Authorization: Bearer $token"],
    ]);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    $note = '';
    if ($err) {
        $note = 'TRANSPORT: ' . $err;
    } elseif ($status >= 500) {
        $decoded = json_decode($body, true);
        $note = $decoded['message'] ?? strip_tags(substr($body, 0, 140));
    }

    // The API allows 60 requests/minute; pace the sweep so a 429 never
    // masks an endpoint we have not actually exercised.
    if ($status === 429) {
        sleep(61);
        $ch = curl_init("$base/$path");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => ['Accept: application/json', "Authorization: Bearer $token"]]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $note = $status >= 500 ? (json_decode($body, true)['message'] ?? substr(strip_tags($body),0,140)) : '';
    }

    $rows[] = ['path' => $path, 'status' => $status ?: 0, 'note' => trim(preg_replace('/\s+/', ' ', $note))];
}

$byStatus = [];
foreach ($rows as $r) $byStatus[$r['status']] = ($byStatus[$r['status']] ?? 0) + 1;
ksort($byStatus);

echo "swept " . count($rows) . " GET endpoints\n\n";
foreach ($byStatus as $s => $n) printf("  %-4s %d\n", $s, $n);

$bad = array_filter($rows, fn($r) => $r['status'] >= 500 || $r['status'] === 0);
echo "\nfailures: " . count($bad) . "\n";
foreach ($bad as $r) printf("  %-3d %-46s %s\n", $r['status'], $r['path'], substr($r['note'], 0, 90));

file_put_contents(__DIR__ . '/../../storage/app/sweep.json', json_encode($rows, JSON_PRETTY_PRINT));
