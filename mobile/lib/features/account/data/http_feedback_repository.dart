import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;

import '../../../core/api/api_client.dart';
import '../domain/feedback_repository.dart';

/// «ملاحظاتك» → `POST /feedback` (S6.2). The API adds the app version from the `X-App-Version` header.
class HttpFeedbackRepository implements FeedbackRepository {
  HttpFeedbackRepository(this._api);

  final ApiClient _api;

  @override
  Future<void> send({required FeedbackCategory category, required String message, required String screen}) => _api.post<Json>(
    '/feedback',
    body: {'category': category.name, 'message': message, 'screen': screen, 'platform': !kIsWeb && Platform.isIOS ? 'ios' : 'android'},
  );
}
