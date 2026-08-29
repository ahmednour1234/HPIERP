#!/usr/bin/env bash
# Build a local SQLite database for API testing:
#   schema  <- database/migrations (the verified set)
#   data    <- u178445728_erpnew.sql (production dump)
#   + a known-credential test seller, so login can be exercised
#
# The resulting .sqlite contains real customer data and is gitignored.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DB="$ROOT/storage/testing.sqlite"
DUMP="${1:-$ROOT/u178445728_erpnew.sql}"
SEED="$ROOT/storage/app/seed-data.sql"

mkdir -p "$(dirname "$SEED")"
rm -f "$DB"
: > "$DB"

echo "==> schema (migrations)"
DB_CONNECTION=sqlite DB_DATABASE="$DB" php "$ROOT/artisan" migrate --force --no-interaction >/dev/null
echo "    $(sqlite3 "$DB" "SELECT count(*) FROM sqlite_master WHERE type='table';") tables"

if [ -f "$DUMP" ]; then
  echo "==> data (dump)"
  php "$ROOT/tools/testdb/import_dump.php" "$DUMP" "$SEED" >/dev/null
  sqlite3 "$DB" < "$SEED"
  echo "    admins=$(sqlite3 "$DB" 'SELECT count(*) FROM admins;')" \
       "customers=$(sqlite3 "$DB" 'SELECT count(*) FROM customers;')" \
       "orders=$(sqlite3 "$DB" 'SELECT count(*) FROM orders;')" \
       "products=$(sqlite3 "$DB" 'SELECT count(*) FROM products;')"
else
  echo "==> dump not found at $DUMP - schema only"
fi

echo "==> test fixtures"
DB_CONNECTION=sqlite DB_DATABASE="$DB" php "$ROOT/artisan" db:seed \
  --class=ApiTestingSeeder --force --no-interaction

echo
echo "database: $DB"
