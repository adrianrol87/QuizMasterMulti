import 'package:shared_preferences/shared_preferences.dart';

class NotificationPreferences {
  const NotificationPreferences({
    this.enabled = true,
    this.dailyQuiz = true,
    this.newContent = true,
    this.rewards = true,
    this.reminders = true,
    this.events = true,
    this.sound = true,
    this.vibration = true,
  });

  factory NotificationPreferences.fromApi(Map<String, dynamic> json) {
    bool value(String key, {bool fallback = true}) {
      final raw = json[key];
      if (raw == null) return fallback;
      return raw == true || raw.toString() == '1';
    }

    return NotificationPreferences(
      enabled: value('notifications_enabled'),
      dailyQuiz: value('daily_quiz'),
      newContent: value('new_content'),
      rewards: value('rewards'),
      reminders: value('reminders'),
      events: value('events'),
      sound: value('sound_enabled'),
      vibration: value('vibration_enabled'),
    );
  }

  final bool enabled;
  final bool dailyQuiz;
  final bool newContent;
  final bool rewards;
  final bool reminders;
  final bool events;
  final bool sound;
  final bool vibration;

  NotificationPreferences copyWith({
    bool? enabled,
    bool? dailyQuiz,
    bool? newContent,
    bool? rewards,
    bool? reminders,
    bool? events,
    bool? sound,
    bool? vibration,
  }) {
    return NotificationPreferences(
      enabled: enabled ?? this.enabled,
      dailyQuiz: dailyQuiz ?? this.dailyQuiz,
      newContent: newContent ?? this.newContent,
      rewards: rewards ?? this.rewards,
      reminders: reminders ?? this.reminders,
      events: events ?? this.events,
      sound: sound ?? this.sound,
      vibration: vibration ?? this.vibration,
    );
  }

  Map<String, String> toApi() => {
        'notifications_enabled': enabled ? '1' : '0',
        'daily_quiz': dailyQuiz ? '1' : '0',
        'new_content': newContent ? '1' : '0',
        'rewards': rewards ? '1' : '0',
        'reminders': reminders ? '1' : '0',
        'events': events ? '1' : '0',
        'sound_enabled': sound ? '1' : '0',
        'vibration_enabled': vibration ? '1' : '0',
      };

  bool allowsCategory(String category) {
    if (!enabled) return false;
    switch (category.trim().toLowerCase()) {
      case 'daily_quiz':
        return dailyQuiz;
      case 'new_content':
      case 'category':
        return newContent;
      case 'rewards':
        return rewards;
      case 'reminders':
        return reminders;
      case 'events':
        return events;
      default:
        return true;
    }
  }
}

class NotificationPreferenceStore {
  const NotificationPreferenceStore();

  String _key(String userId, String field) =>
      'notification_preferences_${userId}_$field';

  Future<NotificationPreferences> load(String userId) async {
    final prefs = await SharedPreferences.getInstance();
    bool read(String field) => prefs.getBool(_key(userId, field)) ?? true;
    return NotificationPreferences(
      enabled: read('enabled'),
      dailyQuiz: read('daily_quiz'),
      newContent: read('new_content'),
      rewards: read('rewards'),
      reminders: read('reminders'),
      events: read('events'),
      sound: read('sound'),
      vibration: read('vibration'),
    );
  }

  Future<void> save(String userId, NotificationPreferences value) async {
    final prefs = await SharedPreferences.getInstance();
    await Future.wait([
      prefs.setBool(_key(userId, 'enabled'), value.enabled),
      prefs.setBool(_key(userId, 'daily_quiz'), value.dailyQuiz),
      prefs.setBool(_key(userId, 'new_content'), value.newContent),
      prefs.setBool(_key(userId, 'rewards'), value.rewards),
      prefs.setBool(_key(userId, 'reminders'), value.reminders),
      prefs.setBool(_key(userId, 'events'), value.events),
      prefs.setBool(_key(userId, 'sound'), value.sound),
      prefs.setBool(_key(userId, 'vibration'), value.vibration),
    ]);
  }
}
