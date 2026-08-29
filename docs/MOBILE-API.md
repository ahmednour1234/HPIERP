# Mobile integration guide — API v2

For the seller (mandob) app. This is the task-ordered companion to
[`API-V2.md`](API-V2.md), which is the full endpoint reference — start here,
go there for the complete field lists.

Everything below is verified against a running server: the payloads are real
responses, not illustrations.

---

## 1. Setup

| | |
|---|---|
| Base URL (local) | `http://127.0.0.1:8000/api/v2` |
| Base URL (live) | `https://<host>/api/v2` |
| Login lives on | `/api/v1/login` — there is no v2 login |

Send these on every authenticated call:

```
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

> **`Accept: application/json` is not optional.** Without it Laravel may answer
> a failure with an HTML error page instead of JSON, and the app's parser will
> throw on something it cannot read.

### Testing from a device

`127.0.0.1` is the phone itself, not your machine. Start the server bound to
the network and use your computer's LAN IP:

```bash
APP_ENV=local php artisan serve --host=0.0.0.0 --port=8000
# then use http://192.168.1.x:8000/api/v2
```

Android blocks plaintext HTTP by default — allow it for the dev host, or use
HTTPS.

---

## 2. Login and session

```http
POST /api/v1/login
Content-Type: application/json

{ "code": "MND-01", "password": "password" }
```

```json
{
  "message": "You are logged in",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "admin": { "id": 800050, "f_name": "Ahmed", "role": "seller" },
  "kilometer": 5
}
```

- `code` is the seller's **mandob code**, not an email.
- Only accounts with `role = seller` can log in.
- Store the token in secure storage (Keychain / EncryptedSharedPreferences),
  never in plain preferences.

**Handle these:**

| Status | Meaning | What the app should do |
|---|---|---|
| `422` | Wrong code or password | Show the message, stay on login |
| `403` | A field was missing | Show the field errors |
| `401` *(on any later call)* | Token expired or revoked | Clear the token, return to login |

> Treat **any** `401` as "session over". Retrying with the same token will not
> recover.

---

## 3. The response envelope

Every v2 response has the same shape, so one parser handles all of them.

```json
{ "success": true, "message": "Customers retrieved", "data": { } }
```

Lists add `meta`:

```json
{
  "success": true,
  "message": "Customers retrieved",
  "data": [ ... ],
  "meta": { "current_page": 1, "per_page": 25, "total": 5, "last_page": 5 }
}
```

Failures keep the same envelope; `errors` appears only for validation:

```json
{
  "success": false,
  "message": "The given data was invalid",
  "errors": { "mobile": ["Mobile number is required."] }
}
```

**Suggested client model**

```dart
class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final Map<String, List<String>>? errors;   // field -> messages
  final Meta? meta;                          // lists only
}
```

Render `errors` under the matching input. For a business-rule rejection
(`422` with no `errors`) show `message` as a toast — for example
*"Not enough stock for Paracetamol 500mg (have 3, need 10)."*

### Status codes

| Code | Meaning | App behaviour |
|---|---|---|
| `200` / `201` | Success | Continue |
| `401` | Bad or expired token | Log out |
| `403` | Not your record | "This isn't assigned to you" |
| `404` | Not found | Empty state |
| `422` | Validation or business rule | Show `errors`, else `message` |
| `429` | Rate limited (60/min) | Back off and retry |
| `5xx` | Server error | "Try again later" — do not retry in a loop |

---

## 4. Pagination

| Parameter | Default | Meaning |
|---|---|---|
| `limit` | `25` | Rows per page |
| `offset` | `1` | **Page number** |
| `search` | — | Free-text filter |

> **`offset` is a page number, not a row offset.** For infinite scroll,
> increment it by 1 — not by `limit`. Stop when
> `meta.current_page >= meta.last_page`.

```http
GET /api/v2/customers?limit=25&offset=2      → rows 26–50
```

---

## 5. Daily flow

The order a seller actually works in.

```
login → profile → stocks (load the van)
   → customers → visits → orders → returns → collect
   → deposits (hand in the cash) → stocks/confirm (end of day)
