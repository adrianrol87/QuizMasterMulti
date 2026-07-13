import 'dart:async';
import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../../features/auth/models/app_user.dart';
import '../network/php_api_client.dart';

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
  final StreamController<Map<String, String>> _notificationTapController =
      StreamController<Map<String, String>>.broadcast();

  static const AndroidNotificationChannel _defaultChannel =
      AndroidNotificationChannel(
    'default_channel',
    'General Notifications',
    description: 'QuizMaster notifications',
    importance: Importance.high,
  );

  StreamSubscription<String>? _tokenRefreshSubscription;
  StreamSubscription<RemoteMessage>? _messageOpenSubscription;
  String? _currentUserId;
  bool _initialized = false;

  Stream<Map<String, String>> get notificationTapStream =>
      _notificationTapController.stream;

  Future<void> initialize() async {
    if (_initialized || kIsWeb) {
      return;
    }

    _initialized = true;

    await _initializeLocalNotifications();

    final notificationSettings = await _messaging.requestPermission(
      alert: true,
      announcement: false,
      badge: true,
      carPlay: false,
      criticalAlert: false,
      provisional: false,
      sound: true,
    );
    debugPrint(
      'Push notification authorization: '
      '${notificationSettings.authorizationStatus.name}; '
      'alert=${notificationSettings.alert.name}; '
      'lockScreen=${notificationSettings.lockScreen.name}; '
      'notificationCenter=${notificationSettings.notificationCenter.name}; '
      'sound=${notificationSettings.sound.name}.',
    );

    if (defaultTargetPlatform == TargetPlatform.iOS) {
      await _messaging.setForegroundNotificationPresentationOptions(
        alert: true,
        badge: true,
        sound: true,
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

    FirebaseMessaging.onMessage.listen((message) {
      final shouldShowLocalNotification =
          defaultTargetPlatform != TargetPlatform.iOS ||
              message.notification == null;
      if (shouldShowLocalNotification) {
        unawaited(_showForegroundNotification(message));
      }
      debugPrint(
        'Push foreground: ${message.notification?.title ?? ''} ${message.notification?.body ?? ''}',
      );
    });

    _messageOpenSubscription = FirebaseMessaging.onMessageOpenedApp.listen(
      _emitTapPayload,
    );

    final initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      _emitTapPayload(initialMessage);
    }
  }

  Future<void> syncUser(AppUser user) async {
    _currentUserId = user.id;

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
  }

  Future<void> dispose() async {
    await _tokenRefreshSubscription?.cancel();
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
  }

  Future<void> _showForegroundNotification(RemoteMessage message) async {
    if (kIsWeb) {
      return;
    }

    final title = message.notification?.title ?? message.data['title'];
    final body = message.notification?.body ?? message.data['body'];

    if ((title == null || title.isEmpty) && (body == null || body.isEmpty)) {
      return;
    }

    await _localNotifications.show(
      message.hashCode,
      title,
      body,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'default_channel',
          'General Notifications',
          channelDescription: 'QuizMaster notifications',
          importance: Importance.high,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
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
