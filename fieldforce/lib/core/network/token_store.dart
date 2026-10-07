import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// تخزين آمن للتوكن وبيانات المستخدم.
class TokenStore {
  TokenStore._();
  static final TokenStore instance = TokenStore._();

  static const _storage = FlutterSecureStorage();
  static const _kToken = 'access_token';
  static const _kSellerId = 'seller_id';
  static const _kSellerName = 'seller_name';
  static const _kRole = 'user_role';

  String? _cachedToken;
  String? _cachedRole;

  Future<void> save({
    required String token,
    required int sellerId,
    required String sellerName,
    String role = 'seller',
  }) async {
    _cachedToken = token;
    _cachedRole = role;
    await _storage.write(key: _kToken, value: token);
    await _storage.write(key: _kSellerId, value: '$sellerId');
    await _storage.write(key: _kSellerName, value: sellerName);
    await _storage.write(key: _kRole, value: role);
  }

  Future<String?> token() async {
    if (_cachedToken != null) return _cachedToken;
    try {
      return _cachedToken = await _storage
          .read(key: _kToken)
          .timeout(const Duration(seconds: 5));
    } catch (_) {
      // التخزين المشفّر بايظ (مثلاً اترجّع من نسخة احتياطية بمفتاح قديم
      // بعد إعادة التثبيت) → نمسحه ونعتبر المستخدم مش مسجّل دخول.
      try {
        await _storage.deleteAll();
      } catch (_) {}
      return null;
    }
  }

  Future<bool> get isLoggedIn async => (await token()) != null;

  Future<String?> sellerName() => _storage.read(key: _kSellerName);

  /// دور المستخدم: 'seller' (مندوب) أو 'manager' (مدير).
  Future<String> role() async =>
      _cachedRole ??= (await _storage.read(key: _kRole)) ?? 'seller';

  /// هل المستخدم مدير؟
  Future<bool> get isManager async => (await role()) == 'manager';

  Future<void> clear() async {
    _cachedToken = null;
    _cachedRole = null;
    await _storage.deleteAll();
  }
}