```

### 5.1 Van stock

```http
GET /api/v2/stocks?type=4
```

`type=4` is the seller's **own van stock**. Anything else returns the
catalogue they are allowed to sell.

```json
{
  "success": true,
  "message": "Stock list retrieved",
  "data": [
    {
      "stock_id": 800400,
      "refund": false,
      "quantity": 33,
      "main_stock": 47,
      "product": {
        "id": 800101,
        "name": "Ibuprofen 400mg",
        "product_code": "IBU-400",
        "purchase_price": 18.5,
        "selling_price": 28,
        "discount_type": "amount",
        "discount": 0,
        "tax": 14,
        "image": "def.png"
      }
    }
  ],
  "meta": { "current_page": 1, "per_page": 1, "total": 7, "last_page": 7 }
}
```

- `quantity` — what is left on the van now
- `main_stock` — what was loaded at the start of the day
- `selling_price` already reflects a seller-specific price if one exists

Also useful: `?category_id=` and `?search=` (catalogue mode).

### 5.2 Customers

```http
GET /api/v2/customers?search=صيدلية
```

The list is **already scoped to the signed-in seller** — no filter needed.

```json
{
  "id": 800313,
  "name": "صيدلية الخريطة",
  "mobile": "01555777888",
  "pharmacy_name": null,
  "address": "",
  "latitude": 31.2001,
  "longitude": 29.9187,
  "balance": 0,
  "credit": 0,
  "limit": 0,
  "type": 0,
  "category_id": null,
  "active": true,
  "region_id": 800010,
  "region": { "id": 800010, "name": "Cairo" },
  "order_count": 0,
  "executed_visits": 0,
  "created_at": "2026-08-26T03:23:18+03:00"
}
```

`balance` is what the customer **owes**. `limit` is their credit ceiling —
check it before allowing a credit sale.

#### Adding a customer

```http
POST /api/v2/customers

{
  "name": "صيدلية النور",
  "mobile": "01555000222",
  "region_id": 800010,
  "type": 1,
  "category_id": 3,
  "specialist": 1,
  "pharmacy_name": "صيدلية النور",
  "city": "طنطا",
  "address": "شارع الجيش",
  "limit": 5000,
  "latitude": 30.0444,
  "longitude": 31.2357
}
```

Only `name` and `mobile` are required; everything else is optional.
The new customer is linked to the signed-in seller automatically.

**Capturing the location.** Send the device's GPS fix when the seller is
standing at the pharmacy:

| Field | Range |
|---|---|
| `latitude` | `-90` … `90` |
| `longitude` | `-180` … `180` |

Send them as **numbers**, not strings. Out-of-range values return `422`:

```json
{ "errors": { "latitude": ["The latitude must be between -90 and 90."] } }
```

Both come back as numbers, or `null` when never set — so guard before opening
a map:

```dart
if (c.latitude != null && c.longitude != null) openMap(c.latitude!, c.longitude!);
```

To attach a photo, send `multipart/form-data` with an `image` part (max 4 MB)
instead of JSON.

#### Editing

```http
PUT /api/v2/customers

{ "id": 800313, "latitude": 31.2001, "longitude": 29.9187 }
```

`id` in the **body**, not the URL. Send only the fields that changed —
omitted fields keep their values.

> The response echoes back everything stored, including `region` with its
> name and the coordinates, so the app can update its cache from the reply
> without a second fetch.

### 5.3 Visits

```http
POST /api/v2/visits
{ "customer_id": 800300, "date": "2026-09-02", "note": "متابعة" }
```

Recording the outcome — this is where the GPS fix of the *visit* goes:

```http
POST /api/v2/visits/results
{ "customer_id": 800300, "note": "تم الطلب", "lat": 30.05, "lang": 31.23 }
```

> **Watch the field names.** The visit result uses **`lat` / `lang`** (the
> schema's column names). The customer record uses **`latitude` / `longitude`**.
> They are different fields on different endpoints.
>
> Responses expose both as `latitude` / `longitude`.

### 5.4 Placing an order

```http
POST /api/v2/orders

{
  "user_id": 800300,
  "order_type": 4,
  "cart": [
    { "id": 800100, "quantity": 5, "price": 20 },
    { "id": 800101, "quantity": 2, "price": 28 }
  ],
  "type": 800001,
  "cash": 1,
  "collected_cash": 170
}
```

| Field | Meaning |
|---|---|
| `user_id` | The **customer** id |
| `order_type` | `4` sale · `7` return · `12`/`24` installment |
| `type` | The **account** id the money lands on |
| `collected_cash` | What was actually collected — less than the total means partly paid |
| `extra_discount` | A discount on the whole order |
| `extra_discount_type` | `percent` or `amount` |

**Per-line discounts.** Each cart line takes an optional `discount`,
`discount_type` and `tax`:

```json
{
  "cart": [
    { "id": 800100, "quantity": 2, "price": 100,
      "discount": 10, "discount_type": "percent", "tax": 5 }
  ]
}
```

| `discount_type` | Meaning |
|---|---|
| `percent` | A percentage of that line's price — `10` on a price of `100` is `10` per unit |
| `amount` | A flat figure per unit — `15` is `15` per unit |

Omit them and the product's own configured discount and tax apply, so an app
that does not offer discounts still gets correct totals.

The response echoes `discount_on_product` (already resolved to a figure) and
`discount_type` on each line — display the figure, use the type only to label
it.

**The receipt photo.** Send `img` as `multipart/form-data` to attach the slip:

```
POST /api/v2/orders          (multipart/form-data)
  user_id=800300
  order_type=4
  cart[0][id]=800100
  cart[0][quantity]=2
  cart[0][price]=100
  img=@receipt.png
