import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';

/// بطاقة رقم إحصائي (KPI) — فاخرة بظل ناعم وأيقونة.
class KpiCard extends StatelessWidget {
  final String label;
  final String value;
  final String unit;
  final String delta;
  final bool positive;
  final IconData? icon;

  const KpiCard({
    super.key,
    required this.label,
    required this.value,
    required this.unit,
    required this.delta,
    required this.positive,
    this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.line),
        boxShadow: AppColors.softShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(
                  color: AppColors.primaryWash,
                  borderRadius: BorderRadius.circular(9),
                ),
                child: Icon(icon ?? Icons.insights_rounded,
                    size: 17, color: AppColors.primary),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 12, color: AppColors.muted)),
              ),
            ],
          ),
          RichText(
            text: TextSpan(
              text: value,
              style: const TextStyle(
                fontSize: 23,
                fontWeight: FontWeight.w800,
                color: AppColors.ink,
                letterSpacing: -0.5,
                fontFeatures: [FontFeature.tabularFigures()],
              ),
              children: [
                TextSpan(
                  text: ' $unit',
                  style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.muted,
                      fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
          Row(
            children: [
              Icon(
                positive
                    ? Icons.trending_up_rounded
                    : Icons.trending_down_rounded,
                size: 14,
                color: positive ? AppColors.good : AppColors.danger,
              ),
              const SizedBox(width: 4),
              Flexible(
                child: Text(
                  delta,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 11.5,
                    fontWeight: FontWeight.w600,
                    color: positive ? AppColors.good : AppColors.danger,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
