import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';

/// Answers Dio requests from a table of `'METHOD /path'` → handler, and records what was sent. No network.
class FakeAdapter implements HttpClientAdapter {
  FakeAdapter(this.routes);

  final Map<String, Object Function(RequestOptions r)> routes;
  final List<RequestOptions> sent = [];

  /// A response other than 200: `(status, body)`.
  static (int, Object?) status(int code, Object? body) => (code, body);

  @override
  Future<ResponseBody> fetch(RequestOptions options, Stream<Uint8List>? requestStream, Future<void>? cancelFuture) async {
    sent.add(options);
    final key = '${options.method} ${options.uri.path.replaceFirst('/api/v1', '')}';
    final handler = routes[key];
    if (handler == null) {
      return ResponseBody.fromString(
        jsonEncode({
          'error': {'code': 'not_found', 'message': 'no route $key'},
        }),
        404,
        headers: _json,
      );
    }
    final result = handler(options);
    final (code, body) = result is (int, Object?) ? result : (200, result);
    return ResponseBody.fromString(jsonEncode(body), code, headers: _json);
  }

  static const _json = {
    Headers.contentTypeHeader: ['application/json'],
  };

  @override
  void close({bool force = false}) {}

  /// Bodies sent to a route, decoded.
  List<dynamic> bodies(String method, String path) => [
    for (final r in sent)
      if (r.method == method && r.uri.path.endsWith(path)) r.data is String ? jsonDecode(r.data as String) : r.data,
  ];
}
