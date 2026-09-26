# IQ Brandx (hpi) — مرجع الـ API

> **مستخرج من:** `app-release-8.apk` (تطبيق Flutter — من `lib/arm64-v8a/libapp.so`)
> **النوع:** تطبيق مندوب مبيعات / توزيع ميداني (Field Sales & Distribution)

---

## معلومات أساسية

| البند | القيمة |
|---|---|
| **Base URL** | `https://hpi.iqbrandx.com/` |
| **ERP** | `https://erpnew.hpi-eg.com/` |
| **بادئة الـ API** | `api/v1/` |
| **المصادقة** | `Authorization: Bearer <token>` |
| **الهيدرز** | `Accept: application/json` · `Content-Type: application/json` (أو `multipart/form-data` عند رفع الصور) · `Accept-Language: ar` / `en` |
| **تخزين التوكن** | FlutterSecureStorage (`access_token`, `seller_id`) |

> ⚠️ **ملاحظة عن الـ Methods:** التطبيق Flutter مترجم AOT، فالربط بين الـ method والـ path مش مخزّن نصياً. الـ methods أدناه **مستنتجة** من طبيعة كل endpoint (قاعدة: `list/search/index/invoice/result/*?params` → **GET** ، `store/place/confirm/update/upload` → **POST**). أكّدها بالـ traffic الفعلي (Proxy) لو محتاج دقة 100%.

---

## الـ Flow العام للتطبيق

```
[Splash]
   └─> GET api/v1/config            (تحميل الإعدادات)
        └─> فيه توكن محفوظ؟
              ├─ لا  ─> [Login] ─> POST api/v1/login ─> يخزّن token + seller
              └─ نعم ─> [الرئيسية]
                           │
      ┌──────────────┬─────┴──────┬──────────────┐
   الإحصائيات      التحصيلات      المبيعات        الطلبات
  (dashboard)    (collections)    (POS/sales)    (orders)
      │              │               │               │
      │              │               │          GET pos/order/list
   revenue-        account/list   place/order       │
   summary        cash invoice   place/return   invoice PDF
                  collected/non  place/installment
```

---

## 1) المصادقة (Auth)

### `POST api/v1/login`
تسجيل دخول المندوب بالكود وكلمة المرور.
```json
{
  "mandob_code": "12345",
  "password": "••••••"
}
```
**الرد:** `access_token` + كائن `seller` (بياناته تتخزّن secure).

### `POST api/v1/update/user`
تحديث بيانات المستخدم الحالي.
```json
{ "f_name": "", "l_name": "", "phone": "", "image": "<file>" }
```

---

## 2) الإعدادات والإحصائيات

| Method | Endpoint | الوصف | Body / Params |
|---|---|---|---|
| GET | `api/v1/config` | إعدادات التطبيق (Splash) | — |
| GET | `api/v1/dashboard/revenue-summary` | ملخص الإيرادات للوحة الإحصائيات | — |
| GET | `api/v1/dataadmin` | بيانات إدارية عامة | — |
| GET | `api/v1/admin/sellers` | قائمة المناديب | — |
| GET | `api/v1/branches` | قائمة الفروع | — |

---

## 3) العملاء (Customers)

| Method | Endpoint | الوصف | Params / Body |
|---|---|---|---|
| GET | `api/v1/customer/search?name=` | بحث عن عميل بالاسم | `?name=` |
| GET | `api/v1/customer/listcategory` | تصنيفات العملاء | — |
| POST | `api/v1/customer/store` | إضافة عميل جديد | body ↓ |

```json
// customer/store
{
  "name": "",
  "phone": "",
  "address": "",
  "shop_name": "",
  "region_id": 0,
  "latitude": 0.0,
  "longitude": 0.0,
  "image": "<file>"
}
```

---

## 4) المبيعات / نقاط البيع (POS)

| Method | Endpoint | الوصف | Params / Body |
|---|---|---|---|
| GET | `api/v1/pos/order/list?type=` | قائمة الطلبات حسب النوع | `?type=` |
| GET | `api/v1/pos/order/install` | الطلبات بالتقسيط | — |
| GET | `api/v1/pos/order/notinstall` | الطلبات النقدية | — |
| POST | `api/v1/pos/place/order` | إنشاء طلب/بيع | body ↓ |
| POST | `api/v1/pos/place/installment` | بيع بالتقسيط | body ↓ |
| POST | `api/v1/pos/place/return` | مرتجع | body ↓ |
| GET | `api/v1/pos/invoice?order_id=` | فاتورة طلب (PDF) | `?order_id=` |
| GET | `api/v1/pos/installment/list` | قائمة الأقساط | — |
| GET | `api/v1/pos/installment/invoice?installment_id=` | فاتورة قسط | `?installment_id=` |

