/// إعدادات الاتصال بالـ API.
class ApiConfig {
  ApiConfig._();

  /// السيرفر المباشر (HTTPS).
  /// للتطوير المحلي بدّلها بـ:
  /// - محاكي Android: http://10.0.2.2:8000
  /// - ويب / iOS sim: http://127.0.0.1:8000
  static const String host = 'https://2026-test-new.hpi-eg.com';

  static const String v1 = '$host/api/v1';
  static const String v2 = '$host/api/v2';

  static const Duration timeout = Duration(seconds: 40);
}
