import 'package:material_ui/material_ui.dart';

import 'app_colors.dart';

const _font = 'IBMPlexSansArabic';

ThemeData buildAppTheme() {
  final scheme = ColorScheme.fromSeed(seedColor: AppColors.teal600).copyWith(
    primary: AppColors.teal600,
    onPrimary: Colors.white,
    secondary: AppColors.navy900,
    surface: AppColors.surface,
    onSurface: AppColors.ink,
    error: AppColors.red600,
    outline: AppColors.line2,
    outlineVariant: AppColors.line,
  );
  final shape = RoundedRectangleBorder(borderRadius: BorderRadius.circular(13));
  const textStyle = TextStyle(fontFamily: _font, fontWeight: FontWeight.w700, fontSize: 15);

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    fontFamily: _font,
    scaffoldBackgroundColor: AppColors.bg,
    splashFactory: InkSparkle.splashFactory,
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.bg,
      foregroundColor: AppColors.ink,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: true,
      titleTextStyle: TextStyle(fontFamily: _font, fontSize: 17, fontWeight: FontWeight.w700, color: AppColors.ink),
    ),
    textTheme: const TextTheme(
      headlineSmall: TextStyle(fontSize: 24, fontWeight: FontWeight.w700, color: AppColors.ink),
      titleLarge: TextStyle(fontSize: 19, fontWeight: FontWeight.w700, color: AppColors.ink),
      titleMedium: TextStyle(fontSize: 15.5, fontWeight: FontWeight.w700, color: AppColors.ink),
      bodyLarge: TextStyle(fontSize: 15, height: 1.7, color: AppColors.ink),
      bodyMedium: TextStyle(fontSize: 14, height: 1.7, color: AppColors.ink),
      bodySmall: TextStyle(fontSize: 12.5, height: 1.6, color: AppColors.muted),
      labelLarge: TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(52),
        shape: shape,
        textStyle: textStyle,
        backgroundColor: AppColors.teal600,
        disabledBackgroundColor: AppColors.teal300,
        disabledForegroundColor: Colors.white,
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size(0, 52),
        shape: shape,
        textStyle: textStyle,
        foregroundColor: AppColors.ink,
        side: const BorderSide(color: AppColors.line2),
        backgroundColor: AppColors.surface,
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(foregroundColor: AppColors.teal600, textStyle: textStyle.copyWith(fontSize: 13.5)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      hintStyle: const TextStyle(color: AppColors.faint),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.line2),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.line2),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.teal500, width: 1.6),
      ),
    ),
    dividerTheme: const DividerThemeData(color: AppColors.line, space: 1),
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: AppColors.navy900,
      contentTextStyle: const TextStyle(fontFamily: _font, color: Colors.white),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ),
    bottomSheetTheme: const BottomSheetThemeData(backgroundColor: AppColors.surface, showDragHandle: true),
  );
}
