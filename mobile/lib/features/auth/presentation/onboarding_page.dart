import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/brand_mark.dart';

/// «الترحيب» (prototype m01). Invite-only: no «إنشاء حساب» button in Project 1.
class OnboardingPage extends StatelessWidget {
  const OnboardingPage({super.key});

  static const _points = [
    (LucideIcons.sparkles, 'تحليل ذكي لقضيتك', 'مبني على الأنظمة واللوائح الصحية السعودية مع ذكر المصدر'),
    (LucideIcons.scale, 'اعتماد من محامٍ مرخّص', 'لا يُعتمد أي رد قبل مراجعته قانونيًا'),
    (LucideIcons.lock, 'خصوصية وتشفير كامل', 'بيانات المرضى مرمّزة ومحمية وفق نظام حماية البيانات'),
  ];

  @override
  Widget build(BuildContext context) => Scaffold(
    body: DecoratedBox(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [AppColors.navy900, AppColors.navy800, AppColors.teal700],
          stops: [0, .7, 1],
        ),
      ),
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
          child: Column(
            children: [
              const Spacer(),
              const BrandMark(size: 72),
              const SizedBox(height: 16),
              const Text(
                'DentLegal',
                style: TextStyle(fontSize: 32, fontWeight: FontWeight.w700, color: Colors.white),
              ),
              const Text('الحماية القانونية لأطباء الأسنان', style: TextStyle(color: Color(0xFFB7CFD5))),
              const SizedBox(height: 32),
              for (final (icon, title, subtitle) in _points)
                Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(color: Colors.white.withValues(alpha: .1), borderRadius: BorderRadius.circular(12)),
                        child: Icon(icon, color: AppColors.teal300, size: 20),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              title,
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                            ),
                            Text(subtitle, style: const TextStyle(color: Color(0xFFA9C3CA), fontSize: 12.5)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              const Spacer(),
              FilledButton(onPressed: () => context.go('/login'), child: const Text('تسجيل الدخول')),
              const SizedBox(height: 12),
              const Text('التطبيق متاح حاليًا للأطباء المدعوين فقط.', style: TextStyle(color: Color(0xFFA9C3CA), fontSize: 12.5)),
            ],
          ),
        ),
      ),
    ),
  );
}
