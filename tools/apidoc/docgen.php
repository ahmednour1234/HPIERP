<?php
/**
 * Build the API reference from the resolved route table + extracted
 * controller parameters. Everything here comes from the app itself —
 * nothing is hand-written per endpoint.
 */
$routes = json_decode(file_get_contents($argv[1]), true);
$params = json_decode(file_get_contents($argv[2]), true);
$outFile = $argv[3];

/* ---- keep only the v1 API surface ---- */
$api = array();
foreach ($routes as $r) {
    if (strpos($r['uri'], 'api/v1/') !== 0 && strpos($r['uri'], 'api/v2/') !== 0) continue;
    if (strpos($r['action'], 'Closure') === 0) continue;
    $api[] = $r;
}

/* ---- group by prefix segment ---- */
function group_of($uri) {
    if (strpos($uri, 'api/v2/') === 0) {
        $seg = explode('/', substr($uri, strlen('api/v2/')));
        return 'v2 - ' . ucfirst($seg[0]);
    }
    $p = substr($uri, strlen('api/v1/'));
    $seg = explode('/', $p);
    $first = $seg[0];

    $map = array(
        'login' => 'Authentication', 'confirm_login' => 'Authentication',
        'change-password' => 'Authentication', 'profile' => 'Authentication',
        'config' => 'Authentication', 'dataadmin' => 'Authentication',
        'update' => 'Authentication', 'storage' => 'Authentication',
        'dashboard' => 'Dashboard',
        'category' => 'Categories', 'sub' => 'Categories',
        'brand' => 'Brands', 'unit' => 'Units', 'coupon' => 'Coupons',
        'customer' => 'Customers', 'account' => 'Accounts',
        'income' => 'Income', 'supplier' => 'Suppliers',
        'transaction' => 'Transactions & Expenses',
        'transactionseller' => 'Seller Transactions',
        'add' => 'Cart', 'remove' => 'Cart',
        'pos' => 'POS & Orders', 'product' => 'Products',
        'stocks' => 'Stock',
        'visitor' => 'Visits & Sellers', 'seller' => 'Visits & Sellers',
        'storeseller' => 'Visits & Sellers', 'admin' => 'Visits & Sellers',
        'develop-seller' => 'Visits & Sellers', 'develop' => 'Developer / Sellers',
        'courses' => 'Developer / Sellers', 'salary' => 'Developer / Sellers',
        'uploadcertificates' => 'Developer / Sellers',
        'attendance' => 'Attendance',
    );
    return isset($map[$first]) ? $map[$first] : ucfirst($first);
}

$groups = array();
foreach ($api as $r) {
    $groups[group_of($r['uri'])][] = $r;
}
$order = array(
    'Authentication', 'Dashboard', 'Categories', 'Brands', 'Units', 'Products',
    'Stock', 'POS & Orders', 'Cart', 'Customers', 'Suppliers', 'Coupons',
    'Accounts', 'Income', 'Transactions & Expenses', 'Seller Transactions',
    'Visits & Sellers', 'Developer / Sellers', 'Attendance',
);
$sorted = array();
foreach ($order as $g) if (isset($groups[$g])) { $sorted[$g] = $groups[$g]; unset($groups[$g]); }
foreach ($groups as $g => $v) $sorted[$g] = $v;

/* ---- helpers ---- */
function methods_of($m) {
    $parts = array_filter(explode('|', $m), function ($x) { return $x !== 'HEAD'; });
    return implode(', ', $parts);
}
function primary_method($m) {
    $parts = array_filter(explode('|', $m), function ($x) { return $x !== 'HEAD'; });
    return reset($parts);
}
function is_auth($r) {
    foreach ((array) $r['middleware'] as $mw) {
        if (strpos($mw, 'admin-api') !== false) return true;
    }
    return false;
}
function short_action($a) {
    $a = str_replace('App\\Http\\Controllers\\Api\\V1\\', '', $a);
    return $a;
}
function param_key($a) {
    $a = short_action($a);
    return $a;
}
function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/* rule -> readable */
function rule_bits($rule) {
    $req  = strpos($rule, 'required') !== false;
    $type = 'string';
    if (preg_match('/\b(integer|numeric)\b/', $rule)) $type = 'number';
    elseif (strpos($rule, 'boolean') !== false)       $type = 'boolean';
    elseif (strpos($rule, 'array') !== false)         $type = 'array';
    elseif (strpos($rule, 'file') !== false || strpos($rule, 'image') !== false) $type = 'file';
    elseif (strpos($rule, 'date') !== false)          $type = 'date';
    elseif (strpos($rule, 'email') !== false)         $type = 'email';
    return array($req, $type);
}

