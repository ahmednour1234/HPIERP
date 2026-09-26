# IQ Brandx (hpi) — الـ Flow وشاشات التطبيق

> **مستخرج من:** `app-release-8.apk` (Flutter — من `libapp.so`)
> **النوع:** تطبيق مندوب مبيعات / توزيع ميداني (Field Sales & Distribution)
> **مرجع الـ API:** [API_REFERENCE.md](API_REFERENCE.md)

---

## 🚀 البداية (Startup Flow)

```
SplashScreen
    │
    └─> GET api/v1/config   (تحميل الإعدادات)
             │
        فيه توكن محفوظ؟
             ├─ لا  ──> LoginScreen
             └─ نعم ──> ButtonNavigationScreen (الرئيسية)
```

- **SplashScreen:** لوجو + تحميل إعدادات التطبيق.
- **LoginScreen:** حقلين — **كود المندوب** (`mandob_code`) + **كلمة المرور** + زرار دخول.
  (يوجد `RegisterScreen` لكن الدخول الأساسي بالكود/الباسورد.)

---

## 🏠 الشاشة الرئيسية — `ButtonNavigationScreen`

شريط تنقّل سفلي فيه **4 تبويبات**:

| # | التبويب | الشاشة | الأيقونة |
|---|---|---|---|
| 1 | الإحصائيات | `StatisticsScreen` | chart |
| 2 | التحصيلات | `CollectionsScreen` | money |
| 3 | المبيعات | `SalesScreen` | sales |
| 4 | الطلبات | `HeadOrdersScreen` | orders |

---

## 📊 1) الإحصائيات — `StatisticsScreen`

**بيعرض:**
- ملخص إيرادات — `GET api/v1/dashboard/revenue-summary`
- رسم بياني (`CartesianChart` + `ChartWidget` + `ChartLegendItem`) — أداء بالوقت.
- كروت أرقام (KPI): مبيعات / تحصيل / طلبات.
- شاشة إضافية: `SomeStaticsScreen` لإحصائيات تفصيلية.

---

## 💰 2) التحصيلات — `CollectionsScreen`

**بيعرض** (`CollectionsBodyWidget`):
- اختيار عميل للتحصيل منه.
- اختيار **حساب بنكي** (`CollectionsBankAccountWidget`) → `GET api/v1/account/list`
- اختيار **طريقة الدفع** (`CollectionsPayAccountWidget`).
- خانة **المبلغ** + **ملاحظة** + **رفع صورة** (إيصال).
- أزرار التنفيذ (`CollectionsButtonsWidget`) → `POST api/v1/transactionseller`

**شاشات فرعية للفواتير:**
| الشاشة | الوصف |
|---|---|
| `CashInvoiceScreen` | فواتير كاش |
| `CollectedInvoiceScreen` | فواتير **محصّلة** |
| `NonCollectedInvoiceScreen` | فواتير **غير محصّلة** |
| `FundListScreen` | قائمة الأموال / العُهد |

---

## 🛒 3) المبيعات — `SalesScreen`

**بيعرض** (`SalesAndReturnBodyWidget`):
- تصنيفات المنتجات (`SalesCategoriesWidget`) → `GET api/v1/category/list?offset=`
- بحث منتج (`AllProductsAndSearchProductsWidget`) → `GET api/v1/product/search?name=`
- اختيار منتجات وإضافتها للسلة (`ProductsSelectedWidget`): كمية / سعر / خصم.
- **نوع الطلب** (`SalesOrderTypesWidget`): كاش / تقسيط / **مرتجع**.

**عند الحفظ:**
| النوع | الـ Endpoint |
|---|---|
| بيع كاش/تقسيط | `POST api/v1/pos/place/order` |
| تقسيط | `POST api/v1/pos/place/installment` |
| مرتجع | `POST api/v1/pos/place/return` |

**شاشات الفواتير الناتجة:**
`SalesInvoiceScreen` · `ReturnInvoiceScreen` · `ElectronicInvoiceScreen` · `ElectronicCashInvoiceScreen`
ثم → **طباعة حرارية** (`PrintScreen` + XPrinter Bluetooth).

---

## 📦 4) الطلبات — `HeadOrdersScreen`

**بيعرض:**
- قائمة كل الطلبات (`AllOrdersBodyWidget` / `AllOrdersItemWidget`) → `GET api/v1/pos/order/list?type=`
- لكل طلب: العميل / المبلغ / النوع / الحالة.
- فلترة حسب النوع: تقسيط (`pos/order/install`) / كاش (`pos/order/notinstall`).
- فتح تفاصيل الطلب وفاتورته PDF → `GET api/v1/pos/invoice?order_id=`

