import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/dates.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/tone_badge.dart';
import '../../cases/domain/case_models.dart';
import '../../cases/presentation/case_labels.dart';
import '../domain/document_picker.dart';
import 'wizard_controller.dart';

/// «قضية جديدة» — the prototype's 4-step wizard (m06, m07) without OCR auto-filled fields or calendar.
class NewCasePage extends ConsumerWidget {
  const NewCasePage({super.key});

  static const _stepTitles = ['', 'نوع القضية والجهة', 'تفاصيل الواقعة', 'المستندات', 'المراجعة والإرسال'];

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = ref.watch(wizardControllerProvider);
    final c = ref.read(wizardControllerProvider.notifier);

    Future<void> submit() async {
      try {
        final id = await c.submit();
        if (context.mounted) context.pushReplacement('/cases/$id/processing');
      } on Object catch (e) {
        // ApiError, AuthException and DocumentTooLarge all carry a message that is safe to show.
        if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    }

    return PopScope(
      canPop: s.step == 1,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) c.back();
      },
      child: Scaffold(
        appBar: AppBar(
          title: Column(
            children: [
              const Text('قضية جديدة'),
              Text(
                'الخطوة ${s.step} من 4 · ${_stepTitles[s.step]}',
                style: const TextStyle(fontSize: 12, color: AppColors.muted, fontWeight: FontWeight.w500),
              ),
            ],
          ),
          leading: IconButton(icon: const Icon(LucideIcons.chevronRight), tooltip: 'رجوع', onPressed: () => s.step == 1 ? context.pop() : c.back()),
        ),
        body: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
              child: Row(
                children: [
                  for (var i = 1; i <= 4; i++)
                    Expanded(
                      child: Container(
                        height: 4,
                        margin: const EdgeInsets.symmetric(horizontal: 2),
                        decoration: BoxDecoration(color: i <= s.step ? AppColors.teal600 : AppColors.line2, borderRadius: BorderRadius.circular(4)),
                      ),
                    ),
                ],
              ),
            ),
            Expanded(
              child: switch (s.step) {
                1 => const _StepType(),
                2 => const _StepFacts(),
                3 => const _StepDocuments(),
                _ => const _StepReview(),
              },
            ),
          ],
        ),
        bottomNavigationBar: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                if (s.step > 1) ...[OutlinedButton(onPressed: c.back, child: const Text('السابق')), const SizedBox(width: 10)],
                Expanded(
                  child: s.step < 4
                      ? FilledButton(onPressed: s.stepValid ? c.next : null, child: const Text('التالي'))
                      : FilledButton.icon(
                          onPressed: s.stepValid && !s.submitting ? submit : null,
                          icon: const Icon(LucideIcons.sparkles),
                          label: Text(s.submitting ? 'جارٍ الإنشاء…' : 'إنشاء القضية وبدء التحليل'),
                        ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

Widget _label(String text) => Padding(
  padding: const EdgeInsets.only(top: 16, bottom: 6),
  child: Text(text, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
);

class _StepType extends ConsumerWidget {
  const _StepType();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = ref.watch(wizardControllerProvider);
    final c = ref.read(wizardControllerProvider.notifier);
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
      children: [
        Text('ما نوع القضية؟', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 8),
        for (final type in CaseType.values)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: AppCard(
              onTap: () => c.update((s) => s.copyWith(caseType: type)),
              borderColor: s.caseType == type ? AppColors.teal500 : null,
              color: s.caseType == type ? AppColors.teal50 : null,
              child: Row(
                children: [
                  Icon(type.icon, color: AppColors.teal700),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(type.label, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                        Text(type.hint, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                      ],
                    ),
                  ),
                  if (s.caseType == type) const Icon(LucideIcons.circleCheck, color: AppColors.teal600),
                ],
              ),
            ),
          ),
        _label('الجهة'),
        TextFormField(
          initialValue: s.authority,
          onChanged: (v) => c.update((s) => s.copyWith(authority: v)),
        ),
        _label('رقم الشكوى أو الخطاب'),
        TextFormField(
          initialValue: s.complaintNo,
          onChanged: (v) => c.update((s) => s.copyWith(complaintNo: v)),
          decoration: const InputDecoration(hintText: 'مثال: 02871/1448'),
        ),
        _label('تاريخ الاستلام'),
        _DateField(
          value: s.receivedOn,
          onPick: (d) => c.update((s) => s.copyWith(receivedOn: d)),
        ),
      ],
    );
  }
}

