import 'package:material_ui/material_ui.dart';

import '../theme/app_colors.dart';

/// The DentLegal tooth logo from the approved prototype (icons.js · logoMark), drawn on a 24×24 grid.
class BrandMark extends StatelessWidget {
  const BrandMark({super.key, this.size = 44, this.background = AppColors.teal600});
  final double size;
  final Color background;

  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    padding: EdgeInsets.all(size * 0.2),
    decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(size * 0.28)),
    child: const CustomPaint(painter: _ToothPainter()),
  );
}

class _ToothPainter extends CustomPainter {
  const _ToothPainter();

  @override
  void paint(Canvas canvas, Size size) {
    canvas.scale(size.width / 24, size.height / 24);
    final paint = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.7
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;
    final tooth = Path()
      ..moveTo(12, 5.5)
      ..cubicTo(10.6, 4.5, 9.4, 3.8, 7.7, 3.8)
      ..cubicTo(5, 3.8, 3.5, 5.9, 3.5, 8.6)
      ..cubicTo(3.5, 10.6, 4.3, 12, 4.8, 13.6)
      ..cubicTo(5.4, 15.6, 5.5, 20.2, 7.2, 20.2)
      ..cubicTo(8.8, 20.2, 8.8, 17, 9.6, 15.2)
      ..cubicTo(10, 14.3, 10.5, 13.8, 12, 13.8)
      ..cubicTo(13.5, 13.8, 14, 14.3, 14.4, 15.2)
      ..cubicTo(15.2, 17, 15.2, 20.2, 16.8, 20.2)
      ..cubicTo(18.5, 20.2, 18.6, 15.6, 19.2, 13.6)
      ..cubicTo(19.7, 12, 20.5, 10.6, 20.5, 8.6)
      ..cubicTo(20.5, 5.9, 19, 3.8, 16.3, 3.8)
      ..cubicTo(14.6, 3.8, 13.4, 4.5, 12, 5.5)
      ..close();
    final scale = Path()
      ..moveTo(12, 7.6)
      ..lineTo(12, 12.2)
      ..moveTo(9.2, 8.6)
      ..lineTo(14.8, 8.6)
      ..moveTo(9.2, 8.6)
      ..lineTo(8.3, 10.6)
      ..lineTo(10.1, 10.6)
      ..close()
      ..moveTo(14.8, 8.6)
      ..lineTo(13.9, 10.6)
      ..lineTo(15.7, 10.6)
      ..close();
    canvas
      ..drawPath(tooth, paint)
      ..drawPath(scale, paint);
  }

  @override
  bool shouldRepaint(_ToothPainter oldDelegate) => false;
}
