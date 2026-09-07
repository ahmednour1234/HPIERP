# HPI FieldForce — دليل الـ API للتطبيق

**Base URL:** `https://2026-test-new.hpi-eg.com/api/v2`
**المصادقة:** `Authorization: Bearer <token>` على كل المسارات
**آخر تحديث:** 2026-09-07

---

## شكل الرد الموحّد

كل الردود بنفس الشكل:

```json
{
  "success": true,
  "message": "Customers retrieved",
  "data": [ ... ],
  "meta": { "current_page": 1, "per_page": 25, "total": 473, "last_page": 19 }
}
```

- `meta` **بره `data`** — مش جواها.
- عند الخطأ: `{ "success": false, "message": "...", "errors": {...} }`

### الترقيم — ⚠️ نقطة مهمة

| Param | المعنى |
|---|---|
| `limit` | عدد الصفوف (الافتراضي 25) |
| `offset` | **رقم الصفحة** يبدأ من 1 — **مش** عدد الصفوف المتخطّاة |

`offset=2` مع `limit=25` بيجيب الصفحة التانية (الصفوف 26-50).

---

## ⚠️ قبل أي حاجة: `specialist` مش `category_id`

فيه حقلين مختلفين تمامًا على العميل، والخلط بينهم مصدر لبس متكرر:

| الحقل | معناه | القيم | مصدره |
|---|---|---|---|
| `category_id` | **التخصص الطبي** | روماتيزم، جلدية، أطفال… (20) | `GET /specialties` |
| `specialist` | **نوع الجهة** | 1=صيدلية · 2=مركز طبي · 3=مستشفى · 4=طبيب | ثابت في الكود |

**اللي بتدوّر عليه (روماتيزم، جلدية) هو `category_id`.**

مفيش جدول `specialists`. التخصصات في `categories` ويميّزها `type`:
`type=0` تخصصات طبية (للعملاء) · `type=1` فئات منتجات

> 🚨 **بيانات `specialist` فيها فوضى:** المفروض 1-4 بس فيه قيم زي `2147483647`. استخدم `category_id` للفلترة.

---

## 1. الملف الشخصي والأدوار

```http
GET /profile
```

```json
{
  "id": 140, "name": "test 90", "email": "...", "phone": "...",
  "role": "seller",
  "type": "manager",
  "is_manager": true,
  "mandob_code": "test90", "vehicle_code": "...", "company_id": 1
}
```

### ⚠️ إزاي تعرف المدير

`role` بيرجّع **`seller`** حتى لحسابات المديرين. **استخدم `is_manager`** — بيغطي `type='manager'` والأدمن وأي حساب له مناديب.

---

## 2. القوائم المرجعية

| Endpoint | الوصف |
|---|---|
| `GET /specialties` | 20 تخصص طبي (`type=0`) |
| `GET /product-categories` | 6 فئات منتجات (`type=1`) |
| `GET /regions` | كل المناطق |
| `GET /regions/mine` | مناطق المندوب الحالي |
| `GET /documents` | الوثائق |

```json
{ "success": true, "data": [ { "id": 46, "name": "اطفال عام" } ] }
```

**التخصصات (20):** اطفال عام · تغذية علاجية أطفال · أمراض دم أطفال · مخ وأعصاب أطفال · حديثي الولادة · نسا وتوليد · علاج طبيعي · نفسية وعصبية · أنف وأذن · باطنة · كلى أطفال · أمراض قلب · جراحة أطفال · الوراثي والجينومي · عظام · جلدية · صدر أطفال · روماتيزم · تخاطب · غدد صماء وسكر

---

## 3. العملاء

```http
GET /customers?search=&category_id=&region_ids[]=&limit=&offset=
```

بترجّع عملاء **المندوب المسجّل فقط**.

| Param | ملاحظات |
|---|---|
| `search` | الاسم أو الموبايل أو اسم الصيدلية |
| `category_id` | التخصص الطبي (int أو array) |
| `region_ids` | **array** — يقبل أكتر من منطقة |

**حقول العميل:**
```
id, name, name_en, mobile, email, pharmacy_name, specialist, address,
state, city, zip_code, latitude, longitude, balance, credit, limit,
type, category_id, active, image, region_id, region, order_count,
executed_visits, created_at
```

**مُتحقَّق منه (المندوب 127):** بدون فلتر 473 · `category_id=46` → 184 · `region_ids[]=16` → 119 · الاتنين → 41 (تقاطع AND)

**باقي المسارات:** `POST /customers` · `PUT /customers` · `GET /customers/{id}` · `DELETE /customers/{id}` · `POST /customers/add-balance`

---

## 4. المنتجات والمخزون

```http
GET /stocks?type=&category_id=&search=&customer_id=&limit=&offset=
```

بترجّع منتجات **فئات المندوب فقط** — مش الكتالوج كله.

| `type` | الناتج |
|---|---|
| (فاضي) | الكتالوج المسموح للمندوب |
| `4` | **الرصيد الفعلي** بالكميات |

### `customer_id` — يوفّر request

```http
GET /stocks?type=4&customer_id=1750
```

