# رفع HPI على Google Play

## الملفات الجاهزة (في الفولدر ده)

| الملف | الاستخدام |
|---|---|
| `HPI-ERP-1.0.4.aab` | **الملف اللي بيترفع** على Play (Release → Create release) |
| `icon-512.png` | App icon (512 × 512) |
| `feature-graphic-1024x500.png` | Feature graphic (1024 × 500) |

- الإصدار: `1.0.4` · Version code: `6` — من `fieldforce/pubspec.yaml`. **لازم يزيد** مع كل رفعة.
- Package: `com.hpii.app`
- السيرفر: `https://erp.hpi-eg.com`

## 🔐 مفتاح الرفع (Upload key) — أهم حاجة

| | |
|---|---|
| الملف | `E:\HPI-keys\hpi-upload.jks` |
| كلمة السر | في `E:\HPI-keys\key.properties.backup` |
| Alias | `upload` |
| SHA-256 | `A9:D2:97:C6:1C:53:CE:E3:F5:EA:CD:E8:C3:04:D8:CB:F3:79:68:BA:8D:25:32:F0:37:30:0C:8A:76:68:B8:6D` |

- **اعمل نسخة من فولدر `E:\HPI-keys` في مكانين آمنين** (فلاشة + Drive خاص). مش متحطّط على GitHub عن قصد.
- لو ضاع: مش هتقدر ترفع تحديثات لحد ما تطلب من Google *Upload key reset* (بياخد أيام).
- البناء بيقرأه من `fieldforce/android/key.properties` (متجاهَل في git). على جهاز تاني: انسخ الملفين وظبط `storeFile`.

## خطوات الرفع

1. **حساب Google Play Console** — [play.google.com/console](https://play.google.com/console) (25$ مرة واحدة).
   افتحه كـ **Organization** باسم الشركة (محتاج D-U-N-S number).
   > لو اتفتح كـ Personal: جوجل بتشترط **Closed testing بـ 12 مختبِر لمدة 14 يوم** قبل النشر للعامة.
2. **Create app** → Name `HPI` · Default language Arabic · App · Free.
3. **Play App Signing**: سيبه مفعّل (الافتراضي). المفتاح اللي فوق بيبقى *Upload key* بس.
4. **Main store listing** — النصوص كاملة في `STORE_LISTING.md` (قسم Google Play):
   - الوصف القصير والكامل (عربي + English)
   - الأيقونة والـ feature graphic من الفولدر ده
   - **Phone screenshots**: من 2 لـ 8 صور (أقل ضلع 320px) — صوّرهم من موبايل بعد تسجيل الدخول
     (الرئيسية · العملاء · الطلبات · التحصيل · الحضور · خريطة المناديب).
5. **App content** (لازم كله يبقى ✅ قبل النشر):

| البند | الإجابة |
|---|---|
| Privacy policy | `https://erp.hpi-eg.com/privacy-policy` |
| App access | *All or some functionality is restricted* → حط حساب مندوب تجريبي (كود + كلمة سر) |
| Ads | No |
| Content rating | استبيان IARC → Category: *Utility / Productivity* → كل الإجابات No → الناتج Everyone |
| Target audience | 18+ |
| News app | No |
| Data safety | تحت ↓ |
| Government app | No |
| Financial features | None (التحصيل داخلي، مش دفع إلكتروني) |
| Health | No |

6. **Data safety**:
   - Collects data: **Yes** · Shared: **No** · Encrypted in transit: **Yes** · Users can request deletion: **Yes** (عن طريق الشركة).
   - Data types (كلها *Collected* · *Required* · Purpose: **App functionality**):
     - Location → **Precise location**
     - Personal info → **Name**, **User IDs**, **Phone number**
     - Photos and videos → **Photos**
7. **Release** → Production (أو Internal testing الأول للتجربة) → Create release → ارفع `HPI-ERP-1.0.4.aab`
   → Release notes: `الإصدار الأول من تطبيق HPI للمناديب.` → Review → **Start rollout**.

المراجعة بتاخد من ساعات لـ 7 أيام في أول مرة.

## كل تحديث بعد كده

```bash
# 1) زوّد الإصدار في fieldforce/pubspec.yaml  مثلاً: version: 1.0.5+7
cd fieldforce
flutter build appbundle --release
# الملف: build/app/outputs/bundle/release/app-release.aab
```