```

Max 4 MB, must be a real image. It is saved on the order **and its ledger
entry**, and comes back as a filename in `data.img` — build the full URL
against your storage base.

**Server-authoritative pricing.** A negotiated customer price beats a seller
price, which beats the `price` you sent. Read the totals back from the
response; do not display your own computed figure as final.

**Everything is one transaction.** The order, its lines, the stock movements
and the ledger entry all succeed or all fail. There is no partial write to
clean up.

Insufficient stock returns `422` and writes nothing:

```json
{
  "success": false,
  "message": "Not enough stock for Paracetamol 500mg (have 3, need 10)."
}
```

> **Offline carts.** If the app stores the cart as a JSON string, you may send
> `cart` as that string — it is decoded before validation. Sending a real
> array is preferred.

#### Retries and duplicate orders

There is no idempotency key yet. A network timeout after the server committed
will create a second order if you blind-retry. Before retrying, call
`GET /api/v2/orders?limit=5` and check whether yours is already there.

### 5.5 Filing a return

A return is always **against the invoice it reverses**. Two calls:

**1. Ask what can still be returned**

```http
GET /api/v2/orders/{id}/returnable
```

```json
{
  "data": {
    "order": { "id": 800533, "order_amount": 570,
               "customer": { "id": 800300, "name": "El Ezaby Pharmacy" } },
    "lines": [
      { "product_id": 800101, "product_name": "Ibuprofen 400mg",
        "product_code": "IBU-400", "price": 100, "tax_amount": 14,
        "quantity_sold": 5, "quantity_returned": 0, "quantity_returnable": 5 }
    ],
    "fully_returned": false
  }
}
```

Build the return screen from `lines`: show `product_name`, what was sold, and
cap the stepper at **`quantity_returnable`**. That figure already accounts for
earlier returns on the same invoice.

**2. Send back the quantities**

```http
POST /api/v2/orders/returns

{
  "order_id": 800533,
  "items": [{ "product_id": 800101, "quantity": 2 }],
  "note": "تالف"
}
```

Send only the lines the customer is actually giving back — leave the rest out.

The refund uses **the price on the invoice**, not today's catalogue price, so
do not compute the refund yourself: read `order_amount` off the response.

One call restocks the van, records the refund on the ledger, credits the
customer, and bumps `quantity_returned` on the original invoice — all or
nothing.

**Errors to surface** (`422`, nothing written):

| Situation | Message |
|---|---|
| Too many | `Only 3 of Ibuprofen 400mg can still be returned (2 already returned).` |
| Not on the invoice | `Product 800100 is not on invoice 800533.` |
| Returning a return | `Only a sale invoice can be returned against.` |

> After a successful return, re-fetch `/returnable` before letting the seller
> file another against the same invoice — the caps have changed.

> **Do not use `POST /orders` with `order_type: 7`** for new work. It still
> works, but records no link to a sale, so nothing stops the same units being
> returned again and again. It exists only for older clients.
### 5.6 Collecting for an invoice

When the seller collects against a **specific invoice** — the usual case on a
round — use this rather than `add-balance`:

```http
POST /api/v2/orders/{id}/collect

{ "amount": 114, "account_id": 800001, "date": "2026-08-26", "note": "دفعة" }
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

Read `remaining` and `payment_status` straight off the response to update the
row in place — no need to re-fetch the invoice.

| `payment_status` | Show as |
|---|---|
| `unpaid` | Nothing collected |
| `partial` | Part paid — `remaining` is what is left |
| `paid` | Settled |

Attach the receipt by sending `multipart/form-data` with an `img` part
(max 4 MB) instead of JSON.

**Errors to surface** (`422`, nothing written):

