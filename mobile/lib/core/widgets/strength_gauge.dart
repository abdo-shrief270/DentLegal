import 'dart:math' as math;

import 'package:material_ui/material_ui.dart';

import '../theme/app_colors.dart';

/// «قوة الموقف» ring from the prototype's analysis screen.
class StrengthGauge extends StatelessWidget {
  const StrengthGauge({super.key, required this.value, this.size = 96});
  final int value;
  final double size;

  @override
  Widget build(BuildContext context) {
    final color = value >= 75
        ? AppColors.teal600
        : value >= 50
        ? AppColors.gold500
        : AppColors.red600;
    return Semantics(
      label: 'قوة الموقف $value بالمئة',
      child: SizedBox(
        width: size,
        height: size,
        child: CustomPaint(
          painter: _RingPainter(value / 100, color),
          child: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  '$value%',
                  style: TextStyle(fontSize: size * 0.23, fontWeight: FontWeight.w700, color: AppColors.ink, height: 1.1),
                ),
                Text(
                  'قوة الموقف',
                  style: TextStyle(fontSize: size * 0.1, color: AppColors.muted),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _RingPainter extends CustomPainter {
  _RingPainter(this.fraction, this.color);
  final double fraction;
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final stroke = size.width * 0.1;
    final rect = Offset.zero & size;
    final inset = rect.deflate(stroke / 2);
    canvas.drawArc(
      inset,
      0,
      2 * math.pi,
      false,
      Paint()
        ..color = AppColors.bg2
        ..style = PaintingStyle.stroke
        ..strokeWidth = stroke,
    );
    canvas.drawArc(
      inset,
      -math.pi / 2,
      2 * math.pi * fraction.clamp(0, 1),
      false,
      Paint()
        ..color = color
        ..style = PaintingStyle.stroke
        ..strokeCap = StrokeCap.round
        ..strokeWidth = stroke,
    );
  }

  @override
  bool shouldRepaint(_RingPainter old) => old.fraction != fraction || old.color != color;
}
