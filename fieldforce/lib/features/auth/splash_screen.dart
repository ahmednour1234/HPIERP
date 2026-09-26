import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/theme/app_colors.dart';
import '../../core/network/token_store.dart';
import '../../core/network/api_client.dart';
import '../../core/network/api_exception.dart';

/// شاشة البداية — تقرر الوجهة حسب وجود توكن محفوظ.
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _decide();
  }

  Future<void> _decide() async {
    final loggedIn = await TokenStore.instance.isLoggedIn;
    if (!loggedIn) {
      if (mounted) context.go('/login');
      return;
    }
    // تأكّد أن التوكن صالح فعلاً (قد يكون قديماً من سيرفر آخر).
    try {
      await ApiClient.instance.get('/profile');
      if (mounted) context.go('/home');
    } on ApiException catch (e) {
      // توكن غير صالح (401) → امسحه وارجع للدخول.
      // أي خطأ آخر (شبكة/سيرفر) لا يسجّل الخروج.
      if (e.isUnauthorized) {
        await TokenStore.instance.clear();
        if (mounted) context.go('/login');
      } else {
        if (mounted) context.go('/home');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: AppColors.nav,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _Logo(),
            SizedBox(height: 20),
            SizedBox(
              width: 26,
              height: 26,
              child: CircularProgressIndicator(
                  strokeWidth: 2.4, color: Colors.white),
            ),
          ],
        ),
      ),
    );
  }
}

class _Logo extends StatelessWidget {
  const _Logo();
  @override
  Widget build(BuildContext context) {
    // اللوجو الكامل بنسخته الفاتحة — مباشرة على الخلفية الكحلي بلا بطاقة.
    return SizedBox(
      width: 230,
      child: Image.asset('assets/images/hpi_logo_full_light.png',
          fit: BoxFit.contain),
    );
  }
}
