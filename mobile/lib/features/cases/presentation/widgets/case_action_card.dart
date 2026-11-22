import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/dates.dart';
import '../../../../core/widgets/app_card.dart';
import '../../domain/case_models.dart';

/// The "what to do now" card that changes with the case status (prototype caseActionCard).
class CaseActionCard extends StatelessWidget {
  const CaseActionCard({super.key, required this.dentalCase});
  final DentalCase dentalCase;

  @override
  Widget build(BuildContext context) {
    final c = dentalCase;
    final base = '/cases/${c.id}';
    return switch (c.status) {
      CaseStatus.draft || CaseStatus.analyzing => AppCard(
        color: AppColors.teal50,
        borderColor: AppColors.teal100,
        onTap: () => context.push('$base/processing'),
        child: const _Row(icon: LucideIcons.refreshCw, color: AppColors.teal600, title: 'التحليل الذكي جارٍ', subtitle: 'اضغط لمتابعة خطوات المعالجة'),
      ),
      CaseStatus.aiReady => AppCard(
        color: AppColors.violet50,
        borderColor: const Color(0xFFD9CDF5),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const _Row(
              icon: LucideIcons.sparkles,
              color: AppColors.violet600,
              title: 'مسودة الرد جاهزة',
              subtitle: 'راجع التحليل والمسودة ثم أرسلها للمراجعة القانونية',
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(onPressed: () => context.push('$base/analysis'), child: Text('التحليل (${c.positionStrength ?? 0}%)')),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: FilledButton(onPressed: () => context.push('$base/draft'), child: const Text('مسودة الرد')),
                ),
              ],
            ),
          ],
        ),
      ),
      CaseStatus.lawyerReview => AppCard(
        color: AppColors.blue50,
        borderColor: const Color(0xFFD6E2FB),
        onTap: () => context.push('$base/draft'),
        child: _Row(
          icon: LucideIcons.scale,
          color: AppColors.blue600,
          title: 'لدى المحامي للمراجعة',
          subtitle: 'أُرسلت ${c.submittedAt == null ? '' : AppDates.relative(c.submittedAt!)} · سنُشعرك فور الاعتماد',
        ),
      ),
      CaseStatus.approved => AppCard(
        color: AppColors.gold100,
        borderColor: AppColors.gold500,
        onTap: () => context.push('$base/approved'),
        child: _Row(
          icon: LucideIcons.stamp,
          color: AppColors.gold600,
          title: 'تم اعتماد الرد قانونيًا',
          subtitle: 'اعتمده ${c.lawyerName ?? 'المحامي'} — اضغط لعرض الرد المعتمد',
        ),
      ),
      CaseStatus.failed => AppCard(
        color: AppColors.red50,
        borderColor: const Color(0xFFF5C9C9),
        onTap: () => context.push('$base/processing'),
        child: const _Row(icon: LucideIcons.triangleAlert, color: AppColors.red600, title: 'تعذّر إنشاء التحليل', subtitle: 'اضغط لإعادة المحاولة'),
      ),
    };
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.icon, required this.color, required this.title, required this.subtitle});
  final IconData icon;
  final Color color;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Icon(icon, color: color, size: 24),
      const SizedBox(width: 12),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
            Text(subtitle, style: const TextStyle(fontSize: 12.5, color: AppColors.muted)),
          ],
        ),
      ),
      const Icon(LucideIcons.chevronLeft, size: 18, color: AppColors.muted),
    ],
  );
}
