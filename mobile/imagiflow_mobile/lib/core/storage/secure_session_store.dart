import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureSessionStore {
  SecureSessionStore([FlutterSecureStorage? storage]) : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;
  static const _baseUrlKey = 'imagiflow.base_url';
  static const _accessTokenKey = 'imagiflow.access_token';
  static const _profileKey = 'imagiflow.profile';
  static const _biometricEnabledKey = 'imagiflow.biometric_enabled';

  Future<String?> baseUrl() => _storage.read(key: _baseUrlKey);
  Future<void> saveBaseUrl(String value) => _storage.write(key: _baseUrlKey, value: value.trim().replaceAll(RegExp(r'/$'), ''));

  Future<String?> accessToken() => _storage.read(key: _accessTokenKey);
  Future<void> saveAccessToken(String value) => _storage.write(key: _accessTokenKey, value: value);

  Future<Map<String, dynamic>?> profile() async {
    final raw = await _storage.read(key: _profileKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      final decoded = jsonDecode(raw);
      return decoded is Map<String, dynamic> ? decoded : null;
    } catch (_) {
      return null;
    }
  }

  Future<void> saveProfile(Map<String, dynamic> value) => _storage.write(key: _profileKey, value: jsonEncode(value));

  Future<bool> biometricEnabled() async => (await _storage.read(key: _biometricEnabledKey)) == 'true';
  Future<void> setBiometricEnabled(bool value) => _storage.write(key: _biometricEnabledKey, value: value.toString());

  Future<void> clearSession({bool clearTenant = false}) async {
    await _storage.delete(key: _accessTokenKey);
    await _storage.delete(key: _profileKey);
    await _storage.delete(key: _biometricEnabledKey);
    if (clearTenant) await _storage.delete(key: _baseUrlKey);
  }
}
