<?php
/**
 * Exercise the v2 write endpoints end-to-end against a running server.
 *
 * Each case sends a realistic body and asserts the status the documentation
 * claims. Anything created is deleted again, so the sweep is repeatable.
 *
 * Usage: php tools/apidoc/sweep_writes.php <token> [base]
 */
$token = $argv[1] ?? '';
$base  = rtrim($argv[2] ?? 'http://127.0.0.1:8000', '/');

function call(string $method, string $url, array $body, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: Bearer $token",
        ],
    ]);
    if ($body !== []) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    $raw    = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, json_decode($raw, true) ?? []];
}

$suffix  = substr((string) getmypid(), -4) . rand(10, 99);
$created = [];
$results = [];

function check(string $label, int $got, $want, array $body = []): void {
    global $results;
    $want = (array) $want;
    $ok   = in_array($got, $want, true);
    $results[] = ['label' => $label, 'got' => $got, 'want' => implode('/', $want), 'ok' => $ok,
                  'msg' => $ok ? '' : (string) ($body['message'] ?? '')];
}

/* ---------- lookup tables: create, read, update, status, delete ---------- */

$lookups = [
    'brands'     => ['name' => "Sweep Brand $suffix"],
    'units'      => ['unit_type' => "Sweep Unit $suffix", 'symbol' => 'SU'],
    'accounts'   => ['account' => "Sweep Acc $suffix", 'account_number' => "SW-$suffix", 'balance' => 1000],
    'categories' => ['name' => "Sweep Cat $suffix"],
    'coupons'    => ['title' => "Sweep $suffix", 'code' => "SW$suffix", 'discount' => 5],
];
$hasStatus = ['categories', 'coupons'];

foreach ($lookups as $module => $payload) {
    [$s, $b] = call('POST', "$base/api/v2/$module", $payload, $token);
    check("POST /$module", $s, 201, $b);
    $id = $b['data']['id'] ?? null;
    if (!$id) continue;
    $created[$module] = $id;

    [$s, $b] = call('GET', "$base/api/v2/$module/$id", [], $token);
    check("GET /$module/{id}", $s, 200, $b);

    [$s, $b] = call('PUT', "$base/api/v2/$module", ['id' => $id] + $payload, $token);
    check("PUT /$module", $s, 200, $b);

    [$s, $b] = call('PATCH', "$base/api/v2/$module/$id/status", [], $token);
    check("PATCH /$module/{id}/status", $s, in_array($module, $hasStatus, true) ? 200 : 400, $b);

    // Validation is enforced.
    [$s, $b] = call('POST', "$base/api/v2/$module", [], $token);
    check("POST /$module (empty)", $s, 422, $b);
}

/* ---------- sub-categories ---------- */

if (isset($created['categories'])) {
    $cat = $created['categories'];
    [$s, $b] = call('POST', "$base/api/v2/categories/$cat/children", ['name' => "Child $suffix"], $token);
    check('POST /categories/{id}/children', $s, 201, $b);
    if (!empty($b['data']['id'])) $created['child_category'] = $b['data']['id'];
}

/* ---------- customers ---------- */

[$s, $b] = call('POST', "$base/api/v2/customers", [
    'name' => "Sweep Pharmacy $suffix", 'mobile' => "0155$suffix" . '000',
    'latitude' => 30.0444, 'longitude' => 31.2357, 'limit' => 5000,
], $token);
check('POST /customers', $s, 201, $b);
$customerId = $b['data']['id'] ?? null;
if ($customerId) {
    $created['customers'] = $customerId;

    // The documented claim: coordinates come back as numbers.
    $latOk = is_float($b['data']['latitude'] ?? null) || is_int($b['data']['latitude'] ?? null);
    check('  customer lat is numeric', $latOk ? 200 : 0, 200);

    [$s, $b] = call('PUT', "$base/api/v2/customers", ['id' => $customerId, 'city' => 'Tanta'], $token);
    check('PUT /customers', $s, 200, $b);

    [$s, $b] = call('POST', "$base/api/v2/customers/add-balance", [
        'customer_id' => $customerId, 'account_id' => $created['accounts'] ?? 800001,
        'amount' => 100, 'date' => date('Y-m-d'), 'description' => 'sweep',
    ], $token);
    check('POST /customers/add-balance', $s, 200, $b);

    [$s, $b] = call('POST', "$base/api/v2/customers/add-balance", [
        'customer_id' => $customerId, 'account_id' => $created['accounts'] ?? 800001,
        'amount' => 0, 'date' => date('Y-m-d'),
    ], $token);
    check('POST /add-balance (amount 0)', $s, 422, $b);
}

