import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:local_auth/local_auth.dart';

import '../api/imagiflow_api_client.dart';
import '../storage/secure_session_store.dart';

final sessionStoreProvider = Provider<SecureSessionStore>((ref) => SecureSessionStore());
final apiClientProvider = Provider<ImagiFlowApiClient>((ref) => ImagiFlowApiClient(ref.watch(sessionStoreProvider)));
final authControllerProvider = StateNotifierProvider<AuthController, AuthState>((ref) {
  return AuthController(ref.watch(sessionStoreProvider), ref.watch(apiClientProvider));
});

class AuthState {
  const AuthState({
    this.initializing = true,
    this.authenticated = false,
    this.baseUrl,
    this.profile,
    this.pendingChallenge,
    this.error,
  });

  final bool initializing;
  final bool authenticated;
  final String? baseUrl;
  final Map<String, dynamic>? profile;
  final String? pendingChallenge;
  final String? error;

  AuthState copyWith({
    bool? initializing,
    bool? authenticated,
    String? baseUrl,
    Map<String, dynamic>? profile,
    String? pendingChallenge,
    bool clearChallenge = false,
    String? error,
    bool clearError = false,
  }) =>
      AuthState(
        initializing: initializing ?? this.initializing,
        authenticated: authenticated ?? this.authenticated,
        baseUrl: baseUrl ?? this.baseUrl,
        profile: profile ?? this.profile,
        pendingChallenge: clearChallenge ? null : pendingChallenge ?? this.pendingChallenge,
        error: clearError ? null : error ?? this.error,
      );
}

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._store, this._api) : super(const AuthState());

  final SecureSessionStore _store;
  final ImagiFlowApiClient _api;

  Future<void> restore() async {
    final baseUrl = await _store.baseUrl();
    final token = await _store.accessToken();
    final profile = await _store.profile();
    state = AuthState(initializing: false, baseUrl: baseUrl, authenticated: token != null && profile != null, profile: profile);
  }

  Future<void> validateTenant(String rawValue) async {
    final normalized = _normalizeUrl(rawValue);
    await _store.saveBaseUrl(normalized);
    try {
      final data = await _api.get('/api/mobile/v1/tenant/ping', authenticated: false);
      state = state.copyWith(baseUrl: normalized, clearError: true);
    } on ApiFailure catch (failure) {
      await _store.clearSession(clearTenant: true);
      state = state.copyWith(error: failure.message);
      rethrow;
    }
  }

  Future<bool> login({required String email, required String password, required String deviceName, required String platform}) async {
    try {
      final data = await _api.post('/api/mobile/v1/login', authenticated: false, data: {
        'email': email,
        'password': password,
        'device_name': deviceName,
        'device_platform': platform,
      });
      if (data['requires_2fa'] == true) {
        state = state.copyWith(pendingChallenge: data['challenge_token'] as String?, clearError: true);
        return false;
      }
      await _saveLogin(data);
      return true;
    } on ApiFailure catch (failure) {
      state = state.copyWith(error: failure.message);
      rethrow;
    }
  }

  Future<void> verifyTwoFactor(String code) async {
    final challenge = state.pendingChallenge;
    if (challenge == null) throw ApiFailure('A verificação expirou. Faça login novamente.');
    final data = await _api.post('/api/mobile/v1/2fa/verify', authenticated: false, data: {
      'challenge_token': challenge,
      'code': code,
    });
    await _saveLogin(data);
    state = state.copyWith(clearChallenge: true, clearError: true);
  }

  Future<void> resendTwoFactor() async {
    final challenge = state.pendingChallenge;
    if (challenge == null) throw ApiFailure('A verificação expirou. Faça login novamente.');
    await _api.post('/api/mobile/v1/2fa/resend', authenticated: false, data: {'challenge_token': challenge});
  }

  Future<void> requestPasswordReset(String email) => _api.post('/api/mobile/v1/forgot-password', authenticated: false, data: {'email': email});

  Future<bool> unlockWithBiometrics() async {
    if (!await _store.biometricEnabled() || await _store.accessToken() == null) return false;
    final auth = LocalAuthentication();
    final canAuthenticate = await auth.canCheckBiometrics || await auth.isDeviceSupported();
    if (!canAuthenticate) return false;
    final ok = await auth.authenticate(
      localizedReason: 'Confirme sua identidade para acessar o ImagiFlow.',
      options: const AuthenticationOptions(biometricOnly: true, stickyAuth: true),
    );
    if (ok) await restore();
    return ok;
  }

  Future<void> setBiometrics(bool value) => _store.setBiometricEnabled(value);

  Future<void> logout({bool changeTenant = false}) async {
    try {
      if (state.authenticated) await _api.post('/api/mobile/v1/logout');
    } catch (_) {
      // A limpeza local deve ocorrer mesmo sem rede ou com token já revogado.
    }
    await _store.clearSession(clearTenant: changeTenant);
    state = AuthState(initializing: false, baseUrl: changeTenant ? null : await _store.baseUrl());
  }

  Future<void> _saveLogin(Map<String, dynamic> data) async {
    final token = data['access_token'] as String?;
    final profile = data['profile'];
    if (token == null || profile is! Map<String, dynamic>) throw ApiFailure('Resposta de login inválida.');
    await _store.saveAccessToken(token);
    await _store.saveProfile(profile);
    state = AuthState(initializing: false, authenticated: true, baseUrl: await _store.baseUrl(), profile: profile);
  }

  String _normalizeUrl(String raw) {
    var value = raw.trim().toLowerCase();
    if (!value.startsWith('http://') && !value.startsWith('https://')) value = 'https://$value';
    final uri = Uri.tryParse(value);
    if (uri == null || uri.host.isEmpty) throw ApiFailure('Informe o domínio da empresa, por exemplo empresa.imagiflow.com.br.');
    if (uri.scheme != 'https' && uri.scheme != 'http') throw ApiFailure('Endereço da empresa inválido.');
    return '${uri.scheme}://${uri.host}${uri.hasPort ? ':${uri.port}' : ''}';
  }
}
