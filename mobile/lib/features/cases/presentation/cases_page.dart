import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/async_view.dart';
import '../domain/case_models.dart';
import 'case_labels.dart';
import 'cases_providers.dart';
import 'widgets/case_card.dart';

/// «قضاياي» (m04): search + «الكل» / «تحتاج إجراء».
class CasesPage extends ConsumerStatefulWidget {
  const CasesPage({super.key});
  @override
  ConsumerState<CasesPage> createState() => _CasesPageState();
}

class _CasesPageState extends ConsumerState<CasesPage> {
  String _query = '';
  bool _needsAction = false;

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Text('قضاياي'),
      centerTitle: false,
      actions: [IconButton(tooltip: 'قضية جديدة', onPressed: () => context.push('/cases/new'), icon: const Icon(LucideIcons.plus))],
    ),
    body: AsyncView<List<DentalCase>>(
      value: ref.watch(casesListProvider),
      onRetry: () => ref.invalidate(casesListProvider),
      builder: (all) {
        final actionable = all.where((c) => c.status.needsAction).toList();
        final shown = (_needsAction ? actionable : all).where((c) => _query.isEmpty || c.title.contains(_query) || c.code.contains(_query)).toList();
        return ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 100),
          children: [
            TextField(
              onChanged: (v) => setState(() => _query = v.trim()),
              decoration: const InputDecoration(hintText: 'ابحث برقم القضية أو العنوان', prefixIcon: Icon(LucideIcons.search, size: 18)),
            ),
            const SizedBox(height: 10),
            SegmentedButton<bool>(
              segments: [
                ButtonSegment(value: false, label: Text('الكل (${all.length})')),
                ButtonSegment(value: true, label: Text('تحتاج إجراء (${actionable.length})')),
              ],
              selected: {_needsAction},
              showSelectedIcon: false,
              onSelectionChanged: (s) => setState(() => _needsAction = s.first),
            ),
            const SizedBox(height: 12),
            if (shown.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 40),
                child: Column(
                  children: [
                    Icon(LucideIcons.folderOpen, size: 40, color: AppColors.faint),
                    SizedBox(height: 8),
                    Text('لا توجد قضايا في هذا التصنيف', style: TextStyle(color: AppColors.muted)),
                  ],
                ),
              ),
            for (final c in shown)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: CaseCard(dentalCase: c),
              ),
          ],
        );
      },
    ),
  );
}
