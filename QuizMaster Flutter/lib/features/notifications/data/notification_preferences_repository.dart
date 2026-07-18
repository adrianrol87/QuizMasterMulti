import '../../../core/network/php_api_client.dart';
import '../../../core/notifications/notification_preferences.dart';

class NotificationPreferencesRepository {
  const NotificationPreferencesRepository({
    required this.apiClient,
    this.store = const NotificationPreferenceStore(),
  });

  final PhpApiClient apiClient;
  final NotificationPreferenceStore store;

  Future<NotificationPreferences> load(String userId) async {
    final local = await store.load(userId);
    try {
      final response = await apiClient.post({
        'get_notification_preferences': '1',
        'user_id': userId,
      });
      final data = response['data'];
      if (data is! Map) return local;
      final remote = NotificationPreferences.fromApi(
        Map<String, dynamic>.from(data),
      );
      await store.save(userId, remote);
      return remote;
    } catch (_) {
      return local;
    }
  }

  Future<void> save(
    String userId,
    NotificationPreferences preferences,
  ) async {
    await store.save(userId, preferences);
    await apiClient.post({
      'update_notification_preferences': '1',
      'user_id': userId,
      ...preferences.toApi(),
    });
  }
}
