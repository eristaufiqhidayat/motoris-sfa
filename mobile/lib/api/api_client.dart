import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.status});
  final String message;
  final int? status;

  @override
  String toString() => message;
}

/// Klien HTTP tipis untuk API Laravel (Sanctum token).
class ApiClient {
  ApiClient({http.Client? client, this.baseUrl = apiUrl}) : _http = client ?? http.Client();

  final http.Client _http;
  final String baseUrl;
  String? token;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<dynamic> get(String path) => _send(() => _http.get(Uri.parse('$baseUrl$path'), headers: _headers));

  Future<dynamic> post(String path, [Map<String, dynamic>? body]) =>
      _send(() => _http.post(Uri.parse('$baseUrl$path'), headers: _headers, body: jsonEncode(body ?? {})));

  /// POST multipart (form + satu file), dipakai untuk check-in berfoto.
  Future<dynamic> postFile(String path, Map<String, String> fields, {required String field, required List<int> bytes, required String filename}) =>
      _send(() async {
        final req = http.MultipartRequest('POST', Uri.parse('$baseUrl$path'))
          ..headers.addAll({'Accept': 'application/json', if (token != null) 'Authorization': 'Bearer $token'})
          ..fields.addAll(fields)
          ..files.add(http.MultipartFile.fromBytes(field, bytes, filename: filename));
        return http.Response.fromStream(await _http.send(req));
      }, timeout: const Duration(seconds: 60));

  Future<dynamic> _send(Future<http.Response> Function() call, {Duration timeout = const Duration(seconds: 20)}) async {
    final http.Response res;
    try {
      res = await call().timeout(timeout);
    } catch (_) {
      throw ApiException('Tidak bisa terhubung ke server. Periksa sinyal lalu coba lagi.');
    }

    final body = res.body.isEmpty ? null : jsonDecode(res.body);
    if (res.statusCode >= 200 && res.statusCode < 300) return body;

    // Laravel: {"message": "...", "errors": {"field": ["pesan"]}}
    String msg = 'Terjadi kesalahan (${res.statusCode}).';
    if (body is Map) {
      final errors = body['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        msg = first is List && first.isNotEmpty ? first.first.toString() : first.toString();
      } else if (body['message'] != null) {
        msg = body['message'].toString();
      }
    }
    if (res.statusCode == 401) msg = 'Sesi habis. Silakan masuk lagi.';
    throw ApiException(msg, status: res.statusCode);
  }
}
