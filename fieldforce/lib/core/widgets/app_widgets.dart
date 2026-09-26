import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

/// بطاقة بظل ناعم (استخدام عام بدل Card حيث يلزم عمق).
class BrandCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;
  final VoidCallback? onTap;

  const BrandCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final content = Container(
      padding: padding,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.line),
        boxShadow: AppColors.cardShadow,
      ),
      child: child,
    );
    if (onTap == null) return content;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: content,
      ),
    );
  }
}

/// ترويسة شاشة موحّدة: أيقونة + عنوان + وصف.
class ScreenHeader extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final Widget? trailing;

  const ScreenHeader({
    super.key,
    required this.icon,
    required this.title,
    required this.subtitle,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 46,
          height: 46,
          decoration: BoxDecoration(
            gradient: AppColors.brandGradient,
            borderRadius: BorderRadius.circular(14),
            boxShadow: AppColors.brandShadow,
          ),
          child: Icon(icon, color: Colors.white, size: 23),
        ),
        const SizedBox(width: 13),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title,
                  style: const TextStyle(
                      fontSize: 19.5,
                      fontWeight: FontWeight.w800,
                      color: AppColors.ink,
                      letterSpacing: -0.3)),
              const SizedBox(height: 1),
              Text(subtitle,
                  style: const TextStyle(
                      fontSize: 12.5, color: AppColors.muted)),
            ],
          ),
        ),
        ?trailing,
      ],
    );
  }
}

/// شارة حالة ملوّنة (Pill).
class StatusBadge extends StatelessWidget {
  final String text;
  final Color color;
  final Color bg;

  const StatusBadge(
      {super.key, required this.text, required this.color, required this.bg});

  factory StatusBadge.good(String t) =>
      StatusBadge(text: t, color: AppColors.good, bg: AppColors.goodWash);
  factory StatusBadge.warn(String t) =>
      StatusBadge(text: t, color: const Color(0xFFB4740B), bg: AppColors.warnWash);
  factory StatusBadge.danger(String t) => StatusBadge(
      text: t, color: const Color(0xFFC0362F), bg: AppColors.dangerWash);
  factory StatusBadge.muted(String t) => StatusBadge(
      text: t, color: AppColors.muted, bg: const Color(0xFFF0F1F5));

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
      decoration:
          BoxDecoration(color: bg, borderRadius: BorderRadius.circular(999)),
      child: Text(text,
          style: TextStyle(
              color: color, fontSize: 11, fontWeight: FontWeight.w600)),
    );
  }
}

/// صف قائمة موحّد: مربع رمزي + عنوان + وصف + طرف (مبلغ/شارة).
class AppListRow extends StatelessWidget {
  final String thumb;
  final Color thumbColor;
  final Color thumbBg;
  final String name;
  final String sub;
  /// سطر ثالث اختياري (ملاحظة/مكان) — يظهر بأيقونة دبوس تحت `sub`.
  final String? note;
  final Widget? trailing;
  final VoidCallback? onTap;

  const AppListRow({
    super.key,
    required this.thumb,
    required this.name,
    required this.sub,
    this.note,
    this.trailing,
    this.thumbColor = AppColors.primaryDeep,
    this.thumbBg = AppColors.primaryWash,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 11),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: thumbBg,
                borderRadius: BorderRadius.circular(12),
              ),
              // FittedBox يمنع لفّ الرقم الطويل (مثل #2853) لسطرين.
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 3),
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Text(thumb,
                      maxLines: 1,
                      style: TextStyle(
                          color: thumbColor,
                          fontWeight: FontWeight.w700,
                          fontSize: 15)),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 14, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 2),
                  Text(sub,
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.muted)),
                  if (note != null && note!.trim().isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Padding(
                          padding: EdgeInsets.only(top: 1),
                          child: Icon(Icons.place_outlined,
                              size: 12.5, color: AppColors.primaryLight),
                        ),
                        const SizedBox(width: 3),
                        Expanded(
                          child: Text(note!,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                  fontSize: 11.5,
                                  height: 1.4,
                                  color: AppColors.inkSoft)),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            ?trailing,
          ],
        ),
      ),
    );
  }
}

/// مبلغ نقدي بخط ثابت العرض.
class MoneyText extends StatelessWidget {
  final String amount;
  final double size;
  final Color? color;
  const MoneyText(this.amount, {super.key, this.size = 14, this.color});

  @override
  Widget build(BuildContext context) => Text(
        amount,
        style: TextStyle(
          fontSize: size,
          fontWeight: FontWeight.w700,
          color: color ?? AppColors.ink,
          fontFeatures: const [FontFeature.tabularFigures()],
        ),
      );
}

/// شريط مقسّم (Segmented control).
class SegmentedTabs extends StatelessWidget {
  final List<String> items;
  final int selected;
  final ValueChanged<int> onChanged;
  final Color activeColor;

  const SegmentedTabs({
    super.key,
    required this.items,
    required this.selected,
    required this.onChanged,
    this.activeColor = AppColors.primary,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: AppColors.line),
      ),
      child: Row(
        children: [
          for (var i = 0; i < items.length; i++)
            Expanded(
              child: GestureDetector(
                onTap: () => onChanged(i),
                child: Container(
                  padding: const EdgeInsets.symmetric(vertical: 9),
                  decoration: BoxDecoration(
                    color: i == selected ? activeColor : Colors.transparent,
                    borderRadius: BorderRadius.circular(9),
                  ),
                  alignment: Alignment.center,
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    child: Text(
                      items[i],
                      maxLines: 1,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: i == selected ? Colors.white : AppColors.muted,
                      ),
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// حقل مُعنون (label + input).
class LabeledField extends StatelessWidget {
  final String label;
  final String hint;
  final TextEditingController? controller;
  final TextInputType? keyboardType;
  final Widget? suffix;

  const LabeledField({
    super.key,
    required this.label,
    required this.hint,
    this.controller,
    this.keyboardType,
    this.suffix,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 6, right: 2),
          child: Text(label,
              style: const TextStyle(
                  fontSize: 12.5,
                  color: AppColors.inkSoft,
                  fontWeight: FontWeight.w500)),
        ),
        TextField(
          controller: controller,
          keyboardType: keyboardType,
          decoration: InputDecoration(hintText: hint, suffixIcon: suffix),
        ),
      ],
    );
  }
}

/// صندوق GPS ملاحظة صغيرة.
class GpsNote extends StatelessWidget {
  final String text;
  const GpsNote(this.text, {super.key});

  @override
  Widget build(BuildContext context) => Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.location_on_rounded,
              size: 15, color: AppColors.primaryDeep),
          const SizedBox(width: 6),
          Flexible(
            child: Text(text,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                    fontSize: 12, color: AppColors.primaryDeep)),
          ),
        ],
      );
}
