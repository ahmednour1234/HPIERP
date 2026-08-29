#!/usr/bin/env bash
# Run the project locally against SQLite.
#
#   bash tools/run-local.sh            build the database (if absent) and serve
#   bash tools/run-local.sh --fresh    rebuild the database from scratch first
#   bash tools/run-local.sh --with-data  also import the production dump
#
# Configuration comes from .env.local (APP_ENV=local), so the MySQL credentials
# in .env are never read or modified.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

export APP_ENV=local
DB="$ROOT/storage/local.sqlite"

# .env.local ships with a placeholder so the file is portable; point it at this
# checkout. SQLite needs an absolute path - a relative one resolves against the
# working directory, which differs between the CLI and the served app.
if grep -q '__PROJECT__' .env.local 2>/dev/null; then
  # Under Git Bash on Windows $ROOT is /z/HPIERP, which PHP cannot open;
  # cygpath turns it into the Z:/HPIERP form PHP expects.
  PROJECT_PATH="$ROOT"
  if command -v cygpath >/dev/null 2>&1; then
    PROJECT_PATH="$(cygpath -m "$ROOT")"
  fi
  sed -i "s#__PROJECT__#${PROJECT_PATH}#" .env.local
fi
DUMP="$ROOT/u178445728_erpnew.sql"

FRESH=0
WITH_DATA=0
PORT=8000
for arg in "$@"; do
  case "$arg" in
    --fresh)     FRESH=1 ;;
    --with-data) WITH_DATA=1 ;;
    --port=*)    PORT="${arg#*=}" ;;
  esac
done

if [ "$FRESH" = "1" ]; then
  rm -f "$DB"
fi

# The views call asset('public/...'), which is correct on the shared host where
# the document root is the project root. `artisan serve` serves from public/,
# so those URLs arrive as /public/assets/... A public/public link pointing back
# at public/ lets the built-in server return them as STATIC files.
#
# That matters for more than tidiness: artisan serve is single-threaded, so
# routing 15 assets through PHP serialises them behind the session middleware
# and the browser stalls. Served statically they load in parallel.
if [ ! -e "$ROOT/public/public" ]; then
  echo "==> linking public/public (asset path shim)"
  if command -v cmd.exe >/dev/null 2>&1; then
    WIN_PUBLIC="$(cygpath -w "$ROOT/public" 2>/dev/null || echo "$ROOT/public")"
    cmd.exe //c mklink //J "${WIN_PUBLIC}\public" "${WIN_PUBLIC}" >/dev/null 2>&1 || true
  else
    ln -s "$ROOT/public" "$ROOT/public/public" 2>/dev/null || true
  fi
fi

php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear  >/dev/null 2>&1 || true

if [ ! -f "$DB" ]; then
  echo "==> creating $DB"
  : > "$DB"

  echo "==> migrating"
  php artisan migrate --force --no-interaction

  if [ "$WITH_DATA" = "1" ] && [ -f "$DUMP" ]; then
    echo "==> importing production data"
    SEED="$ROOT/storage/app/seed-data.sql"
    mkdir -p "$(dirname "$SEED")"
    php "$ROOT/tools/testdb/import_dump.php" "$DUMP" "$SEED" >/dev/null
    sqlite3 "$DB" < "$SEED"
  fi

  echo "==> seeding test fixtures"
  php artisan db:seed --class=ApiTestingSeeder --force --no-interaction

fi

# Passport signs the tokens that POST /api/v1/login issues. Checked on every
# run, not only when the database is created: wiping the tables leaves the
# file in place, and login then fails with "Personal access client not found".
if [ ! -f "$ROOT/storage/oauth-private.key" ]; then
  echo "==> generating passport keys"
  php artisan passport:keys --force >/dev/null 2>&1 || true
fi

if [ "$(sqlite3 "$DB" "SELECT count(*) FROM oauth_personal_access_clients;" 2>/dev/null || echo 0)" = "0" ]; then
  echo "==> creating passport personal access client"
  php artisan passport:client --personal --name="Local Personal Access Client" \
    --no-interaction >/dev/null 2>&1 || true
fi

echo
echo "database  : $DB"
echo "tables    : $(sqlite3 "$DB" "SELECT count(*) FROM sqlite_master WHERE type='table';")"
echo
echo "admin panel : http://127.0.0.1:${PORT}/admin/auth/login"
echo "              admin@example.test / password"
echo "API login   : POST /api/v1/login  { \"code\": \"TESTSELLER\", \"password\": \"password\" }"
echo
echo "serving on http://127.0.0.1:${PORT}  (ctrl-c to stop)"
echo

php artisan serve --host=127.0.0.1 --port="$PORT"
