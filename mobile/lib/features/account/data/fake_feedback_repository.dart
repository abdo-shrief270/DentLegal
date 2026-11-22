import '../../../core/fake/fake_latency.dart';
import '../domain/feedback_repository.dart';

class FakeFeedbackRepository implements FeedbackRepository {
  final List<({FeedbackCategory category, String message, String screen})> sent = [];

  @override
  Future<void> send({required FeedbackCategory category, required String message, required String screen}) async {
    await fakeLatency();
    sent.add((category: category, message: message, screen: screen));
  }
}