```json
"product": {
  "id": 14316, "name": "فوليك أسيد",
  "selling_price": 79,
  "customer_price": 75
}
```

- `customer_price` = سعر العميل، أو `null` لو مفيش سعر خاص
- **مش محتاج** تنادي `POST /products/customer-prices` بعد اختيار كل عميل

> ⚠️ **متستخدمش `GET /products`** — بيرجّع الكتالوج كله بلا تقييد بالمندوب.

**عن `selling_price = 0`:** فحصت المنتجات كلها ومفيش صفر. لو ظهر، السبب سعر بديل بصفر في `seller_prices`/`customer_prices` — وده اتحصّن دلوقتي (أي سعر صفر يُتجاهل ويفضل السعر الأساسي).

---

## 5. الزيارات

### تسجيل زيارة

```http
POST /visits/results        (multipart/form-data)
```

| الحقل | مطلوب |
|---|---|
| `customer_id` | ✅ |
| `note` | ✅ (5000 حرف) |
| `lat` / `lang` | اختياري |
| `img` | اختياري — صورة، حد أقصى 5MB |

### قراءة الزيارات

```http
GET /visits/results?from=&to=&region_id=&category_id=&customer_id=&limit=&offset=
```

```json
{
  "id": 18012,
  "customer_id": 1750,
  "note": "...",
  "latitude": "26.6982086",
  "longitude": "31.603488",
  "customer": {
    "id": 1750,
    "name": "دكتور جيهان محمد - المراغة",
    "mobile": "088436",
    "region_id": 51,
    "region":   { "id": 51, "name": "سوهاج - مركز المراغة" },
    "category_id": 46,
    "category": { "id": 46, "name": "اطفال عام" }
  },
  "img": "visit/2026-09-07-abc.png",
  "img_url": "https://2026-test-new.hpi-eg.com/storage/visit/2026-09-07-abc.png",
  "created_at": "2026-08-25T18:46:40+03:00"
}
```

**المنطقة والتخصص جوه الرد** — مش محتاج تجيب كل العملاء وتدمجهم.

**مُتحقَّق منه (المندوب 124):** بدون فلتر 3805 · `region_id=48` → 1607 · `category_id=46` → 1732 · `from=to=2025-10-29` → 15

> **الزيارات القديمة** هترجّع `img: null` لأنها اتسجّلت قبل إضافة الصورة — متوقع مش باگ.

> **لو رجّع `total: 0`:** غالبًا فلتر تاريخ مفيهوش زيارات. جرّب `?limit=1` بدون فلاتر للتأكد.

**باقي المسارات:** `GET /visits` · `POST /visits` (تخطيط) · `GET /visits/customers/{id}/results`

---

## 6. شؤون المندوب (HR)

| Endpoint | الوصف |
|---|---|
| `GET /hr/ratings` | التقييم الشهري + ملاحظة المدير |
| `GET /hr/development` | ملاحظات تطوير المندوب |
| `GET /hr/courses` | الكورسات |
| `GET /hr/requests` | طلبات الموظف |
| `POST /hr/requests` | تقديم طلب |
| `GET /hr/leaves` | طلبات الإجازة |
| `POST /hr/leaves` | تقديم إجازة |

**فلاتر القراءة:** `status` (0 قيد المراجعة · 1 مقبول · 2 مرفوض) · `from` · `to`

**التقديم:**
```json
{ "note": "طلب إذن من الزيارات الصباحية", "date": "2026-09-20" }
```

```json
{
  "id": 185, "note": "...", "type": 2,
  "status": 0, "status_text": "قيد المراجعة",
  "date": "2026-09-20", "created_at": "..."
}
```

> ✅ **اتصلح:** كان `POST` بيفشل بـ `Column 'admin_id' cannot be null`. مفيش تغيير مطلوب في التطبيق.

---

## 6.1 المرتب

```http
GET /salary?month=2025-01
GET /salary/history?limit=12
```

```json
{
  "id": 7, "month": "2025-01",
  "basic": 9500,
  "transport": 0,
  "visits_pay": 680,
  "collection_incentive": 250,
  "other": 150,
  "deductions": 0,
  "net": 10580,
  "commission": 2000,
  "visits": { "target": 52, "achieved": 191 },
  "working_days": 26,
  "score": 8,
  "note": "...", "manager_note": "...",
  "status": "approved", "status_text": "معتمد",
  "details": [
    { "label": "الراتب الأساسي", "amount": 9500, "type": "add" },
    { "label": "حافز الزيارات",  "amount": 680,  "type": "add" },
    { "label": "حافز التحصيل",   "amount": 250,  "type": "add" },
    { "label": "أخرى",           "amount": 150,  "type": "add" }
  ]
}
```

- **حافز التحصيل** موجود في `details` وكحقل مستقل `collection_incentive`
- البنود الصفرية **مستبعدة** من `details` — اعرض اللي راجع بس
- `net` مأخوذ من العمود المخزَّن؛ لو فاضي بيتحسب: `basic + transport + visits_pay + collection_incentive + other - deductions`
- لو الشهر مالوش كشف: `{ "month": "...", "salary": null }` بـ **200** مش 404

