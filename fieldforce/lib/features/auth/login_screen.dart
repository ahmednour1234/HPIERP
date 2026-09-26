import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/theme/app_colors.dart';
import '../../core/network/api_exception.dart';
import 'auth_repository.dart';

/// شاشة تسجيل الدخول — كود المندوب + كلمة المرور.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _codeCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _obscure = true;
  bool _loading = false;
  final _auth = AuthRepository();

  @override
  void dispose() {
    _codeCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      await _auth.login(code: _codeCtrl.text.trim(), password: _passCtrl.text);
      if (!mounted) return;
      context.go('/home');
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message), backgroundColor: AppColors.danger),
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.nav,
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [AppColors.navSoft, AppColors.nav, AppColors.primaryDeep],
            begin: Alignment.topRight,
            end: Alignment.bottomLeft,
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 12, 24, 26),
            child: Form(
              key: _formKey,
              // المحتوى ككتلة واحدة متماسكة في منتصف الشاشة — بلا فراغ
              // بين اللوجو والنموذج، ويتمرّر عند ظهور لوحة المفاتيح.
              child: LayoutBuilder(
                builder: (context, constraints) {
                  // مع فتح لوحة المفاتيح تقلّ المساحة، فنصغّر اللوجو ونقلّص
                  // المسافات بدل أن يتداخل المحتوى.
                  final tight = constraints.maxHeight < 560;
                  return SingleChildScrollView(
                    child: ConstrainedBox(
                      constraints: BoxConstraints(
                        minHeight: constraints.maxHeight,
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(height: tight ? 8 : 24),
                          // اللوجو الكامل بنسخته الفاتحة — مباشرة على الخلفية
                          // الكحلي، ويغني عن عنوان يكرّر اسم الشركة.
                          SizedBox(
                            width: tight ? 150 : 230,
                            child: Image.asset(
                              'assets/images/hpi_logo_full_light.png',
                              fit: BoxFit.contain,
                            ),
                          ),
                          SizedBox(height: tight ? 10 : 18),
                          // السطر التوضيحي يختفي عند ضيق المساحة.
                          if (!tight) ...[
                            const Text(
                              'ادخل كود المندوب وكلمة المرور لبدء يومك.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: AppColors.onNavMuted,
                                fontSize: 13.5,
                              ),
                            ),
                            const SizedBox(height: 30),
                          ] else
                            const SizedBox(height: 12),
                          _label('كود المندوب'),
                          _field(
                            controller: _codeCtrl,
                            hint: 'FF-2048',
                            validator: (v) => (v == null || v.isEmpty)
                                ? 'اكمل هذا الحقل'
                                : null,
                          ),
                          const SizedBox(height: 14),
                          _label('كلمة المرور'),
                          _field(
                            controller: _passCtrl,
                            hint: '••••••••',
                            obscure: _obscure,
                            suffix: IconButton(
                              icon: Icon(
                                _obscure
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                                color: AppColors.onNavMuted,
                                size: 20,
                              ),
                              onPressed: () =>
                                  setState(() => _obscure = !_obscure),
                            ),
                            validator: (v) => (v == null || v.isEmpty)
                                ? 'اكمل هذا الحقل'
                                : null,
                          ),
                          const SizedBox(height: 22),
                          SizedBox(
                            width: double.infinity,
                            // زر معكوس عن باقي التطبيق: خلفية بيضاء وكتابة كحلي —
                            // لأن الشاشة نفسها بخلفية كحلي داكنة.
                            child: ElevatedButton(
                              onPressed: _loading ? null : _submit,
                              style:
                                  ElevatedButton.styleFrom(
                                    backgroundColor: Colors.white,
                                    foregroundColor: AppColors.primary,
                                    disabledBackgroundColor: Colors.white70,
                                    disabledForegroundColor:
                                        AppColors.primaryLight,
                                    // لمسة الضغط/التحويم: طبقة كحلية فوق الأبيض.
                                    overlayColor: AppColors.primary,
                                  ).copyWith(
                                    backgroundColor:
                                        WidgetStateProperty.resolveWith((
                                          states,
                                        ) {
                                          if (states.contains(
                                            WidgetState.disabled,
                                          )) {
                                            return Colors.white70;
                                          }
                                          if (states.contains(
                                                WidgetState.pressed,
                                              ) ||
                                              states.contains(
                                                WidgetState.hovered,
                                              )) {
                                            return AppColors.primaryWash;
                                          }
                                          return Colors.white;
                                        }),
                                  ),
                              child: _loading
                                  ? const SizedBox(
                                      width: 22,
                                      height: 22,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2.4,
                                        color: AppColors.primary,
                                      ),
                                    )
                                  : const Text('تسجيل الدخول'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _label(String text) => Align(
    alignment: Alignment.centerRight,
    child: Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text(
        text,
        style: const TextStyle(
          color: AppColors.onNavMuted,
          fontSize: 12.5,
          fontWeight: FontWeight.w500,
        ),
      ),
    ),
  );

  Widget _field({
    required TextEditingController controller,
    required String hint,
    bool obscure = false,
    Widget? suffix,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      obscureText: obscure,
      validator: validator,
      style: const TextStyle(color: AppColors.onNav),
      decoration: InputDecoration(
        hintText: hint,
        suffixIcon: suffix,
        filled: true,
        fillColor: AppColors.navSoft,
        hintStyle: const TextStyle(color: AppColors.onNavMuted),
        enabledBorder: _navBorder(AppColors.navLine),
        focusedBorder: _navBorder(AppColors.primary, width: 1.6),
        errorBorder: _navBorder(AppColors.danger),
        focusedErrorBorder: _navBorder(AppColors.danger, width: 1.6),
      ),
    );
  }

  OutlineInputBorder _navBorder(Color c, {double width = 1}) =>
      OutlineInputBorder(
        borderRadius: BorderRadius.circular(13),
        borderSide: BorderSide(color: c, width: width),
      );
}
