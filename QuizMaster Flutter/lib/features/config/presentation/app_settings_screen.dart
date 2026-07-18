import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/l10n/app_strings.dart';
import '../../../core/theme/app_theme.dart';
import '../data/app_content_repository.dart';
import '../models/app_document.dart';
import '../models/system_config.dart';
import 'app_document_screen.dart';

class AppSettingsScreen extends StatelessWidget {
  const AppSettingsScreen({
    super.key,
    required this.locale,
    required this.onLocaleChanged,
    required this.themeMode,
    required this.onThemeModeChanged,
    required this.contentRepository,
    this.systemConfig,
    this.systemConfigFuture,
  });

  final Locale locale;
  final ValueChanged<Locale> onLocaleChanged;
  final ThemeMode themeMode;
  final ValueChanged<ThemeMode> onThemeModeChanged;
  final SystemConfig? systemConfig;
  final Future<SystemConfig>? systemConfigFuture;
  final AppContentRepository contentRepository;

  bool get _es => locale.languageCode == 'es';
  String _text(String spanish, String english) => _es ? spanish : english;

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(locale);
    final configFuture = systemConfigFuture ?? Future.value(systemConfig!);

    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.textColor(context),
        title: Text(
          strings.text('settings'),
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
              ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(18, 6, 18, 32),
        children: [
          _SettingsHero(
            title: _text('Hazla tuya', 'Make it yours'),
            subtitle: _text(
              'Ajusta la apariencia y el idioma de QuizMaster.',
              'Choose how QuizMaster looks and speaks.',
            ),
          ),
          const SizedBox(height: 18),
          _SectionLabel(_text('Apariencia', 'Appearance')),
          _SettingsCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _text('Tema de la aplicación', 'App theme'),
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                Text(
                  _text(
                    'El modo del sistema cambia automáticamente con tu teléfono.',
                    'System mode follows your phone automatically.',
                  ),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: 16),
                _ThemeSelector(
                  initialMode: themeMode,
                  isSpanish: _es,
                  onChanged: onThemeModeChanged,
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          _SectionLabel(_text('Idioma', 'Language')),
          _SettingsCard(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: _LanguageSelector(
              initialLocale: locale,
              onChanged: onLocaleChanged,
            ),
          ),
          const SizedBox(height: 18),
          _SectionLabel(_text('Información y privacidad', 'Info and privacy')),
          _SettingsCard(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Column(
              children: [
                _ActionTile(
                  title: strings.text('termsOfService'),
                  icon: Icons.description_outlined,
                  onTap: () => _openDocument(
                    context,
                    contentRepository.fetchTermsOfService(locale.languageCode),
                  ),
                ),
                _ActionTile(
                  title: strings.text('privacyPolicy'),
                  icon: Icons.privacy_tip_outlined,
                  onTap: () => _openDocument(
                    context,
                    contentRepository.fetchPrivacyPolicy(locale.languageCode),
                  ),
                ),
                _ActionTile(
                  title: strings.text('aboutUs'),
                  icon: Icons.info_outline_rounded,
                  onTap: () => _openDocument(
                    context,
                    contentRepository.fetchAboutUs(locale.languageCode),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          FutureBuilder<SystemConfig>(
            future: configFuture,
            builder: (context, snapshot) {
              final config = snapshot.data;
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _SectionLabel(_text('Aplicación', 'Application')),
                  _SettingsCard(
                    child: Column(
                      children: [
                        _InfoLine(
                          label: strings.text('versionLabel'),
                          value: config?.appVersion.trim().isNotEmpty == true
                              ? config!.appVersion
                              : '-',
                        ),
                        _InfoLine(
                          label: _text('Estado', 'Status'),
                          value: snapshot.hasError
                              ? _text('Sin conexión', 'Offline')
                              : _text('Activa', 'Active'),
                        ),
                      ],
                    ),
                  ),
                  if (config?.hasSocialLinks == true) ...[
                    const SizedBox(height: 18),
                    _SectionLabel(strings.text('socialLinks')),
                    _SettingsCard(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Column(
                        children: [
                          if (config!.instagramLink.isNotEmpty)
                            _CopyLinkTile(
                              title: 'Instagram',
                              value: config.instagramLink,
                              onCopy: () => _copyLink(
                                  context, config.instagramLink, strings),
                            ),
                          if (config.facebookLink.isNotEmpty)
                            _CopyLinkTile(
                              title: 'Facebook',
                              value: config.facebookLink,
                              onCopy: () => _copyLink(
                                  context, config.facebookLink, strings),
                            ),
                          if (config.youtubeLink.isNotEmpty)
                            _CopyLinkTile(
                              title: 'YouTube',
                              value: config.youtubeLink,
                              onCopy: () => _copyLink(
                                  context, config.youtubeLink, strings),
                            ),
                        ],
                      ),
                    ),
                  ],
                ],
              );
            },
          ),
        ],
      ),
    );
  }

  void _openDocument(BuildContext context, Future<AppDocument> future) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => AppDocumentScreen(future: future),
      ),
    );
  }

  Future<void> _copyLink(
    BuildContext context,
    String value,
    AppStrings strings,
  ) async {
    await Clipboard.setData(ClipboardData(text: value));
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(strings.text('copiedMessage'))),
    );
  }
}

