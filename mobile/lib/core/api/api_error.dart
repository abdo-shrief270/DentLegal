/// An error from the Laravel API's envelope `{ "error": { "code", "message", "details" } }` (message is Arabic and safe
/// to show), or a network failure mapped to the same shape.
class ApiError implements Exception {
  const ApiError(this.code, this.message, {this.status, this.details = const {}});

  final String code;
  final String message;
  final int? status;
  final Map<String, dynamic> details;

  static const network = ApiError('network_error', 'تعذّر الاتصال بالخادم. تحقق من الاتصال وحاول مرة أخرى.');
  static const timeout = ApiError('timeout', 'استغرق الخادم وقتًا أطول من المعتاد. حاول مرة أخرى.');

  /// Seconds to wait after a 429, when the API says.
  int? get retryAfterSeconds => (details['retry_after_s'] as num?)?.toInt();

  @override
  String toString() => message;
}