class _StepFacts extends ConsumerWidget {
  const _StepFacts();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = ref.watch(wizardControllerProvider);
    final c = ref.read(wizardControllerProvider.notifier);
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
      children: [
        _label('عنوان مختصر للقضية'),
        TextFormField(
          initialValue: s.title,
          onChanged: (v) => c.update((s) => s.copyWith(title: v)),
          decoration: const InputDecoration(hintText: 'مثال: تنميل بعد زراعة سن'),
        ),
        _label('الإجراء الطبي محل الشكوى'),
        TextFormField(
          initialValue: s.procedure,
          onChanged: (v) => c.update((s) => s.copyWith(procedure: v)),
        ),
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _label('تاريخ الإجراء'),
                  _DateField(
                    value: s.procedureDate,
                    onPick: (d) => c.update((s) => s.copyWith(procedureDate: d)),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _label('رمز المريض'),
                  Directionality(
                    textDirection: TextDirection.ltr,
                    child: TextFormField(
                      initialValue: s.patientCode,
                      onChanged: (v) => c.update((s) => s.copyWith(patientCode: v)),
                      decoration: const InputDecoration(hintText: 'P-0000'),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: AppColors.blue50, borderRadius: BorderRadius.circular(14)),
          child: const Row(
            children: [
              Icon(LucideIcons.lock, color: AppColors.blue600, size: 18),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  'لا تُدخل اسم المريض أو هويته — يكفي رمز داخلي من ملفك. نحمي الخصوصية من البداية.',
                  style: TextStyle(fontSize: 12.5, color: AppColors.blue600),
                ),
              ),
            ],
          ),
        ),
        _label('ملخص ما ورد في الشكوى'),
        TextFormField(
          initialValue: s.facts,
          minLines: 4,
          maxLines: 8,
          onChanged: (v) => c.update((s) => s.copyWith(facts: v)),
          decoration: InputDecoration(
            hintText: 'اكتب الوقائع كما وردت في الشكوى وما حدث فعلًا (20 حرفًا على الأقل)',
            errorText: s.factsContainIdentifier ? 'يبدو أنك أدخلت رقم هوية أو جوال. استخدم رمز المريض فقط.' : null,
          ),
        ),
        _label('نوع المطالبة'),
        Wrap(
          spacing: 8,
          children: [
            for (final claim in ClaimType.values)
              ChoiceChip(
                label: Text(claim.label),
                selected: s.claimType == claim,
                onSelected: (_) => c.update((s) => s.copyWith(claimType: claim)),
              ),
          ],
        ),
      ],
    );
  }
}

