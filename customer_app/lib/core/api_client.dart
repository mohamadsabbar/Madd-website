import 'dart:convert';

import 'package:http/http.dart' as http;

import 'config.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});
  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;
  String? _token;

  String? get token => _token;

  void setToken(String? token) => _token = token;

  Uri _uri(String path, [Map<String, String>? query]) {
    final base = AppConfig.apiBase.replaceAll(RegExp(r'/+$'), '');
    final p = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$base$p').replace(queryParameters: query);
  }

  Map<String, String> _headers({bool auth = true, bool jsonBody = true}) {
    final h = <String, String>{
      'Accept': 'application/json',
      if (jsonBody) 'Content-Type': 'application/json',
    };
    if (auth && _token != null && _token!.isNotEmpty) {
      h['Authorization'] = 'Bearer $_token';
    }
    return h;
  }

  Future<Map<String, dynamic>> get(String path, {bool auth = true}) async {
    final res = await _client.get(_uri(path), headers: _headers(auth: auth));
    return _decode(res);
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? body,
    bool auth = true,
  }) async {
    final res = await _client.post(
      _uri(path),
      headers: _headers(auth: auth),
      body: body == null ? null : jsonEncode(body),
    );
    return _decode(res);
  }

  Future<Map<String, dynamic>> delete(
    String path, {
    Map<String, dynamic>? body,
    bool auth = true,
  }) async {
    final req = http.Request('DELETE', _uri(path));
    req.headers.addAll(_headers(auth: auth));
    if (body != null) req.body = jsonEncode(body);
    final streamed = await _client.send(req);
    final res = await http.Response.fromStream(streamed);
    return _decode(res);
  }

  Map<String, dynamic> _decode(http.Response res) {
    Map<String, dynamic> json = {};
    if (res.body.isNotEmpty) {
      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map<String, dynamic>) {
          json = decoded;
        } else {
          json = {'data': decoded};
        }
      } catch (_) {
        json = {'message': res.body};
      }
    }

    if (res.statusCode >= 200 && res.statusCode < 300) {
      return json;
    }

    final msg = _extractMessage(json) ?? 'حدث خطأ (${res.statusCode})';
    throw ApiException(msg, statusCode: res.statusCode);
  }

  String? _extractMessage(Map<String, dynamic> json) {
    if (json['message'] is String) return json['message'] as String;
    final errors = json['errors'];
    if (errors is Map && errors.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) return first.first.toString();
      return first.toString();
    }
    return null;
  }
}
