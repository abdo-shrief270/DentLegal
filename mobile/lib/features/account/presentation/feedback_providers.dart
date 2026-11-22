import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../data/fake_feedback_repository.dart';
import '../domain/feedback_repository.dart';

part 'feedback_providers.g.dart';

@Riverpod(keepAlive: true)
FeedbackRepository feedbackRepository(Ref ref) => FakeFeedbackRepository();
