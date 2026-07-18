import 'dart:async';
import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../../features/auth/models/app_user.dart';
import '../network/php_api_client.dart';
import 'notification_preferences.dart';

class PushNotificationService {
  PushNotificationService({
    FirebaseMessaging? messaging,
    PhpApiClient? apiClient,
    FlutterLocalNotificationsPlugin? localNotifications,
  })  : _messaging = messaging ?? FirebaseMessaging.instance,
        _apiClient = apiClient,
        _localNotifications =
            localNotifications ?? FlutterLocalNotificationsPlugin();

  final FirebaseMessaging _messaging;
  final PhpApiClient? _apiClient;
  final FlutterLocalNotificationsPlugin _localNotifications;
  final NotificationPreferenceStore _preferenceStore =
      const NotificationPreferenceStore();
  final StreamController<Map<String, String>> _notificationTapController =
      StreamController<Map<String, String>>.broadcast();

  static const AndroidNotificationChannel _defaultChannel =
      AndroidNotificationChannel(
    'default_channel',
    'General Notifications',
    description: 'QuizMaster notifications',
    importance: Importance.high,
  );
  static const AndroidNotificationChannel _soundOnlyChannel =
      AndroidNotificationChannel(
    'sound_only_channel',
    'Notifications with sound',
    description: 'QuizMaster notifications with sound only',
    importance: Importance.high,
    enableVibration: false,
  );
  static const AndroidNotificationChannel _vibrationOnlyChannel =
      AndroidNotificationChannel(
    'vibration_only_channel',
    'Notifications with vibration',
    description: 'QuizMaster notifications with vibration only',
    importance: Importance.high,
    playSound: false,
  );
  static const AndroidNotificationChannel _silentChannel =
      AndroidNotificationChannel(
    'silent_channel',
    'Silent notifications',
    description: 'QuizMaster notifications without sound or vibration',
    importance: Importance.high,
    playSound: false,
    enableVibration: false,
  );

  StreamSubscription<String>? _tokenRefreshSubscription;
  StreamSubscription<RemoteMessage>? _foregroundMessageSubscription;
  StreamSubscription<RemoteMessage>? _messageOpenSubscription;
  String? _currentUserId;
  NotificationPreferences _preferences = const NotificationPreferences();
  bool _initialized = false;

  Stream<Map<String, String>> get notificationTapStream =>
      _notificationTapController.stream;

