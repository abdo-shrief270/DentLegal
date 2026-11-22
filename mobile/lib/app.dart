import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:material_ui/material_ui.dart';

import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';

class DentLegalApp extends ConsumerWidget {
  const DentLegalApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => MaterialApp.router(
    title: 'DentLegal',
    debugShowCheckedModeBanner: false,
    theme: buildAppTheme(),
    // Arabic only in Project 1; the locale makes the whole app right-to-left.
    locale: const Locale('ar'),
    supportedLocales: const [Locale('ar')],
    localizationsDelegates: GlobalMaterialLocalizations.delegates,
    routerConfig: ref.watch(appRouterProvider),
  );
}