> ⚠️ **حافز التحصيل صفر في كل الصفوف حاليًا** — العمود اتضاف حديثًا ولسه ماتملاش من لوحة الإدارة. الـ API جاهز؛ أول ما يتسجّل رقم هيظهر تلقائيًا.

---

## 7. البصمة

```http
GET /attendance?from=&to=&limit=&offset=
```

بترجّع `check_in` / `check_out` / `status` للمندوب المسجّل.

---

## 8. واجهة المدير

> كل المسارات دي مقصورة على **مناديب المدير الحالي**. الوصول لمندوب تابع لمدير تاني بيرجّع **403**.

### قائمة/خريطة المناديب

```http
GET /manager/sellers?search=&region_id[]=
```

```json
{
  "id": 124,
  "name": "Sohag Mr",
  "mandob_code": "Sohag",
  "phone": null,
  "image_url": null,
  "last_location": {
    "latitude": 26.6982093, "longitude": 31.6034919, "updated_at": null
  },
  "today_status": "absent",
  "today_check_in": null,
  "today_check_out": null
}
```

`today_status`: `present` · `absent` · `checked_out` — لتلوين الدبوس
`last_location`: `null` لو مفيش إحداثيات مسجّلة

### بصمة مندوب

```http
GET /manager/sellers/{id}/attendance?from=&to=&limit=&offset=
```

بترجّع `check_in` · `check_out` · `status` · `location` · `time_late` · `worked_hours` · `expected_hours` · `note`

### ملاحظات المدير

```http
POST /manager/sellers/{id}/notes     المدير يكتب
GET  /manager/sellers/{id}/notes     المدير يقرأ ملاحظاته على مندوب
GET  /manager-notes                  المندوب يقرأ ملاحظات مديره
```

```json
{ "note": "برجاء الالتزام بمواعيد الزيارات", "date": "2026-09-07" }
```

```json
{
  "id": 187, "seller_id": 124, "manager_id": 139,
  "manager_name": "Moaz Ahmed",
  "note": "...", "date": "2026-09-07",
  "created_at": "2026-09-07T17:31:19+03:00"
}
```

**مُتحقَّق منه (المدير 139، 6 مناديب):** القائمة 6=6 · البصمة 512=512 · الكتابة 201 · المندوب قرأها ✅ · محاولة الوصول لمندوب غيره → **403** ✅

---

## 9. مسارات أخرى متاحة

| المجموعة | المسارات |
|---|---|
| الطلبات | `GET/POST /orders` · `/orders/totals` · `/orders/returns` · `/orders/{id}` · `/orders/customers/{id}` |
| المخزون | `/stocks/confirm` · `/stocks/history` |
| الحجوزات | `/reservations` |
| المرتب | `/salary` · `/salary/history` |
| الإيداعات | `/deposits` · `/deposits/summary` · `/deposits/{id}` |
| المعاملات | `/transactions` · `/transactions/types` · `/transactions/totals` · `/expense` · `/income` · `/transfer` |
| لوحة المعلومات | `/dashboard/summary` · `/low-stock` · `/top-products` · `/monthly-revenue` |

### `/dashboard/summary`

```json
{
  "period": { "from": "2025-01-01", "to": "2025-01-31" },
  "sales":   { "count": 12, "amount": 5400 },
  "returns": { "count": 1,  "amount": 200 },
  "net_sales": 5200,
  "collected": 4800,
  "visits": 52,
  "visits_target": 52,
  "stock_value": 1106,
  "low_stock_products": 3
}
```

- **`visits_target`** مأخوذ من كشف راتب نفس الشهر (`number_of_visitors`) — نفس مصدر `visits.target` في `/salary`
- **`0`** يعني مفيش مستهدف محدَّد للشهر ده (الكشف لسه ماتدخلش) — اعرض العدد وحده بلا نسبة
| الحساب | `POST /profile/change-password` |

---

## ملخص سريع لأهم المسارات

| المطلوب | المسار |
|---|---|
| التخصصات | `GET /specialties` |
| فئات المنتجات | `GET /product-categories` |
| عملاء المندوب + فلترة | `GET /customers?category_id=&region_ids[]=` |
| منتجات المندوب | `GET /stocks?category_id=` |
| رصيد المندوب | `GET /stocks?type=4` |
| مع سعر عميل | `GET /stocks?type=4&customer_id=` |
| الزيارات + فلترة | `GET /visits/results?from=&to=&region_id=&category_id=` |
| تسجيل زيارة بصورة | `POST /visits/results` (multipart) |
| طلبات وإجازات | `GET/POST /hr/requests` · `/hr/leaves` |
| مناديب المدير | `GET /manager/sellers` |
| ملاحظة على مندوب | `POST /manager/sellers/{id}/notes` |

---

## ⚠️ قبل ما تبدأ

المسارات دي شغالة على **السيرفر بعد آخر نشر**. لو أي واحد رجّع **404**، يبقى الكود لسه ماترفعش — كلّم الباك إند.

**اختبار سريع:** `GET /api/v2/specialties` بدون توكن لازم يرجّع **401** (مش 404). لو رجّع 404، النشر ناقص.
