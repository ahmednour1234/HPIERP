<?php
/**
 * Extract, per controller method:
 *   - Validator::make / $request->validate rules
 *   - $request->input('x') / $request['x'] / $request->x reads
 * so the API doc lists real parameters instead of guessed ones.
 */
$dir = $argv[1];
$out = array();

$skipProps = array(
    'all','user','file','hasFile','has','ajax','method','url','ip','header',
    'bearerToken','route','merge','only','except','validate','input','query',
    'post','get','wantsJson','fullUrl','path','session','cookie','files',
    'isMethod','expectsJson','filled','boolean','string','collect','json',
);

foreach (glob($dir . '/*.php') as $file) {
    $ctrl = basename($file, '.php');
    $src  = file_get_contents($file);

    // Locate every method declaration and slice its body by brace matching
    // starting at the first '{' after the signature.
    if (!preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $src, $fm, PREG_OFFSET_CAPTURE)) continue;

    foreach ($fm[1] as $idx => $hit) {
        $method = $hit[0];
        $from   = $hit[1];
        $open   = strpos($src, '{', $from);
        if ($open === false) continue;

        // brace match, skipping strings and comments
        $len = strlen($src); $depth = 0; $end = null;
        for ($p = $open; $p < $len; $p++) {
            $c = $src[$p];
            if ($c === "'" || $c === '"') {
                $qt = $c;
                for ($p++; $p < $len; $p++) {
                    if ($src[$p] === "\\") { $p++; continue; }
                    if ($src[$p] === $qt) break;
                }
                continue;
            }
            if ($c === '/' && $p + 1 < $len && $src[$p + 1] === '/') { $p = strpos($src, "\n", $p); if ($p === false) break; continue; }
            if ($c === '/' && $p + 1 < $len && $src[$p + 1] === '*') { $p = strpos($src, '*/', $p); if ($p === false) break; $p++; continue; }
            if ($c === '{') $depth++;
            if ($c === '}') { $depth--; if ($depth === 0) { $end = $p; break; } }
        }
        if ($end === null) continue;
        $body = substr($src, $open, $end - $open + 1);

        $rules = array();
        if (preg_match_all('/(?:Validator::make|->validate)\s*\(/', $body, $vm, PREG_OFFSET_CAPTURE)) {
            foreach ($vm[0] as $v) {
                // Slice exactly the rules array: the first '[' after the call,
                // matched to its own closing ']'. A wider window would bleed
                // into the response payload and pick up keys like "message".
                $lb = strpos($body, '[', $v[1]);
                if ($lb === false) continue;
                $bl = strlen($body); $d = 0; $rb = null;
                for ($p = $lb; $p < $bl; $p++) {
                    $c = $body[$p];
                    if ($c === "'" || $c === '"') {
                        $qt = $c;
                        for ($p++; $p < $bl; $p++) {
                            if ($body[$p] === "\\") { $p++; continue; }
                            if ($body[$p] === $qt) break;
                        }
                        continue;
                    }
                    if ($c === '[') $d++;
                    if ($c === ']') { $d--; if ($d === 0) { $rb = $p; break; } }
                }
                if ($rb === null) continue;
                $blk = substr($body, $lb, $rb - $lb + 1);
                if (preg_match_all("/'([A-Za-z0-9_.*\\[\\]]+)'\s*=>\s*'([^']*)'/", $blk, $rm, PREG_SET_ORDER)) {
                    foreach ($rm as $r) if (!isset($rules[$r[1]])) $rules[$r[1]] = $r[2];
                }
                if (preg_match_all("/'([A-Za-z0-9_.*\\[\\]]+)'\s*=>\s*\[([^\]]*)\]/", $blk, $rm2, PREG_SET_ORDER)) {
                    foreach ($rm2 as $r) {
                        if (isset($rules[$r[1]])) continue;
                        $parts = array();
                        if (preg_match_all("/'([^']*)'/", $r[2], $pm)) $parts = $pm[1];
                        if ($parts) $rules[$r[1]] = implode('|', $parts);
                    }
                }
            }
        }

        $reads = array();
        if (preg_match_all("/\\\$request\s*->\s*(?:input|query|post|get)\s*\(\s*'([A-Za-z0-9_]+)'/", $body, $m1))
            foreach ($m1[1] as $f) $reads[$f] = true;
        if (preg_match_all("/\\\$request\s*\[\s*'([A-Za-z0-9_]+)'\s*\]/", $body, $m2))
            foreach ($m2[1] as $f) $reads[$f] = true;
        if (preg_match_all("/\\\$request\s*->\s*([a-z][A-Za-z0-9_]*)\b(?!\s*\()/", $body, $m3))
            foreach ($m3[1] as $f) {
                if (in_array($f, $skipProps, true)) continue;
                $reads[$f] = true;
            }

        foreach (array_keys($rules) as $f) unset($reads[$f]);

        $key = $ctrl . '@' . $method;
        if (isset($out[$key])) continue;
        $out[$key] = array('rules' => $rules, 'reads' => array_keys($reads));
    }
}

file_put_contents($argv[2], json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo count($out) . " methods analysed\n";
