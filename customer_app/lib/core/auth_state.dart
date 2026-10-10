import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api_client.dart';
import 'push_service.dart';

class AuthState extends ChangeNotifier {
  AuthState(this.api, {FlutterSecureStorage? storage, PushService? push})
      : _storage = storage ?? const FlutterSecureStorage(),
        _push = push ?? PushService();

  final ApiClient api;
  final FlutterSecureStorage _storage;
  final PushService _push;

  static const _tokenKey = 'madd_api_token';

  bool bootstrapping = true;
  bool loading = false;
  String? error;
  Map<String, dynamic>? user;

  bool get isLoggedIn => api.token != null && api.token!.isNotEmpty;

  Future<void> bootstrap() async {
    bootstrapping = true;
    notifyListeners();
    try {
      final token = await _storage.read(key: _tokenKey);
      if (token != null && token.isNotEmpty) {
        api.setToken(token);
        try {
          user = await api.get('/user');
        } catch (_) {
          await _clearSession();
        }
      }
    } finally {
      bootstrapping = false;
      notifyListeners();
    }
  }

  Future<void> login(String login, String password) async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      final res = await api.post(
        '/login',
        auth: false,
        body: {'login': login.trim(), 'password': password},
      );
      final token = res['token'] as String?;
      if (token == null || token.isEmpty) {
        throw ApiException('لم يُرجع الخادم رمز الدخول.');
      }
      api.setToken(token);
      await _storage.write(key: _tokenKey, value: token);
      user = (res['user'] as Map?)?.cast<String, dynamic>() ?? await api.get('/user');
      await _registerPushQuietly();
    } on ApiException catch (e) {
      error = e.message;
      rethrow;
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> acceptSession(String token, Map<String, dynamic>? userData) async {
    api.setToken(token);
    await _storage.write(key: _tokenKey, value: token);
    user = userData ?? await api.get('/user');
    await _registerPushQuietly();
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      final fcm = await _push.currentToken();
      if (fcm != null) {
        await api.delete('/customer/device-token', body: {'token': fcm});
      }
    } catch (_) {}
    try {
      await api.post('/logout');
    } catch (_) {}
    await _clearSession();
    notifyListeners();
  }

  Future<void> _clearSession() async {
    api.setToken(null);
    user = null;
    await _storage.delete(key: _tokenKey);
  }

  Future<void> _registerPushQuietly() async {
    try {
      final token = await _push.ensureToken();
      if (token == null) return;
      final platform = (!kIsWeb && Platform.isIOS) ? 'ios' : 'android';
      await api.post('/customer/device-token', body: {
        'token': token,
        'platform': platform,
      });
    } catch (e) {
      debugPrint('Push register skipped: $e');
    }
  }

  Future<void> refreshPushRegistration() => _registerPushQuietly();
}