class _SettingsHero extends StatelessWidget {
  const _SettingsHero({required this.title, required this.subtitle});
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF2678D7), Color(0xFF55B8FF)],
          ),
          borderRadius: BorderRadius.circular(26),
        ),
        child: Row(
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.18),
                borderRadius: BorderRadius.circular(17),
              ),
              child: const Icon(Icons.tune_rounded, color: Colors.white),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 21,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      color: Colors.white70,
                      height: 1.35,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(4, 0, 4, 9),
        child: Text(
          text,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w800,
              ),
        ),
      );
}

class _SettingsCard extends StatelessWidget {
  const _SettingsCard({
    required this.child,
    this.padding = const EdgeInsets.all(18),
  });
  final Widget child;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) => Container(
        padding: padding,
        decoration: BoxDecoration(
          color: AppTheme.cardBackground(context),
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: AppTheme.borderColor(context)),
          boxShadow: Theme.of(context).brightness == Brightness.dark
              ? null
              : const [
                  BoxShadow(
                    color: Color(0x100E2741),
                    blurRadius: 18,
                    offset: Offset(0, 9),
                  ),
                ],
        ),
        child: child,
      );
}

class _ThemeSelector extends StatefulWidget {
  const _ThemeSelector({
    required this.initialMode,
    required this.isSpanish,
    required this.onChanged,
  });
  final ThemeMode initialMode;
  final bool isSpanish;
  final ValueChanged<ThemeMode> onChanged;

  @override
  State<_ThemeSelector> createState() => _ThemeSelectorState();
}

class _ThemeSelectorState extends State<_ThemeSelector> {
  late ThemeMode _mode = widget.initialMode;

  void _select(ThemeMode mode) {
    setState(() => _mode = mode);
    widget.onChanged(mode);
  }

  @override
  Widget build(BuildContext context) => LayoutBuilder(
        builder: (context, constraints) {
          final width = (constraints.maxWidth - 16) / 3;
          return Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _ThemeChoice(
                width: width,
                icon: Icons.brightness_auto_rounded,
                label: widget.isSpanish ? 'Sistema' : 'System',
                selected: _mode == ThemeMode.system,
                onTap: () => _select(ThemeMode.system),
              ),
              _ThemeChoice(
                width: width,
                icon: Icons.light_mode_rounded,
                label: widget.isSpanish ? 'Claro' : 'Light',
                selected: _mode == ThemeMode.light,
                onTap: () => _select(ThemeMode.light),
              ),
              _ThemeChoice(
                width: width,
                icon: Icons.dark_mode_rounded,
                label: widget.isSpanish ? 'Oscuro' : 'Dark',
                selected: _mode == ThemeMode.dark,
                onTap: () => _select(ThemeMode.dark),
              ),
            ],
          );
        },
      );
}

