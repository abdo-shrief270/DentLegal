import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/dates.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/tone_badge.dart';
import '../domain/case_models.dart';
import 'case_labels.dart';
import 'cases_providers.dart';
import 'widgets/case_action_card.dart';
import '../../../core/utils/bidi.dart';

/// «تفاصيل القضية» (m05, simplified): no legal-stage stepper or timeline (Project 2).
class CaseDetailsPage extends ConsumerWidget {
  const CaseDetailsPage({super.key, required this.caseId});
  final String caseId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(caseDetailProvider(caseId));
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(title: Text(value.value == null ? '' : ltr(value.value!.code))),
        body: AsyncView<DentalCase>(
          value: value,
          onRetry: () => ref.invalidate(caseDetailProvider(caseId)),
          builder: (c) => NestedScrollView(
            headerSliverBuilder: (_, _) => [
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
                sliver: SliverList.list(
                  children: [
                    AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Wrap(
                            spacing: 6,
                            runSpacing: 6,
                            children: [
                              ToneBadge(c.caseType.label),
                              ToneBadge(c.status.label, tone: c.status.tone, icon: c.status.icon),
                              if (c.risk != null) ToneBadge(c.risk!.label, tone: c.risk!.tone),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(c.title, style: Theme.of(context).textTheme.titleLarge),
                          Text(c.authority, style: const TextStyle(fontSize: 12.5, color: AppColors.muted)),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    CaseActionCard(dentalCase: c),
                    const SizedBox(height: 8),
                    const TabBar(
                      tabs: [
                        Tab(text: 'نظرة عامة'),
                        Tab(text: 'المستندات'),
                      ],
                    ),
                  ],
                ),
              ),
            ],
            body: TabBarView(children: [_Overview(c), _Documents(c)]),
          ),
        ),
      ),
    );
  }
}

class _Overview extends StatelessWidget {
  const _Overview(this.c);
  final DentalCase c;

  @override
  Widget build(BuildContext context) {
    final rows = <(IconData, String, String)>[
      (LucideIcons.landmark, 'الجهة', c.authority),
      (LucideIcons.fileText, 'رقم الشكوى', c.complaintNo),
      (LucideIcons.stethoscope, 'الإجراء الطبي', c.procedure),
      (LucideIcons.calendar, 'تاريخ الإجراء', AppDates.both(c.procedureDate)),
      (LucideIcons.lock, 'رمز المريض', '${c.patientCode} (مرمّز لحماية الخصوصية)'),
      (LucideIcons.scale, 'المحامي المراجِع', c.lawyerName ?? 'يُحدد عند الإرسال للمراجعة'),
    ];
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
      children: [
        AppCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('ملخص الوقائع', style: TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 4),
              Text(c.facts, style: const TextStyle(fontSize: 13.5)),
            ],
          ),
        ),
        const SizedBox(height: 12),
        AppCard(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
          child: Column(
            children: [
              for (final (icon, label, value) in rows)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: Icon(icon, color: AppColors.muted, size: 20),
                  title: Text(label, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                  subtitle: Text(
                    value,
                    style: const TextStyle(fontSize: 14, color: AppColors.ink, fontWeight: FontWeight.w600),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }
}

class _Documents extends StatelessWidget {
  const _Documents(this.c);
  final DentalCase c;

  @override
  Widget build(BuildContext context) => ListView(
    padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
    children: [
      for (final d in c.documents)
        Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: AppCard(
            onTap: d.id.startsWith('local-') ? null : () => context.push('/cases/${c.id}/documents/${d.id}?name=${Uri.encodeQueryComponent(d.name)}'),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(color: d.isImage ? AppColors.blue50 : AppColors.red50, borderRadius: BorderRadius.circular(11)),
                  child: Icon(d.isImage ? LucideIcons.image : LucideIcons.fileText, color: d.isImage ? AppColors.blue600 : AppColors.red600, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(d.name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                      Text('${d.pages} ص · ${d.sizeLabel}', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                    ],
                  ),
                ),
                if (d.lowQuality) const ToneBadge('جودة منخفضة', tone: Tone.amber) else const ToneBadge('مشفّر', tone: Tone.green, icon: LucideIcons.lock),
              ],
            ),
          ),
        ),
      const Text('المستندات مشفّرة، ولا يطّلع عليها إلا أنت والمحامي المراجِع بعد إرسالها للمراجعة.', style: TextStyle(fontSize: 12, color: AppColors.muted)),
    ],
  );
}
