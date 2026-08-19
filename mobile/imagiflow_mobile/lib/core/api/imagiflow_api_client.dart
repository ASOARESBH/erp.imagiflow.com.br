import 'dart:io';

import 'package:dio/dio.dart';

import '../storage/secure_session_store.dart';

class ApiFailure implements Exception {
  ApiFailure(this.message, {this.statusCode, this.errors = const {}});
  final String message;
  final int? statusCode;
  final Map<String, List<String>> errors;
}

class ImagiFlowApiClient {
  ImagiFlowApiClient(this._store)
      : _dio = Dio(BaseOptions(
          connectTimeout: const Duration(seconds: 20),
          receiveTimeout: const Duration(seconds: 30),
          sendTimeout: const Duration(seconds: 30),
          headers: const {'Accept': 'application/json'},
        ));

  final SecureSessionStore _store;
  final Dio _dio;

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query, bool authenticated = true}) =>
      _request('GET', path, query: query, authenticated: authenticated);

  Future<Map<String, dynamic>> post(String path, {Object? data, bool authenticated = true}) =>
      _request('POST', path, data: data, authenticated: authenticated);

  Future<Map<String, dynamic>> upload(String path, FormData data) => _request('POST', path, data: data);

  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Map<String, dynamic>? query,
    Object? data,
    bool authenticated = true,
  }) async {
    final baseUrl = await _store.baseUrl();
    if (baseUrl == null || baseUrl.isEmpty) throw ApiFailure('Informe a empresa antes de continuar.');

    final headers = <String, dynamic>{};
    if (authenticated) {
      final token = await _store.accessToken();
      if (token == null || token.isEmpty) throw ApiFailure('Sua sessão expirou. Entre novamente.', statusCode: 401);
      headers[HttpHeaders.authorizationHeader] = 'Bearer $token';
    }

    try {
      final response = await _dio.request<dynamic>(
        '$baseUrl$path',
        data: data,
        queryParameters: query,
        options: Options(method: method, headers: headers),
      );
      return _decode(response.data, response.statusCode);
    } on DioException catch (exception) {
      final data = exception.response?.data;
      if (data is Map) return _decode(Map<String, dynamic>.from(data), exception.response?.statusCode);
      if (exception.type == DioExceptionType.connectionError || exception.type == DioExceptionType.connectionTimeout) {
        throw ApiFailure('Sem conexão com a empresa. Confira sua internet e tente novamente.');
      }
      throw ApiFailure('Não foi possível comunicar com o ERP.', statusCode: exception.response?.statusCode);
    }
  }

  Map<String, dynamic> _decode(dynamic raw, int? statusCode) {
    if (raw is! Map) throw ApiFailure('Resposta inválida do servidor.', statusCode: statusCode);
    final response = Map<String, dynamic>.from(raw);
    if (response['success'] == true) {
      final data = response['data'];
      return data is Map<String, dynamic> ? data : {'value': data};
    }

    final rawErrors = response['errors'];
    final errors = <String, List<String>>{};
    if (rawErrors is Map) {
      rawErrors.forEach((key, value) {
        errors['$key'] = value is List ? value.map((item) => '$item').toList() : ['$value'];
      });
    }
    throw ApiFailure('${response['message'] ?? 'Não foi possível concluir a solicitação.'}', statusCode: statusCode, errors: errors);
  }
}