```json
// pos/place/order
{
  "customer_id": 0,
  "seller_id": 0,
  "order_type": "cash",           // cash | installment
  "payment_id": 0,
  "discount": 0,
  "discount_type": "percent",
  "total_price": 0,
  "tax_amount": 0,
  "transport_amount": 0,
  "latitude": 0.0,
  "longitude": 0.0,
  "products": [
    { "product_id": 0, "quantity": 1, "selling_price": 0, "discount": 0 }
  ]
}
```
```json
// pos/place/return  — نفس البنية للمرتجع
{ "customer_id": 0, "order_id": 0, "products": [ { "product_id": 0, "quantity": 1, "price": 0 } ], "reason": "" }
```

---

## 5) المنتجات والمخزون (Products & Stock)

| Method | Endpoint | الوصف | Params / Body |
|---|---|---|---|
| GET | `api/v1/product/search?name=` | بحث منتج | `?name=` |
| GET | `api/v1/category/list?offset=` | تصنيفات (بترقيم صفحات) | `?offset=` |
| GET | `api/v1/stocks/list?category_id=` | مخزون حسب التصنيف | `?category_id=` |
| POST | `api/v1/stocks/confirm` | تأكيد المخزون | body ↓ |

```json
// stocks/confirm
{ "products": [ { "product_id": 0, "quantity": 0, "account_stock_Id": 0 } ] }
```

---

## 6) الحجوزات (Reservations)

| Method | Endpoint | الوصف |
|---|---|---|
| GET | `api/v1/product/reservation/list/{id}` | قائمة الحجوزات |
| POST | `api/v1/product/reservation/place` | إنشاء حجز |
| GET | `api/v1/product/reservation/invoice/{id}` | فاتورة الحجز |

---

## 7) التحصيلات والحسابات (Collections & Accounts)

| Method | Endpoint | الوصف | Params |
|---|---|---|---|
| GET | `api/v1/account/list` | حسابات الدفع/البنوك | — |
| GET | `api/v1/transactionseller/listalltodaybyseller?from=` | حركات اليوم للمندوب | `?from=` |
| POST | `api/v1/transactionseller` | تسجيل حركة/تحصيل | body ↓ |

```json
// transactionseller
{
  "seller_id": 0,
  "account_id": 0,
  "amount": 0,
  "type": "collect",
  "note": "",
  "image": "<file>",
  "date": "2026-08-26"
}
```

---

## 8) الزيارات (Visits)

| Method | Endpoint | الوصف | Params / Body |
|---|---|---|---|
| GET | `api/v1/visitor/list` | قائمة الزيارات المطلوبة | — |
| POST | `api/v1/visitor/store` | تسجيل زيارة | body ↓ |
| GET | `api/v1/visitor/result` | نتيجة الزيارات | — |
| GET | `api/v1/seller/result/visitor/{id}` | نتيجة زيارة مندوب | — |

```json
// visitor/store
{
  "customer_id": 0,
  "seller_id": 0,
  "result": "done",              // done | not_found | closed ...
  "note": "",
  "latitude": 0.0,
  "longitude": 0.0,
  "image": "<file>",
  "date": "2026-08-26"
}
```

---

## 9) الحضور والبصمة (Attendance)

| Method | Endpoint | الوصف | Body |
|---|---|---|---|
| GET | `api/v1/attendance` | سجل الحضور | — |
| POST | `api/v1/attendance/store` | تسجيل حضور/بصمة | body ↓ |

```json
// attendance/store  (بصمة يومية مع الموقع)
{ "seller_id": 0, "latitude": 0.0, "longitude": 0.0, "type": "checkin", "date": "2026-08-26" }
```

---

## 10) تطوير العملاء (Develop)

| Method | Endpoint | الوصف |
|---|---|---|
| GET | `api/v1/develop/` | قائمة التطوير |
| GET | `api/v1/develop-seller/pending-type2` | معلّقة نوع 2 |
| POST | `api/v1/develop/storedevelop` | حفظ تطوير عميل |
| POST | `api/v1/storeseller` | حفظ بيانات مندوب |

