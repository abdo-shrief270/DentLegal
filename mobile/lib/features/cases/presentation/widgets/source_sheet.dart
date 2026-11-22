import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/async_view.dart';
import '../../../../core/widgets/tone_badge.dart';
import '../../domain/case_models.dart';
import '../cases_providers.dart';

/// «نص المادة» bottom sheet (m11) for a regulation citation; a short note for a case-document citation.
Future<void> showCitationSheet(BuildContext context, Citation citation) => showModalBottomSheet<void>(
  context: context,
  isScrollControlled: true,
  builder: (_) => citation.isDocument ? _DocumentNote(citation) : _SourceSheet(citation.ref),
);

class _SourceSheet extends ConsumerWidget {
  const _SourceSheet(this.sourceRef);
  final String sourceRef;

  @override
  Widget build(BuildContext context, WidgetRef ref) => Padding(
    padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
    child: AsyncView<RegulationSource>(
      value: ref.watch(regulationSourceProvider(sourceRef)),
      builder: (s) => Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              const Icon(LucideIcons.bookOpen, color: AppColors.teal700, size: 20),
              const SizedBox(width: 8),
              Expanded(child: Text('${s.label} — ${s.title}', style: Theme.of(context).textTheme.titleMedium)),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 6,
            children: [
              ToneBadge(s.law, tone: Tone.teal),
              ToneBadge('درجة الصلة ${(s.score * 100).round()}%'),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.teal50,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.teal100),
            ),
            child: Text(s.text, style: const TextStyle(fontSize: 14.5, height: 1.9)),
          ),
          const SizedBox(height: 10),
          const Text('النص من قاعدة المعرفة المفهرسة (الإصدار الساري).', style: TextStyle(fontSize: 12, color: AppColors.muted)),
        ],
      ),
    ),
  );
}

class _DocumentNote extends StatelessWidget {
  const _DocumentNote(this.citation);
  final Citation citation;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            const Icon(LucideIcons.paperclip, color: AppColors.blue600, size: 20),
            const SizedBox(width: 8),
            Text(citation.label, style: Theme.of(context).textTheme.titleMedium),
          ],
        ),
        const SizedBox(height: 10),
        const Text('هذه الفقرة مستندة إلى مستند رفعته في القضية. يمكنك مراجعته من تبويب «المستندات».', style: TextStyle(fontSize: 14)),
      ],
    ),
  );
}