$totalAuth = 0; $totalPub = 0;
foreach ($api as $r) { if (is_auth($r)) $totalAuth++; else $totalPub++; }

/* ---- render ---- */
$H = array();
$H[] = '<!-- generated -->';

ob_start();
?>
<title>HPI ERP API Reference</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">
<style>
  :root{
    --bg:#faf9f7; --panel:#ffffff; --sunk:#f2f0ec;
    --ink:#1c1b19; --ink-2:#3d3a35; --muted:#6b6862; --line:#e2ded7;
    --accent:#0f5c4a; --accent-soft:#e4efe9;
    --get:#0f5c4a;  --get-bg:#e4efe9;
    --post:#8a4b16; --post-bg:#f7ebe0;
    --put:#1f4f7a;  --put-bg:#e5eef6;
    --del:#95271f;  --del-bg:#f7e5e3;
    --flag:#7a5410; --flag-bg:#f7eeda;
    --sans:"IBM Plex Sans",ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  }
  @media (prefers-color-scheme:dark){
    :root:not([data-theme="light"]){
      --bg:#15161a; --panel:#1c1e23; --sunk:#23262c;
      --ink:#eceae5; --ink-2:#c9c6bf; --muted:#96938c; --line:#31343b;
      --accent:#6fc9ad; --accent-soft:#16302a;
      --get:#6fc9ad;  --get-bg:#16302a;
      --post:#dda06a; --post-bg:#33261a;
      --put:#84b4dd;  --put-bg:#1a2833;
      --del:#e3897f;  --del-bg:#33201d;
      --flag:#d7b475; --flag-bg:#31281a;
    }
  }
  :root[data-theme="dark"]{
    --bg:#15161a; --panel:#1c1e23; --sunk:#23262c;
    --ink:#eceae5; --ink-2:#c9c6bf; --muted:#96938c; --line:#31343b;
    --accent:#6fc9ad; --accent-soft:#16302a;
    --get:#6fc9ad;  --get-bg:#16302a;
    --post:#dda06a; --post-bg:#33261a;
    --put:#84b4dd;  --put-bg:#1a2833;
    --del:#e3897f;  --del-bg:#33201d;
    --flag:#d7b475; --flag-bg:#31281a;
  }
  *{box-sizing:border-box}
  html{-webkit-text-size-adjust:100%}
  body{margin:0;background:var(--bg);color:var(--ink);font-family:var(--sans);
    font-size:15px;line-height:1.62;font-feature-settings:"kern","liga"}
  .wrap{max-width:1060px;margin:0 auto;padding:44px 22px 90px}

  header.top{padding-bottom:26px;border-bottom:1px solid var(--line)}
  .eyebrow{font-family:var(--mono);font-size:11.5px;letter-spacing:.14em;
    text-transform:uppercase;color:var(--accent);margin:0 0 12px}
  h1{font-size:clamp(28px,4.4vw,40px);line-height:1.1;margin:0 0 10px;
    font-weight:600;letter-spacing:-.022em;text-wrap:balance}
  .sub{color:var(--muted);margin:0;max-width:62ch;font-size:15.5px}

  .facts{display:flex;flex-wrap:wrap;gap:0;margin:26px 0 0;
    border:1px solid var(--line);border-radius:10px;overflow:hidden;background:var(--panel)}
  .fact{flex:1 1 130px;padding:13px 16px;border-right:1px solid var(--line)}
  .fact:last-child{border-right:0}
  .fact b{display:block;font-size:23px;line-height:1.2;font-weight:600;
    font-variant-numeric:tabular-nums;letter-spacing:-.01em}
  .fact span{color:var(--muted);font-size:11px;text-transform:uppercase;
    letter-spacing:.09em;font-family:var(--mono)}

  .toc{display:flex;flex-wrap:wrap;gap:6px;margin-top:22px}
  .toc a{background:var(--panel);border:1px solid var(--line);border-radius:6px;
    padding:5px 10px;font-size:12.5px;text-decoration:none;color:var(--ink-2);
    display:inline-flex;gap:7px;align-items:baseline;transition:border-color .12s,color .12s}
  .toc a:hover,.toc a:focus-visible{border-color:var(--accent);color:var(--accent)}
  .toc a span{color:var(--muted);font-family:var(--mono);font-size:11px;
    font-variant-numeric:tabular-nums}

  .card{background:var(--panel);border:1px solid var(--line);border-radius:10px;
    padding:19px 21px;margin:22px 0}
  .card h2{margin:0 0 11px;font-size:16px;font-weight:600;letter-spacing:-.008em}

  code,kbd{font-family:var(--mono);font-size:12.5px;background:var(--sunk);
    padding:1.5px 5px;border-radius:4px;word-break:break-word}
  pre{background:var(--sunk);border:1px solid var(--line);border-radius:8px;
    padding:14px 16px;overflow-x:auto;margin:12px 0}
  pre code{background:none;padding:0;font-size:12.5px;line-height:1.68;color:var(--ink-2)}

  h2.grp{font-size:20px;margin:44px 0 3px;padding-top:20px;font-weight:600;
    border-top:1px solid var(--line);letter-spacing:-.015em}
  .grpn{color:var(--muted);font-family:var(--mono);font-size:11.5px;
    letter-spacing:.06em;text-transform:uppercase;margin:0 0 15px}

  details.ep{background:var(--panel);border:1px solid var(--line);
    border-radius:8px;margin:7px 0;overflow:hidden}
  details.ep[open]{border-color:var(--accent)}
  summary{cursor:pointer;padding:10px 13px;display:flex;gap:11px;align-items:center;
    flex-wrap:wrap;list-style:none}
  summary::-webkit-details-marker{display:none}
  summary:hover{background:var(--sunk)}
  summary:focus-visible{outline:2px solid var(--accent);outline-offset:-2px}
  .verb{font-family:var(--mono);font-size:10.5px;font-weight:600;letter-spacing:.07em;
    padding:3.5px 7px;border-radius:4px;flex:none;min-width:50px;text-align:center}
  .GET{color:var(--get);background:var(--get-bg)}
  .POST{color:var(--post);background:var(--post-bg)}
  .PUT{color:var(--put);background:var(--put-bg)}
  .DELETE{color:var(--del);background:var(--del-bg)}
  .path{font-family:var(--mono);font-size:13px;flex:1;min-width:180px;
    word-break:break-all;color:var(--ink)}
  .path .v{color:var(--muted)}
  .lock{font-family:var(--mono);font-size:10px;color:var(--flag);background:var(--flag-bg);
    padding:3px 7px;border-radius:4px;white-space:nowrap;letter-spacing:.04em}
  .open{font-family:var(--mono);font-size:10px;color:var(--muted);
    border:1px solid var(--line);padding:2px 7px;border-radius:4px;
    white-space:nowrap;letter-spacing:.04em}

  .body{padding:3px 14px 17px;border-top:1px solid var(--line)}
  .body h4{margin:16px 0 7px;font-family:var(--mono);font-size:10.5px;
    text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:500}
  .tw{overflow-x:auto}
  table{width:100%;border-collapse:collapse;margin:4px 0;font-size:13.5px}
  th{text-align:left;font-weight:500;color:var(--muted);font-family:var(--mono);
    font-size:10.5px;text-transform:uppercase;letter-spacing:.08em;
    padding:6px 12px 6px 0;border-bottom:1px solid var(--line);white-space:nowrap}
  td{padding:7px 12px 7px 0;border-bottom:1px solid var(--line);vertical-align:top}
  tr:last-child td{border-bottom:0}
  td:first-child{font-family:var(--mono);font-size:12.5px;white-space:nowrap;color:var(--ink)}
  .req{color:var(--del);font-family:var(--mono);font-size:10.5px;letter-spacing:.04em}
  .opt{color:var(--muted);font-family:var(--mono);font-size:10.5px;letter-spacing:.04em}
  .ty{color:var(--muted);font-size:12.5px;font-family:var(--mono)}
  .ctrl{font-family:var(--mono);font-size:12.5px;color:var(--ink-2)}
  .note{font-size:13.5px;color:var(--muted);margin:9px 0 0;max-width:70ch}
  .note code{font-size:12px}
  .warn{border-left:2px solid var(--flag);background:var(--flag-bg);
    padding:12px 15px;border-radius:0 7px 7px 0;margin:13px 0;font-size:13.5px;color:var(--ink-2)}
  .warn b{color:var(--flag)}
  a{color:var(--accent)}
  :focus-visible{outline:2px solid var(--accent);outline-offset:2px}