| Situation | Message |
|---|---|
| More than is owed | `المبلغ يتجاوز المتبقّي على الفاتورة` |
| Invoice is a return | `لا يمكن تحصيل فاتورة مرتجع` |

> Cap the amount field at `remaining` in the UI so the seller does not hit the
> first error by accident — but still handle it: someone else may have
> collected against the same invoice since the screen loaded.

> **Which endpoint?** `/orders/{id}/collect` when the money is for a known
> invoice. `/customers/add-balance` when the customer is paying down their
> balance generally, with no invoice attached.
### 5.7 Taking a payment

```http
POST /api/v2/customers/add-balance
{ "customer_id": 800300, "account_id": 800001, "amount": 250,
  "date": "2026-09-02", "description": "دفعة نقدية" }
```

Reduces what the customer owes and credits the account, in one transaction.
`amount` must be greater than zero.

### 5.8 Handing in the cash

The seller deposits what they collected into a company account.

```http
POST /api/v2/deposits

{ "account_id": 800001, "amount": 1500, "note": "توريد نقدي" }
```

Attach the deposit slip by sending `multipart/form-data` with an `img` part
instead of JSON.

```json
{
  "success": true,
  "message": "Deposit submitted for approval",
  "data": {
    "id": 1,
    "amount": 1500,
    "status": 0,
    "status_text": "pending",
    "account": { "id": 800001, "account": "Main Cash" }
  }
}
```

> **Nothing moves yet.** The deposit is *pending* until an admin approves it in
> the panel. Show it in the app as "awaiting approval", not as settled — the
> seller is still accountable for that cash until `status` becomes `1`.

| `status` | Show as |
|---|---|
| `0` | Awaiting approval |
| `1` | Approved |
| `2` | Rejected |

Track them with:

```http
GET /api/v2/deposits?status=0        # still pending
GET /api/v2/deposits/summary         # totals per status
```

`amount` must be greater than zero and `account_id` must exist, or you get
`422` with the field named.

> This is **not** `/transactions/transfer` — that one moves money between two
> company accounts. Use `/deposits` for cash the seller is carrying.

### 5.9 End of day

```http
POST /api/v2/stocks/confirm
```

Closes the day: what sold becomes history, unsold units return to the
warehouse, and the working tables are cleared.

The response is the settlement summary — `total_stock`, `remain_stock`,
`order_count`, `total_cash`, `total_credit`, `refund_total`, plus
`products[]` and `remain_products[]`.

> **This is destructive and not idempotent.** Confirm with the seller before
> calling it, disable the button while it is in flight, and never auto-retry.
> Past settlements: `GET /api/v2/stocks/history`.

---

## 6. Searching invoices, collections and returns

Sales, returns and installments are all orders — filter by `type` to get one
kind. Collections and expenses live in the ledger (`/transactions`).

### 6.1 Invoices, returns and installments

```http
GET /api/v2/orders?type=4&payment_status=partial&from=2026-08-01&sort=amount_desc
```

| Parameter | Values | Meaning |
|---|---|---|
| `type` | `4` `7` `12` `24` | Sale · return · installment |
| `payment_status` | `paid` `partial` `unpaid` | Settlement state |
| `customer_id` | id | One customer |
| `product_id` | id | Orders containing this product |
| `account_id` | id | Which account took the money |
| `cash` | `1` `2` | Cash or credit |
| `min_amount` / `max_amount` | number | Amount range |
| `from` / `to` | `YYYY-MM-DD` | Date range |
| `search` | text | Order id, reference, or customer name |
| `sort` | `newest` `oldest` `amount_asc` `amount_desc` | Default `newest` |
| `limit` / `offset` | number | Pagination |

**Settlement state is derived, not stored:**

| Value | Meaning |
|---|---|
| `paid` | `collected_cash >= order_amount` |
| `partial` | Something collected, but less than the total |
| `unpaid` | Nothing collected |

Common screens:

```http
GET /api/v2/orders?type=4                      # sales invoices
GET /api/v2/orders?type=7                      # returns
GET /api/v2/orders?payment_status=unpaid       # what is still owed
GET /api/v2/orders?customer_id=800300&type=4   # one customer's invoices
```

### 6.2 Totals for the current filter

```http
GET /api/v2/orders/totals?type=4&payment_status=partial
```

Takes **exactly the same parameters** as the list.

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

> **Computed over the whole filtered result, not the visible page.** Call it
> alongside the list with the same query string and show it as the header —
> the figures describe every matching order, not just the 25 on screen.

