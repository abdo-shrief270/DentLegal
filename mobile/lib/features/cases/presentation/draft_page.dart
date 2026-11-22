import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/rating_row.dart';
import '../domain/case_models.dart';
import 'case_actions.dart';
import 'cases_providers.dart';
import 'widgets/draft_paper.dart';

/// «مسودة الرد» (m10) + «تأكيد الإرسال» (m12). No «طلب تعديل» in Project 1.
class DraftPage extends ConsumerStatefulWidget {
  const DraftPage({super.key, required this.caseId});
  final String caseId;
  @override
  ConsumerState<DraftPage> createState() => _DraftPageState();
}

class _DraftPageState extends ConsumerState<DraftPage> {
  bool _submitting = false;

  Future<void> _submit() async {
    final confirmed = await showDialog<bool>(context: context, builder: (_) => const _ConfirmDialog());
    if (confirmed != true || !mounted) return;
    setState(() => _submitting = true);
    try {
      await ref.read(caseActionsProvider.notifier).submitForReview(widget.caseId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('أُرسلت المسودة للمراجعة القانونية. سنُشعرك فور اعتمادها.')));
      context.go('/cases/${widget.caseId}');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = ref.watch(caseDetailProvider(widget.caseId)).value?.status;
    final canSubmit = status == CaseStatus.aiReady;
    return Scaffold(
      appBar: AppBar(title: const Text('مسودة الرد')),
      body: AsyncView<CaseDraft>(
        value: ref.watch(caseDraftProvider(widget.caseId)),
        onRetry: () => ref.invalidate(caseDraftProvider(widget.caseId)),
        builder: (draft) => ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          children: [
            _Banner(status: status),
            const SizedBox(height: 10),
            const _Legend(),
            const SizedBox(height: 10),
            DraftPaper(draft: draft),
            const SizedBox(height: 8),
            Center(
              child: RatingRow(
                onRate: (useful, comment) =>
                    ref.read(caseActionsProvider.notifier).rate(RatingTarget.draft, '${widget.caseId}-v${draft.versionNo}', useful: useful, comment: comment),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: canSubmit
          ? SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: FilledButton.icon(
                  onPressed: _submitting ? null : _submit,
                  icon: const Icon(LucideIcons.send),
                  label: Text(_submitting ? 'جارٍ الإرسال…' : 'إرسال للمراجعة القانونية'),
                ),
              ),
            )
          : null,
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({required this.status});
  final CaseStatus? status;

  @override
  Widget build(BuildContext context) {
    final review = status == CaseStatus.lawyerReview;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: review ? AppColors.blue50 : AppColors.violet50, borderRadius: BorderRadius.circular(14)),
      child: Row(
        children: [
          Icon(review ? LucideIcons.hourglass : LucideIcons.sparkles, color: review ? AppColors.blue600 : AppColors.violet600, size: 18),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              review
                  ? 'المسودة لدى المحامي للمراجعة القانونية. سنُشعرك فور الاعتماد.'
                  : 'مسودة أنشأها الذكاء الاصطناعي — غير معتمدة. راجعها ثم أرسلها للمحامي. لا تُقدَّم للجهة قبل الاعتماد.',
              style: TextStyle(fontSize: 12.5, color: review ? AppColors.blue600 : AppColors.violet600),
            ),
          ),
        ],
      ),
    );
  }
}

class _Legend extends StatelessWidget {
  const _Legend();
  @override
  Widget build(BuildContext context) {
    Widget item(Color color, String label) => Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(3)),
        ),
        const SizedBox(width: 5),
        Text(label, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
      ],
    );
    return Wrap(
      spacing: 14,
      runSpacing: 6,
      children: [item(AppColors.teal100, 'مادة نظامية'), item(const Color(0xFFD6E2FB), 'مستند من القضية'), item(const Color(0xFFFFF6DC), 'فقرة غير مسندة')],
    );
  }
}

class _ConfirmDialog extends StatefulWidget {
  const _ConfirmDialog();
  @override
  State<_ConfirmDialog> createState() => _ConfirmDialogState();
}

class _ConfirmDialogState extends State<_ConfirmDialog> {
  bool _consent = false;

  @override
  Widget build(BuildContext context) => AlertDialog(
    title: const Text('إرسال للمراجعة القانونية'),
    content: Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('سيطّلع المحامي المراجِع على القضية ومستنداتها ومسودة الرد ليعدّلها ويعتمدها.'),
        const SizedBox(height: 8),
        CheckboxListTile(
          value: _consent,
          onChanged: (v) => setState(() => _consent = v!),
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          title: const Text('أوافق على اطلاع المحامي على القضية ومستنداتها.', style: TextStyle(fontSize: 13.5)),
        ),
      ],
    ),
    actions: [
      TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('إلغاء')),
      FilledButton(
        style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
        onPressed: _consent ? () => Navigator.pop(context, true) : null,
        child: const Text('إرسال'),
      ),
    ],
  );
}
