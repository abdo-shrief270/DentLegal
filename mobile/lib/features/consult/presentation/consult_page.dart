import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/citation_chip.dart';
import '../../../core/widgets/mock_badge.dart';
import '../../../core/widgets/rating_row.dart';
import '../../../core/widgets/tone_badge.dart';
import '../../cases/presentation/widgets/source_sheet.dart';
import '../domain/consult_models.dart';
import 'consult_controller.dart';

/// «المستشار القانوني الذكي» — prototype m15. Answers only from retrieved regulations; refuses otherwise.
class ConsultPage extends ConsumerStatefulWidget {
  const ConsultPage({super.key});

  @override
  ConsumerState<ConsultPage> createState() => _ConsultPageState();
}

class _ConsultPageState extends ConsumerState<ConsultPage> {
  final _input = TextEditingController();
  final _scroll = ScrollController();

  @override
  void dispose() {
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _ask(String text) async {
    _input.clear();
    FocusScope.of(context).unfocus();
    final future = ref.read(consultControllerProvider.notifier).ask(text);
    _scrollToEnd();
    await future;
    _scrollToEnd();
  }

  void _scrollToEnd() => WidgetsBinding.instance.addPostFrameCallback((_) {
    if (_scroll.hasClients) _scroll.animateTo(_scroll.position.maxScrollExtent, duration: const Duration(milliseconds: 250), curve: Curves.easeOut);
  });

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(consultControllerProvider);
    final suggestions = ref.watch(suggestedQuestionsProvider).value ?? const [];

    return Scaffold(
      appBar: AppBar(
        title: const Column(
          children: [
            Text('المستشار القانوني الذكي'),
            SizedBox(height: 2),
            ToneBadge('مدعوم بالمصادر النظامية', tone: Tone.teal, icon: LucideIcons.bookOpen),
          ],
        ),
        toolbarHeight: 68,
        actions: const [Padding(padding: EdgeInsetsDirectional.only(end: 12), child: MockBadge())],
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView(
              controller: _scroll,
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              children: [
                const _Bubble(
                  fromDentist: false,
                  child: Text('مرحبًا د. أحمد. اسألني عن أنظمة مزاولة المهن الصحية والمسؤولية الطبية وحقوق المرضى. أجيب من النصوص النظامية مع ذكر المصدر.'),
                ),
                if (state.messages.isEmpty && suggestions.isNotEmpty) ...[
                  const Padding(
                    padding: EdgeInsets.only(top: 12, bottom: 8),
                    child: Text(
                      'أسئلة مقترحة',
                      style: TextStyle(fontWeight: FontWeight.w600, color: AppColors.muted, fontSize: 13),
                    ),
                  ),
                  for (final q in suggestions)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: OutlinedButton.icon(
                        onPressed: () => _ask(q),
                        style: OutlinedButton.styleFrom(
                          alignment: AlignmentDirectional.centerStart,
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        ),
                        icon: const Icon(LucideIcons.messageCircleQuestion, size: 17),
                        label: Text(q, style: const TextStyle(fontWeight: FontWeight.w500)),
                      ),
                    ),
                ],
                for (final m in state.messages) _MessageView(m),
                if (state.waiting) const _Bubble(fromDentist: false, child: _Typing()),
                if (state.error != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(state.error!, style: const TextStyle(color: AppColors.red600, fontSize: 13)),
                  ),
              ],
            ),
          ),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              'الإجابات إرشادية ولا تُغني عن الاستشارة القانونية المعتمدة.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 11.5, color: AppColors.faint),
            ),
          ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 10),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _input,
                      minLines: 1,
                      maxLines: 4,
                      textInputAction: TextInputAction.send,
                      onSubmitted: _ask,
                      decoration: const InputDecoration(hintText: 'اكتب سؤالك النظامي…'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(tooltip: 'إرسال', onPressed: state.waiting ? null : () => _ask(_input.text), icon: const Icon(LucideIcons.sendHorizontal)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MessageView extends ConsumerWidget {
  const _MessageView(this.m);
  final ConsultMessage m;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (m.fromDentist) {
      return _Bubble(
        fromDentist: true,
        child: Text(m.text, style: const TextStyle(color: Colors.white)),
      );
    }
    return _Bubble(
      fromDentist: false,
      tint: m.refused ? AppColors.amber50 : null,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (m.refused)
            const Padding(
              padding: EdgeInsets.only(bottom: 6),
              child: ToneBadge('لا توجد مصادر كافية', tone: Tone.amber, icon: LucideIcons.circleAlert),
            ),
          Text(m.text, style: const TextStyle(height: 1.8)),
          if (m.citations.isNotEmpty) ...[
            const SizedBox(height: 8),
            Wrap(spacing: 6, runSpacing: 6, children: [for (final c in m.citations) CitationChip(c, onTap: () => showCitationSheet(context, c))]),
          ],
          if (m.refused) ...[
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: () => context.push('/cases/new'),
              icon: const Icon(LucideIcons.scale, size: 16),
              label: const Text('افتح قضية لمراجعة محامٍ'),
            ),
          ],
          const Divider(height: 18),
          RatingRow(
            initial: m.ratedUseful,
            dense: true,
            onRate: (useful, comment) => ref.read(consultControllerProvider.notifier).rate(m.id, useful: useful, comment: comment),
          ),
        ],
      ),
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble({required this.fromDentist, required this.child, this.tint});
  final bool fromDentist;
  final Widget child;
  final Color? tint;

  @override
  Widget build(BuildContext context) {
    const r = Radius.circular(18);
    return Align(
      // RTL: the dentist's own messages sit on the left (end), the assistant's on the right (start).
      alignment: fromDentist ? AlignmentDirectional.centerEnd : AlignmentDirectional.centerStart,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * .84),
        child: Container(
          margin: const EdgeInsets.only(top: 10),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            color: fromDentist ? AppColors.teal600 : (tint ?? AppColors.surface),
            border: fromDentist ? null : Border.all(color: AppColors.line),
            borderRadius: fromDentist
                ? const BorderRadiusDirectional.only(topStart: r, topEnd: r, bottomStart: r, bottomEnd: Radius.circular(6)).resolve(Directionality.of(context))
                : const BorderRadiusDirectional.only(topStart: r, topEnd: r, bottomEnd: r, bottomStart: Radius.circular(6)).resolve(Directionality.of(context)),
          ),
          child: DefaultTextStyle.merge(
            style: const TextStyle(fontSize: 14, color: AppColors.ink),
            child: child,
          ),
        ),
      ),
    );
  }
}

class _Typing extends StatelessWidget {
  const _Typing();

  @override
  Widget build(BuildContext context) => const Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2)),
      SizedBox(width: 10),
      Text('أبحث في المصادر النظامية…', style: TextStyle(color: AppColors.muted, fontSize: 13)),
    ],
  );
}
