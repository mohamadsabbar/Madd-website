import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

/// Graceful FCM wrapper — works without google-services when Firebase init fails.
class PushService {
  bool _ready = false;

  Future<bool> init() async {
    if (kIsWeb) return false;
    try {
      await Firebase.initializeApp();
      await FirebaseMessaging.instance.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );
      _ready = true;
      return true;
    } catch (e) {
      debugPrint('Firebase unavailable (ok for local UI work): $e');
      _ready = false;
      return false;
    }
  }

  Future<String?> ensureToken() async {
    if (!_ready) {
      final ok = await init();
      if (!ok) return null;
    }
    try {
      return await FirebaseMessaging.instance.getToken();
    } catch (e) {
      debugPrint('FCM token error: $e');
      return null;
    }
  }

  Future<String?> currentToken() async {
    if (!_ready) return null;
    try {
      return await FirebaseMessaging.instance.getToken();
    } catch (_) {
      return null;
    }
  }
}
