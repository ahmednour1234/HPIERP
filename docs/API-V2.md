# HPI ERP — API v2

Seller-facing REST API. Every endpoint listed here is live: the paths come from
the application's route table, the parameters from the `FormRequest` classes
that enforce them, and the example bodies are real responses captured from a
running server.

- **Base URL** — `https://<host>/api/v2`
- **Auth** — Passport bearer token, guard `admin-api`
- **Endpoints** — 94
- **Format** — JSON in, JSON out; every response uses the same envelope

> **v1 is untouched.** These endpoints sit alongside the existing `/api/v1`
> surface, which keeps its original payloads. Clients migrate one module at a
> time; nothing breaks by adopting v2 partially.

---

## Contents

| Module | Prefix | Endpoints |
|---|---|---|
| [Authentication](#authentication) | *(v1 login)* | — |
| [Profile](#profile) | `/profile` | 2 |
| [Dashboard](#dashboard) | `/dashboard` | 4 |
| [Orders (POS)](#orders-pos) | `/orders` | 8 |
| [Stock](#stock) | `/stocks` | 5 |
| [Products](#products) | `/products` | 9 |
| [Customers](#customers) | `/customers` | 6 |
| [Suppliers](#suppliers) | `/suppliers` | 8 |
| [Transactions](#transactions) | `/transactions` | 6 |
| [Seller deposits](#seller-deposits) | `/deposits` | 4 |
| [Visits](#visits) | `/visits` | 5 |
| [Attendance](#attendance) | `/attendance` | 1 |
| [Salary](#salary) | `/salary` | 2 |
| [Manager](#manager) | `/manager` | 12 |
| [Lookup tables](#lookup-tables) | `/brands` `/units` `/accounts` `/categories` `/coupons` | 32 |
| [Reference data](#reference-data) | `/regions` `/storages` `/documents` `/specialties` `/product-categories` | 8 |
| **Total** | | **97** |

---

## Response envelope

Every v2 response — success or failure — has the same top-level shape.

**Success**

```json
{
  "success": true,
  "message": "Customers retrieved",
  "data": { }
}
```

**Paginated list** — adds `meta`:

```json
{
  "success": true,
  "message": "Customers retrieved",
  "data": [ { "id": 800309, "name": "Misr Pharmacy" } ],
  "meta": { "current_page": 1, "per_page": 25, "total": 4, "last_page": 1 }
}
```

**Error** — `errors` is present only for validation failures, keyed by field:

```json
{
  "success": false,
  "message": "The given data was invalid",
  "errors": {
    "name":   ["Customer name is required."],
    "mobile": ["Mobile number is required."]
  }
}
```

### Status codes

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Resource created |
| `401` | Missing, invalid or expired token |
| `403` | Authenticated, but the record is not yours |
| `404` | No such endpoint or record |
| `405` | Wrong HTTP method for this path |
| `422` | Validation failed, or a business rule rejected the request |
| `429` | Rate limit exceeded |
| `500` | Server error |

Business rules return `422` with a plain message and no `errors` object — for
example `{"success": false, "message": "The source account does not have enough balance."}`.

### Pagination

List endpoints accept:

| Parameter | Default | Meaning |
|---|---|---|
| `limit` | `25` | Rows per page |
| `offset` | `1` | **Page number**, not a row offset |
| `search` | — | Free-text filter (where the module supports it) |

> `offset` is a page number. `offset=2` with `limit=25` returns rows 26–50.

### Rate limit

60 requests per minute per authenticated user (per IP when anonymous).

---

## Authentication

Tokens are issued by the **v1** login endpoint; there is no separate v2 login.

### `POST /api/v1/login`

| Field | Rules |
|---|---|
| `code` | required — matches `admins.mandob_code` |
| `password` | required |

Only accounts whose `role` is `seller` can log in.

```bash
curl -X POST "$BASE/api/v1/login" \
  -H 'Content-Type: application/json' \
  -d '{"code":"MND-01","password":"password"}'
```

```json
{
  "message": "You are logged in",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "admin": { "id": 800050, "f_name": "Ahmed", "role": "seller" },
  "kilometer": 5
}
```

Send the token on every v2 request:

```
Authorization: Bearer <token>
Accept: application/json
```

Failures: `403` validation, `422` wrong code or password.

---

## Profile

### `GET /profile`

The signed-in seller. Password and token fields are never returned.

```json
{
  "success": true,
  "message": "Profile retrieved",
  "data": {
    "id": 800050,
    "name": "Ahmed Nour",
    "name_en": "Ahmed Nour",
    "email": "ahmed.seller@example.test",
    "phone": null,
    "image": null,
    "role": "seller",
    "mandob_code": "MND-01",
    "vehicle_code": "800060",
    "company_id": 1
  }
}
```

### `POST /profile/change-password`

| Field | Rules |
|---|---|
| `current_password` | required |
| `password` | required, confirmed, min 8 |
| `password_confirmation` | must match `password` |

Returns `422` if the current password is wrong.

---

## Dashboard

All figures are **scoped to the signed-in seller**.

### `GET /dashboard/summary`

| Parameter | Rules |
|---|---|
| `from` | optional date — defaults to the start of this month |
| `to` | optional date, `after_or_equal:from` — defaults to month end |

```json
{
  "success": true,
  "message": "Dashboard summary retrieved",
  "data": {
    "period": { "from": "2026-08-01", "to": "2026-08-31" },
    "sales":   { "count": 2, "amount": 1767 },
    "returns": { "count": 0, "amount": 0 },
    "net_sales": 1767,
    "collected": 706.8,
    "visits": 4,
    "stock_value": 16192,
    "low_stock_products": 0
  }
}
```

### `GET /dashboard/monthly-revenue`

| Parameter | Rules |
|---|---|
| `months` | optional integer, `between:1,36` (default 12) |

Returns one entry per month, oldest first:

```json
{
  "success": true,
  "message": "Monthly revenue retrieved",
  "data": [
    { "month": "2026-06", "sales": 0,    "returns": 0, "orders": 0 },
    { "month": "2026-07", "sales": 2340, "returns": 190, "orders": 3 },
    { "month": "2026-08", "sales": 1767, "returns": 0, "orders": 2 }
  ]
}
```

### `GET /dashboard/top-products`

| Parameter | Rules |
|---|---|
| `limit` | optional integer, `between:1,50` (default 10) |
| `from` / `to` | optional dates |

Ranked by quantity sold. Each entry: `id`, `name`, `product_code`, `quantity`, `amount`.

### `GET /dashboard/low-stock`

Products the seller carries at or below their `limit_stock` threshold.
Each entry: `id`, `name`, `product_code`, `stock`, `limit_stock`.

---

## Orders (POS)

Placing an order writes the order, its lines, the stock movements and the
ledger entry **in a single database transaction**. If any part fails, nothing
is written — stock is never left decremented without an order.

### Order types

| `type` | Meaning | Effect on stock |
|---|---|---|
| `4` | Sale | Decrements |
| `7` | Return | Increments, and credits the customer |
| `12`, `24` | Installment variants | Decrements; no ledger entry |

### `POST /orders`

| Field | Rules |
|---|---|
| `user_id` | required, integer, `exists:customers,id` |
| `cart` | required, array, min 1 |
| `cart.*.id` | required, integer, `exists:products,id` |
| `cart.*.quantity` | required, numeric, `gt:0` |
| `cart.*.price` | optional, numeric, min 0 |
| `cart.*.discount` | optional, numeric, min 0 |
| `cart.*.discount_type` | optional, `in:percent,amount` |
| `cart.*.tax` | optional, numeric, min 0 |
| `order_type` | optional, `in:4,7,12,24` (default 4) |
| `type` | optional integer — the account id the payment lands on |
| `cash` | optional integer |
| `collected_cash` | optional, numeric, min 0 |
| `order_amount` | optional, numeric, min 0 |
| `extra_discount` | optional, numeric, min 0 |
| `extra_discount_type` | optional, `in:percent,amount` |
| `coupon_code` / `coupon_title` | optional strings |
| `coupon_discount` | optional, numeric, min 0 |
| `img` | optional, image, max 4 MB — the receipt photo |

**Discounts.** A line discount may be a percentage of that line's price or a
flat amount, set by `cart.*.discount_type`. Send neither and the product's own
configured discount and tax apply, so a client that knows nothing about
discounts still gets correct figures.

`extra_discount` is a further discount on the whole order, controlled by
`extra_discount_type` (`percent` or `amount`).

> v1 stored `Helpers::discount_calculate()` on the line but added the client's
> `cart[].discount` to the order total, so an invoice could disagree with the
> lines beneath it whenever the two differed. Here one figure is used for both.

**The receipt photo.** Send `img` as `multipart/form-data` to attach the slip
photographed at the counter. It is stored on the order **and on its ledger
entry**, so the accounts screen shows the same proof as the invoice.

> `img` was missing from `Transection::$fillable`, so the photo silently never
> reached the ledger entry even when the order carried it.

> **Pricing is server-authoritative.** A negotiated customer price beats a
> seller price, which beats the `price` the client sent. A tampered client
> cannot set its own price.

> **Legacy carts accepted.** If `cart` arrives as a JSON *string* (as v1
> clients send it) it is decoded before validation.

```bash
curl -X POST "$BASE/api/v2/orders" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
        "user_id": 800300,
        "order_type": 4,
        "cart": [{"id": 800100, "quantity": 5, "price": 20}],
        "collected_cash": 114
      }'
```

`201` on success; `422` with `"Not enough stock for <product> (have N, need M)."`
when the seller is not carrying enough.

### Collecting against an invoice

#### `POST /orders/{id}/collect`

Records a payment against **one invoice**, moving its `collected_cash` so the
derived `payment_status` follows.

> Not the same as [`POST /customers/add-balance`](#customers), which settles a
> customer's overall balance without reference to a document. Use this one when
> the seller is collecting for a specific invoice.

| Field | Rules |
|---|---|
| `amount` | required, numeric, `gt:0` |
| `account_id` | required, integer, `exists:accounts,id` |
| `date` | required, date |
| `note` | nullable, string, max 2000 |
| `img` | nullable, image, max 4 MB — the receipt, via `multipart/form-data` |
| `cash` | optional, `in:1,2`; defaults to the invoice's |

```bash
curl -X POST "$BASE/api/v2/orders/800549/collect" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{ "amount": 114, "account_id": 800001, "date": "2026-08-26" }'
```

```json
{
  "success": true,
  "message": "Payment collected",
  "data": {
    "order_id": 800549,
    "order_amount": 114,
    "collected_cash": 114,
    "remaining": 0,
    "payment_status": "paid"
  }
}
```

`payment_status` uses the same rule as the `payment_status` filter on
`GET /orders`, so a list refreshed after collecting agrees with this response.

What one call does, in a single transaction:

1. Adds `amount` to the invoice's `collected_cash`
2. Writes a `Receivable` ledger entry carrying `order_id`
3. Credits the receiving account
4. Reduces what the customer owes

The invoice, the account and the customer row are all locked for the duration,
so two collections filed at once cannot both read the same remaining figure and
together overpay the invoice.

**Rejections** (`422`, and nothing is written):

| Situation | Message |
|---|---|
| More than is still owed | `المبلغ يتجاوز المتبقّي على الفاتورة` |
| The invoice is a return | `لا يمكن تحصيل فاتورة مرتجع` |

`403` if the invoice belongs to another seller.
### Returns against an invoice

A return reverses a sale, so it is filed **against the invoice it reverses** —
not as a free-standing order. The flow is two calls: ask what is still
returnable, then send back the quantities.

#### `GET /orders/{id}/returnable`

The invoice's lines with how much of each can still come back.

```json
{
  "success": true,
  "message": "Returnable lines retrieved",
  "data": {
    "order": {
      "id": 800533,
      "type": 4,
      "order_amount": 570,
      "collected_cash": 500,
      "customer": { "id": 800300, "name": "El Ezaby Pharmacy", "mobile": "01001234501" },
      "created_at": "2026-08-26T14:02:11+03:00"
    },
    "lines": [
      {
        "order_detail_id": 800969,
        "product_id": 800101,
        "product_name": "Ibuprofen 400mg",
        "product_code": "IBU-400",
        "unit_value": 1,
        "price": 100,
        "tax_amount": 14,
        "discount_on_product": 0,
        "quantity_sold": 5,
        "quantity_returned": 0,
        "quantity_returnable": 5
      }
    ],
    "fully_returned": false
  }
}
```

`quantity_returnable` is `quantity_sold - quantity_returned` — the cap for the
next return on that line. `403` if the invoice belongs to another seller;
`422` if it is not a sale (you cannot return against a return).

#### `POST /orders/returns`

| Field | Rules |
|---|---|
| `order_id` | required, integer, `exists:orders,id` |
| `items` | required, array, min 1 |
| `items.*.product_id` | required, integer, `exists:products,id` |
| `items.*.quantity` | required, numeric, `gt:0` |
| `note` | nullable, string, max 2000 |
| `type` | optional — account id; defaults to the invoice's |
| `cash` | optional, `in:1,2`; defaults to the invoice's |

```bash
curl -X POST "$BASE/api/v2/orders/returns" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
        "order_id": 800533,
        "items": [{ "product_id": 800101, "quantity": 2 }],
        "note": "damaged"
      }'
```

What one call does, in a single transaction:

1. Writes a new order of type `7` carrying `parent_id` = the invoice's id
2. Copies each returned line at **the price actually charged on the invoice**
3. Adds the quantity to `quantity_returned` on the original line
4. Puts the units back on the seller's van
5. Writes a credit entry to the ledger
6. Credits the refund against what the customer owes

Refunds use the invoiced price, discount and tax — not the catalogue price,
which may have moved since the sale.

**Rejections** (`422`, and nothing is written):

| Situation | Message |
|---|---|
| More than is left | `Only 3 of Ibuprofen 400mg can still be returned (2 already returned).` |
| Product not on the invoice | `Product 800100 is not on invoice 800533.` |
| Target is not a sale | `Only a sale invoice can be returned against.` |

Returns accumulate: returning 2 of 5 then 3 more is fine; a fourth unit after
that is refused. The line rows are locked for the duration, so two returns
filed at once cannot both pass the check.

#### The older, unlinked form

`POST /orders` with `order_type: 7` still works and still restocks, but it
records **no `parent_id`** — nothing ties it to a sale, and nothing stops the
same units being returned repeatedly. It remains only so existing clients keep
working. **Use `POST /orders/returns` for new work.**
### `GET /orders`

Sales, returns and installments all live here; filter by `type` for one kind.

| Parameter | Rules |
|---|---|
| `type` | optional, `in:4,7,12,24` |
| `payment_status` | optional, `in:paid,partial,unpaid` |
| `customer_id` · `product_id` · `account_id` | optional integer |
| `cash` | optional, `in:1,2` |
| `min_amount` / `max_amount` | optional numeric, `max_amount` `gte:min_amount` |
| `from` / `to` | optional dates, `to` `after_or_equal:from` |
| `sort` | optional, `in:newest,oldest,amount_asc,amount_desc` |
| `search` | order id, transaction reference, or customer name |

Returns only the signed-in seller's orders.

`payment_status` is derived from the row rather than stored: `paid` when
`collected_cash >= order_amount`, `partial` when something was collected but
not all of it, `unpaid` when nothing was.

`product_id` matches orders that contain that product on a line.

### `GET /orders/totals`

Totals for **the same filter set** as `GET /orders` — accepts every parameter
above.

```json
{
  "success": true,
  "message": "Order totals retrieved",
  "data": {
    "orders": 7,
    "total": 4965.84,
    "collected": 1983.56,
    "tax": 609.84,
    "remaining": 2982.28
  }
}
```

Computed in SQL over the whole filtered result, not the current page, so the
summary above a list always describes the rows beneath it. `remaining` is what
is still owed on the matching orders.

### `GET /orders/{id}`

One order with its lines and customer — the invoice payload. Returns `403`
if the order belongs to another seller.

```json
{
  "success": true,
  "message": "Order retrieved",
  "data": {
    "id": 800500,
    "type": 4,
    "customer_id": 800300,
    "seller_id": 800050,
    "order_amount": 883.5,
    "total_tax": 108.5,
    "collected_cash": 883.5,
    "customer": { "id": 800300, "name": "El Ezaby Pharmacy", "mobile": "01001234501" },
    "details": [
      {
        "id": 800900,
        "product_id": 800100,
        "product_name": "Paracetamol 500mg",
        "product_code": "PRC-500",
        "quantity": 2,
        "price": 20,
        "tax_amount": 2.8,
        "line_total": 40
      }
    ],
    "created_at": "2026-08-25T23:08:47+03:00"
  }
}
```

> `product_name` and `product_code` come from a JSON snapshot stored on the
> line, so an old invoice still renders correctly after the catalogue changes.

### `GET /orders/customers/{id}`

A customer's order history. `403` unless the customer is assigned to you.

---

## Stock

### `GET /stocks`

| Parameter | Meaning |
|---|---|
| `type=4` | The seller's own van stock |
| *(anything else)* | The catalogue they are allowed to sell |
| `category_id` | Narrow to one category |
| `search` | Product name or code (catalogue mode) |
| `limit` / `offset` | Pagination |

Seller-specific prices replace the catalogue price where one exists.

```json
{
  "success": true,
  "message": "Stock list retrieved",
  "data": [
    {
      "stock_id": 800400,
      "refund": false,
      "quantity": 39,
      "main_stock": 56,
      "product": {
        "id": 800101,
        "name": "Ibuprofen 400mg",
        "product_code": "IBU-400",
        "selling_price": 28,
        "tax": 14
      }
    }
  ],
  "meta": { "current_page": 1, "per_page": 10, "total": 7, "last_page": 1 }
}
```

### Returning stock to the warehouse

The seller picks what goes back from their van and files a request; **nothing
moves until an admin approves it**. Approval transfers the quantities in one
transaction, rejection changes no stock at all.

> Previously `POST /stocks/confirm` settled immediately and irreversibly: the
> server decided what was left over and returned it to the warehouse, with no
> say from the seller and no review. A miscount had no remedy. The old
> immediate settlement still lives at `POST /api/v1/stocks/confirm`, which
> also writes the `confirm_stocks` / `stock_histories` records that
> [`GET /stocks/history`](#stock) reads.

#### `POST /stocks/confirm`

| Field | Rules |
|---|---|
| `items` | required, array, min 1 |
| `items.*.product_id` | required, integer, `exists:products,id` |
| `items.*.quantity` | required, integer, min 1 |
| `note` | nullable, string, max 2000 |

```bash
curl -X POST "$BASE/api/v2/stocks/confirm"   -H "Authorization: Bearer $TOKEN"   -H 'Content-Type: application/json'   -d '{ "items": [{ "product_id": 812, "quantity": 6 }], "note": "باقي اليوم" }'
```

```json
{
  "success": true,
  "message": "Stock return request submitted for approval",
  "data": {
    "id": 77,
    "status": "pending",
    "status_text": "بانتظار الموافقة",
    "note": "باقي اليوم",
    "admin_note": null,
    "reviewed_at": null,
    "created_at": "2026-09-11T14:20:00+03:00",
    "items": [
      { "product_id": 812, "quantity": 6,
        "product": { "id": 812, "name": "فوليك أسيد", "product_code": "P-812" } }
    ]
  }
}
```

`201` on success. Rejections:

| Situation | Code |
|---|---|
| More than the van holds, or a product not in the van | `422` |
| A pending request is already open | `409` |

Repeated lines for the same product are summed before the check, so two lines
that each fit but together exceed the van are refused rather than accepted.

#### `GET /stocks/confirm/current`

The seller's latest request — the pending one if there is one, otherwise the
last reviewed. `data` is `null` when they have never filed one, which is what
tells the app to show the picker rather than the waiting screen.

Carries `admin_note` and `reviewed_at` once an admin has acted, so the app can
show why a request was refused.

#### `POST /stocks/confirm/{id}/cancel`

Withdraws a pending request before review. `409` once it has been reviewed,
`404` for another seller's request — not `403`, so the response does not
reveal that it exists.

#### Admin review

Handled in the panel at `admin/stock-returns`, not over the API:

- **approve** — moves each quantity from the van to warehouse stock in a single
  transaction, and marks the request `approved`. Rows are locked while it runs,
  so two approvals cannot both pass the check and deduct twice. If the van no
  longer holds the amount (it changed between filing and review), the whole
  approval is refused and nothing is written.
- **reject** — records `admin_note` explaining why. No stock changes.

### `GET /stocks/history`

Past settlements, newest first, each with its stored summary decoded.

---

## Products

### `GET /products`

| Parameter | Meaning |
|---|---|
| `search` | Name, English name or product code |
| `category_id` | Filter by category |
| `limit` / `offset` | Pagination |

### `GET /products/{id}`

### `GET /products/by-code`

| Parameter | Rules |
|---|---|
| `code` | required string |

`404` when no product carries that code.

### `GET /products/low-stock`

Products at or below their configured threshold.

### `POST /products`

| Field | Rules |
|---|---|
| `name` | required, string, max 255 |
| `product_code` | required, string, max 255, **unique** |
| `purchase_price` | required, numeric, min 0 |
| `selling_price` | required, numeric, min 0 |
| `name_en` | nullable, string, max 255 |
| `category_id` | nullable, string, max 255 |
| `unit_type` | nullable, integer |
| `unit_value` | nullable, numeric |
| `brand` | nullable, string, max 255 |
| `discount` | nullable, numeric, min 0 |
| `discount_type` | nullable, string |
| `tax` | nullable, numeric, min 0 |
| `quantity` | nullable, integer, min 0 |
| `limit_stock` | nullable, integer, min 0 |
| `supplier_id` | nullable, integer |
| `type` | nullable, string |
| `image` | nullable, image, max 4 MB |

The four tiered price columns default to the base price when not supplied.

> This endpoint did not exist in v1 — the route pointed at a controller method
> that was never written and always returned `500`.

### `PUT /products`

Same fields, plus `id` (required, `exists:products,id`). Fields marked
`required` above become `sometimes|required`: send only what changes.

### `DELETE /products/{id}`

### `POST /products/customer-prices`

Prices for a cart of products for one customer, falling back to the catalogue
price where none is negotiated.

| Field | Rules |
|---|---|
| `user_id` | required, integer, `exists:customers,id` |
| `cart` | required, array, min 1 |
| `cart.*` | required, integer, `exists:products,id` |

```json
{
  "success": true,
  "message": "Prices retrieved",
  "data": [ { "id": 800100, "price": 20, "custom": false } ]
}
```

`custom` tells you whether the price is negotiated or the catalogue default.

### `POST /products/customer-price`

| Field | Rules |
|---|---|
| `customer_id` | required, integer, `exists:customers,id` |
| `product_id` | required, integer, `exists:products,id` |
| `price` | required, numeric, min 0 |

---

## Customers

A seller may only read or modify customers assigned to them — anything else
returns `403`.

### `GET /customers`

Supports `search` (name, mobile, pharmacy name), `limit`, `offset`.
`order_count` and `executed_visits` are computed in SQL.

```json
{
  "success": true,
  "message": "Customers retrieved",
  "data": [
    {
      "id": 800309,
      "name": "Misr Pharmacy",
      "mobile": "01001234510",
      "email": "branch9@pharmacy.test",
      "pharmacy_name": "Misr Pharmacy",
      "address": "Talkha",
      "balance": 0,
      "credit": 0,
      "limit": 20000,
      "active": true,
      "region": { "id": 800011, "name": "Giza" },
      "order_count": 1,
      "executed_visits": 0,
      "created_at": "2026-08-25T23:08:47+03:00"
    }
  ],
  "meta": { "current_page": 1, "per_page": 25, "total": 4, "last_page": 1 }
}
```

### `GET /customers/{id}` · `DELETE /customers/{id}`

### `POST /customers`

| Field | Rules |
|---|---|
| `name` | required, string, max 255 |
| `mobile` | required, string, max 255 |
| `email` | nullable, email, max 255 |
| `name_en` | nullable, string, max 255 |
| `pharmacy_name` | nullable, string, max 5000 |
| `region_id` | nullable, integer, `exists:regions,id` |
| `category_id` · `specialist` · `type` | nullable, integer |
| `state` · `city` · `zip_code` · `address` | nullable, string, max 255 |
| `limit` | nullable, numeric, min 0 |
| `latitude` | nullable, numeric, `between:-90,90` |
| `longitude` | nullable, numeric, `between:-180,180` |
| `image` | nullable, image, max 4 MB |

`latitude` / `longitude` are the pharmacy's position on the map. They are
stored as varchar in this schema, so the range is enforced by validation; the
response casts them back to numbers, or `null` when never set.

The new customer is linked to the signed-in seller in the same transaction.

### `PUT /customers`

Same fields plus `id` (required, integer).

### `POST /customers/add-balance`

Records a payment against the customer's balance and the account, in one
transaction.

| Field | Rules |
|---|---|
| `customer_id` | required, integer, `exists:customers,id` |
| `account_id` | required, integer, `exists:accounts,id` |
| `amount` | required, numeric, `gt:0` |
| `date` | required, date |
| `description` | nullable, string, max 255 |

> Also new in v2 — the v1 route pointed at a method that was never written.

---

## Suppliers

### `GET /suppliers`

`search` covers name, mobile, email and address.

### `GET /suppliers/{id}` · `POST /suppliers` · `PUT /suppliers` · `DELETE /suppliers/{id}`

| Field | Rules |
|---|---|
| `name` | required, string, max 255 |
| `mobile` | required, string, max 255 |
| `email` | nullable, email, max 255 |
| `state` · `city` · `zip_code` · `address` | nullable, string, max 255 |
| `due_amount` · `credit` | nullable, numeric |
| `limit` | nullable, numeric, min 0 |
| `type` | nullable, integer |
| `active` | nullable, boolean |
| `image` | nullable, image, max 4 MB |

### `GET /suppliers/by-city`

| Parameter | Rules |
|---|---|
| `city` | required string |

### `GET /suppliers/{id}/transactions`

The supplier's ledger, newest first.

| Parameter | Rules |
|---|---|
| `from` | optional date |
| `to` | optional date, `after_or_equal:from` |

`404` when the supplier does not exist — rather than an empty ledger that looks
like a supplier with no history.

### `POST /suppliers/pay`

Pays down a supplier's due amount from an account.

| Field | Rules |
|---|---|
| `supplier_id` | required, integer, `exists:suppliers,id` |
| `account_id` | required, integer, `exists:accounts,id` |
| `amount` | required, numeric, `gt:0` |
| `description` | nullable, string, max 255 |

Returns `422` when the account cannot cover it, and **writes nothing**.

> v1 required four amount fields the client had to compute and trusted them;
> it also answered `success: true` on insufficient funds, so a failed payment
> read as a successful one. Here the service derives the rest and rejects it.

---

## Transactions

### `GET /transactions`

| Parameter | Meaning |
|---|---|
| `type` | `tran_type` — see `/transactions/types` |
| `account_id` · `seller_id` · `customer_id` · `supplier_id` | Filter by party |
| `order_id` | Entries tied to one order |
| `direction` | `in` (debit) or `out` (credit) |
| `cash` | `in:1,2` |
| `min_amount` / `max_amount` | Amount range, `max_amount` `gte:min_amount` |
| `from` / `to` | Date range, `to` `after_or_equal:from` |
| `search` | Description, entry id, or order id |
| `limit` / `offset` | Pagination |

### `GET /transactions/totals`

Money in, money out and the net, for the same filters as the listing.

```json
{
  "success": true,
  "message": "Transaction totals retrieved",
  "data": { "entries": 33, "money_in": 17189.04, "money_out": 8377.48, "net": 8811.56 }
}
```

`money_in` sums the debit entries, `money_out` the credits.

### `GET /transactions/types`

The distinct `tran_type` values present in the ledger.

### `POST /transactions/transfer`

Moves money between two accounts. Both legs and both balance updates share one
transaction, and the rows are locked so two concurrent transfers cannot both
pass the balance check.

| Field | Rules |
|---|---|
| `account_from_id` | required, integer, `exists:accounts,id`, `different:account_to_id` |
| `account_to_id` | required, integer, `exists:accounts,id` |
| `amount` | required, **numeric**, `gt:0` |
| `description` | required, string, max 255 |
| `date` | required, date |

```json
{
  "success": true,
  "message": "Transfer completed",
  "data": {
    "from": { "account_id": 800001, "balance": 249700, "transaction_id": 91 },
    "to":   { "account_id": 800002, "balance": 750300, "transaction_id": 92 },
    "amount": 300
  }
}
```

`422` when the source account lacks the balance — nothing is written.

> v1 wrote both legs with no transaction, so a failure after the first left
> money debited from one account and never credited to the other. It also
> reported insufficient funds as HTTP `203`, which most clients treat as
> success, and its `amount` rule omitted `numeric`, so `"abc"` passed.

### `POST /transactions/expense` · `POST /transactions/income`

| Field | Rules |
|---|---|
| `account_id` | required, integer, `exists:accounts,id` |
| `amount` | required, numeric, `gt:0` |
| `description` | required, string, max 255 |
| `date` | required, date |

Both return `201`. An expense beyond the account balance is rejected with `422`.

---

## Seller deposits

Cash the seller has collected on the road, handed in to one of the company's
accounts.

> Not the same as [`/transactions/transfer`](#transactions), which moves money
> between two accounts that already belong to the company. Here the money is
> physically with the seller.

**The money does not move when the deposit is filed.** It is recorded as
*pending*; an admin approves it from the panel, and only then is the account
credited, the ledger entry written and the amount cleared off what the seller
is holding — all in one transaction, with the account row locked.

| `status` | `status_text` | Meaning |
|---|---|---|
| `0` | `pending` | Filed, waiting for an admin |
| `1` | `approved` | Account credited, ledger written |
| `2` | `rejected` | Refused; nothing moved |

### `POST /deposits`

| Field | Rules |
|---|---|
| `account_id` | required, integer, `exists:accounts,id` |
| `amount` | required, numeric, `gt:0` |
| `note` | nullable, string, max 2000 |
| `img` | nullable, image, max 4 MB — the deposit slip |

Send `multipart/form-data` when attaching the photo, JSON otherwise.

```json
{
  "success": true,
  "message": "Deposit submitted for approval",
  "data": {
    "id": 1,
    "seller_id": 800050,
    "account_id": 800001,
    "amount": 1500,
    "note": "Cash handed in",
    "image": null,
    "status": 0,
    "status_text": "pending",
    "account": { "id": 800001, "account": "Main Cash", "account_number": "CASH-001" },
    "created_at": "2026-08-26T12:33:55+03:00"
  }
}
```

> v1's `POST /api/v1/transactionseller` inserted without a value for `img`,
> which is `NOT NULL` with no default — so **every request that did not attach
> a photo returned `500`**. It also accepted a negative amount and an
> `account_id` that did not exist. That route now points at this
> implementation.

### `GET /deposits`

| Parameter | Rules |
|---|---|
| `status` | optional, `in:0,1,2` |
| `from` / `to` | optional dates, `to` `after_or_equal:from` |
| `limit` / `offset` | Pagination |

Returns only the signed-in seller's deposits.

### `GET /deposits/{id}`

`403` if the deposit was filed by another seller.

### `GET /deposits/summary`

Totals by status, for the seller's summary screen.

```json
{
  "success": true,
  "message": "Deposit summary retrieved",
  "data": {
    "pending":  { "count": 1, "total": 1500 },
    "approved": { "count": 0, "total": 0 },
    "rejected": { "count": 0, "total": 0 }
  }
}
```

### Approval

Approval happens in the admin panel, not over the API. It is guarded against
being applied twice — a second approval is refused rather than crediting the
account again.

---

## Visits

A seller may only plan or log visits against their own customers.

### `GET /visits`

| Parameter | Meaning |
|---|---|
| `date` | Exact date |
| `customer_id` | Filter by customer |
| `limit` / `offset` | Pagination |

### `POST /visits`

| Field | Rules |
|---|---|
| `customer_id` | required, integer, `exists:customers,id` |
| `date` | required, date |
| `note` | nullable, string, max 2000 |

### `GET /visits/results`

Outcomes this seller has recorded.

### `POST /visits/results`

| Field | Rules |
|---|---|
| `customer_id` | required, integer, `exists:customers,id` |
| `note` | required, string, max 5000 |
| `lat` | nullable, numeric, `between:-90,90` |
| `lang` | nullable, numeric, `between:-180,180` |

> The columns are named `lat` / `lang` in this schema. The response exposes
> them as `latitude` / `longitude`.

### `GET /visits/customers/{id}/results`

Outcomes recorded against one customer. `403` unless they are assigned to you.

---

## Attendance

### `GET /attendance`

The signed-in seller's own attendance records.

| Parameter | Rules |
|---|---|
| `from` | optional date |
| `to` | optional date, `after_or_equal:from` |

Each entry: `id`, `admin_id`, `date`, `check_in`, `check_out`, `status`,
`time_late`, `expected_hours`, `worked_hours`, `note`.

> **Check in / out stays on v1** (`POST /api/v1/attendance/store`). Its shift
> matching — midnight-crossing shifts, grace windows, lateness — feeds payroll
> and already validates its input, so it was left alone deliberately.

---

## Salary

The signed-in seller's own payslips. A payslip exists only once an admin has
entered it for that month.

### `GET /salary`

| Parameter | Rules |
|---|---|
| `month` | optional — `2026-08`, or just `8` when paired with `year` |
| `year` | optional, integer, `between:2000,2100` |

With neither, it answers for the month just gone.

> The column stores `YYYY-MM`. v1 matched the raw input against it, so
> `?month=8` never matched a row and the endpoint returned an empty list for
> every month. Here `8` is normalised to `2026-08` first.

```json
{
  "success": true,
  "message": "Salary retrieved",
  "data": {
    "id": 800800,
    "month": "2026-07",
    "basic": 6000,
    "transport": 750,
    "visits_pay": 425,
    "other": 300,
    "deductions": 0,
    "net": 7475,
    "commission": 940,
    "visits": { "target": 40, "achieved": 31 },
    "working_days": 26,
    "score": 86,
    "note": "لاتوجد ملاحظات",
    "manager_note": "أداء جيد هذا الشهر",
    "status": "approved",
    "status_text": "معتمد",
    "details": [
      { "label": "الراتب الأساسي", "amount": 6000, "type": "add" },
      { "label": "بدل انتقال",     "amount": 750,  "type": "add" },
      { "label": "حافز الزيارات",   "amount": 425,  "type": "add" },
      { "label": "أخرى",            "amount": 300,  "type": "add" }
    ],
    "created_at": "2026-08-26T18:04:41+03:00"
  }
}
```

| Field | Column | Meaning |
|---|---|---|
| `basic` | `salary` | Base pay |
| `transport` | `transport_amount` | Travel allowance |
| `visits_pay` | `salary_of_visitors` | Visit incentive |
| `other` | `other` | Any other addition |
| `deductions` | `discount` | Total deducted |
| `net` | `total` | What is actually paid |
| `commission` | `commission` | Sales commission — **not part of `net`** |
| `visits.target` / `.achieved` | `number_of_visitors` / `result_of_visitors` | Visit plan vs actual |
| `working_days` | `number_of_days` | Days worked |
| `score` | `score` | Performance score |
| `manager_note` | `notemanager` | The manager's comment |

**`net` is the stored `total`**, which the admin form computes as
`salary + transport_amount + salary_of_visitors + other - discount`. It is not
recalculated at read time, so a payslip always reports the figure the admin
approved. Where an older row has no `total`, that formula is applied as a
fallback.

**Commission is excluded from `net`** — the admin form records it but leaves it
out of the total. Show it as its own line, not folded into the net.

Every money column on `salaries` is a varchar; all of them are cast to numbers
here.

`details` is the same figures as a labelled breakdown, with `type` of `add` or
`deduct`. Lines worth zero are omitted.

**No payslip for that month** — `200`, not `404`, because nothing having been
entered yet is not an error:

```json
{
  "success": true,
  "message": "No salary recorded for this month",
  "data": { "month": "2026-08", "salary": null }
}
```

### `GET /salary/history`

| Parameter | Rules |
|---|---|
| `limit` | optional, integer, `between:1,36` (default 12) |

Recent payslips, newest first, each in the shape above.
---

## Manager

For accounts that manage sellers. A manager is identified by
`admins.type = 'manager'` — `role` stays `seller` on these accounts, so
checking `role` alone treats a manager as an ordinary seller. Full admins and
anyone with sellers assigned in `admin_sellers` also qualify.

Every path here is limited to the manager's own sellers, so one manager cannot
read or write another's. `403` when the account is not a manager, or when the
seller named is not theirs.

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/manager/sellers` | Their sellers — last position and today's attendance |
| `GET` | `/manager/sellers/{id}/attendance` | One seller's attendance |
| `GET` | `/manager/sellers/{id}/notes` | Development notes on one seller |
| `POST` | `/manager/sellers/{id}/notes` | Write a development note |
| `GET` | `/manager/sellers/{id}/rating` | One seller's current rating |
| `POST` | `/manager/sellers/{id}/rating` | Write a seller's rating |
| `GET` | `/manager-notes` | The signed-in seller's own manager notes |

### Rating

The seller's **current** rating — a single score and note held on the seller
record (`admins.score` / `admins.note`), overwritten each time it is set. This
is what the panel's rating screen writes, so the app and the panel read and
write the same value.

> Not the same as the **monthly** score on a payslip (`salaries.score`, read
> through [`GET /hr/ratings`](#salary)), which is kept per month and does not
> change once the month is entered.

| Field | Rules |
|---|---|
| `score` | required, numeric, `between:0,100` |
| `note` | nullable, string, max 5000 |

Omitting `note` leaves any existing note untouched rather than clearing it.
The seller reads their own from `GET /hr/my-rating`, and every row of
`GET /manager/sellers` carries `rating` and `rating_note`.

```json
{
  "seller": { "id": 800050, "name": "Ahmed Nour" },
  "score": 87,
  "note": "أداء ممتاز",
  "updated_at": "2026-09-11T16:40:07+03:00"
}
```

> The v1 equivalent, `POST /api/v1/seller/rating`, writes the same two columns
> but checks no ownership at all — any authenticated seller can rate any
> other. This one is limited to the manager's own sellers.

### Courses

Courses a manager assigns to their sellers. The seller reads their own from
[`GET /hr/courses`](#seller-hr).

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/manager/courses` | Courses this manager created — `seller_id`, `search`, `limit`, `offset` |
| `POST` | `/manager/courses` | Assign a course to a seller |
| `GET` | `/manager/courses/{id}` | One course |
| `PUT` `POST` | `/manager/courses/{id}` | Update it — `POST` too, for `multipart` uploads |
| `DELETE` | `/manager/courses/{id}` | Remove it |

| Field | Rules |
|---|---|
| `seller_id` | required, integer, `exists:admins,id` — must be one of yours |
| `name` | required, string, max 500 |
| `link` | nullable, url |
| `image` | nullable, image, max 4 MB — send as `multipart/form-data` |

On update every field is `sometimes`: send only what changes. Sending `link`
as `null` clears it.

Ownership is twofold — a course belongs to the manager who created it
(`admin_id`), and may only be assigned to a seller in their `admin_sellers`.
A course belonging to another manager answers `404`, not `403`, so the
response does not reveal that it exists.

```json
{
  "id": 12,
  "name": "Cold chain handling",
  "link": "https://example.com/course",
  "image": "course/2026-09-10-abc123.png",
  "image_url": "https://host/storage/course/2026-09-10-abc123.png",
  "seller": { "id": 800050, "name": "Ahmed Nour" },
  "created_at": "2026-09-10T18:04:41+03:00"
}
```

---

## Lookup tables

Five modules share one implementation, so they expose an identical surface.

| Module | Prefix | Searchable on | Has status |
|---|---|---|---|
| Brands | `/brands` | name | no |
| Units | `/units` | unit type, symbol | no |
| Accounts | `/accounts` | account, number, description | no |
| Categories | `/categories` | name | **yes** |
| Coupons | `/coupons` | title, code | **yes** |

### Common endpoints

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/{module}` | List — `search`, `limit`, `offset` |
| `GET` | `/{module}/{id}` | One record |
| `POST` | `/{module}` | Create |
| `PUT` | `/{module}` | Update — body carries `id` |
| `DELETE` | `/{module}/{id}` | Delete |
| `PATCH` | `/{module}/{id}/status` | Toggle status |

`PATCH .../status` returns `400` on a table without a status column
(brands, units, accounts).

### Fields

**Brands** — `name` (required, max 255), `image` (nullable image).

**Units** — `unit_type` (required, max 255), `symbol`, `conversion_rate`
(numeric, min 0), `base_unit_id` (integer), `is_base` (boolean).

**Accounts** — `account` and `account_number` (required, max 255),
`description`, `balance` (numeric), `storage_id` (`exists:storages,id`).

**Categories** — `name` (required, max 255), `parent_id`, `position`, `type`
(integers), `status` (boolean), `image`.

**Coupons** — `title` (required, max 100), `code` (required, max 255),
`coupon_type`, `user_limit` (integer, min 0), `start_date`,
`expire_date` (`after_or_equal:start_date`), `min_purchase`, `max_discount`,
`discount` (numeric, min 0), `discount_type` (max 15), `status` (boolean).

### Category filters

`GET /categories` takes three filters beyond the shared `search`:

| Parameter | Rules | Meaning |
|---|---|---|
| `type` | optional integer | `0` medical specialties, `1` product categories |
| `status` | optional boolean | `1` active only, `0` inactive only |
| `parent_id` | optional integer | Children of one parent; `0` for top level |

They combine, so `?type=0&status=1` returns the active medical specialties.

Every category also carries `image_url` — the full URL to the image, built
from `storage/category/`. The raw `image` filename is still returned beside
it, so existing clients keep working.

### Sub-categories

Sub-categories are category rows carrying a parent, not a separate table.

| Method | Path |
|---|---|
| `GET` | `/categories/{id}/children` |
| `POST` | `/categories/{id}/children` |

The child is created with `parent_id` set and `position = 1`.

---

## Reference data

Read-only lists for populating pickers.

| Method | Path | Returns |
|---|---|---|
| `GET` | `/regions` | All regions — `id`, `name`, `name_en` |
| `GET` | `/regions/mine` | Only the regions this seller covers |
| `GET` | `/categories/mine` | Only the categories assigned to this seller |
| `GET` | `/storages` | `id`, `name` |
| `GET` | `/specialties` | Medical specialties — `categories.type = 0` |
| `GET` | `/product-categories` | Product categories — `categories.type = 1` |
| `GET` | `/documents` | The signed-in seller's documents — `id`, `name`, `description`, `attachments[]` |
| `GET` | `/documents/{id}` | One document, same shape |

### The seller's own categories

`GET /categories/mine` returns the categories assigned to the signed-in seller
through `seller_categories` — the same assignment that decides what they are
allowed to sell, so the app can fill a category picker with those rather than
every category in the system.

| Parameter | Rules | Meaning |
|---|---|---|
| `type` | optional integer | `0` medical specialties, `1` product categories |
| `status` | optional boolean | `1` active only, `0` disabled only |

Each entry is the same shape as [`GET /categories`](#lookup-tables), `image_url`
included. Assignments can point at disabled categories, so pass `status=1` when
the picker should offer only what is actually sellable.

A seller with no assignment gets an empty list, not an error.

### Document visibility

Documents are scoped to the signed-in seller. A document is visible when it is
either assigned to that seller through the `document_sellers` pivot, or
assigned to nobody at all — an unassigned document is public to every seller,
which is what keeps documents created before assignment existed visible.

Assignment is managed from the admin document form, whose seller picker is
limited to the sellers that admin manages (`admin_sellers`).

`GET /documents/{id}` runs through the same rule and answers `404` — not `403`
— for a document the seller may not see, so the response does not reveal that
the document exists.

The rule lives in one place, `Document::scopeVisibleTo()`, so both endpoints
cannot drift apart.

---

## Architecture

```
Controller (Api/V2)   thin: HTTP in, Resource out
  → FormRequest       validation rules
  → Service           business rules, transactions
     → Repository     data access only
  → Resource          response shape
```

| Piece | Role |
|---|---|
| `App\Traits\ApiResponse` | Builds the `{success, message, data}` envelope |
| `App\Http\Middleware\StandardApiResponse` | The `api.standard` alias that opts a route in |
| `App\Exceptions\Handler` | Renders errors in the envelope — **only** for routes carrying `api.standard`, so v1 keeps its legacy payloads |

Domain rules throw rather than return an HTTP status, which keeps them testable
outside a request: `InsufficientBalanceException` and
`InsufficientStockException` both map to `422`.

### Adding a module

1. `App\Repositories\<X>Repository` extending `BaseRepository` (or `CrudRepository` for a lookup table)
2. `App\Services\<X>Service` for the rules
3. `App\Http\Requests\Api\V1\<X>Request` for validation
4. `App\Http\Resources\Api\V1\<X>Resource` for the response
5. A controller in `App\Http\Controllers\Api\V2`
6. Register it in `routes/api/v2/api.php` — already inside `api.standard`

---

## Testing

```bash
./vendor/bin/phpunit tests/Feature/Api/V2      # the v2 modules
./vendor/bin/phpunit                           # everything
```

Tests run against in-memory SQLite, so they never touch the database in `.env`.
See `tools/testdb/README.md` for running the app locally and seeding demo data.
