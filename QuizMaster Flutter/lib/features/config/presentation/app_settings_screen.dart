import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/l10n/app_strings.dart';
import '../../../core/theme/app_theme.dart';
import '../data/app_content_repository.dart';
import '../models/system_config.dart';
import 'app_document_screen.dart';

class AppSettingsScreen extends StatelessWidget {
  const AppSettingsScreen({
    super.key,
    required this.locale,
    required this.contentRepository,
    this.systemConfig,
    this.systemConfigFuture,
  });

  final Locale locale;
  final SystemConfig? systemConfig;
  final Future<SystemConfig>? systemConfigFuture;
  final AppContentRepository contentRepository;

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(locale);

    return Scaffold(
      backgroundColor: const Color(0xFFF4F7FB),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.ink,
        title: Text(
          strings.text('settings'),
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
              ),
        ),
      ),
      body: FutureBuilder<SystemConfig>(
        future: systemConfigFuture ?? Future.value(systemConfig!),
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text('${snapshot.error}', textAlign: TextAlign.center),
              ),
            );
          }

          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final loadedConfig = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
          _InfoCard(
            title: strings.text('appStatus'),
            children: [
              _InfoLine(
                label: strings.text('versionLabel'),
                value: loadedConfig.appVersion.isEmpty ? '-' : loadedConfig.appVersion,
              ),
              _InfoLine(
                label: strings.text('languageLabel'),
                value: loadedConfig.languageMode
                    ? strings.text('enabledLabel')
                    : strings.text('disabledLabel'),
              ),
              _InfoLine(
                label: strings.text('maintenanceMode'),
                value: loadedConfig.appMaintenance
                    ? strings.text('enabledLabel')
                    : strings.text('disabledLabel'),
              ),
            ],
          ),
          const SizedBox(height: 14),
          _InfoCard(
            title: strings.text('legalSection'),
            children: [
              _ActionTile(
                title: strings.text('termsOfService'),
                icon: Icons.description_outlined,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => AppDocumentScreen(
                        future: contentRepository.fetchTermsOfService(
                          locale.languageCode,
                        ),
                      ),
                    ),
                  );
                },
              ),
              _ActionTile(
                title: strings.text('privacyPolicy'),
                icon: Icons.privacy_tip_outlined,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => AppDocumentScreen(
                        future: contentRepository.fetchPrivacyPolicy(
                          locale.languageCode,
                        ),
                      ),
                    ),
                  );
                },
              ),
            ],
          ),
          if (loadedConfig.hasSocialLinks) ...[
            const SizedBox(height: 14),
            _InfoCard(
              title: strings.text('socialLinks'),
              children: [
                if (loadedConfig.instagramLink.isNotEmpty)
                  _CopyableLinkTile(
                    title: 'Instagram',
                    value: loadedConfig.instagramLink,
                    onCopy: () => _copyText(
                      context,
                      loadedConfig.instagramLink,
                      strings.text('copiedMessage'),
                    ),
                  ),
                if (loadedConfig.facebookLink.isNotEmpty)
                  _CopyableLinkTile(
                    title: 'Facebook',
                    value: loadedConfig.facebookLink,
                    onCopy: () => _copyText(
                      context,
                      loadedConfig.facebookLink,
                      strings.text('copiedMessage'),
                    ),
                  ),
                if (loadedConfig.youtubeLink.isNotEmpty)
                  _CopyableLinkTile(
                    title: 'YouTube',
                    value: loadedConfig.youtubeLink,
                    onCopy: () => _copyText(
                      context,
                      loadedConfig.youtubeLink,
                      strings.text('copiedMessage'),
                    ),
                  ),
              ],
            ),
          ],
            ],
          );
        },
      ),
    );
  }

  Future<void> _copyText(
    BuildContext context,
    String text,
    String message,
  ) async {
    await Clipboard.setData(ClipboardData(text: text));
    if (!context.mounted) {
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({
    required this.title,
    required this.children,
  });

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: const [
          BoxShadow(
            color: Color(0x120E2741),
            blurRadius: 18,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
          ),
          const SizedBox(height: 14),
          ...children,
        ],
      ),
    );
  }
}

class _InfoLine extends StatelessWidget {
  const _InfoLine({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                color: Color(0xFF41576E),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(
                color: AppTheme.ink,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
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
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon, color: AppTheme.primary),
      title: Text(
        title,
        style: const TextStyle(fontWeight: FontWeight.w700),
      ),
      trailing: const Icon(Icons.chevron_right_rounded),
      onTap: onTap,
    );
  }
}

class _CopyableLinkTile extends StatelessWidget {
  const _CopyableLinkTile({
    required this.title,
    required this.value,
    required this.onCopy,
  });

  final String title;
  final String value;
  final VoidCallback onCopy;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: const Color(0xFFF1F6FB),
          borderRadius: BorderRadius.circular(18),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                color: AppTheme.ink,
              ),
            ),
            const SizedBox(height: 6),
            SelectableText(
              value,
              style: const TextStyle(
                color: Color(0xFF41576E),
                height: 1.4,
              ),
            ),
            const SizedBox(height: 10),
            OutlinedButton.icon(
              onPressed: onCopy,
              icon: const Icon(Icons.copy_rounded),
              label: const Text('Copy'),
            ),
          ],
        ),
      ),
    );
  }
}