  Future<void> initialize() async {
    if (_initialized || kIsWeb) {
      return;
    }

    _initialized = true;

    await _initializeLocalNotifications();

    final authorization = await requestPermission();
    debugPrint('Push notification authorization: ${authorization.name}.');

    if (defaultTargetPlatform == TargetPlatform.iOS) {
      // Foreground messages are rendered locally so category, sound and
      // vibration preferences can be respected consistently on both systems.
      await _messaging.setForegroundNotificationPresentationOptions(
        alert: false,
        badge: false,
        sound: false,
      );
    }

    _tokenRefreshSubscription =
        FirebaseMessaging.instance.onTokenRefresh.listen(
      (token) {
        final userId = _currentUserId;
        if (userId == null || token.trim().isEmpty) {
          return;
        }
        unawaited(_updateBackendToken(userId, token));
      },
    );

    _foregroundMessageSubscription = FirebaseMessaging.onMessage.listen(
      (message) {
        unawaited(_showForegroundNotification(message));
        debugPrint(
          'Push foreground: ${message.notification?.title ?? ''} ${message.notification?.body ?? ''}',
        );
      },
    );

    _messageOpenSubscription = FirebaseMessaging.onMessageOpenedApp.listen(
      _emitTapPayload,
    );

    final initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      _emitTapPayload(initialMessage);
    }
  }

  Future<AuthorizationStatus> requestPermission() async {
    final notificationSettings = await _messaging.requestPermission(
      alert: true,
      announcement: false,
      badge: true,
      carPlay: false,
      criticalAlert: false,
      provisional: false,
      sound: true,
    );
    return notificationSettings.authorizationStatus;
  }

  Future<AuthorizationStatus> authorizationStatus() async {
    final settings = await _messaging.getNotificationSettings();
    return settings.authorizationStatus;
  }

  Future<void> applyPreferences(NotificationPreferences preferences) async {
    _preferences = preferences;
  }

  Future<void> syncUser(AppUser user) async {
    _currentUserId = user.id;
    _preferences = await _preferenceStore.load(user.id);

    if (kIsWeb || _apiClient == null || user.id.trim().isEmpty) {
      return;
    }

    if (defaultTargetPlatform == TargetPlatform.iOS) {
      String? apnsToken;
      for (var attempt = 0; attempt < 20 && apnsToken == null; attempt++) {
        apnsToken = await _messaging.getAPNSToken();
        if (apnsToken == null) {
          await Future<void>.delayed(const Duration(milliseconds: 500));
        }
      }
      if (apnsToken == null) {
        debugPrint('APNs token is not available yet.');
        return;
      }
      debugPrint('APNs token is available.');
    }

    String? token;
    try {
      token = await _messaging.getToken();
    } on FirebaseException catch (error) {
      debugPrint('FCM token is not available yet: $error');
      return;
    }
    if (token == null || token.trim().isEmpty) {
      return;
    }

    await _updateBackendToken(user.id, token);
  }

  void clearUser() {
    _currentUserId = null;
    _preferences = const NotificationPreferences();
  }

  Future<void> dispose() async {
    await _tokenRefreshSubscription?.cancel();
    await _foregroundMessageSubscription?.cancel();
    await _messageOpenSubscription?.cancel();
    await _notificationTapController.close();
  }

  Future<void> _updateBackendToken(String userId, String token) async {
    try {
      await _apiClient?.updateFcmId(
        userId: userId,
        fcmId: token,
      );
      debugPrint('FCM token synced successfully.');
    } catch (error) {
      debugPrint('Could not sync FCM token: $error');
    }
  }

  Future<void> _initializeLocalNotifications() async {
    const android = AndroidInitializationSettings('@mipmap/ic_launcher');
    const darwin = DarwinInitializationSettings();
    const settings = InitializationSettings(
      android: android,
      iOS: darwin,
    );

    await _localNotifications.initialize(
      settings,
      onDidReceiveNotificationResponse: (response) {
        final payload = response.payload;
        if (payload == null || payload.isEmpty) {
          return;
        }

        try {
          final decoded = jsonDecode(payload);
          if (decoded is Map<String, dynamic>) {
            _notificationTapController.add(
              decoded.map(
                (key, value) => MapEntry(key, value?.toString() ?? ''),
              ),
            );
          }
        } catch (_) {}
      },
    );

    final androidPlugin =
        _localNotifications.resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>();
    await androidPlugin?.createNotificationChannel(_defaultChannel);
    await androidPlugin?.createNotificationChannel(_soundOnlyChannel);
    await androidPlugin?.createNotificationChannel(_vibrationOnlyChannel);
    await androidPlugin?.createNotificationChannel(_silentChannel);
  }

  Future<void> _showForegroundNotification(RemoteMessage message) async {
    if (kIsWeb) {
      return;
    }

    final category = (message.data['notification_category'] ??
            message.data['type'] ??
            'general')
        .toString();
    if (!_preferences.allowsCategory(category)) {
      return;
    }

    final title = message.notification?.title ?? message.data['title'];
    final body = message.notification?.body ?? message.data['body'];

    if ((title == null || title.isEmpty) && (body == null || body.isEmpty)) {
      return;
    }

    final channel = _preferences.sound
        ? (_preferences.vibration ? _defaultChannel : _soundOnlyChannel)
        : (_preferences.vibration ? _vibrationOnlyChannel : _silentChannel);

    await _localNotifications.show(
      message.hashCode,
      title,
      body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          channel.id,
          channel.name,
          channelDescription: channel.description,
          importance: Importance.high,
          priority: Priority.high,
          playSound: _preferences.sound,
          enableVibration: _preferences.vibration,
        ),
        iOS: DarwinNotificationDetails(
          presentSound: _preferences.sound,
        ),
      ),
      payload: jsonEncode(_messagePayload(message)),
    );
  }

  void _emitTapPayload(RemoteMessage message) {
    _notificationTapController.add(_messagePayload(message));
  }

  Map<String, String> _messagePayload(RemoteMessage message) {
    final payload = <String, String>{
      ...message.data.map(
        (key, value) => MapEntry(key, value.toString()),
      ),
    };

    final notification = message.notification;
    if (notification?.title != null) {
      payload.putIfAbsent('title', () => notification!.title!);
    }
    if (notification?.body != null) {
      payload.putIfAbsent('body', () => notification!.body!);
    }

    return payload;
  }
}
