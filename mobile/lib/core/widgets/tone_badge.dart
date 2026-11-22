import 'package:material_ui/material_ui.dart';

import '../theme/app_colors.dart';

enum Tone { green, amber, red, blue, violet, teal, gold, gray }

extension ToneColors on Tone {
  (Color background, Color foreground) get colors => switch (this) {
    Tone.green => (AppColors.green50, AppColors.green600),
    Tone.amber => (AppColors.amber50, AppColors.amber600),
    Tone.red => (AppColors.red50, AppColors.red600),
    Tone.blue => (AppColors.blue50, AppColors.blue600),
    Tone.violet => (AppColors.violet50, AppColors.violet600),
    Tone.teal => (AppColors.teal50, AppColors.teal700),
    Tone.gold => (AppColors.gold100, AppColors.gold600),
    Tone.gray => (AppColors.bg2, AppColors.ink2),
  };
}

/// Pill badge (`.badge.b-*` in the prototype).
class ToneBadge extends StatelessWidget {
  const ToneBadge(this.label, {super.key, this.tone = Tone.gray, this.icon});

  final String label;
  final Tone tone;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = tone.colors;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 2),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(999)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[Icon(icon, size: 13, color: fg), const SizedBox(width: 4)],
          Text(
            label,
            style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: fg, height: 1.7),
          ),
        ],
      ),
    );
  }
}
