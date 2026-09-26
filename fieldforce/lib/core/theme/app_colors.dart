import 'package:flutter/material.dart';

/// ألوان HPI — مستخرجة من اللوجو الرسمي.
/// كحلي #184878 (أساسي) + أزرق فاتح #90C0F0 (لمسات).
class AppColors {
  AppColors._();

  // ===== Brand (من اللوجو) =====
  static const Color primary = Color(0xFF184878); // كحلي HPI
  static const Color primaryDeep = Color(0xFF0F3159); // أغمق للضغط/العمق
  static const Color primaryLight = Color(0xFF2E6BA8); // كحلي فاتح
  static const Color sky = Color(0xFF90C0F0); // أزرق فاتح (accent)
  static const Color skyDeep = Color(0xFF5B9BD5);
  static const Color primaryWash = Color(0xFFEAF2FB); // خلفية زرقاء فاتحة جداً
  static const Color skyWash = Color(0xFFF0F7FF);

  // ===== Neutrals (مائلة للكحلي — مختارة مش افتراضية) =====
  static const Color bg = Color(0xFFF4F7FB); // خلفية عامة
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceAlt = Color(0xFFFAFCFF);
  static const Color line = Color(0xFFE4EAF2);
  static const Color lineSoft = Color(0xFFEEF2F8);
  static const Color ink = Color(0xFF0C1D33); // نص أساسي (كحلي غامق)
  static const Color inkSoft = Color(0xFF3A4C66);
  static const Color muted = Color(0xFF7A8BA3);

  // ===== Dark frames (login / splash) =====
  static const Color nav = Color(0xFF0F3159);
  static const Color navSoft = Color(0xFF184878);
  static const Color navLine = Color(0xFF2E5A8C);
  static const Color onNav = Color(0xFFEAF2FB);
  static const Color onNavMuted = Color(0xFF9FBAD8);

  // ===== Semantic =====
  static const Color good = Color(0xFF13A97C);
  static const Color warn = Color(0xFFE8A317);
  static const Color danger = Color(0xFFE24C4B);

  static const Color goodWash = Color(0xFFE4F6EF);
  static const Color warnWash = Color(0xFFFCF3E0);
  static const Color dangerWash = Color(0xFFFCE9E8);

  // ===== Gradients =====
  static const LinearGradient brandGradient = LinearGradient(
    colors: [primaryLight, primaryDeep],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  static const LinearGradient skyGradient = LinearGradient(
    colors: [sky, skyDeep],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  // ===== Shadows (ناعمة، فاخرة) =====
  static List<BoxShadow> cardShadow = [
    BoxShadow(
      color: const Color(0xFF184878).withValues(alpha: 0.06),
      blurRadius: 18,
      offset: const Offset(0, 6),
    ),
  ];
  static List<BoxShadow> softShadow = [
    BoxShadow(
      color: const Color(0xFF184878).withValues(alpha: 0.04),
      blurRadius: 10,
      offset: const Offset(0, 3),
    ),
  ];
  static List<BoxShadow> brandShadow = [
    BoxShadow(
      color: const Color(0xFF184878).withValues(alpha: 0.28),
      blurRadius: 20,
      offset: const Offset(0, 10),
    ),
  ];
}