</style>

<div class="wrap">
<header class="top">
  <p class="eyebrow">Version v1 · Admin &amp; seller</p>
  <h1>HPI ERP API Reference</h1>
  <p class="sub">Admin / seller REST API, version <code>v1</code>. Generated from the application's
     route table and controller source.</p>

  <div class="facts">
    <div class="fact"><b><?= count($api) ?></b><span>Endpoints</span></div>
    <div class="fact"><b><?= count($sorted) ?></b><span>Groups</span></div>
    <div class="fact"><b><?= $totalAuth ?></b><span>Authenticated</span></div>
    <div class="fact"><b><?= $totalPub ?></b><span>Public</span></div>
  </div>

  <div class="toc">
  <?php foreach ($sorted as $g => $rs): ?>
    <a href="#<?= e(strtolower(preg_replace('/[^a-z0-9]+/i','-',$g))) ?>"><?= e($g) ?> <span><?= count($rs) ?></span></a>
  <?php endforeach; ?>
  </div>
</header>

<div class="card">
  <h2>Base URL</h2>
  <pre><code>https://&lt;your-host&gt;/api/v1</code></pre>
  <p class="note">All paths below are relative to this base. Responses are JSON.
     Unknown routes under <code>/api</code> return <code>404 {"message":"Not Found."}</code>.</p>
