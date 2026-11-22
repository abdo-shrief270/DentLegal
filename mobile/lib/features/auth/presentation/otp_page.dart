import 'dart:async';

import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../domain/auth_repository.dart';
import 'auth_controller.dart';
import '../../../core/utils/bidi.dart';

/// «رمز التحقق»: a single 6-digit field (works with SMS autofill and RTL). The router moves on after success.
class OtpPage extends ConsumerStatefulWidget {
  const OtpPage({super.key, required this.phone});
  final String phone;

  @override
  ConsumerState<OtpPage> createState() => _OtpPageState();
}

class _OtpPageState extends ConsumerState<OtpPage> {
  final _code = TextEditingController();
  Timer? _timer;
  int _resendIn = 60;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _startTimer();
  }

  void _startTimer() {
    _timer?.cancel();
    _resendIn = 60;
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_resendIn <= 1) t.cancel();
      setState(() => _resendIn--);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _code.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await ref.read(authControllerProvider.notifier).verifyOtp(widget.phone, _code.text);
    } on AuthException catch (e) {
      _code.clear();
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _resend() async {
    await ref.read(authControllerProvider.notifier).requestOtp(widget.phone);
    _startTimer();
  }

  String get _masked => widget.phone.length == 9 ? '+966 ${widget.phone.substring(0, 2)} *** ${widget.phone.substring(5)}' : widget.phone;

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(),
    body: SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(22, 8, 22, 24),
        children: [
          Text('رمز التحقق', style: Theme.of(context).textTheme.headlineSmall),
          const SizedBox(height: 4),
          Text.rich(
            TextSpan(
              style: const TextStyle(color: AppColors.muted),
              children: [
                const TextSpan(text: 'أرسلنا رمزًا من 6 أرقام إلى '),
                TextSpan(
                  text: ltr(_masked),
                  style: const TextStyle(fontWeight: FontWeight.w600, color: AppColors.ink),
                ),
              ],
            ),
          ),
          const SizedBox(height: 28),
          Directionality(
            textDirection: TextDirection.ltr,
            child: TextField(
              controller: _code,
              autofocus: true,
              textAlign: TextAlign.center,
              keyboardType: TextInputType.number,
              autofillHints: const [AutofillHints.oneTimeCode],
              inputFormatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(6)],
              style: const TextStyle(fontSize: 26, letterSpacing: 14, fontWeight: FontWeight.w700),
              decoration: InputDecoration(hintText: '••••••', errorText: _error),
              onChanged: (value) {
                setState(() => _error = null);
                if (value.length == 6 && !_loading) _verify();
              },
            ),
          ),
          const SizedBox(height: 12),
          Center(
            child: _resendIn > 0
                ? Text('إعادة الإرسال خلال 0:${_resendIn.toString().padLeft(2, '0')}', style: const TextStyle(color: AppColors.muted, fontSize: 13))
                : TextButton(onPressed: _resend, child: const Text('إعادة إرسال الرمز')),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _code.text.length == 6 && !_loading ? _verify : null,
            child: _loading
                ? const SizedBox.square(dimension: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Text('تحقق ودخول'),
          ),
        ],
      ),
    ),
  );
}
