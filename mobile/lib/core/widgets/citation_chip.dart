import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../../features/cases/domain/case_models.dart';
import '../theme/app_colors.dart';

/// Tappable citation: teal for regulations, blue for case documents, amber when the quote failed verification.
class CitationChip extends StatelessWidget {
  const CitationChip(this.citation, {super.key, this.onTap});
  final Citation citation;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final (bg, fg, border) = !citation.verified
        ? (AppColors.amber50, AppColors.amber600, const Color(0xFFF3D79B))
        : citation.isDocument
        ? (AppColors.blue50, AppColors.blue600, const Color(0xFFD6E2FB))
        : (AppColors.teal50, AppColors.teal700, AppColors.teal100);
    return Material(
      color: bg,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
        side: BorderSide(color: border),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(citation.isDocument ? LucideIcons.paperclip : LucideIcons.bookOpen, size: 12, color: fg),
              const SizedBox(width: 4),
              Text(
                citation.label,
                style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: fg, height: 1.6),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