---

## 11) الموارد البشرية والمستندات (HR)

| Method | Endpoint | الوصف | Params |
|---|---|---|---|
| GET | `api/v1/salary?month=` | كشف الراتب | `?month=` |
| GET | `api/v1/courses` | الكورسات التدريبية | — |
| GET | `api/v1/seller/indexdocument` | مستندات المندوب | — |
| GET | `api/v1/seller/indexregions` | مناطق المندوب | — |
| POST | `api/v1/seller/rating` | تقييم المندوب | `{ "value": 0, "note": "" }` |
| POST | `api/v1/uploadcertificates` | رفع شهادات (multipart) | `image`, `course_id` |

---

## ملخص الـ Endpoints (سريع)

| # | Endpoint | Method* |
|---|---|---|
| 1 | `api/v1/config` | GET |
| 2 | `api/v1/login` | POST |
| 3 | `api/v1/update/user` | POST |
| 4 | `api/v1/dashboard/revenue-summary` | GET |
| 5 | `api/v1/dataadmin` | GET |
| 6 | `api/v1/admin/sellers` | GET |
| 7 | `api/v1/branches` | GET |
| 8 | `api/v1/customer/search?name=` | GET |
| 9 | `api/v1/customer/listcategory` | GET |
| 10 | `api/v1/customer/store` | POST |
| 11 | `api/v1/pos/order/list?type=` | GET |
| 12 | `api/v1/pos/order/install` | GET |
| 13 | `api/v1/pos/order/notinstall` | GET |
| 14 | `api/v1/pos/place/order` | POST |
| 15 | `api/v1/pos/place/installment` | POST |
| 16 | `api/v1/pos/place/return` | POST |
| 17 | `api/v1/pos/invoice?order_id=` | GET |
| 18 | `api/v1/pos/installment/list` | GET |
| 19 | `api/v1/pos/installment/invoice?installment_id=` | GET |
| 20 | `api/v1/product/search?name=` | GET |
| 21 | `api/v1/category/list?offset=` | GET |
| 22 | `api/v1/stocks/list?category_id=` | GET |
| 23 | `api/v1/stocks/confirm` | POST |
| 24 | `api/v1/product/reservation/list/{id}` | GET |
| 25 | `api/v1/product/reservation/place` | POST |
| 26 | `api/v1/product/reservation/invoice/{id}` | GET |
| 27 | `api/v1/account/list` | GET |
| 28 | `api/v1/transactionseller` | POST |
| 29 | `api/v1/transactionseller/listalltodaybyseller?from=` | GET |
| 30 | `api/v1/visitor/list` | GET |
| 31 | `api/v1/visitor/store` | POST |
| 32 | `api/v1/visitor/result` | GET |
| 33 | `api/v1/seller/result/visitor/{id}` | GET |
| 34 | `api/v1/attendance` | GET |
| 35 | `api/v1/attendance/store` | POST |
| 36 | `api/v1/develop/` | GET |
| 37 | `api/v1/develop-seller/pending-type2` | GET |
| 38 | `api/v1/develop/storedevelop` | POST |
| 39 | `api/v1/storeseller` | POST |
| 40 | `api/v1/salary?month=` | GET |
| 41 | `api/v1/courses` | GET |
| 42 | `api/v1/seller/indexdocument` | GET |
| 43 | `api/v1/seller/indexregions` | GET |
| 44 | `api/v1/seller/rating` | POST |
| 45 | `api/v1/uploadcertificates` | POST |

\* الـ Method مستنتج — راجع الملاحظة في الأعلى.

---

## ملاحظات مهمة لبناء التطبيق الجديد

- **الموقع (GPS) إجباري** مع أغلب العمليات (بيع/زيارة/بصمة/عميل) → `latitude` + `longitude`.
- **رفع الصور** يتم عبر `multipart/form-data` (صور العملاء/الحركات/الشهادات).
- **الترقيم (Pagination)** عبر `?offset=` أو `?page=`.
- **الفواتير** ترجع كـ PDF وتُعرض بـ Syncfusion PDF Viewer.
- **الطباعة** عبر طابعة حرارية Bluetooth (XPrinter).
- **العمل offline** مدعوم جزئياً عبر sqflite (تخزين محلي ثم مزامنة).
