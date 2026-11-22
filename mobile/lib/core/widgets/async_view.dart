import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../theme/app_colors.dart';

/// Standard loading / error / data rendering for an [AsyncValue].
class AsyncView<T> extends StatelessWidget {
  const AsyncView({super.key, required this.value, required this.builder, this.onRetry});

  final AsyncValue<T> value;
  final Widget Function(T data) builder;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) => switch (value) {
    AsyncData(:final value) => builder(value),
    AsyncError() => Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(LucideIcons.triangleAlert, color: AppColors.amber600, size: 32),
            const SizedBox(height: 10),
            const Text('تعذّر تحميل البيانات.', textAlign: TextAlign.center),
            if (onRetry != null) TextButton(onPressed: onRetry, child: const Text('إعادة المحاولة')),
          ],
        ),
      ),
    ),
    _ => const Center(child: CircularProgressIndicator()),
  };
}
