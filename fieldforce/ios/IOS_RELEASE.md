# رفع HPI على App Store

| الإعداد | القيمة |
|---|---|
| Bundle ID | `com.hpii.app` |
| الاسم الظاهر | `HPI` |
| الإصدار | من `pubspec.yaml` → `version: 1.0.0+1` (1.0.0 = Version، 1 = Build) |
| أقل iOS | 15.0 |
| الأجهزة | iPhone فقط — عمودي فقط |
| الصلاحيات | الموقع (أثناء الاستخدام) · الكاميرا · مكتبة الصور |
| Privacy Manifest | `Runner/PrivacyInfo.xcprivacy` |
| Encryption | `ITSAppUsesNonExemptEncryption = NO` |

## مرة واحدة بس (أول رفع)

1. **Apple Developer** → [developer.apple.com/account](https://developer.apple.com/account)
   → Certificates, IDs & Profiles → Identifiers → **+** → App IDs
   → Bundle ID: `com.hpii.app` (Explicit). مش محتاج أي Capabilities إضافية.
2. **App Store Connect** → [appstoreconnect.apple.com](https://appstoreconnect.apple.com)
   → My Apps → **+** → New App → Platform iOS · Name `HPI` · Primary language Arabic
   · Bundle ID `com.hpii.app` · SKU `hpi-fieldforce`.
3. **Team ID**: من developer.apple.com → Membership، وحطّه مكان `YOUR_TEAM_ID` في
   `ios/ExportOptions.plist`.
4. على الماك افتح `ios/Runner.xcworkspace` في Xcode
   → Runner → Signing & Capabilities → اختار الـ **Team** وسيب *Automatically manage signing* متعلّمة.

## كل رفعة

على الماك (Xcode + CocoaPods متسطّبين):

```bash
cd fieldforce
flutter clean
flutter pub get
cd ios && pod install && cd ..

# زوّد رقم الـ build كل رفعة (Apple بترفض نفس الرقم مرتين)
flutter build ipa --release \
  --build-name=1.0.0 --build-number=1 \
  --export-options-plist=ios/ExportOptions.plist
```

الملف بيطلع في `build/ios/ipa/HPI.ipa`. ارفعه بأي طريقة:
- **Transporter** (من الـ Mac App Store) → اسحب الـ `.ipa` → Deliver، أو
- Xcode → Window → Organizer → الأرشيف → **Distribute App** → App Store Connect.

بعد ~15 دقيقة الـ build يظهر في App Store Connect → TestFlight، ومنه تختاره للإصدار.

## قبل ما تضغط Submit for Review
باقي البيانات (الوصف، الصور، الحساب التجريبي، الخصوصية) في `STORE_LISTING.md` في أول الريبو.
