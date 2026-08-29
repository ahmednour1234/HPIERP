# HPI ERP — API v1 (legacy)

The original API. It is **still live and unchanged** for the endpoints existing
clients depend on — this document exists so you can see what v1 actually
accepts before migrating a screen to [v2](API-V2.md).

- **Base URL** — `https://<host>/api/v1`
- **Endpoints** — 143 (8 of them unauthenticated)
- **Auth** — Passport bearer token, guard `admin-api`

> **Which one should I use?** New work should target [v2](API-V2.md): it
> validates its input, uses one response shape, and runs money operations in
> database transactions. v1 is documented here for reference and for clients
> that have not migrated yet.

---

## How v1 differs from v2

| | v1 | v2 |
|---|---|---|
| Response shape | Different per endpoint | One envelope: `{success, message, data}` |
| Errors | Mixed — `errors[]`, `message`, sometimes `200` on failure | Always the envelope, correct status |
| Validation | Sparse; several endpoints have none | A `FormRequest` per endpoint |
| Pagination | `total`/`limit`/`offset` at the top level | `meta` object |
| Money operations | Often no transaction | Single transaction, rows locked |

There is **no v2 login** — both versions use `POST /api/v1/login`.

---

## Authentication

### `POST /api/v1/login`

| Field | Rules |
|---|---|
| `code` | required — matches `admins.mandob_code` |
| `password` | required |

Only accounts whose `role` is `seller` can log in.

```json
{
  "message": "You are logged in",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "admin": { "id": 800050, "f_name": "Ahmed", "role": "seller" },
  "kilometer": 5
}
```

Failures: `403` validation · `422` wrong code or password.

---

## Placing an order — `POST /api/v1/pos/place/order`

The endpoint most worth understanding before you migrate, because its input is
looser than it looks.

### What it accepts

| Field | Notes |
|---|---|
| `user_id` | The customer id |
| `cart` | **A JSON string**, not an array — see below |
| `order_type` | `4` sale · `7` return · `12`/`24` installment |
| `type` | The account id the payment lands on |
| `cash` | `1` cash · `2` credit |
| `collected_cash` | What was collected |
| `order_amount` | Order total |
| `total_tax` | Tax total |
| `extra_discount` | Discount on the whole order |
| `extra_discount_type` | `percent` or `amount` |
| `coupon_code` · `coupon_title` · `coupon_discount` | Coupon fields |
| `img` | Receipt photo, `multipart/form-data` |

Each cart line carries `id`, `quantity`, `price`, and — used only for the
totals — `discount` and `tax`.

### The cart is a string

v1 accepts `cart` as loosely-quoted JSON and repairs it with a regular
expression before decoding:

```php
$cart = preg_replace('/([{,])(\s*)([a-zA-Z0-9_]+)(\s*:\s*)/', '$1$2"$3"$4', $request->cart);
$cart = json_decode($cart, true);
```

So `[{id:1,quantity:2}]` is accepted. v2 takes a real array and validates each
line, but still decodes a JSON string for older clients.

### Known issues

These are why the v2 endpoint exists. They are **not fixed in v1**:

| Issue | Effect |
|---|---|
| Stock is decremented **before** the `try` block | A later failure leaves stock reduced with no order to explain it |
| `$this->order->max('id') + 1` for the order id | Two sellers checking out at once can collide |
| Three queries per cart line | A 20-line cart runs 60 extra queries |
| Line discount stored ≠ discount summed | The invoice total can disagree with its own lines |
| No validation | A missing customer or product is discovered mid-write |

`422` with `"Insufficient stock for product: <name>"` when the seller is not
carrying enough — but by then stock may already have been touched.

---

## Endpoints that were broken and now point at v2

These v1 routes returned `500` on every call, so nothing could have been
depending on them. They now run the v2 implementation and answer `422` with
field errors instead. **The URL is unchanged.**

| Route | Was |
|---|---|
| `POST /product/store` | `PosController@storeProduct` — the method did not exist |
| `POST /product/update` | `PosController@productUpdate` — did not exist |
| `POST /customer/add-balance` | `CustomerController@addBalance` — did not exist |
| `POST /product/customer/price` | Looped over an unvalidated cart; `null` crashed it |
| `POST /product/customer/price/change` | Violated a `NOT NULL` constraint |
| `POST /transactionseller` | Inserted without `img`, which is `NOT NULL` — every request without a photo failed |

Because they now use v2 controllers, these six return the **v2 envelope**, not
the v1 shape.

---

## Other v1 behaviour worth knowing

**Deletes use `GET`.** Every `/delete` endpoint is `GET ...?id=`. Keep them
away from link prefetchers.

**Adding a customer has no validation.** `POST /customer/store` calls
`$request->validate([])` — an empty rule set — and substitutes placeholders for
anything missing:

| Field | Stored when absent |
|---|---|
| `name` | `'r'` |
| `name_en` | `'r'` |
| `pharmacy_name` | `'s'` |
| `email` | `'das'` |
| `mobile` | `'0'` |

So a client that forgets the name creates a customer called `r` and gets a
success response. v2 answers `422`.

**Fund transfer reports failure as success.** `POST /transaction/fund/transfer`
returns HTTP `203` when the source account lacks the balance — most clients
treat 2xx as success. Its `amount` rule also omits `numeric`, so `"abc"`
passes validation. Both are fixed in `POST /api/v2/transactions/transfer`.

**Three endpoints need no token** by design — `product/export`,
`product/barcode/generate`, `transaction/transfer/export` — plus
`seller/rating`, `seller/indexdocument` and `seller/result/visitor/{id}`, which
sit outside the auth group. If that was not intended, move them inside it.

**Three endpoints use MySQL-only SQL** (`YEAR()`, `GREATEST()`) and cannot run
on SQLite: `dashboard/monthly/revenue`, `pos/order/install`,
`pos/order/notinstall`. They work in production. The v2 dashboard uses portable
date ranges instead.

---

## Migrating a screen to v2

1. Find the v2 equivalent in [`API-V2.md`](API-V2.md).
2. Switch the base path from `/api/v1` to `/api/v2` — the token is the same.
3. Read the response through the envelope: `data` instead of the top level,
   `meta` instead of top-level `total`/`limit`/`offset`.
4. Handle `422` with a field-keyed `errors` object.

The two versions run side by side, so you can migrate one screen at a time.
[`MOBILE-API.md`](MOBILE-API.md) covers the seller app's flow on v2.

---

## Full endpoint list

`tools/apidoc/api.html` lists all 232 endpoints across both versions with
parameters and examples. Regenerate it after changing routes:

```bash
bash tools/apidoc/build.sh
```
