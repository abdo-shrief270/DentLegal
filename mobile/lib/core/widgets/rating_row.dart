import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../theme/app_colors.dart';

/// «مفيدة / غير مفيدة» + optional comment (feedback tools, contract item 6).
/// «غير مفيدة» also serves as the AI-output report mechanism Google Play requires.
class RatingRow extends StatefulWidget {
  const RatingRow({super.key, required this.onRate, this.initial, this.dense = false});

  final Future<void> Function(bool useful, String? comment) onRate;
  final bool? initial;
  final bool dense;

  @override
  State<RatingRow> createState() => _RatingRowState();
}

class _RatingRowState extends State<RatingRow> {
  late bool? _value = widget.initial;

  Future<void> _rate(bool useful) async {
    String? comment;
    if (!useful) {
      comment = await showModalBottomSheet<String>(context: context, isScrollControlled: true, builder: (_) => const _CommentSheet());
      if (comment == null) return;
    }
    setState(() => _value = useful);
    await widget.onRate(useful, comment);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('شكرًا! تقييمك يحسّن جودة الإجابات.')));
  }

  @override
  Widget build(BuildContext context) {
    Widget button(bool useful) {
      final selected = _value == useful;
      final color = useful ? AppColors.green600 : AppColors.red600;
      return TextButton.icon(
        onPressed: () => _rate(useful),
        style: TextButton.styleFrom(
          foregroundColor: selected ? color : AppColors.muted,
          backgroundColor: selected ? (useful ? AppColors.green50 : AppColors.red50) : null,
          visualDensity: VisualDensity.compact,
        ),
        icon: Icon(useful ? LucideIcons.thumbsUp : LucideIcons.thumbsDown, size: 16),
        label: Text(useful ? 'مفيدة' : 'غير مفيدة'),
      );
    }

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (!widget.dense) const Text('هل كان هذا مفيدًا؟', style: TextStyle(fontSize: 12.5, color: AppColors.muted)),
        if (!widget.dense) const SizedBox(width: 4),
        button(true),
        button(false),
      ],
    );
  }
}

class _CommentSheet extends StatefulWidget {
  const _CommentSheet();
  @override
  State<_CommentSheet> createState() => _CommentSheetState();
}

class _CommentSheetState extends State<_CommentSheet> {
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Padding(
    padding: EdgeInsets.fromLTRB(20, 0, 20, MediaQuery.viewInsetsOf(context).bottom + 20),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text('ما الذي لم يكن مفيدًا؟', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 4),
        const Text('تعليقك اختياري ويساعدنا على التحسين. لا تكتب بيانات تعريفية للمريض.', style: TextStyle(fontSize: 12.5, color: AppColors.muted)),
        const SizedBox(height: 12),
        TextField(
          controller: _controller,
          maxLines: 3,
          decoration: const InputDecoration(hintText: 'مثال: الإجابة لم تتناول سؤالي مباشرة'),
        ),
        const SizedBox(height: 12),
        FilledButton(onPressed: () => Navigator.pop(context, _controller.text.trim()), child: const Text('إرسال التقييم')),
      ],
    ),
  );
}