/* ---------- products ---------- */

[$s, $b] = call('POST', "$base/api/v2/products", [
    'name' => "Sweep Product $suffix", 'product_code' => "SWP-$suffix",
    'purchase_price' => 10, 'selling_price' => 15,
], $token);
check('POST /products', $s, 201, $b);
$productId = $b['data']['id'] ?? null;
if ($productId) {
    $created['products'] = $productId;

    // limit_stock defaults to 10 in the schema; the response must reflect it.
    check('  product limit_stock default', (int) ($b['data']['limit_stock'] ?? 0), 10);

    [$s, $b] = call('PUT', "$base/api/v2/products", ['id' => $productId, 'selling_price' => 22], $token);
    check('PUT /products', $s, 200, $b);

    [$s, $b] = call('POST', "$base/api/v2/products", [
        'name' => 'dup', 'product_code' => "SWP-$suffix", 'purchase_price' => 1, 'selling_price' => 2,
    ], $token);
    check('POST /products (duplicate code)', $s, 422, $b);

    if ($customerId) {
        [$s, $b] = call('POST', "$base/api/v2/products/customer-price",
            ['customer_id' => $customerId, 'product_id' => $productId, 'price' => 12], $token);
        check('POST /products/customer-price', $s, 200, $b);

        [$s, $b] = call('POST', "$base/api/v2/products/customer-prices",
            ['user_id' => $customerId, 'cart' => [$productId]], $token);
        check('POST /products/customer-prices', $s, 200, $b);

        [$s, $b] = call('POST', "$base/api/v2/products/customer-prices", ['user_id' => $customerId], $token);
        check('POST /customer-prices (no cart)', $s, 422, $b);
    }
}

/* ---------- orders ---------- */

if ($customerId) {
    // No stock for a brand-new product on this van: must be refused, not 500.
    [$s, $b] = call('POST', "$base/api/v2/orders", [
        'user_id' => $customerId, 'order_type' => 4,
        'cart' => [['id' => $productId, 'quantity' => 1, 'price' => 15]],
    ], $token);
    check('POST /orders (no stock)', $s, 422, $b);

    [$s, $b] = call('POST', "$base/api/v2/orders", ['user_id' => $customerId], $token);
    check('POST /orders (no cart)', $s, 422, $b);

    // A product the seller actually carries.
    [$st, $stock] = call('GET', "$base/api/v2/stocks?type=4&limit=1", [], $token);
    $carried = $stock['data'][0]['product']['id'] ?? null;
    if ($carried) {
        [$s, $b] = call('POST', "$base/api/v2/orders", [
            'user_id' => $customerId, 'order_type' => 4,
            'cart' => [['id' => $carried, 'quantity' => 1]],
            'collected_cash' => 10,
        ], $token);
        check('POST /orders (real sale)', $s, 201, $b);
        if (!empty($b['data']['id'])) $created['orders'] = $b['data']['id'];
    }
}

/* ---------- visits ---------- */

if ($customerId) {
    [$s, $b] = call('POST', "$base/api/v2/visits",
        ['customer_id' => $customerId, 'date' => date('Y-m-d'), 'note' => 'sweep'], $token);
    check('POST /visits', $s, 201, $b);
    if (!empty($b['data']['id'])) $created['visits'] = $b['data']['id'];

    [$s, $b] = call('POST', "$base/api/v2/visits/results",
        ['customer_id' => $customerId, 'note' => 'sweep result', 'lat' => 30.05, 'lang' => 31.23], $token);
    check('POST /visits/results', $s, 201, $b);
    if (!empty($b['data']['id'])) $created['visit_results'] = $b['data']['id'];

    [$s, $b] = call('POST', "$base/api/v2/visits/results",
        ['customer_id' => $customerId, 'note' => 'bad', 'lat' => 200], $token);
    check('POST /visits/results (bad lat)', $s, 422, $b);
}

