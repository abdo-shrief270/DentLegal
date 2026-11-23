import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/mock_badge.dart';
import '../../../core/widgets/section_title.dart';
import '../../../core/widgets/tone_badge.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../../core/security/app_lock.dart';
import '../../../core/utils/bidi.dart';

/// «حسابي»: profile, security, feedback, legal pages, logout and account deletion (store requirement).
class AccountPage extends ConsumerStatefulWidget {
  const AccountPage({super.key});

  @override
  ConsumerState<AccountPage> createState() => _AccountPageState();
}

class _AccountPageState extends ConsumerState<AccountPage> {
  bool _notifications = true;

  Future<void> _confirmDelete() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('حذف الحساب'),
        content: const Text(
          'سيُرسل طلب حذف حسابك وبياناتك. تُحذف القضايا والمستندات خلال 30 يومًا، ما لم يلزم الاحتفاظ بها نظامًا. لا يمكن التراجع عن هذا الإجراء.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('إلغاء')),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.red600),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('حذف الحساب'),
          ),
        ],
      ),
    );
    if (ok == true) await ref.read(authControllerProvider.notifier).deleteAccount();
  }

  void _soon(String what) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$what — تُضاف الصفحة مع النصوص المعتمدة.')));

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(authControllerProvider)?.profile;
    final phone = ref.watch(authControllerProvider)?.phone ?? '';

    return Scaffold(
      appBar: AppBar(
        title: const Text('حسابي'),
        actions: const [Padding(padding: EdgeInsetsDirectional.only(end: 12), child: MockBadge())],
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
        children: [
          if (profile != null)
            AppCard(
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: AppColors.teal100,
                    child: Text(
                      profile.initials,
                      style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.teal700, fontSize: 18),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(profile.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                        Text('${profile.specialty} · ${profile.city}', style: const TextStyle(color: AppColors.muted, fontSize: 13)),
                        Text(profile.facility, style: const TextStyle(color: AppColors.muted, fontSize: 13)),
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 6,
                          runSpacing: 4,
                          children: [
                            if (profile.licenseNo != null) ToneBadge('ترخيص ${ltr(profile.licenseNo!)}', tone: Tone.teal, icon: LucideIcons.badgeCheck),
                            ToneBadge(ltr('+966 $phone'), icon: LucideIcons.phone),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          const SectionTitle('الأمان والإشعارات', icon: LucideIcons.shieldCheck),
          AppCard(
            padding: EdgeInsets.zero,
            child: Column(
              children: [
                Builder(
                  builder: (context) {
                    final lock = ref.watch(appLockProvider);
                    return SwitchListTile(
                      value: lock.available && lock.enabled,
                      onChanged: lock.available ? (v) => ref.read(appLockProvider.notifier).setEnabled(v) : null,
                      secondary: const Icon(LucideIcons.fingerprint),
                      title: const Text('الدخول بالبصمة أو الوجه'),
                      subtitle: Text(lock.available ? 'يُطلب عند فتح التطبيق وبعد 5 دقائق في الخلفية' : 'غير متاح على هذا الجهاز'),
                    );
                  },
                ),
                const Divider(height: 1),
                SwitchListTile(
                  value: _notifications,
                  onChanged: (v) => setState(() => _notifications = v),
                  secondary: const Icon(LucideIcons.bell),
                  title: const Text('الإشعارات'),
                  subtitle: const Text('جاهزية التحليل والرد المعتمد'),
                ),
              ],
            ),
          ),
          const SectionTitle('الدعم والمعلومات', icon: LucideIcons.info),
          AppCard(
            padding: EdgeInsets.zero,
            child: Column(
              children: [
                _tile(LucideIcons.messageSquareText, 'ملاحظاتك', 'أبلغ عن مشكلة أو اقترح تحسينًا', () => context.push('/account/feedback')),
                const Divider(height: 1),
                _tile(LucideIcons.lock, 'سياسة الخصوصية', null, () => _soon('سياسة الخصوصية')),
                const Divider(height: 1),
                _tile(LucideIcons.fileText, 'الشروط والأحكام', null, () => _soon('الشروط والأحكام')),
              ],
            ),
          ),
          const SizedBox(height: 20),
          OutlinedButton.icon(
            onPressed: () => ref.read(authControllerProvider.notifier).logout(),
            icon: const Icon(LucideIcons.logOut),
            label: const Text('تسجيل الخروج'),
          ),
          const SizedBox(height: 8),
          TextButton.icon(
            onPressed: _confirmDelete,
            style: TextButton.styleFrom(foregroundColor: AppColors.red600),
            icon: const Icon(LucideIcons.trash2),
            label: const Text('حذف الحساب'),
          ),
          const SizedBox(height: 12),
          const Text(
            'DentLegal · الإصدار 0.1.0',
            textAlign: TextAlign.center,
            style: TextStyle(color: AppColors.faint, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _tile(IconData icon, String title, String? subtitle, VoidCallback onTap) => ListTile(
    leading: Icon(icon),
    title: Text(title),
    subtitle: subtitle == null ? null : Text(subtitle),
    trailing: const Icon(LucideIcons.chevronLeft, size: 18, color: AppColors.faint),
    onTap: onTap,
  );
}
