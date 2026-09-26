import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';

/// رسم بياني بسيط للإيرادات (area chart) مرسوم بـ CustomPainter.
class RevenueChart extends StatelessWidget {
  /// نسب 0..1 لكل نقطة.
  final List<double> points;
  const RevenueChart({super.key, required this.points});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: Size.infinite,
      painter: _ChartPainter(points),
    );
  }
}

class _ChartPainter extends CustomPainter {
  final List<double> points;
  _ChartPainter(this.points);

  @override
  void paint(Canvas canvas, Size size) {
    // خطوط الشبكة
    final grid = Paint()
      ..color = AppColors.line
      ..strokeWidth = 1;
    for (var i = 1; i <= 3; i++) {
      final y = size.height * i / 4;
      canvas.drawLine(Offset(0, y), Offset(size.width, y), grid);
    }

    // إحداثيات النقاط
    final dx = size.width / (points.length - 1);
    final coords = <Offset>[
      for (var i = 0; i < points.length; i++)
        Offset(i * dx, size.height * (1 - points[i])),
    ];

    // مسار الخط
    final linePath = Path()..moveTo(coords.first.dx, coords.first.dy);
    for (final p in coords.skip(1)) {
      linePath.lineTo(p.dx, p.dy);
    }

    // تعبئة المساحة
    final fillPath = Path.from(linePath)
      ..lineTo(size.width, size.height)
      ..lineTo(0, size.height)
      ..close();
    canvas.drawPath(
      fillPath,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0x472563EB), Color(0x002563EB)],
        ).createShader(Offset.zero & size),
    );

    // الخط
    canvas.drawPath(
      linePath,
      Paint()
        ..color = AppColors.primary
        ..strokeWidth = 2.5
        ..style = PaintingStyle.stroke
        ..strokeJoin = StrokeJoin.round
        ..strokeCap = StrokeCap.round,
    );

    // نقطة النهاية
    canvas.drawCircle(coords.last, 4.5,
        Paint()..color = AppColors.primary);
    canvas.drawCircle(coords.last, 4.5,
        Paint()
          ..color = AppColors.surface
          ..strokeWidth = 2
          ..style = PaintingStyle.stroke);
  }

  @override
  bool shouldRepaint(covariant _ChartPainter oldDelegate) =>
      oldDelegate.points != points;
}
