/// خطأ موحّد من الـ API — يحمل الرسالة وأخطاء التحقق.
class ApiException implements Exception {
  final String message;
  final int? statusCode;

  /// أخطاء التحقق (422) مفهرسة بالحقل: { "name": ["مطلوب"] }
  final Map<String, List<String>>? errors;

  ApiException(this.message, {this.statusCode, this.errors});

  bool get isUnauthorized => statusCode == 401;
  bool get isValidation => statusCode == 422 || statusCode == 403;

  /// أول رسالة خطأ لحقل معيّن (لو موجودة).
  String? fieldError(String field) {
    final list = errors?[field];
    return (list != null && list.isNotEmpty) ? list.first : null;
  }

  @override
  String toString() => message;
}
