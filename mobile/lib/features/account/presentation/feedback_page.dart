import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../domain/feedback_repository.dart';
import 'feedback_providers.dart';

/// «ملاحظاتك» — general feedback (contract item 6). Answer ratings live next to each answer instead.
class FeedbackPage extends ConsumerStatefulWidget {
  const FeedbackPage({super.key});

  @override
  ConsumerState<FeedbackPage> createState() => _FeedbackPageState();
}

class _FeedbackPageState extends ConsumerState<FeedbackPage> {
  final _message = TextEditingController();
  FeedbackCategory _category = FeedbackCategory.suggestion;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _message.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _message.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    setState(() => _sending = true);
    try {
      await ref.read(feedbackRepositoryProvider).send(category: _category, message: _message.text.trim(), screen: 'account');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('وصلتنا ملاحظتك، شكرًا لك.')));
      context.pop();
    } on Object {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تعذّر الإرسال. حاول مرة أخرى.')));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final valid = _message.text.trim().length >= 5;
    return Scaffold(
      appBar: AppBar(title: const Text('ملاحظاتك')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text('نقرأ كل ملاحظة. لا تكتب بيانات تعريفية للمريض.', style: TextStyle(color: AppColors.muted, fontSize: 13)),
          const SizedBox(height: 16),
          SegmentedButton<FeedbackCategory>(
            segments: const [
              ButtonSegment(value: FeedbackCategory.bug, label: Text('مشكلة'), icon: Icon(LucideIcons.bug, size: 16)),
              ButtonSegment(value: FeedbackCategory.suggestion, label: Text('اقتراح'), icon: Icon(LucideIcons.lightbulb, size: 16)),
              ButtonSegment(value: FeedbackCategory.other, label: Text('أخرى'), icon: Icon(LucideIcons.ellipsis, size: 16)),
            ],
            selected: {_category},
            onSelectionChanged: (s) => setState(() => _category = s.first),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _message,
            minLines: 5,
            maxLines: 10,
            maxLength: 1000,
            decoration: const InputDecoration(hintText: 'اكتب ملاحظتك هنا…'),
          ),
          const SizedBox(height: 8),
          FilledButton.icon(
            onPressed: valid && !_sending ? _send : null,
            icon: const Icon(LucideIcons.send),
            label: Text(_sending ? 'جارٍ الإرسال…' : 'إرسال'),
          ),
        ],
      ),
    );
  }
}
