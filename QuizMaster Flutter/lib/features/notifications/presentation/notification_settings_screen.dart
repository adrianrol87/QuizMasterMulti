import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';

import '../../../core/notifications/notification_preferences.dart';
import '../../../core/notifications/push_notification_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/models/app_user.dart';
import '../data/notification_preferences_repository.dart';

class NotificationSettingsScreen extends StatefulWidget {
  const NotificationSettingsScreen({
    super.key,
    required this.locale,
    required this.currentUser,
    required this.repository,
    required this.pushService,
  });

  final Locale locale;
  final AppUser currentUser;
  final NotificationPreferencesRepository repository;
  final PushNotificationService pushService;

  @override
  State<NotificationSettingsScreen> createState() =>
      _NotificationSettingsScreenState();
}

class _NotificationSettingsScreenState
    extends State<NotificationSettingsScreen> {
  NotificationPreferences? _preferences;
  AuthorizationStatus? _authorizationStatus;
  bool _saving = false;

  bool get _es => widget.locale.languageCode == 'es';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final results = await Future.wait<Object>([
      widget.repository.load(widget.currentUser.id),
      widget.pushService.authorizationStatus(),
    ]);
    if (!mounted) return;
    final preferences = results[0] as NotificationPreferences;
    setState(() {
      _preferences = preferences;
      _authorizationStatus = results[1] as AuthorizationStatus;
    });
    await widget.pushService.applyPreferences(preferences);
  }

  Future<void> _requestPermission() async {
    final status = await widget.pushService.requestPermission();
    if (!mounted) return;
    setState(() => _authorizationStatus = status);
  }

  Future<void> _update(NotificationPreferences value) async {
    if (_saving) return;
    setState(() {
      _preferences = value;
      _saving = true;
    });
    await widget.pushService.applyPreferences(value);
    try {
      await widget.repository.save(widget.currentUser.id, value);
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _es
                  ? 'Se guardó en este dispositivo, pero no se pudo sincronizar con el servidor.'
                  : 'Saved on this device, but could not sync with the server.',
            ),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  bool get _permissionGranted =>
      _authorizationStatus == AuthorizationStatus.authorized ||
      _authorizationStatus == AuthorizationStatus.provisional;

  @override
  Widget build(BuildContext context) {
    final preferences = _preferences;
    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        foregroundColor: AppTheme.textColor(context),
        elevation: 0,
        title: Text(_es ? 'Notificaciones' : 'Notifications'),
      ),
      body: preferences == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.fromLTRB(18, 8, 18, 32),
              children: [
                _PermissionCard(
                  granted: _permissionGranted,
                  isSpanish: _es,
                  onRequest: _requestPermission,
                ),
                const SizedBox(height: 14),
                _SettingsCard(
                  children: [
                    _PreferenceSwitch(
                      icon: Icons.notifications_active_rounded,
                      title: _es
                          ? 'Permitir notificaciones'
                          : 'Allow notifications',
                      subtitle: _es
                          ? 'Interruptor principal para todos los avisos.'
                          : 'Main switch for every notification.',
                      value: preferences.enabled,
                      onChanged: (value) =>
                          _update(preferences.copyWith(enabled: value)),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                _SectionTitle(_es ? 'Qué quieres recibir' : 'What to receive'),
                _SettingsCard(
                  children: [
                    _PreferenceSwitch(
                      icon: Icons.quiz_rounded,
                      title: _es ? 'Quiz diario' : 'Daily quiz',
                      subtitle: _es
                          ? 'Avisos cuando haya un nuevo quiz diario.'
                          : 'Alerts when a new daily quiz is available.',
                      value: preferences.dailyQuiz,
                      enabled: preferences.enabled,
                      onChanged: (value) =>
                          _update(preferences.copyWith(dailyQuiz: value)),
                    ),
                    _PreferenceSwitch(
                      icon: Icons.extension_rounded,
                      title: _es ? 'Contenido nuevo' : 'New content',
                      subtitle: _es
                          ? 'Juegos, niveles y categorías nuevas.'
                          : 'New games, levels and categories.',
                      value: preferences.newContent,
                      enabled: preferences.enabled,
                      onChanged: (value) =>
                          _update(preferences.copyWith(newContent: value)),
                    ),
                    _PreferenceSwitch(
                      icon: Icons.monetization_on_rounded,
                      title: _es ? 'Recompensas' : 'Rewards',
                      subtitle: _es
                          ? 'Monedas, regalos y promociones.'
                          : 'Coins, gifts and promotions.',
                      value: preferences.rewards,
                      enabled: preferences.enabled,
                      onChanged: (value) =>
                          _update(preferences.copyWith(rewards: value)),
                    ),
                    _PreferenceSwitch(
                      icon: Icons.alarm_rounded,
                      title: _es ? 'Recordatorios' : 'Reminders',
                      subtitle: _es
                          ? 'Recordatorios para continuar jugando.'
                          : 'Reminders to continue playing.',
                      value: preferences.reminders,
                      enabled: preferences.enabled,
                      onChanged: (value) =>
                          _update(preferences.copyWith(reminders: value)),
                    ),
                    _PreferenceSwitch(
                      icon: Icons.emoji_events_rounded,
                      title: _es ? 'Eventos' : 'Events',
                      subtitle: _es
                          ? 'Competencias y eventos especiales.'
                          : 'Competitions and special events.',
                      value: preferences.events,
                      enabled: preferences.enabled,
                      onChanged: (value) =>
                          _update(preferences.copyWith(events: value)),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                _SectionTitle(
                    _es ? 'Alertas del dispositivo' : 'Device alerts'),
                _SettingsCard(
                  children: [
                    _PreferenceSwitch(
                      icon: Icons.volume_up_rounded,
                      title: _es ? 'Sonido' : 'Sound',
                      value: preferences.sound,
                      enabled: preferences.enabled,
                      onChanged: (value) =>
                          _update(preferences.copyWith(sound: value)),
                    ),
                    _PreferenceSwitch(
                      icon: Icons.vibration_rounded,
                      title: _es ? 'Vibración' : 'Vibration',
                      value: preferences.vibration,
                      enabled: preferences.enabled,
                      onChanged: (value) =>
                          _update(preferences.copyWith(vibration: value)),
                    ),
                  ],
                ),
              ],
            ),
    );
  }
}

class _PermissionCard extends StatelessWidget {
  const _PermissionCard({
    required this.granted,
    required this.isSpanish,
    required this.onRequest,
  });

  final bool granted;
  final bool isSpanish;
  final VoidCallback onRequest;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: granted ? const Color(0xFFEAF8F0) : const Color(0xFFFFF3DD),
        borderRadius: BorderRadius.circular(22),
      ),
      child: Row(
        children: [
          Icon(
            granted ? Icons.check_circle_rounded : Icons.warning_amber_rounded,
            color: granted ? const Color(0xFF238258) : const Color(0xFFB46A08),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              granted
                  ? (isSpanish
                      ? 'El sistema permite notificaciones.'
                      : 'System notifications are allowed.')
                  : (isSpanish
                      ? 'Falta permitir notificaciones en el dispositivo.'
                      : 'Notifications still need device permission.'),
              style: const TextStyle(
                color: AppTheme.ink,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          if (!granted)
            TextButton(
              onPressed: onRequest,
              child: Text(isSpanish ? 'Permitir' : 'Allow'),
            ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);
  final String text;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(4, 0, 4, 8),
        child: Text(
          text,
          style: const TextStyle(
            color: AppTheme.ink,
            fontSize: 17,
            fontWeight: FontWeight.w800,
          ),
        ),
      );
}

class _SettingsCard extends StatelessWidget {
  const _SettingsCard({required this.children});
  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(22),
          boxShadow: const [
            BoxShadow(
              color: Color(0x100E2741),
              blurRadius: 16,
              offset: Offset(0, 7),
            ),
          ],
        ),
        child: Column(children: children),
      );
}

class _PreferenceSwitch extends StatelessWidget {
  const _PreferenceSwitch({
    required this.icon,
    required this.title,
    required this.value,
    required this.onChanged,
    this.subtitle,
    this.enabled = true,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final bool value;
  final bool enabled;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) => SwitchListTile.adaptive(
        secondary: Icon(icon, color: const Color(0xFF2B6FB6)),
        title: Text(
          title,
          style: const TextStyle(
            color: AppTheme.ink,
            fontWeight: FontWeight.w700,
          ),
        ),
        subtitle: subtitle == null ? null : Text(subtitle!),
        value: value,
        onChanged: enabled ? onChanged : null,
      );
}
