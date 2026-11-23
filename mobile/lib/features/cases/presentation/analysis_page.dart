import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/citation_chip.dart';
import '../../../core/widgets/rating_row.dart';
import '../../../core/widgets/section_title.dart';
import '../../../core/widgets/strength_gauge.dart';
import '../../../core/widgets/tone_badge.dart';
import '../domain/case_models.dart';
import 'case_actions.dart';
import 'case_labels.dart';
import 'cases_providers.dart';
import 'widgets/source_sheet.dart';

/// «نتيجة التحليل» (m09).
class AnalysisPage extends ConsumerWidget {
  const AnalysisPage({super.key, required this.caseId});
  final String caseId;

  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
    appBar: AppBar(title: const Text('التحليل الذكي')),
    body: AsyncView<CaseAnalysis>(
      value: ref.watch(caseAnalysisProvider(caseId)),
      onRetry: () => ref.invalidate(caseAnalysisProvider(caseId)),
      builder: (a) => ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
        children: [
          AppCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    StrengthGauge(value: a.positionStrength),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          ToneBadge(a.risk.label, tone: a.risk.tone),
                          const SizedBox(height: 6),
                          Text(
                            'استنادًا إلى مستندات القضية و${a.sources.length} مواد نظامية مسترجعة',
                            style: const TextStyle(fontSize: 12.5, color: AppColors.muted),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(a.summary, style: const TextStyle(fontSize: 13.5)),
              ],
            ),
          ),
          const SizedBox(height: 12),
          const _Notice(),
          SectionTitle(
            'نقاط القوة',
            icon: LucideIcons.circleCheck,
            trailing: ToneBadge('${a.strengths.length}', tone: Tone.green),
          ),
          _Points(points: a.strengths, icon: LucideIcons.check, color: AppColors.green600),
          SectionTitle(
            'المخاطر ونقاط الضعف',
            icon: LucideIcons.triangleAlert,
            trailing: ToneBadge('${a.weaknesses.length}', tone: Tone.amber),
          ),
          _Points(points: a.weaknesses, icon: LucideIcons.triangleAlert, color: AppColors.amber600),
          const SectionTitle('الإجراءات الموصى بها', icon: LucideIcons.listChecks),
          _Points(points: a.actions, icon: LucideIcons.arrowLeft, color: AppColors.blue600),
          const SectionTitle('المصادر النظامية المسترجعة', icon: LucideIcons.bookOpen),
          AppCard(
            child: Column(
              children: [
                for (final s in a.sources)
                  InkWell(
                    onTap: () => showCitationSheet(context, Citation(ref: s.ref, label: s.label)),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Row(
                        children: [
                          SizedBox(
                            width: 92,
                            child: Text(
                              s.label,
                              style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.teal700),
                            ),
                          ),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(s.title, style: const TextStyle(fontSize: 12.5)),
                                const SizedBox(height: 3),
                                LinearProgressIndicator(value: s.score, minHeight: 5, borderRadius: BorderRadius.circular(9), backgroundColor: AppColors.bg2),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text('${(s.score * 100).round()}%', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Center(
            child: RatingRow(
              onRate: (useful, comment) => ref.read(caseActionsProvider.notifier).rate(RatingTarget.analysis, a.id, useful: useful, comment: comment),
            ),
          ),
        ],
      ),
    ),
    bottomNavigationBar: SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: FilledButton.icon(
          onPressed: () => context.push('/cases/$caseId/draft'),
          icon: const Icon(LucideIcons.fileText),
          label: const Text('عرض مسودة الرد'),
        ),
      ),
    ),
  );
}

class _Notice extends StatelessWidget {
  const _Notice();
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(12),
    decoration: BoxDecoration(color: AppColors.violet50, borderRadius: BorderRadius.circular(14)),
    child: const Row(
      children: [
        Icon(LucideIcons.sparkles, color: AppColors.violet600, size: 18),
        SizedBox(width: 8),
        Expanded(
          child: Text('تحليل آلي إرشادي. لا يُستخدم أي رد قبل مراجعته واعتماده من محامٍ مرخّص.', style: TextStyle(fontSize: 12.5, color: AppColors.violet600)),
        ),
      ],
    ),
  );
}

class _Points extends StatelessWidget {
  const _Points({required this.points, required this.icon, required this.color});
  final List<AnalysisPoint> points;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) => AppCard(
    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
    child: Column(
      children: [
        for (final (i, p) in points.indexed) ...[
          if (i > 0) const Divider(),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(icon, color: color, size: 18),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(p.text, style: const TextStyle(fontSize: 13.5)),
                      if (p.citations.isNotEmpty || p.severity != null || p.urgency != null) ...[
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 6,
                          runSpacing: 6,
                          children: [
                            for (final c in p.citations) CitationChip(c, onTap: () => showCitationSheet(context, c)),
                            if (p.severity != null) ToneBadge(p.severity!.label, tone: p.severity!.tone),
                            if (p.urgency != null)
                              ToneBadge(p.urgency!.label, tone: p.urgency == Urgency.urgent ? Tone.red : Tone.gray, icon: LucideIcons.clock),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    ),
  );
}
