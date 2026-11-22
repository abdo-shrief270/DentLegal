import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/tone_badge.dart';
import '../domain/case_models.dart';
import 'case_actions.dart';
import 'case_labels.dart';
import 'cases_providers.dart';

/// «التحليل الذكي» in progress (m08): real pipeline steps, not a simulation of the UI.
class ProcessingPage extends ConsumerWidget {
  const ProcessingPage({super.key, required this.caseId});
  final String caseId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    ref.listen(casePipelineProvider(caseId), (prev, next) {
      if (next.value?.finished == true && prev?.value?.finished != true) {
        ref.read(caseActionsProvider.notifier).analysisFinished(caseId);
      }
    });
    final progress = ref.watch(casePipelineProvider(caseId));
    final done = progress.value?.finished ?? false;

    return Scaffold(
      appBar: AppBar(title: const Text('التحليل الذكي')),
      body: AsyncView<PipelineProgress>(
        value: progress,
        builder: (p) => ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          children: [
            AppCard(
              color: AppColors.navy900,
              borderColor: AppColors.navy900,
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Icon(done ? LucideIcons.circleCheck : LucideIcons.refreshCw, color: AppColors.teal300, size: 40),
                  const SizedBox(height: 10),
                  Text(
                    done ? 'اكتمل التحليل' : 'نحلّل قضيتك الآن',
                    style: const TextStyle(color: Colors.white, fontSize: 19, fontWeight: FontWeight.w700),
                  ),
                  Text(
                    done ? 'مسودة الرد والتحليل جاهزان للمراجعة' : 'يستغرق عادة بضع دقائق. يمكنك مغادرة الشاشة والعودة لاحقًا.',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Color(0xFFA9C3CA), fontSize: 13),
                  ),
                  const SizedBox(height: 14),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(99),
                    child: LinearProgressIndicator(
                      value: p.fraction,
                      minHeight: 8,
                      backgroundColor: Colors.white.withValues(alpha: .12),
                      color: AppColors.teal500,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            for (final step in PipelineStep.values) _StepTile(step: step, state: p.steps[step] ?? PipelineStepState.pending),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: FilledButton.icon(
            onPressed: done ? () => context.pushReplacement('/cases/$caseId/analysis') : null,
            icon: const Icon(LucideIcons.sparkles),
            label: const Text('عرض نتيجة التحليل'),
          ),
        ),
      ),
    );
  }
}

class _StepTile extends StatelessWidget {
  const _StepTile({required this.step, required this.state});
  final PipelineStep step;
  final PipelineStepState state;

  @override
  Widget build(BuildContext context) {
    final (title, detail, icon) = step.info;
    final running = state == PipelineStepState.running;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        borderColor: running ? AppColors.teal500 : null,
        child: Opacity(
          opacity: state == PipelineStepState.pending ? .5 : 1,
          child: Row(
            children: [
              SizedBox.square(
                dimension: 36,
                child: switch (state) {
                  PipelineStepState.done => const CircleAvatar(
                    backgroundColor: AppColors.green50,
                    child: Icon(LucideIcons.check, color: AppColors.green600, size: 18),
                  ),
                  PipelineStepState.running => const Padding(padding: EdgeInsets.all(8), child: CircularProgressIndicator(strokeWidth: 2.5)),
                  PipelineStepState.pending => CircleAvatar(
                    backgroundColor: AppColors.bg2,
                    child: Icon(icon, color: AppColors.muted, size: 18),
                  ),
                },
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                    Text(detail, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                  ],
                ),
              ),
              if (state == PipelineStepState.done) const ToneBadge('تم', tone: Tone.green),
            ],
          ),
        ),
      ),
    );
  }
}