---

## 📂 شاشات القائمة الجانبية (Drawer) — خارج التبويبات

| الشاشة | بتعرض إيه | Endpoint |
|---|---|---|
| `CustomersScreen` → `AddCustomerScreen` | قائمة العملاء + بحث + إضافة (اسم عربي/إنجليزي، تصنيف، هاتف، عنوان، **GPS**, صورة) | `customer/search`, `customer/store` |
| `AttendanceScreen` | سجل الحضور | `GET api/v1/attendance` |
| `DailyFingerprintManagementScreen` | **بصمة يومية** بالموقع (checkin/checkout) | `POST api/v1/attendance/store` |
| `CreateVisitScreen` | إنشاء زيارة (عميل + نتيجة + ملاحظة + GPS + صورة) | `POST api/v1/visitor/store` |
| `RequiredVisitsListScreen` | الزيارات المطلوبة | `GET api/v1/visitor/list` |
| `VisitsCarriedOutScreen` | الزيارات المنفّذة | `GET api/v1/visitor/result` |
| `CreateMonthlyPlanScreen` | خطة شهرية للزيارات | — |
| `AddRequestResourcesScreen` | طلب موارد/بضاعة (تصنيف + منتجات + كميات) | `stocks/list`, resources |
| `TransferSectionScreen` → `AddTransferSectionScreen` | تحويل بين الأقسام (حساب + مبلغ + صورة) | transfer |
| `ReseatScreen` / `AddSampleScreen` / `AddDonationScreen` | إيصالات: عيّنات + تبرعات | reseat |
| `SalaryScreen` | كشف الراتب (شهري) | `GET api/v1/salary?month=` |
| `AllCoursesScreen` | الكورسات التدريبية | `GET api/v1/courses` |
| `DocumentsScreen` | مستندات المندوب | `GET api/v1/seller/indexdocument` |
| `LeaveRequestsScreen` → `AddLeaveRequestScreen` | طلبات الإجازة (تاريخ + ملاحظة) | leave |
| `MyRequestsScreen` → `AddMyRequestScreen` | طلباتي العامة | requests |
| `MandoubeScreen` / `RateMandoubeScreen` | المناديب + تقييمهم | `seller/rating` |
| `RecommendationsFromManagerScreen` | توصيات المدير | — |
| `ZeroingMyTripsScreen` | تصفير الرحلات (إقفال اليوم) | — |
| `SettingScreen` / `PrivacyScreen` | الإعدادات + الخصوصية | — |

---

## 🔄 الـ Flow اليومي الكامل للمندوب

```
1. Login  (كود المندوب + كلمة المرور)         ──> POST login
        │
2. بصمة الحضور (بصمة + GPS)                    ──> POST attendance/store
        │
3. يبدأ رحلته الميدانية:
        │
   ┌────┴─────────────────────────────────────────────┐
   │                                                   │
 يفتح عميل من Customers / زيارة مطلوبة                  │
   │                                                   │
   ├─ بيع:    SalesScreen ─> يختار منتجات              │
   │          ─> place/order ─> فاتورة ─> طباعة        │
   │                                                   │
   ├─ تحصيل:  CollectionsScreen ─> مبلغ + حساب + صورة  │
   │          ─> transactionseller                     │
   │                                                   │
   └─ زيارة:  CreateVisit ─> نتيجة + صورة + GPS        │
              ─> visitor/store                          │
        │                                              │
4. آخر اليوم:                                          │
   ZeroingMyTrips (تصفير) + مراجعة Statistics ──────────┘
```

---

## 🧩 العنصر المشترك في كل عملية

- **الموقع (GPS)** إجباري مع أغلب العمليات: بيع / تحصيل / زيارة / بصمة / إضافة عميل → `latitude` + `longitude`.
- **الصورة (إثبات)** مرفقة كثيراً: إيصالات التحصيل / التحويلات / نتيجة الزيارة / صورة العميل.
- **الطباعة** الحرارية بعد كل فاتورة (XPrinter Bluetooth).
- **الفواتير** PDF تُعرض بـ Syncfusion PDF Viewer.
- **Shimmer** (شاشات تحميل) قبل ظهور البيانات في كل قائمة.

> جوهر التطبيق: **تتبّع ميداني كامل للمندوب** (Sales + Collection + Visits) مربوط بالموقع والوقت والصور.
