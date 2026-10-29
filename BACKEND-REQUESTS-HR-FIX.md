# طلب باك إند — شؤون الموظف وطلبات المناديب

اتجرّب على `https://erp.hpi-eg.com/api/v2` بحساب المدير `DM` (id 139).

---

## 🔴 (1) `GET /hr/ratings` — `score` بيرجع نص مش رقم

**الرد الحالي:**
```json
{ "id": 101, "month": "2026-09", "score": "0", "visits_target": "165", "visits_done": "136", "work_days": 3, ... }
```

`score` و`visits_target` و`visits_done` راجعين **نص** (`"0"`, `"165"`)، والتطبيق مستنيهم **أرقام**.
النتيجة: تبويب «التقييم» في شؤون الموظف بيعرض «تعذّر تحميل البيانات» مع إن الرد 200 وفيه 13 تقييم.

**المطلوب:** يرجعوا أرقام:
```json
{ "score": 0, "visits_target": 165, "visits_done": 136 }
```
(في Laravel: `'score' => (float) $r->score` في الـ Resource، أو `$casts` في الموديل.)

نفس الكلام لأي رقم في:
- `GET /hr/my-rating` → `score`
- `GET /manager/sellers` → `rating`, `last_location.latitude/longitude`
- `GET /manager/sellers/{id}/rating` → `score`

---

## 🔴 (2) شاشة «طلبات المناديب» عند المدير — المسار مش موجود (404)

```
GET /api/v2/manager/hr/requests?type=1          → 404 Not Found
GET /api/v2/manager/hr/requests?type=2&status=2 → 404 Not Found
```

اتأكدنا من `routes/api/v2/api.php`: جروب `manager` مفيهوش أي route للطلبات/الإجازات.
و`/hr/requests` و`/hr/leaves` (`SellerHrController`) بيرجّعوا طلبات **المستخدم المسجّل نفسه** بس
(المدير شاف 25 طلب بتوعه هو، من غير أي بيانات مندوب) — فمينفعش نستخدمهم للمدير.

**المطلوب:** المسارين دول (التفاصيل والأمثلة كاملة في `BACKEND-REQUESTS-MANAGER-HR.md`):

| | المسار | الوصف |
|---|---|---|
| 1 | `GET /manager/hr/requests?type=1\|2&status=0\|1\|2&seller_id=&offset=&limit=` | طلبات/إجازات **مناديب المدير** — كل عنصر فيه `seller {id, name, mandob_code}` + `meta` |
| 2 | `POST /manager/hr/requests/{id}/decision` body `{status: 1\|2, reply?}` | قبول / رفض |

**مهم:** لو المدير مالوش طلبات → `200` و `data: []` (مش 404).
التطبيق بيعرض «لا توجد طلبات» للـ `data: []`، وبيعرض رسالة خطأ لأي 404/500.