class _StepDocuments extends ConsumerWidget {
  const _StepDocuments();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = ref.watch(wizardControllerProvider);
    final c = ref.read(wizardControllerProvider.notifier);
    Widget source(PickSource src, IconData icon, String label) => Expanded(
      child: OutlinedButton.icon(
        onPressed: s.picking
            ? null
            : () => c.pick(src).catchError((Object e) {
                if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
              }),
        icon: Icon(icon, size: 18),
        label: Text(label, style: const TextStyle(fontSize: 13)),
      ),
    );
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
      children: [
        Row(
          children: [
            Expanded(child: Text('المستندات', style: Theme.of(context).textTheme.titleMedium)),
            ToneBadge('${s.documents.length} مرفوعة', tone: Tone.teal),
          ],
        ),
        const SizedBox(height: 4),
        const Text(
          'خطاب الشكوى، الملف الطبي، إقرار الموافقة، تقارير الأشعة… بالقدر اللازم للدفاع فقط.',
          style: TextStyle(fontSize: 12.5, color: AppColors.muted),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            source(PickSource.scanner, LucideIcons.scanText, 'مسح'),
            const SizedBox(width: 8),
            source(PickSource.pdf, LucideIcons.fileText, 'PDF'),
            const SizedBox(width: 8),
            source(PickSource.photo, LucideIcons.camera, 'صورة'),
          ],
        ),
        if (s.picking) const Padding(padding: EdgeInsets.only(top: 12), child: LinearProgressIndicator()),
        const SizedBox(height: 12),
        for (final d in s.documents)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: AppCard(
              color: AppColors.green50.withValues(alpha: .5),
              child: Row(
                children: [
                  Icon(d.isImage ? LucideIcons.image : LucideIcons.fileCheck, color: AppColors.green600),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(d.name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                        Text('${d.kind.label} · ${d.pages} ص · ${d.sizeLabel}', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                      ],
                    ),
                  ),
                  const ToneBadge('مشفّر', tone: Tone.green, icon: LucideIcons.lock),
                  IconButton(
                    tooltip: 'حذف',
                    onPressed: () => c.removeDocument(d.id),
                    icon: const Icon(LucideIcons.x, size: 18, color: AppColors.muted),
                  ),
                ],
              ),
            ),
          ),
        if (s.documents.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 24),
            child: Text(
              'أضف مستندًا واحدًا على الأقل للمتابعة.',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.muted),
            ),
          ),
      ],
    );
  }
}

class _StepReview extends ConsumerWidget {
  const _StepReview();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = ref.watch(wizardControllerProvider);
    final c = ref.read(wizardControllerProvider.notifier);
    final rows = <(String, String)>[
      ('النوع', '${s.caseType.label} — ${s.claimType.label}'),
      ('الجهة', s.authority),
      ('العنوان', s.title),
      ('الإجراء', s.procedure),
      ('رمز المريض', s.patientCode),
      ('المستندات', '${s.documents.length} مستندات مرفوعة ومشفّرة'),
    ];
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
      children: [
        AppCard(
          child: Column(
            children: [
              for (final (label, value) in rows)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SizedBox(
                        width: 92,
                        child: Text(label, style: const TextStyle(fontSize: 12.5, color: AppColors.muted)),
                      ),
                      Expanded(
                        child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                      ),
                    ],
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        CheckboxListTile(
          value: s.confirmAccuracy,
          onChanged: (v) => c.update((s) => s.copyWith(confirmAccuracy: v!)),
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          title: const Text('أقر بصحة المعلومات المدخلة وأن المستندات صادرة عن منشأتي.', style: TextStyle(fontSize: 13.5)),
        ),
        CheckboxListTile(
          value: s.confirmProcessing,
          onChanged: (v) => c.update((s) => s.copyWith(confirmProcessing: v!)),
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          title: const Text('أوافق على المعالجة الآلية للمستندات بعد إخفاء البيانات التعريفية للمريض.', style: TextStyle(fontSize: 13.5)),
        ),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: AppColors.violet50, borderRadius: BorderRadius.circular(14)),
          child: const Row(
            children: [
              Icon(LucideIcons.sparkles, color: AppColors.violet600, size: 18),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  'بعد الإنشاء يبدأ التحليل الذكي فورًا، وتصلك مسودة الرد مع المصادر خلال دقائق.',
                  style: TextStyle(fontSize: 12.5, color: AppColors.violet600),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _DateField extends StatelessWidget {
  const _DateField({required this.value, required this.onPick});
  final DateTime? value;
  final ValueChanged<DateTime> onPick;

  @override
  Widget build(BuildContext context) => InkWell(
    borderRadius: BorderRadius.circular(12),
    onTap: () async {
      final now = DateTime.now();
      final picked = await showDatePicker(context: context, initialDate: value ?? now, firstDate: DateTime(now.year - 5), lastDate: now);
      if (picked != null) onPick(picked);
    },
    child: InputDecorator(
      decoration: const InputDecoration(suffixIcon: Icon(LucideIcons.calendar, size: 18)),
      child: Text(value == null ? 'اختر التاريخ' : AppDates.gregorian(value!), style: TextStyle(color: value == null ? AppColors.faint : AppColors.ink)),
    ),
  );
}