### 6.3 Collections and expenses

```http
GET /api/v2/transactions?direction=in&from=2026-08-01
```

| Parameter | Values | Meaning |
|---|---|---|
| `direction` | `in` `out` | Money in (debit) or out (credit) |
| `type` | see `/transactions/types` | Ledger entry type |
| `account_id` `customer_id` `supplier_id` `seller_id` | id | Filter by party |
| `order_id` | id | Entries tied to one order |
| `cash` | `1` `2` | Cash or credit |
| `min_amount` / `max_amount` | number | Amount range |
| `from` / `to` | `YYYY-MM-DD` | Date range |
| `search` | text | Description, entry id, or order id |

Search works on Arabic descriptions — URL-encode the term:

```http
GET /api/v2/transactions?search=%D9%85%D8%B1%D8%AA%D8%AC%D8%B9     # مرتجع
```

### 6.4 Ledger totals

```http
GET /api/v2/transactions/totals?direction=in&from=2026-08-01
```

```json
{
  "success": true,
  "message": "Transaction totals retrieved",
  "data": {
    "entries": 33,
    "money_in": 17189.04,
    "money_out": 8377.48,
    "net": 8811.56
  }
}
```

### 6.5 Validation

Bad filter values return `422` with the field named — they are not ignored:

| Sent | Response |
|---|---|
| `payment_status=nope` | `422` on `payment_status` |
| `sort=sideways` | `422` on `sort` |
| `direction=sideways` | `422` on `direction` |
| `min_amount=500&max_amount=100` | `422` on `max_amount` |
| `to` before `from` | `422` on `to` |

> Surface these under the filter control rather than as a generic error — the
> message names which filter was wrong.

---

## 7. Payslips

```http
GET /api/v2/salary?month=2026-07
GET /api/v2/salary?month=7&year=2026     ← both accepted
GET /api/v2/salary                       ← the month just gone
GET /api/v2/salary/history?limit=6
```

```json
{
  "success": true,
  "message": "Salary retrieved",
  "data": {
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
    "manager_note": "أداء جيد هذا الشهر",
    "status": "approved",
    "details": [
      { "label": "الراتب الأساسي", "amount": 6000, "type": "add" },
      { "label": "بدل انتقال",     "amount": 750,  "type": "add" }
    ]
  }
}
```

Build the payslip screen from `details` — each line has a `label`, an `amount`
and a `type` of `add` or `deduct` — then show `net` as the total. Lines worth
zero are already left out.

> **`commission` is not part of `net`.** The company records it separately and
> the admin form excludes it from the total. Show it as its own row; do not add
> it to the net or the figure will not match the payslip the seller is paid.

> **Do not compute `net` yourself.** It is the figure the admin approved and
> stored. Adding the parts up can disagree with it if the admin adjusted the
> total by hand.

**No payslip yet** — still `200`, with `salary: null`:

```json
{ "success": true, "message": "No salary recorded for this month",
  "data": { "month": "2026-08", "salary": null } }
```

Show an empty state ("لم يُعتمد راتب هذا الشهر"), not an error — the admin
simply has not entered it yet. A seller checking early in the month will hit
this every time.
---

## 8. Dashboard

```http
GET /api/v2/dashboard/summary
GET /api/v2/dashboard/monthly-revenue?months=6
GET /api/v2/dashboard/top-products?limit=10
GET /api/v2/dashboard/low-stock
```

All figures are scoped to the signed-in seller.

```json
{
  "period": { "from": "2026-08-01", "to": "2026-08-31" },
  "sales":   { "count": 2, "amount": 1767 },
  "returns": { "count": 0, "amount": 0 },
  "net_sales": 1767,
  "collected": 706.8,
  "visits": 4,
  "stock_value": 16192,
  "low_stock_products": 0
}
```

`summary` accepts `from` / `to`; `to` must not precede `from` or you get `422`.

---

## 9. Regions

Used for the region picker when adding or editing a customer.

### `GET /regions/mine` — use this one

Only the regions this seller actually covers. This is what belongs in the
picker: showing a seller every region in the country invites them to file a
customer outside their own route.

```json
{
  "success": true,
  "message": "Regions retrieved",
  "data": [
    { "id": 800010, "name": "Cairo", "name_en": "Cairo" }
  ]
}
```

### `GET /regions` — every region

The full list, alphabetical by name. Use it only where a seller legitimately
needs to see outside their route.

