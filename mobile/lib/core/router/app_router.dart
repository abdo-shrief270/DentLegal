import 'package:go_router/go_router.dart';
import 'package:material_ui/material_ui.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../features/account/presentation/account_page.dart';
import '../../features/account/presentation/feedback_page.dart';
import '../../features/auth/domain/auth_models.dart';
import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/login_page.dart';
import '../../features/auth/presentation/onboarding_page.dart';
import '../../features/auth/presentation/otp_page.dart';
import '../../features/auth/presentation/profile_completion_page.dart';
import '../../features/case_wizard/presentation/new_case_page.dart';
import '../../features/cases/presentation/analysis_page.dart';
import '../../features/cases/presentation/approved_page.dart';
import '../../features/cases/presentation/case_details_page.dart';
import '../../features/cases/presentation/cases_page.dart';
import '../../features/cases/presentation/draft_page.dart';
import '../../features/cases/presentation/home_page.dart';
import '../../features/cases/presentation/processing_page.dart';
import '../../features/consult/presentation/consult_page.dart';
import '../widgets/app_shell.dart';

part 'app_router.g.dart';

final _rootKey = GlobalKey<NavigatorState>(debugLabel: 'root');

const _publicPaths = {'/onboarding', '/login', '/otp'};

/// Pure redirect rules, unit-tested in test/router_redirect_test.dart.
@visibleForTesting
String? redirectFor(Session? session, String path) {
  final isPublic = _publicPaths.contains(path);
  if (session == null) return isPublic ? null : '/onboarding';
  if (session.needsProfile) return path == '/profile/complete' ? null : '/profile/complete';
  if (isPublic || path == '/profile/complete' || path == '/') return '/home';
  return null;
}

@Riverpod(keepAlive: true)
GoRouter appRouter(Ref ref) {
  final session = ValueNotifier<Session?>(ref.read(authControllerProvider));
  ref
    ..listen(authControllerProvider, (_, next) => session.value = next)
    ..onDispose(session.dispose);

  return GoRouter(
    navigatorKey: _rootKey,
    initialLocation: '/onboarding',
    refreshListenable: session,
    redirect: (context, state) => redirectFor(session.value, state.uri.path),
    routes: [
      GoRoute(path: '/onboarding', builder: (_, _) => const OnboardingPage()),
      GoRoute(path: '/login', builder: (_, _) => const LoginPage()),
      GoRoute(
        path: '/otp',
        builder: (_, state) => OtpPage(phone: state.uri.queryParameters['phone'] ?? ''),
      ),
      GoRoute(path: '/profile/complete', builder: (_, _) => const ProfileCompletionPage()),
      StatefulShellRoute.indexedStack(
        builder: (_, _, shell) => AppShell(shell: shell),
        branches: [
          StatefulShellBranch(
            routes: [GoRoute(path: '/home', builder: (_, _) => const HomePage())],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/cases',
                builder: (_, _) => const CasesPage(),
                routes: [
                  GoRoute(path: 'new', parentNavigatorKey: _rootKey, builder: (_, _) => const NewCasePage()),
                  GoRoute(
                    path: ':id',
                    builder: (_, state) => CaseDetailsPage(caseId: state.pathParameters['id']!),
                    routes: [
                      GoRoute(
                        path: 'processing',
                        parentNavigatorKey: _rootKey,
                        builder: (_, state) => ProcessingPage(caseId: state.pathParameters['id']!),
                      ),
                      GoRoute(
                        path: 'analysis',
                        parentNavigatorKey: _rootKey,
                        builder: (_, state) => AnalysisPage(caseId: state.pathParameters['id']!),
                      ),
                      GoRoute(
                        path: 'draft',
                        parentNavigatorKey: _rootKey,
                        builder: (_, state) => DraftPage(caseId: state.pathParameters['id']!),
                      ),
                      GoRoute(
                        path: 'approved',
                        parentNavigatorKey: _rootKey,
                        builder: (_, state) => ApprovedPage(caseId: state.pathParameters['id']!),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [GoRoute(path: '/consult', builder: (_, _) => const ConsultPage())],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/account',
                builder: (_, _) => const AccountPage(),
                routes: [GoRoute(path: 'feedback', parentNavigatorKey: _rootKey, builder: (_, _) => const FeedbackPage())],
              ),
            ],
          ),
        ],
      ),
    ],
  );
}
