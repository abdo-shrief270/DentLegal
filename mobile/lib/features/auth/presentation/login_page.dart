import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/mock_badge.dart';
import '../data/fake_auth_repository.dart';
import '../domain/auth_repository.dart';
import 'auth_controller.dart';

/// «تسجيل الدخول» (m02): mobile number → OTP. No Nafath in Project 1.
class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});
  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _phone = TextEditingController();
  bool _loading = false;
  String? _error;

  bool get _valid => RegExp(r'^5\d{8}$').hasMatch(_phone.text.replaceAll(' ', ''));

  @override
  void dispose() {
    _phone.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final phone = _phone.text.replaceAll(' ', '');
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await ref.read(authControllerProvider.notifier).requestOtp(phone);
      if (mounted) context.push('/otp?phone=$phone');
    } on AuthException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      actions: const [Padding(padding: EdgeInsetsDirectional.only(end: 16), child: MockBadge())],
    ),
    body: SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(22, 8, 22, 24),
        children: [
          Text('أهلًا بعودتك', style: Theme.of(context).textTheme.headlineSmall),
          const SizedBox(height: 4),
          const Text('سجّل الدخول برقم جوالك المسجّل في الدعوة.', style: TextStyle(color: AppColors.muted)),
          const SizedBox(height: 24),
          const Text('رقم الجوال', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
          const SizedBox(height: 6),
          Directionality(
            textDirection: TextDirection.ltr,
            child: TextField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              autofillHints: const [AutofillHints.telephoneNumberNational],
              inputFormatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(9)],
              onChanged: (_) => setState(() => _error = null),
              decoration: InputDecoration(
                hintText: '5X XXX XXXX',
                prefixIcon: const Padding(
                  padding: EdgeInsets.all(14),
                  child: Text('+966', style: TextStyle(color: AppColors.muted)),
                ),
                errorText: _error,
              ),
            ),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _valid && !_loading ? _submit : null,
            child: _loading
                ? const SizedBox.square(dimension: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Text('إرسال رمز التحقق'),
          ),
          const SizedBox(height: 24),
          const Text(
            'بتسجيل الدخول فإنك توافق على الشروط والأحكام وسياسة الخصوصية.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: AppColors.muted),
          ),
          if (MockBadge.enabled) ...[
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: AppColors.amber50, borderRadius: BorderRadius.circular(14)),
              child: Text(
                'للتجربة: الرقم ${FakeAuthRepository.demoPhone} لحساب مكتمل، أو أي رقم آخر يبدأ بـ 5 لحساب جديد. رمز التحقق ${FakeAuthRepository.demoCode}.',
                style: const TextStyle(fontSize: 12.5, color: Color(0xFF7A4A00)),
              ),
            ),
          ],
        ],
      ),
    ),
  );
}
