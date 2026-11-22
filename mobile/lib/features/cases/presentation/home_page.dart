import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/mock_badge.dart';
import '../../../core/widgets/section_title.dart';
import '../../auth/presentation/auth_controller.dart';
import '../domain/case_models.dart';
import 'cases_providers.dart';
import 'widgets/case_action_card.dart';
import 'widgets/case_card.dart';

/// «الرئيسية» (m03, simplified): greeting, approved-response banner, active case, quick actions, recent cases.
class HomePage extends ConsumerWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profile = ref.watch(authControllerProvider)?.profile;
    final cases = ref.watch(casesListProvider);

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () => ref.refresh(casesListProvider.future),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: AppColors.teal600,
                    child: Text(
                      profile?.initials ?? '',
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('مرحبًا بك،', style: TextStyle(fontSize: 12.5, color: AppColors.muted)),
                        Text(profile?.shortName ?? '', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                        Text(profile?.specialty ?? '', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                      ],
                    ),
                  ),
                  const MockBadge(),
                ],
              ),
              AsyncView<List<DentalCase>>(
                value: cases,
                onRetry: () => ref.invalidate(casesListProvider),
                builder: (list) {
                  final approved = list.where((c) => c.status == CaseStatus.approved).toList();
                  final active = list.where((c) => c.status != CaseStatus.approved).firstOrNull;
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      if (approved.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        AppCard(
                          color: AppColors.navy900,
                          borderColor: AppColors.navy900,
                          onTap: () => context.push('/cases/${approved.first.id}/approved'),
                          child: Row(
                            children: [
                              const Icon(LucideIcons.stamp, color: AppColors.gold500, size: 26),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text(
                                      'رد معتمد جاهز',
                                      style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                                    ),
                                    Text(
                                      approved.first.title,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(color: Color(0xFFA9C3CA), fontSize: 12.5),
                                    ),
                                  ],
                                ),
                              ),
                              const Icon(LucideIcons.chevronLeft, color: Colors.white, size: 18),
                            ],
                          ),
                        ),
                      ],
                      if (active != null) ...[
                        SectionTitle(
                          'قضيتي النشطة',
                          trailing: TextButton(onPressed: () => context.go('/cases'), child: const Text('عرض الكل')),
                        ),
                        CaseCard(dentalCase: active),
                        const SizedBox(height: 10),
                        CaseActionCard(dentalCase: active),
                      ],
                    ],
                  );
                },
              ),
              const SectionTitle('الخدمات السريعة'),
              Row(
                children: [
                  Expanded(
                    child: _Service(icon: LucideIcons.plus, label: 'قضية جديدة', onTap: () => context.push('/cases/new')),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _Service(icon: LucideIcons.messageCircle, label: 'استشارة قانونية', onTap: () => context.go('/consult')),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _Service(icon: LucideIcons.briefcase, label: 'متابعة القضايا', onTap: () => context.go('/cases')),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(color: AppColors.teal50, borderRadius: BorderRadius.circular(14)),
                child: const Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(LucideIcons.info, color: AppColors.teal700, size: 20),
                    SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'هل تعلم؟ يجوز لك الإفصاح عن معلومات المريض بالقدر اللازم للدفاع عن نفسك في شكوى مقدّمة ضدك (المادة 21 من نظام مزاولة المهن الصحية).',
                        style: TextStyle(color: AppColors.teal700, fontSize: 13),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Service extends StatelessWidget {
  const _Service({required this.icon, required this.label, required this.onTap});
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => AppCard(
    onTap: onTap,
    padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 8),
    child: Column(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(color: AppColors.teal50, borderRadius: BorderRadius.circular(13)),
          child: Icon(icon, color: AppColors.teal700),
        ),
        const SizedBox(height: 8),
        Text(
          label,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
        ),
      ],
    ),
  );
}
