import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/dates.dart';
import '../../../core/widgets/async_view.dart';
import '../domain/case_models.dart';
import 'cases_providers.dart';
import 'widgets/draft_paper.dart';

/// «الرد المعتمد» (m13/m14): lawyer name + approval date in both calendars. No verification code (Project 2).
class ApprovedPage extends ConsumerWidget {
  const ApprovedPage({super.key, required this.caseId});
  final String caseId;

  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
    appBar: AppBar(title: const Text('الرد المعتمد')),
    body: AsyncView<CaseDraft>(
      value: ref.watch(caseDraftProvider(caseId)),
      onRetry: () => ref.invalidate(caseDraftProvider(caseId)),
      builder: (draft) => ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.gold100,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.gold500, width: 1.5),
            ),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: AppColors.gold500, width: 2),
                  ),
                  child: const Icon(LucideIcons.stamp, color: AppColors.gold600),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'معتمد قانونيًا',
                        style: TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF5D4419)),
                      ),
                      Text(draft.approvedBy ?? '', style: const TextStyle(fontSize: 12.5, color: Color(0xFF5D4419))),
                      if (draft.approvedAt != null)
                        Text(
                          '${AppDates.both(draft.approvedAt!)} · ${AppDates.time(draft.approvedAt!)}',
                          style: const TextStyle(fontSize: 12, color: Color(0xFF7A6232)),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          DraftPaper(draft: draft),
        ],
      ),
    ),
    bottomNavigationBar: SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: OutlinedButton.icon(
          onPressed: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('مشاركة الرد (نص أو PDF) — تُفعّل مع ربط الخادم.'))),
          icon: const Icon(LucideIcons.share2),
          label: const Text('مشاركة الرد المعتمد'),
        ),
      ),
    ),
  );
}