class _ThemeChoice extends StatelessWidget {
  const _ThemeChoice({
    required this.width,
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });
  final double width;
  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          width: width,
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 14),
          decoration: BoxDecoration(
            color: selected
                ? const Color(0xFF2B80D8)
                : Theme.of(context).colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(18),
          ),
          child: Column(
            children: [
              Icon(
                icon,
                color: selected
                    ? Colors.white
                    : Theme.of(context).colorScheme.onSurfaceVariant,
              ),
              const SizedBox(height: 7),
              FittedBox(
                child: Text(
                  label,
                  style: TextStyle(
                    color: selected
                        ? Colors.white
                        : Theme.of(context).colorScheme.onSurface,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ],
          ),
        ),
      );
}

class _LanguageSelector extends StatefulWidget {
  const _LanguageSelector({
    required this.initialLocale,
    required this.onChanged,
  });
  final Locale initialLocale;
  final ValueChanged<Locale> onChanged;

  @override
  State<_LanguageSelector> createState() => _LanguageSelectorState();
}

class _LanguageSelectorState extends State<_LanguageSelector> {
  late Locale _locale = widget.initialLocale;

  void _select(Locale locale) {
    setState(() => _locale = locale);
    widget.onChanged(locale);
  }

  @override
  Widget build(BuildContext context) => Column(
        children: [
          _LanguageTile(
            title: 'Español',
            subtitle: 'México',
            flag: '🇲🇽',
            selected: _locale.languageCode == 'es',
            onTap: () => _select(const Locale('es')),
          ),
          _LanguageTile(
            title: 'English',
            subtitle: 'United States',
            flag: '🇺🇸',
            selected: _locale.languageCode == 'en',
            onTap: () => _select(const Locale('en')),
          ),
        ],
      );
}

class _LanguageTile extends StatelessWidget {
  const _LanguageTile({
    required this.title,
    required this.subtitle,
    required this.flag,
    required this.selected,
    required this.onTap,
  });
  final String title;
  final String subtitle;
  final String flag;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => ListTile(
        onTap: onTap,
        leading: Text(flag, style: const TextStyle(fontSize: 26)),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w800)),
        subtitle: Text(subtitle),
        trailing: Icon(
          selected ? Icons.check_circle_rounded : Icons.circle_outlined,
          color: selected
              ? Theme.of(context).colorScheme.primary
              : Theme.of(context).colorScheme.outline,
        ),
      );
}

class _ActionTile extends StatelessWidget {
  const _ActionTile({
    required this.title,
    required this.icon,
    required this.onTap,
  });
  final String title;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => ListTile(
        onTap: onTap,
        leading: Container(
          width: 42,
          height: 42,
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.primaryContainer,
            borderRadius: BorderRadius.circular(14),
          ),
          child: Icon(icon, color: Theme.of(context).colorScheme.primary),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
        trailing: const Icon(Icons.chevron_right_rounded),
      );
}

class _InfoLine extends StatelessWidget {
  const _InfoLine({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(
          children: [
            Expanded(child: Text(label)),
            Text(value, style: const TextStyle(fontWeight: FontWeight.w800)),
          ],
        ),
      );
}

class _CopyLinkTile extends StatelessWidget {
  const _CopyLinkTile({
    required this.title,
    required this.value,
    required this.onCopy,
  });
  final String title;
  final String value;
  final VoidCallback onCopy;

  @override
  Widget build(BuildContext context) => ListTile(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w800)),
        subtitle: Text(value, maxLines: 1, overflow: TextOverflow.ellipsis),
        trailing: IconButton(
          onPressed: onCopy,
          icon: const Icon(Icons.copy_rounded),
        ),
      );
}
