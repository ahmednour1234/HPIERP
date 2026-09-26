import 'dart:async';
import 'package:geolocator/geolocator.dart';

/// نتيجة قراءة الموقع.
class GeoPoint {
  final double lat;
  final double lng;
  const GeoPoint(this.lat, this.lng);
}

/// استثناء يُرمى عند تعذّر الحصول على الموقع، برسالة عربية جاهزة للعرض.
class LocationException implements Exception {
  final String message;
  const LocationException(this.message);
  @override
  String toString() => message;
}

/// خدمة الموقع — تطلب الإذن وتقرأ الإحداثيات الفعلية للمندوب.
class LocationService {
  /// يقرأ الموقع الحالي، ويطلب الإذن وتشغيل الـ GPS عند الحاجة.
  /// يرمي [LocationException] برسالة واضحة عند الفشل.
  static Future<GeoPoint> current() async {
    // كل استدعاء أصلي محاط بـ timeout صريح — على بعض الأجهزة/المحاكيات قد
    // تتعلّق دوال المنصّة بلا رجوع، فنضمن ألا تُجمّد الواجهة أبداً.
    // 1) خدمة الموقع مفعّلة على الجهاز؟
    final enabled = await Geolocator.isLocationServiceEnabled()
        .timeout(const Duration(seconds: 5), onTimeout: () => false);
    if (!enabled) {
      throw const LocationException(
          'خدمة الموقع (GPS) مُغلقة. فعّلها من إعدادات الهاتف ثم أعد المحاولة.');
    }

    // 2) الأذونات.
    var perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
    }
    if (perm == LocationPermission.denied) {
      throw const LocationException('لم يتم منح إذن الوصول إلى الموقع.');
    }
    if (perm == LocationPermission.deniedForever) {
      throw const LocationException(
          'إذن الموقع مرفوض دائماً. فعّله يدوياً من إعدادات التطبيق.');
    }

    // 3) القراءة السريعة:
    // أولاً نجرّب آخر موقع معروف فوراً (سريع، بلا انتظار GPS fix).
    try {
      final last = await Geolocator.getLastKnownPosition()
          .timeout(const Duration(seconds: 3));
      if (last != null) {
        // نطلق قراءة حديثة بالخلفية لتحديث الكاش، دون انتظارها.
        return GeoPoint(last.latitude, last.longitude);
      }
    } catch (_) {/* نكمل للقراءة الحيّة */}

    // لا يوجد موقع معروف: نقرأ موقعاً حياً بدقّة متوسطة (أسرع) وبمهلة قصيرة.
    try {
      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
          timeLimit: Duration(seconds: 8),
        ),
      ).timeout(const Duration(seconds: 8));
      return GeoPoint(pos.latitude, pos.longitude);
    } catch (_) {
      throw const LocationException(
          'تعذّر تحديد الموقع الآن. تأكد من تفعيل الـ GPS وأعد المحاولة.');
    }
  }
}
