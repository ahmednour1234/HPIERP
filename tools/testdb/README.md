# API testing & the refactored modules

## Running the app locally on SQLite

```bash
bash tools/run-local.sh                # build the database if absent, then serve
bash tools/run-local.sh --fresh        # rebuild it from scratch first
bash tools/run-local.sh --with-data    # also import the production dump
bash tools/run-local.sh --port=8080    # serve on another port
```

Serves on <http://127.0.0.1:8000>. The admin panel lives at
**`/admin/auth/login`** (there is no `/login` route) - sign in with
`admin@example.test` / `password`. Configuration comes from `.env.local`
(`APP_ENV=local`), so **the MySQL credentials in `.env` are never read or
modified** - production config stays exactly as it is.

The script creates `storage/local.sqlite`, migrates, seeds the fixtures and
generates the Passport keys and personal-access client that `POST /api/v1/login`
needs to issue a token.

```bash
# log in
curl -X POST http://127.0.0.1:8000/api/v1/login   -H 'Content-Type: application/json'   -d '{"code":"TESTSELLER","password":"password"}'

# then use the token
curl http://127.0.0.1:8000/api/v2/customers   -H 'Accept: application/json' -H "Authorization: Bearer $TOKEN"
```

Both `.env.local` and `storage/local.sqlite` are gitignored.

## Seeders

```bash
php artisan db:seed                                  # demo data + API fixtures
php artisan db:seed --class=DemoDataSeeder           # just the demo dataset
php artisan db:seed --class=ApiTestingSeeder         # just the API fixtures
```

`DemoDataSeeder` builds a small, coherent pharmacy-distribution dataset: shop
settings, 4 regions, 5 categories, 5 brands, 14 products, 3 suppliers, 3 sellers
with their own vans/routes/stock, 10 pharmacy customers, and ~18 orders with
their lines, payments, expenses and visits spread over the last three months -
so the dashboards, date filters and low-stock reports all have real shapes.

Every row uses a fixed id in the 800000+ range, so the seeder is safe to re-run
and never collides with real data.

| Login | Credentials |
|---|---|
| Web admin | `demo.admin@example.test` / `password` |
| API seller | `MND-01` / `password` (also `MND-02`, `MND-03`) |

The admin is seeded with every section permission enabled - the flags on
`admins` default to 0 and the `Check*Access` middleware redirects away from any
section not granted.

`ProductTableSeeder` and `CustomerTableSeeder` are **not** run by
`DatabaseSeeder`: they generate tens of thousands of rows of random strings.
Call them explicitly if you want bulk data for load testing.

## Running the tests

```bash
./vendor/bin/phpunit                     # everything
./vendor/bin/phpunit tests/Feature/Api   # API only
```

Tests run against **in-memory SQLite** (pinned in `phpunit.xml`), so they never
touch the MySQL database in `.env`. The schema is built from
`database/migrations/` and the fixtures come from `ApiTestingSeeder`.

| Suite | What it covers |
|---|---|
| `tests/Feature/Api/AllEndpointsSmokeTest.php` | Calls **every** registered API endpoint, writes `storage/logs/api-smoke-report.md` with status, headers and body for each |
| `tests/Feature/Api/V1/LegacyResponseShapeTest.php` | Asserts v1 payloads are **unchanged** — the guarantee that the refactor broke no existing client |
| `tests/Feature/Api/V2/*` | The refactored modules: envelope, validation, ownership, settlement correctness |

### The smoke report

`storage/logs/api-smoke-report.md` is regenerated on every run. It records the
status distribution and, per endpoint, the response headers and body. GET
endpoints are called without parameters, so many legitimately answer 4xx.

The test fails only if the number of 5xx endpoints rises above
`KNOWN_SERVER_ERRORS` in that file — a ratchet over the pre-existing defects.
**Lower that number as they are fixed** so they cannot silently return.

## A local database with real data

```bash
bash tools/testdb/build.sh
```

Builds `storage/testing.sqlite` from the migrations plus the rows in
`u178445728_erpnew.sql`, then adds the fixture seller.

That file contains **real customer data**, so it and the dump are gitignored.
It is for local inspection only — the test suite uses in-memory SQLite.

Test credentials: `TESTSELLER` / `password` (seller id `900001`).

## Architecture of the refactored modules

v1 is untouched. The refactor lives at `/api/v2` and can be adopted per module.

```
Controller (Api/V2)      thin: HTTP in, Resource out
  -> FormRequest         validation rules
  -> Service             business rules, transactions
     -> Repository       data access only
  -> Resource            response shape
```

* `App\Traits\ApiResponse` — the `{ success, message, data }` envelope
* `App\Http\Middleware\StandardApiResponse` — the `api.standard` alias that
  opts a route into the envelope **and** the matching error rendering
* `App\Exceptions\Handler` — converts exceptions to that envelope, but **only**
  for routes carrying `api.standard`; everything else keeps its legacy output

### Modules on v2

| Prefix | Covers |
|---|---|
| `orders` | place order / return, list, invoice, customer history |
| `stocks` | van stock, day settlement, history |
| `customers` | CRUD, ownership, add-balance |
| `products` | CRUD, by-code, low-stock, customer prices |
| `suppliers` | CRUD, by-city, ledger, payment |
| `transactions` | ledger, transfer, expense, income |
| `dashboard` | summary, monthly revenue, top products, low stock |
| `visits` | planned visits, outcomes |
| `attendance` | read-only listing (check-in/out stays on v1) |
| `profile` | profile, change password |
| `regions` `storages` `documents` | read-only reference lists |
| `brands` `units` `accounts` `categories` `coupons` | shared CRUD base |

`categories/{id}/children` covers sub-categories - they are category rows with
a parent, not a separate table.

The five lookup modules run off `CrudController` / `CrudService` /
`CrudRepository`, so adding another lookup table is three small classes.

### Deliberately left on v1

* **Attendance check-in/out** - the shift matching (midnight-crossing shifts,
  grace windows, lateness) feeds payroll and already validates its input.
* **login / confirm_login** - issuing Passport tokens; changing the login
  contract would break every installed client at once.
* **Imports and exports** - Excel/barcode streams, not JSON.
* **Developer-seller, courses, salary, certificates** - internal HR screens
  with no mobile client.

### Adding a module

1. `App\Repositories\<X>Repository` extending `BaseRepository`
2. `App\Services\<X>Service` for the rules
3. `App\Http\Requests\Api\V1\<X>Request` for validation
4. `App\Http\Resources\Api\V1\<X>Resource` for the response
5. A controller in `App\Http\Controllers\Api\V2`
6. Register it in `routes/api/v2/api.php` (already inside `api.standard`)
