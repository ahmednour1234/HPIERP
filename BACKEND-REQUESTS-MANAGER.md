# طلبات الباك إند — واجهة المدير (Manager) في تطبيق HPI FieldForce

التطبيق حالياً مبني للمندوب (`role: seller`). المطلوب إضافة endpoints لواجهة **المدير** (`role: manager`).
التفريق في التطبيق سيكون حسب `role` القادم من `/profile`.

الخادم: `https://2026-test-new.hpi-eg.com/api/v2`
كل المسارات تحت `Authorization: Bearer <token>`.
شكل كل الاستجابات: `{ "success": true, "message": "...", "data": ... }`

---

## ملاحظة عن الأدوار

`GET /api/v2/profile` يرجّع حالياً:
```json
{ "id": 140, "name": "test 90", "role": "seller", "mandob_code": "test90", ... }
```

**المطلوب:** التأكد أن حساب المدير يرجّع `"role": "manager"` (أو القيمة المعتمدة عندكم).
كل الـ endpoints التالية يجب أن تكون **محصورة على المدير** وترجع بيانات **مناديبه فقط** (التابعين له).

---

## 1. 🗺️ قائمة/خريطة المناديب

```http
GET /api/v2/manager/sellers
```

قائمة المناديب التابعين للمدير، مع آخر موقع معروف لكل مندوب (لرسمهم على الخريطة).

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 140,
      "name": "test 90",
      "mandob_code": "test90",
      "phone": "01000000000",
      "image": null,
      "last_location": {
        "latitude": 30.0444,
        "longitude": 31.2357,
        "updated_at": "2026-09-07T14:30:00+03:00"
      },
      "today_status": "present",     // present | absent | checked_out
      "today_check_in": "09:12:00",
      "today_check_out": null
    }
  ]
}
```

- `last_location` = آخر إحداثيات سجّلها المندوب (من البصمة أو الزيارات). `null` لو مفيش.
- `today_status` = حالة اليوم (حاضر/غائب/منصرف) — لتلوين الدبوس على الخريطة.

**فلاتر اختيارية:** `region_id` (مناديب منطقة معينة)، `search` (بالاسم/الكود).

---

## 2. 👆 سجل بصمة مندوب معيّن

```http
GET /api/v2/manager/sellers/{sellerId}/attendance
```

سجل حضور/انصراف مندوب محدّد (المدير يفتحه من قائمة المناديب).

**فلاتر:** `from`, `to` (نطاق تاريخي)، `limit`/`offset`.

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1818,
      "date": "2026-09-07",
      "check_in": "09:12:00",
      "check_out": "17:40:00",
      "status": 2,
      "check_in_location":  { "latitude": 30.04, "longitude": 31.23 },
      "check_out_location": { "latitude": 30.05, "longitude": 31.24 },
      "note": "ملاحظة المندوب إن وُجدت",
      "manager_note": "ملاحظة المدير (see #3)"
    }
  ]
}
```

> نفس شكل `/attendance` الحالي للمندوب، مع إضافة `manager_note` والمواقع.

---

## 3. ✍️ كتابة ملاحظة المدير (تظهر عند المندوب)

المدير يكتب ملاحظة على مندوب، وتظهر عند المندوب في خانة **"ملاحظات المدير"**.

### أ) المدير يكتب/يعدّل ملاحظة

```http
POST /api/v2/manager/sellers/{sellerId}/notes
```
```json
{ "note": "برجاء الالتزام بمواعيد الزيارات الصباحية", "date": "2026-09-07" }
```
- `note` مطلوب (حد أقصى 5000 حرف)
- `date` اختياري (لو الملاحظة مرتبطة بيوم معيّن)

**Response 201:**
```json
{
  "id": 12,
  "seller_id": 140,
  "manager_id": 100,
  "manager_name": "اسم المدير",
  "note": "برجاء الالتزام...",
  "date": "2026-09-07",
  "created_at": "2026-09-07T14:40:00+03:00"
}
```

### ب) المندوب يقرأ ملاحظات مديره

```http
GET /api/v2/manager-notes
```

يرجّع ملاحظات المدير الموجّهة للمندوب المسجّل (يُعرض في شاشة عند المندوب: "ملاحظات المدير").

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "manager_name": "اسم المدير",
      "note": "برجاء الالتزام بمواعيد الزيارات الصباحية",
      "date": "2026-09-07",
      "created_at": "2026-09-07T14:40:00+03:00"
    }
  ]
}
```

### ج) (اختياري) المدير يقرأ ملاحظاته على مندوب

```http
GET /api/v2/manager/sellers/{sellerId}/notes
```

---

## 4. 📄 إدارة الوثائق

`GET /api/v2/documents` **موجود بالفعل** (يرجّع 200). المطلوب توضيح شكل الوثيقة والصلاحيات:

- هل المدير يرفع وثائق للمناديب؟ (`POST /documents`)
- شكل صف الوثيقة: `{ id, title, file_url, type, created_at, ... }`?
- هل المندوب يشوف وثائقه فقط والمدير يشوف الكل؟

يُرجى إرسال مثال JSON لصف وثيقة + هل هناك رفع من التطبيق.

---

## 🔴 باگ في `POST /hr/requests` و `POST /hr/leaves` (مهم)

تقديم طلب موظف أو إجازة من التطبيق **يفشل** بخطأ:
```
SQLSTATE[23000]: Column 'admin_id' cannot be null
(insert into develop_sellers (seller_id, admin_id, note, date, type, active, ...)
 values (140, ?, ..., 2026-09-20, 2, 0, ...))
```

**السبب:** عند الإنشاء، الكود لا يملأ `admin_id` (مدير المندوب). المندوب معروف من التوكن (`seller_id = 140`) لكن `admin_id` يُترك null فيفشل الـ insert.

**الحل:** عند إنشاء الطلب، اجلب `admin_id` من مدير المندوب المسجّل (أو اجعله nullable لو منطقياً الطلب لا يحتاج مديراً محدداً وقت الإنشاء).

**القراءة تعمل بشكل سليم** (`GET /hr/requests` و`/hr/leaves` يرجعان البيانات صح) — المشكلة في الإنشاء فقط.

---

## 🔴 باگ حالي في `/visits/results` (مهم)

بعد آخر تحديث، `GET /api/v2/visits/results` صار يرجّع `total: 0` لمندوب كان عنده زيارات.

**السبب:** الاستعلام الجديد يعمل **INNER JOIN** مع `category`/`region`، فأي زيارة عميلها `category_id = null` **تختفي**.

**الحل:** استخدام **LEFT JOIN** (أو `with()` eager loading بدل `join()`).

**دليل:** المندوب test90 كان عنده 2 زيارة (للعملاء 1434, 1433)، والآن `total: 0`. العميل 1433 عنده `category_id: null`.

---

## ملخص endpoints المدير المطلوبة

| الغرض | المسار | الحالة |
|---|---|---|
| قائمة/خريطة المناديب | `GET /manager/sellers` | 🆕 مطلوب |
| بصمة مندوب | `GET /manager/sellers/{id}/attendance` | 🆕 مطلوب |
| كتابة ملاحظة مدير | `POST /manager/sellers/{id}/notes` | 🆕 مطلوب |
| قراءة ملاحظات المدير (للمندوب) | `GET /manager-notes` | 🆕 مطلوب |
| الوثائق | `GET /documents` | ✅ موجود (يحتاج توضيح) |

> ملاحظة: أسماء المسارات مقترحة — لو عندكم تسمية مختلفة، ابعتوها وأنا أضبط التطبيق عليها.
