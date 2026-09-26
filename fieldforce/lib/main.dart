import 'package:flutter/material.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'core/network/api_client.dart';
import 'core/network/token_store.dart';

void main() {
  // عند أي 401: امسح التوكن وارجع لشاشة الدخول.
  ApiClient.instance.onUnauthorized = () async {
    await TokenStore.instance.clear();
    AppRouter.router.go('/login');
  };
  runApp(const FieldForceApp());
}

class FieldForceApp extends StatelessWidget {
  const FieldForceApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'HPI',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      routerConfig: AppRouter.router,
      // اتجاه عربي RTL
      locale: const Locale('ar'),
      builder: (context, child) => Directionality(
        textDirection: TextDirection.rtl,
        child: child!,
      ),
    );
  }
}