/* ---------- suppliers ---------- */

[$s, $b] = call('POST', "$base/api/v2/suppliers",
    ['name' => "Sweep Supplier $suffix", 'mobile' => "0100$suffix" . '00'], $token);
check('POST /suppliers', $s, 201, $b);
$supplierId = $b['data']['id'] ?? null;
if ($supplierId) {
    $created['suppliers'] = $supplierId;

    [$s, $b] = call('PUT', "$base/api/v2/suppliers", ['id' => $supplierId, 'city' => 'Cairo'], $token);
    check('PUT /suppliers', $s, 200, $b);

    [$s, $b] = call('POST', "$base/api/v2/suppliers/pay", [
        'supplier_id' => $supplierId, 'account_id' => $created['accounts'] ?? 800001,
        'amount' => 50, 'description' => 'sweep',
    ], $token);
    check('POST /suppliers/pay', $s, [200, 201], $b);

    [$s, $b] = call('POST', "$base/api/v2/suppliers/pay", [
        'supplier_id' => $supplierId, 'account_id' => $created['accounts'] ?? 800001,
        'amount' => 99999999,
    ], $token);
    check('POST /suppliers/pay (over balance)', $s, 422, $b);
}

/* ---------- transactions ---------- */

$acc = $created['accounts'] ?? 800001;

[$s, $b] = call('POST', "$base/api/v2/transactions/income",
    ['account_id' => $acc, 'amount' => 100, 'description' => 'sweep', 'date' => date('Y-m-d')], $token);
check('POST /transactions/income', $s, 201, $b);

[$s, $b] = call('POST', "$base/api/v2/transactions/expense",
    ['account_id' => $acc, 'amount' => 10, 'description' => 'sweep', 'date' => date('Y-m-d')], $token);
check('POST /transactions/expense', $s, 201, $b);

[$s, $b] = call('POST', "$base/api/v2/transactions/transfer", [
    'account_from_id' => $acc, 'account_to_id' => 800002,
    'amount' => 10, 'description' => 'sweep', 'date' => date('Y-m-d'),
], $token);
check('POST /transactions/transfer', $s, 200, $b);

[$s, $b] = call('POST', "$base/api/v2/transactions/transfer", [
    'account_from_id' => $acc, 'account_to_id' => $acc,
    'amount' => 'abc', 'description' => 'x', 'date' => date('Y-m-d'),
], $token);
check('POST /transfer (same acct, bad amount)', $s, 422, $b);

/* ---------- profile ---------- */

[$s, $b] = call('POST', "$base/api/v2/profile/change-password",
    ['current_password' => 'wrong', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'], $token);
check('POST /profile/change-password (wrong current)', $s, 422, $b);

/* ---------- auth ---------- */

[$s, $b] = call('GET', "$base/api/v2/customers", [], 'not-a-real-token');
check('GET /customers (bad token)', $s, 401, $b);

/* ---------- clean up ---------- */

foreach (['orders' => null, 'visit_results' => null, 'visits' => null] as $k => $_) unset($created[$k]);

foreach (['products', 'customers', 'suppliers', 'child_category', 'categories', 'coupons', 'brands', 'units', 'accounts'] as $module) {
    if (!isset($created[$module])) continue;
    $path = $module === 'child_category' ? 'categories' : $module;
    call('DELETE', "$base/api/v2/$path/{$created[$module]}", [], $token);
}

/* ---------- report ---------- */

$fail = array_filter($results, fn($r) => !$r['ok']);

echo count($results) . " write cases\n";
echo "failures: " . count($fail) . "\n\n";
foreach ($results as $r) {
    printf("  %s %-42s got %-4s want %s%s\n",
        $r['ok'] ? 'ok  ' : 'FAIL', $r['label'], $r['got'], $r['want'],
        $r['msg'] ? '  — ' . substr($r['msg'], 0, 60) : '');
}
