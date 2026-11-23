import 'package:dio/dio.dart';

import 'api_config.dart';
import 'api_error.dart';
import 'token_store.dart';

typedef Json = Map<String, dynamic>;

/// The one HTTP client for `/api/v1` (PROJECT1_PLAN.md §4.1): JSON, `X-App-Version`, the bearer token from secure
/// storage, and every failure turned into an [ApiError]. A 401 clears the token and calls [onUnauthorized].
class ApiClient {
  ApiClient({required TokenStore tokens, Dio? dio, String? baseUrl, this.onUnauthorized})
    : _tokens = tokens, // ignore: prefer_initializing_formals
      dio =
          dio ??
          Dio(
            BaseOptions(
              baseUrl: '${baseUrl ?? ApiConfig.baseUrl}/api/v1',
              connectTimeout: const Duration(seconds: 10),
              receiveTimeout: const Duration(seconds: 60),
              sendTimeout: const Duration(seconds: 120),
              headers: {'Accept': 'application/json', 'X-App-Version': ApiConfig.appVersion},
            ),
          ) {
    this.dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokens.read();
          if (token != null) options.headers['Authorization'] = 'Bearer $token';
          handler.next(options);
        },
      ),
    );
  }

  final Dio dio;
  final TokenStore _tokens;

  /// Called after a 401 (expired or revoked token), so the app can return to sign-in.
  void Function()? onUnauthorized;

  /// GET and unwrap `data`.
  Future<T> get<T>(String path, {Map<String, dynamic>? query}) => _send(() => dio.get<Json>(path, queryParameters: query));

  Future<T> post<T>(String path, {Object? body, Map<String, String>? headers}) => _send(
    () => dio.post<Json>(
      path,
      data: body,
      options: Options(headers: headers),
    ),
  );

  Future<T> put<T>(String path, {Object? body}) => _send(() => dio.put<Json>(path, data: body));

  Future<void> delete(String path) => _send<Object?>(() => dio.delete<Json>(path));

  Future<T> _send<T>(Future<Response<Json>> Function() call) async {
    try {
      final response = await call();
      return response.data?['data'] as T;
    } on DioException catch (e) {
      throw await _map(e);
    }
  }

  Future<ApiError> _map(DioException e) async {
    switch (e.type) {
      case DioExceptionType.connectionTimeout || DioExceptionType.sendTimeout || DioExceptionType.receiveTimeout:
        return ApiError.timeout;
      case DioExceptionType.connectionError || DioExceptionType.unknown:
        if (e.response == null) return ApiError.network;
      default:
        break;
    }
    final status = e.response?.statusCode;
    final body = e.response?.data;
    final error = body is Map ? body['error'] : null;
    if (status == 401) {
      await _tokens.clear();
      onUnauthorized?.call();
    }
    if (error is Map) {
      return ApiError(
        error['code'] as String? ?? 'error',
        error['message'] as String? ?? 'حدث خطأ غير متوقع.',
        status: status,
        details: (error['details'] as Map?)?.cast<String, dynamic>() ?? const {},
      );
    }
    return ApiError('http_$status', 'حدث خطأ غير متوقع. حاول مرة أخرى.', status: status);
  }
}