```json
{
  "success": true,
  "message": "Regions retrieved",
  "data": [
    { "id": 800012, "name": "Alexandria", "name_en": "Alexandria" },
    { "id": 800010, "name": "Cairo",      "name_en": "Cairo" }
  ]
}
```

> **No pagination here.** Both endpoints return the complete list and `meta` is
> absent — do not send `limit` / `offset` and do not build a paging loop. This
> differs from `/categories`, which *is* paginated.

The `id` is what you send as `region_id` when creating a customer. `name` is
the Arabic name where one is set, `name_en` the English one — fall back to
`name` if `name_en` is null.

**Cache it.** Regions change rarely; fetch once per session and reuse.

---

## 10. Categories

The product tree. A category may have sub-categories beneath it.

### `GET /categories`

| Parameter | Meaning |
|---|---|
| `search` | Filter by name |
| `limit` / `offset` | Pagination — **this endpoint is paginated** |

```json
{
  "success": true,
  "message": "Categories retrieved",
  "data": [
    {
      "id": 800034,
      "name": "Baby Care",
      "parent_id": 0,
      "position": 0,
      "status": true,
      "image": "def.png",
      "type": 1,
      "created_at": "2026-08-25T23:08:47+03:00"
    }
  ],
  "meta": { "current_page": 1, "per_page": 3, "total": 6, "last_page": 2 }
}
```

| Field | Meaning |
|---|---|
| `parent_id` | `0` for a top-level category; otherwise the parent's id |
| `status` | `true` = visible. Filter these out of pickers if false |
| `image` | A filename, not a URL |

> The list returns **both** top-level categories and sub-categories. To show
> only the top level, keep the rows where `parent_id == 0`.

### `GET /categories/{id}/children`

The sub-categories of one parent — paginated, same envelope.

```json
{
  "success": true,
  "message": "Sub-categories retrieved",
  "data": [],
  "meta": { "current_page": 1, "per_page": 25, "total": 0, "last_page": 1 }
}
```

> Sub-categories are ordinary category rows carrying a `parent_id`, not a
> separate table — so a child has all the same fields as its parent.

### Filtering products by category

The category `id` is what `/stocks` and `/products` accept:

```http
GET /api/v2/stocks?type=4&category_id=800030
GET /api/v2/products?category_id=800030
```

> On a **product**, `category_id` comes back as a **string** (`"800030"`), not
> an integer — the column is a varchar in this schema. Compare loosely, or
> parse before matching against a category's numeric `id`.

### Writing

`POST` / `PUT` / `DELETE /categories` and `PATCH /categories/{id}/status`
exist for the admin app; see [`API-V2.md`](API-V2.md#lookup-tables) for the
field rules. The seller app normally only reads.

---

## 11. Other reference data

Cache these too — they change rarely.

| Endpoint | Use | Paginated |
|---|---|---|
| `GET /storages` | Warehouses | no |
| `GET /documents` | Documents with attachments | no |
| `GET /brands` | Catalogue lookup | yes |
| `GET /units` | Units of measure | yes |
| `GET /profile` | The signed-in seller | — |

---

## 12. Practical notes

**Money.** Amounts arrive as JSON numbers. Parse to a decimal type, not a
float, before summing a cart — floats drift on repeated addition.

**Dates.** `created_at` is ISO-8601 with an offset
(`2026-08-26T03:23:18+03:00`). Date-only fields are `YYYY-MM-DD`.

**Images.** The API returns a filename (`def.png`), not a URL. Build the full
path against the storage base for your environment.

**Booleans.** `active` is a real boolean. Numeric flags like `type`,
`specialist` and `cash` are integers with domain meaning — pass them through
rather than coercing to bool.

**Nulls.** Optional fields are `null` when unset. `latitude`, `longitude`,
`email`, `category_id` and `image` are all commonly null.

**Rate limit.** 60 requests per minute. Batch on screen load rather than
firing per list row.

---

## 13. Errors worth handling by name

| Situation | Status | Message |
|---|---|---|
| Not enough stock | `422` | `Not enough stock for <product> (have N, need M).` |
| Account short on funds | `422` | `The source account does not have enough balance.` |
| Someone else's record | `403` | `This customer is not assigned to you` |
| Expired token | `401` | `Unauthenticated` |
| Too many requests | `429` | `Too many requests. Please slow down.` |

---

## 14. Test credentials (local)

| | |
|---|---|
| Sellers | `MND-01`, `MND-02`, `MND-03` / `password` |
| Web admin | `demo.admin@example.test` / `password` |

Seeded by `php artisan db:seed` — see `tools/testdb/README.md`.
