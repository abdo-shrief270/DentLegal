import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';
import 'package:pdfrx/pdfrx.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/async_view.dart';
import '../domain/case_models.dart';
import 'cases_providers.dart';

/// «عرض المستند» (S3.12): the original from a 5-minute signed link, rendered from memory (never written to disk). The
/// route wraps it in a ScreenGuard, so screenshots and recordings are blocked.
class DocumentViewerPage extends ConsumerWidget {
  const DocumentViewerPage({required this.documentId, required this.title, super.key});

  final String documentId;
  final String title;

  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
    appBar: AppBar(title: Text(title, overflow: TextOverflow.ellipsis)),
    body: AsyncView<DocumentFile>(
      value: ref.watch(caseDocumentFileProvider(documentId)),
      builder: (f) => switch (f) {
        _ when f.isPdf => PdfViewer.data(f.bytes, sourceName: 'document-$documentId', useProgressiveLoading: true),
        _ when f.isImage => InteractiveViewer(maxScale: 5, child: Center(child: Image.memory(f.bytes, gaplessPlayback: true))),
        _ when f.mime == 'text/plain' => Padding(
          padding: const EdgeInsets.all(20),
          child: Text(utf8.decode(f.bytes), style: const TextStyle(height: 1.6)),
        ),
        _ => const Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(LucideIcons.fileX, size: 36, color: AppColors.muted),
              SizedBox(height: 8),
              Text('لا يمكن عرض هذا النوع من الملفات.', style: TextStyle(color: AppColors.muted)),
            ],
          ),
        ),
      },
    ),
  );
}