</div>

<div class="card">
  <h2>Authentication</h2>
  <p class="note">Passport bearer tokens, guard <code>admin-api</code>. Obtain one from
     <code>POST /login</code>, then send it on every protected call:</p>
  <pre><code>Authorization: Bearer &lt;token&gt;
Accept: application/json</code></pre>
  <p class="note">Login authenticates against <code>admins.mandob_code</code> and only
     succeeds for records whose <code>role</code> is <code>seller</code>.</p>
  <pre><code>POST /api/v1/login
Content-Type: application/json

{ "code": "SELLER001", "password": "secret" }

200 OK
{
  "message": "You are logged in",
  "token":   "eyJ0eXAiOiJKV1Qi...",
  "admin":   { "id": 1, "f_name": "...", "storage": { ... } },
  "kilometer": 5
}</code></pre>
  <p class="note"><b>Failures:</b> <code>403</code> validation errors ·
     <code>422</code> <code>{"message":"Password mismatch"}</code> or wrong code ·
     <code>401</code> missing/expired token on a protected route.</p>
</div>

<div class="card">
  <h2>Conventions &amp; gotchas</h2>
  <div class="warn">
    <b>Destructive actions use GET.</b> Every <code>/delete</code> endpoint is a
    <code>GET</code> taking <code>?id=</code>. They are not idempotent-safe — never
    put them behind a link prefetcher or a browser accelerator.
  </div>
  <div class="warn">
    <b><?= $totalPub ?> endpoints require no token.</b> Two are intentional
    (<code>login</code>, <code>config</code>). Three opt out explicitly with
    <code>withoutMiddleware</code>: <code>product/export</code>,
    <code>product/barcode/generate</code>, <code>transaction/transfer/export</code>.
    The remaining three — <code>seller/rating</code>,
    <code>seller/indexdocument</code>, <code>seller/result/visitor/{id}</code> —
    are declared <em>after</em> the <code>auth:admin-api</code> group closes in
    <code>routes/api/v1/admin.php</code>, so they are open. If that was not
    intended, move them inside the group.
  </div>
  <p class="note">Rate limit: <b>60 requests/minute</b> per authenticated user (per IP when
     anonymous), applied to the whole <code>api</code> group.</p>
  <p class="note">Write endpoints accept <code>application/json</code> or
     <code>multipart/form-data</code>; the latter is required wherever a file or image is sent.</p>
</div>

<?php foreach ($sorted as $g => $rs):
  usort($rs, function ($a, $b) { return strcmp($a['uri'], $b['uri']); }); ?>
<h2 class="grp" id="<?= e(strtolower(preg_replace('/[^a-z0-9]+/i','-',$g))) ?>"><?= e($g) ?></h2>
<p class="grpn"><?= count($rs) ?> endpoint<?= count($rs) === 1 ? '' : 's' ?></p>

<?php foreach ($rs as $r):
  $verb = primary_method($r['method']);
  $isV2 = strpos($r['uri'], 'api/v2/') === 0;
  $base = $isV2 ? 'api/v2' : 'api/v1';
  $path = '/' . ltrim(substr($r['uri'], strlen($base . '/')), '/');
  $act  = short_action($r['action']);
  $pk   = $act;
  $info = isset($params[$pk]) ? $params[$pk] : null;
  $rules = $info && !empty($info['rules']) ? $info['rules'] : array();
  $reads = $info && !empty($info['reads']) ? $info['reads'] : array();
  preg_match_all('/\{([a-zA-Z_]+)\}/', $path, $pathParams);
  $auth = is_auth($r);
