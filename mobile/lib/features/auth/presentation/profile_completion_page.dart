import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../domain/auth_models.dart';
import '../domain/auth_repository.dart';
import 'auth_controller.dart';

/// «إكمال الملف» (reduced m-register): profile + consents, on first login only.
/// The AI consent names third-party processing outside the Kingdom (Apple 5.1.2(i), plan §9.2).
class ProfileCompletionPage extends ConsumerStatefulWidget {
  const ProfileCompletionPage({super.key});
  @override
  ConsumerState<ProfileCompletionPage> createState() => _ProfileCompletionPageState();
}

class _ProfileCompletionPageState extends ConsumerState<ProfileCompletionPage> {
  static const specialties = [
    'طب أسنان عام',
    'جراحة وزراعة الأسنان',
    'جراحة الفم',
    'تقويم الأسنان',
    'علاج الجذور',
    'طب أسنان الأطفال',
    'اللثة',
    'طب أسنان تجميلي',
    'التركيبات',
  ];
  static const cities = ['الرياض', 'جدة', 'مكة المكرمة', 'المدينة المنورة', 'الدمام', 'الخبر', 'الطائف', 'أبها', 'تبوك', 'أخرى'];

  final _name = TextEditingController(text: 'د. ');
  final _facility = TextEditingController();
  final _license = TextEditingController();
  String _specialty = specialties.first;
  String _city = cities.first;
  bool _terms = false;
  bool _ai = false;
  bool _saving = false;

  bool get _valid => _name.text.trim().length > 5 && _facility.text.trim().isNotEmpty && _terms && _ai;

  @override
  void dispose() {
    for (final c in [_name, _facility, _license]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      await ref
          .read(authControllerProvider.notifier)
          .completeProfile(
            DentistProfile(
              name: _name.text.trim(),
              specialty: _specialty,
              city: _city,
              facility: _facility.text.trim(),
              licenseNo: _license.text.trim().isEmpty ? null : _license.text.trim(),
            ),
            acceptedTerms: _terms,
            acceptedAiProcessing: _ai,
          );
    } on AuthException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Widget _label(String text) => Padding(
    padding: const EdgeInsets.only(top: 14, bottom: 6),
    child: Text(text, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
  );

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('إكمال الملف')),
    body: SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
        children: [
          const Text('مرحبًا بك في تجربة DentLegal. أكمل بياناتك المهنية مرة واحدة.', style: TextStyle(color: AppColors.muted)),
          _label('الاسم الكامل'),
          TextField(controller: _name, onChanged: (_) => setState(() {})),
          _label('التخصص'),
          DropdownButtonFormField<String>(
            initialValue: _specialty,
            items: [for (final s in specialties) DropdownMenuItem(value: s, child: Text(s))],
            onChanged: (v) => setState(() => _specialty = v!),
          ),
          _label('المدينة'),
          DropdownButtonFormField<String>(
            initialValue: _city,
            items: [for (final c in cities) DropdownMenuItem(value: c, child: Text(c))],
            onChanged: (v) => setState(() => _city = v!),
          ),
          _label('المنشأة الصحية'),
          TextField(controller: _facility, onChanged: (_) => setState(() {})),
          _label('رقم التسجيل المهني (اختياري)'),
          Directionality(
            textDirection: TextDirection.ltr,
            child: TextField(
              controller: _license,
              decoration: const InputDecoration(hintText: '21-RD-0000000'),
            ),
          ),
          const SizedBox(height: 18),
          CheckboxListTile(
            value: _terms,
            onChanged: (v) => setState(() => _terms = v!),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            title: const Text('أوافق على الشروط والأحكام وسياسة الخصوصية.', style: TextStyle(fontSize: 13.5)),
          ),
          CheckboxListTile(
            value: _ai,
            onChanged: (v) => setState(() => _ai = v!),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            title: const Text(
              'أوافق على المعالجة الآلية لمستنداتي بعد إخفاء بيانات المريض التعريفية، بما في ذلك معالجتها لدى مزوّد ذكاء اصطناعي خارج المملكة.',
              style: TextStyle(fontSize: 13.5),
            ),
          ),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _valid && !_saving ? _save : null,
            child: _saving
                ? const SizedBox.square(dimension: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Text('حفظ والمتابعة'),
          ),
        ],
      ),
    ),
  );
}
