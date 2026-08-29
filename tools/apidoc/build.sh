#!/usr/bin/env bash
# Regenerate the API reference (tools/apidoc/api.html) from the live route
# table and the controller sources. Run from the project root after any
# change to routes/api/v1/admin.php or app/Http/Controllers/Api/V1/.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
OUT="$ROOT/tools/apidoc"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

# 1. Ask Laravel for the resolved routes (authoritative: includes middleware).
php "$ROOT/artisan" route:list --json > "$TMP/routes.json"

# 2. Extract validation rules and request reads per controller method.
#    PHP's CWD may differ from the shell's, so pass an absolute path.
php "$OUT/params.php" "$ROOT/app/Http/Controllers/Api/V1" "$TMP/params-v1.json"
php "$OUT/params.php" "$ROOT/app/Http/Controllers/Api/V2" "$TMP/params-v2.json"
php -r '$a=json_decode(file_get_contents($argv[1]),true);$b=json_decode(file_get_contents($argv[2]),true);file_put_contents($argv[3],json_encode(array_merge($a,$b)));'     "$TMP/params-v1.json" "$TMP/params-v2.json" "$TMP/params.json"

# 3. Render the reference.
php "$OUT/docgen.php" "$TMP/routes.json" "$TMP/params.json" "$OUT/api.html"

echo "-> $OUT/api.html"
