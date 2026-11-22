import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/dates.dart';
import '../../../../core/widgets/app_card.dart';
import '../../../../core/widgets/tone_badge.dart';
import '../../domain/case_models.dart';
import '../case_labels.dart';
import '../../../../core/utils/bidi.dart';

class CaseCard extends StatelessWidget {
  const CaseCard({super.key, required this.dentalCase});
  final DentalCase dentalCase;

  @override
  Widget build(BuildContext context) {
    final c = dentalCase;
    return AppCard(
      onTap: () => context.go('/cases/${c.id}'),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(ltr(c.code), style: const TextStyle(fontSize: 12, color: AppColors.muted)),
              const Spacer(),
              ToneBadge(c.status.label, tone: c.status.tone, icon: c.status.icon),
            ],
          ),
          const SizedBox(height: 4),
          Text(c.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
          const SizedBox(height: 4),
          Row(
            children: [
              const Icon(LucideIcons.landmark, size: 14, color: AppColors.muted),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  c.authority,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12.5, color: AppColors.muted),
                ),
              ),
            ],
          ),
          Row(
            children: [
              const Icon(LucideIcons.clock, size: 14, color: AppColors.muted),
              const SizedBox(width: 6),
              Text('آخر تحديث ${AppDates.relative(c.updatedAt)}', style: const TextStyle(fontSize: 12.5, color: AppColors.muted)),
            ],
          ),
        ],
      ),
    );
  }
}
