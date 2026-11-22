enum FeedbackCategory { bug, suggestion, other }

/// General in-app feedback («ملاحظاتك»).
abstract interface class FeedbackRepository {
  Future<void> send({required FeedbackCategory category, required String message, required String screen});
}