?>
  <details class="ep">
    <summary>
      <span class="verb <?= e($verb) ?>"><?= e($verb) ?></span>
      <span class="path"><span class="v"><?= $isV2 ? '/api/v2' : '/api/v1' ?></span><?= e($path) ?></span>
      <?php if ($auth): ?><span class="lock">🔒 token</span>
      <?php else: ?><span class="open">public</span><?php endif; ?>
    </summary>
    <div class="body">
      <h4>Handler</h4>
      <div class="ctrl"><?= e($act) ?></div>

      <?php if ($pathParams[1]): ?>
      <h4>Path parameters</h4>
      <div class="tw"><table><tr><th>Name</th><th></th><th>Notes</th></tr>
      <?php foreach ($pathParams[1] as $pp): ?>
        <tr><td><?= e($pp) ?></td><td><span class="req">required</span></td>
            <td class="ty">segment of the URL</td></tr>
      <?php endforeach; ?>
      </table></div>
      <?php endif; ?>

      <?php if ($rules): ?>
      <h4>Validated parameters</h4>
      <div class="tw"><table><tr><th>Name</th><th></th><th>Type</th><th>Rules</th></tr>
      <?php foreach ($rules as $f => $rule): list($req,$ty) = rule_bits($rule); ?>
        <tr><td><?= e($f) ?></td>
            <td><?= $req ? '<span class="req">required</span>' : '<span class="opt">optional</span>' ?></td>
            <td class="ty"><?= e($ty) ?></td>
            <td><code><?= e($rule) ?></code></td></tr>
      <?php endforeach; ?>
      </table></div>
      <?php endif; ?>

      <?php if ($reads): ?>
      <h4><?= $rules ? 'Other fields read' : 'Parameters read' ?></h4>
      <p class="note">
        <?php foreach ($reads as $i => $f): ?><code><?= e($f) ?></code><?= $i < count($reads)-1 ? ' ' : '' ?><?php endforeach; ?>
      </p>
      <p class="note">Read directly from the request without a validation rule, so
         the type is not enforced server-side.</p>
      <?php endif; ?>

      <?php if (!$rules && !$reads && !$pathParams[1]): ?>
      <p class="note">Takes no request parameters.</p>
      <?php endif; ?>

      <h4>Example</h4>
<pre><code>curl -X <?= e($verb) ?> "$BASE<?= e($path) ?><?php
  if ($verb === 'GET' && ($rules || $reads)) {
      $qs = array();
      foreach (array_slice(array_merge(array_keys($rules), $reads), 0, 3) as $f) $qs[] = $f . '=';
      if ($qs) echo '?' . e(implode('&', $qs));
  } ?>"<?php if ($auth): ?> \
  -H "Authorization: Bearer $TOKEN"<?php endif; ?><?php
  if ($verb !== 'GET'): $fields = array_merge(array_keys($rules), $reads); ?> \
  -H "Content-Type: application/json" \
  -d '<?= e(json_encode(array_fill_keys(array_slice($fields, 0, 6), ''), JSON_UNESCAPED_SLASHES) ?: '{}') ?>'<?php endif; ?></code></pre>
    </div>
  </details>
<?php endforeach; ?>
<?php endforeach; ?>

<h2 class="grp" id="errors">Error responses</h2>
<p class="grpn">Shapes returned across the API</p>
<div class="card">
<div class="tw"><table>
  <tr><th>Status</th><th>Body</th><th>When</th></tr>
  <tr><td>200</td><td><code>{ ...data }</code></td><td>Success</td></tr>
  <tr><td>401</td><td><code>{"message":"Unauthenticated."}</code></td><td>Missing or expired bearer token</td></tr>
  <tr><td>403</td><td><code>{"errors":[{"code":"…","message":"…"}]}</code></td><td>Validation failed</td></tr>
  <tr><td>404</td><td><code>{"message":"Not Found."}</code></td><td>Unknown route, or record not found</td></tr>
  <tr><td>422</td><td><code>{"message":"Password mismatch"}</code></td><td>Login credential mismatch</td></tr>
  <tr><td>429</td><td><code>{"message":"Too Many Attempts."}</code></td><td>Rate limit exceeded (60/min)</td></tr>
</table></div>
</div>

<p class="note" style="margin-top:26px">
  Generated from <code>routes/api/v1/admin.php</code> and
  <code>app/Http/Controllers/Api/V1/</code>. Regenerate after route changes rather than editing by hand.
</p>
</div>
<?php
$html = ob_get_clean();
file_put_contents($outFile, $html);
echo "wrote " . strlen($html) . " bytes, " . count($api) . " endpoints, " . count($sorted) . " groups\n";
