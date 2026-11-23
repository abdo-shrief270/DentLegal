import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../core/api/api_config.dart';
import '../../../core/api/api_providers.dart';
import '../data/fake_feedback_repository.dart';
import '../data/http_feedback_repository.dart';
import '../domain/feedback_repository.dart';

part 'feedback_providers.g.dart';

@Riverpod(keepAlive: true)
FeedbackRepository feedbackRepository(Ref ref) => ApiConfig.useRealApi ? HttpFeedbackRepository(ref.watch(apiClientProvider)) : FakeFeedbackRepository();
