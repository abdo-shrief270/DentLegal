import 'package:material_ui/material_ui.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/citation_chip.dart';
import '../../../../core/widgets/tone_badge.dart';
import '../../domain/case_models.dart';
import 'source_sheet.dart';

/// The rendered response «ورقة» (m10/m13) with tappable citations and «غير مسند» flags.
class DraftPaper extends StatelessWidget {
  const DraftPaper({super.key, required this.draft});
  final CaseDraft draft;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.fromLTRB(16, 14, 16, 18),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: AppColors.line),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('${draft.title} · الإصدار ${draft.versionNo}', style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
        const Divider(height: 18),
        Text(draft.addressee, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, height: 1.9)),
        Text(draft.salutation, style: const TextStyle(fontSize: 13.5, height: 1.9)),
        _Paragraph(paragraph: draft.intro),
        for (final section in draft.sections) ...[
          const SizedBox(height: 8),
          Text(
            section.heading,
            style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.navy900, fontSize: 14),
          ),
          for (final (i, p) in section.paragraphs.indexed) _Paragraph(paragraph: p, number: i + 1),
        ],
        const SizedBox(height: 8),
        Text(draft.closing, style: const TextStyle(fontSize: 13.5)),
        const SizedBox(height: 4),
        Text('المرفقات: ${draft.attachments.join('، ')}', style: const TextStyle(fontSize: 12.5, color: AppColors.muted)),
      ],
    ),
  );
}

class _Paragraph extends StatelessWidget {
  const _Paragraph({required this.paragraph, this.number});
  final DraftParagraph paragraph;
  final int? number;

  @override
  Widget build(BuildContext context) {
    final flagged = paragraph.unsupported;
    return Container(
      margin: const EdgeInsets.only(top: 6),
      padding: flagged ? const EdgeInsets.all(6) : EdgeInsets.zero,
      decoration: flagged
          ? BoxDecoration(
              color: const Color(0xFFFFF6DC),
              borderRadius: BorderRadius.circular(8),
              border: const Border(bottom: BorderSide(color: Color(0xFFD39B14), width: 2)),
            )
          : null,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('${number == null ? '' : '$number. '}${paragraph.text}', style: const TextStyle(fontSize: 13.5, height: 1.9)),
          if (paragraph.citations.isNotEmpty || flagged)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  for (final c in paragraph.citations) CitationChip(c, onTap: () => showCitationSheet(context, c)),
                  if (flagged) const ToneBadge('غير مسند', tone: Tone.amber),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
