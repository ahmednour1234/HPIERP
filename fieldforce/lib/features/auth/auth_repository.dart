import '../../core/network/api_client.dart';
import '../../core/network/token_store.dart';

/// المصادقة — login عبر v1 وتخزين التوكن.
class AuthRepository {
  final _api = ApiClient.instance;

  /// تسجيل دخول المندوب. يرمي ApiException عند الفشل.
  Future<void> login({required String code, required String password}) async {
    // login على v1 — رده لا يتبع الـ envelope (token + admin في الجذر).
    final data = await _api.post(
      '/login',
      body: {'code': code, 'password': password},
      v1: true,
    );

    final map = data as Map<String, dynamic>;
    final token = map['token'] as String;
    final admin = map['admin'] as Map<String, dynamic>;

    // المدير: الباك يوصي باستخدام is_manager (يغطي type=manager والأدمن).
    // رد الدخول قد يحمل is_manager أو type=manager.
    final isManager = admin['is_manager'] == true ||
        (admin['type'] ?? '').toString() == 'manager';

    await TokenStore.instance.save(
      token: token,
      sellerId: admin['id'] as int,
      sellerName: (admin['f_name'] ?? 'مندوب').toString(),
      role: isManager ? 'manager' : 'seller',
    );
  }

  Future<void> logout() => TokenStore.instance.clear();

  Future<bool> get isLoggedIn => TokenStore.instance.isLoggedIn;
}
