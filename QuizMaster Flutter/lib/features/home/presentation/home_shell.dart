import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/ads/quiz_ad_service.dart';
import '../../../core/config/backend_config.dart';
import '../../../core/l10n/app_strings.dart';
import '../../../core/network/php_api_client.dart';
import '../../../core/notifications/push_notification_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/data/leaderboard_repository.dart';
import '../../auth/data/user_statistics_repository.dart';
import '../../auth/models/app_user.dart';
import '../../auth/presentation/leaderboard_screen.dart';
import '../../auth/presentation/profile_screen.dart';
import '../../auth/presentation/user_statistics_screen.dart';
import '../../bookmarks/data/bookmark_repository.dart';
import '../../bookmarks/presentation/bookmarks_screen.dart';
import '../../config/data/app_content_repository.dart';
import '../../config/data/mock_system_config_repository.dart';
import '../../config/data/mock_app_content_repository.dart';
import '../../config/models/system_config.dart';
import '../../config/presentation/app_document_screen.dart';
import '../../config/presentation/app_settings_screen.dart';
import '../../notifications/data/notification_preferences_repository.dart';
import '../../notifications/presentation/notification_settings_screen.dart';
import '../../quiz/data/mock_quiz_repository.dart';
import '../../quiz/data/quiz_result_repository.dart';
import '../../referrals/data/referral_repository.dart';
import '../../referrals/presentation/invite_friends_screen.dart';
import '../../shop/presentation/remove_ads_screen.dart';
import '../data/home_bootstrap_repository.dart';
import '../models/home_bootstrap_data.dart';
import 'widgets/dashboard_sections.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({
    super.key,
    required this.locale,
    required this.onLocaleChanged,
    required this.currentUser,
    required this.authRepository,
    required this.onLogout,
    required this.onUserUpdated,
    this.userStatisticsRepository = const MockUserStatisticsRepository(),
    this.leaderboardRepository = const MockLeaderboardRepository(),
    this.systemConfigRepository = const MockSystemConfigRepository(),
    this.appContentRepository = const MockAppContentRepository(),
    this.quizRepository = const MockQuizRepository(),
    this.quizResultRepository = const MockQuizResultRepository(),
    required this.pushNotificationService,
    required this.themeMode,
    required this.onThemeModeChanged,
  });

  final Locale locale;
  final ValueChanged<Locale> onLocaleChanged;
  final AppUser currentUser;
  final AuthRepository authRepository;
  final Future<void> Function() onLogout;
  final ValueChanged<AppUser> onUserUpdated;
  final UserStatisticsRepository userStatisticsRepository;
  final LeaderboardRepository leaderboardRepository;
  final SystemConfigRepository systemConfigRepository;
  final AppContentRepository appContentRepository;
  final QuizRepository quizRepository;
  final QuizResultRepository quizResultRepository;
  final PushNotificationService pushNotificationService;
  final ThemeMode themeMode;
  final ValueChanged<ThemeMode> onThemeModeChanged;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  late HomeBootstrapRepository _repository;
  late Future<HomeBootstrapData> _future;
  BannerAd? _bannerAd;
  bool _isBannerReady = false;

  @override
  void initState() {
    super.initState();
    _repository = HomeBootstrapRepository(
      systemConfigRepository: widget.systemConfigRepository,
      quizRepository: widget.quizRepository,
    );
    _future = _load();
    _prepareBanner();
  }

  @override
  void dispose() {
    _bannerAd?.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant HomeShell oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.locale != widget.locale ||
        oldWidget.currentUser.id != widget.currentUser.id ||
        oldWidget.currentUser.coins != widget.currentUser.coins ||
        oldWidget.currentUser.score != widget.currentUser.score ||
        oldWidget.currentUser.rank != widget.currentUser.rank ||
        oldWidget.systemConfigRepository != widget.systemConfigRepository ||
        oldWidget.quizRepository != widget.quizRepository) {
      _repository = HomeBootstrapRepository(
        systemConfigRepository: widget.systemConfigRepository,
        quizRepository: widget.quizRepository,
      );
      _future = _load();
    }
  }

  Future<HomeBootstrapData> _load() async {
    final languageId = widget.locale.languageCode == 'es' ? '1' : '2';
    final data = await _repository.load(
      languageId: languageId,
      userId: widget.currentUser.id,
    );
    await QuizAdService.instance.configure(data.systemConfig);
    if (mounted) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _prepareBanner();
        }
      });
    }
    return data;
  }

  Future<void> _refreshHome() async {
    setState(() {
      _future = _load();
    });
    await _future;
  }

  void _prepareBanner() {
    if (QuizAdService.instance.adsRemoved || _bannerAd != null) {
      return;
    }

    _bannerAd = QuizAdService.instance.createQuestionBanner(
      onLoaded: () {
        if (!mounted) {
          return;
        }
        setState(() {
          _isBannerReady = true;
        });
      },
      onFailed: () {
        if (!mounted) {
          return;
        }
        setState(() {
          _bannerAd = null;
          _isBannerReady = false;
        });
      },
    );
  }

  Future<void> _openRemoveAds() async {
    final removed = await RemoveAdsScreen.show(
      context,
      locale: widget.locale,
    );
    if (!mounted || !removed) {
      return;
    }

    _disposeBanner();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          AppStrings(widget.locale).text('removeAdsPurchased'),
        ),
      ),
    );
  }

  Future<void> _handleGameReturned() async {
    if (QuizAdService.instance.adsRemoved) {
      if (mounted && _bannerAd != null) {
        _disposeBanner();
      }
      return;
    }
    await QuizAdService.instance.maybeShowInterstitialAfterGameReturn();
  }

  void _disposeBanner() {
    _bannerAd?.dispose();
    if (!mounted) {
      _bannerAd = null;
      _isBannerReady = false;
      return;
    }
    setState(() {
      _bannerAd = null;
      _isBannerReady = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(widget.locale);
    final theme = Theme.of(context);

    return Scaffold(
      drawer: _MainDrawer(
        strings: strings,
        currentUser: widget.currentUser,
        authRepository: widget.authRepository,
        locale: widget.locale,
        onLogout: widget.onLogout,
        onUserUpdated: widget.onUserUpdated,
        userStatisticsRepository: widget.userStatisticsRepository,
        leaderboardRepository: widget.leaderboardRepository,
        appContentRepository: widget.appContentRepository,
        systemConfigRepository: widget.systemConfigRepository,
        pushNotificationService: widget.pushNotificationService,
        themeMode: widget.themeMode,
        onThemeModeChanged: widget.onThemeModeChanged,
        onLocaleChanged: widget.onLocaleChanged,
      ),
      backgroundColor: AppTheme.pageBackground(context),
      body: SafeArea(
        child: Column(
          children: [
            _FixedBlueHeader(
              strings: strings,
              theme: theme,
              locale: widget.locale,
              onLocaleChanged: widget.onLocaleChanged,
              currentUser: widget.currentUser,
              onStatsTap: () => _openUserStats(context),
              adsRemoved: QuizAdService.instance.adsRemoved,
              onRemoveAdsTap: _openRemoveAds,
            ),
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: AppTheme.pageBackground(context),
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(28),
                  ),
                ),
                child: FutureBuilder<HomeBootstrapData>(
                  future: _future,
                  builder: (context, snapshot) {
                    if (snapshot.hasError) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(24),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.cloud_off_rounded,
                                size: 40,
                                color: AppTheme.mutedTextColor(context),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                widget.locale.languageCode == 'es'
                                    ? 'No se pudo cargar.'
                                    : 'Could not load.',
                                style: theme.textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const SizedBox(height: 16),
                              FilledButton(
                                onPressed: () {
                                  setState(() {
                                    _future = _load();
                                  });
                                },
                                child: Text(
                                  widget.locale.languageCode == 'es'
                                      ? 'Reintentar'
                                      : 'Retry',
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    if (!snapshot.hasData) {
                      return const Center(
                        child: CircularProgressIndicator(),
                      );
                    }

                    final data = snapshot.data!;
                    final hasVisibleModules = data.quizCategories.isNotEmpty ||
                        data.systemConfig.dailyQuizMode ||
                        data.systemConfig.trueFalseMode ||
                        data.systemConfig.spinMode ||
                        data.systemConfig.learningZoneMode ||
                        data.systemConfig.mathsQuizMode ||
                        data.systemConfig.battleGroupCategoryMode ||
                        data.systemConfig.battleRandomCategoryMode ||
                        data.systemConfig.contestMode;

                    if (data.systemConfig.appMaintenance) {
                      return _MaintenanceView(
                        title: strings.text('maintenanceTitle'),
                        message: data.systemConfig.appMaintenanceMessage.isEmpty
                            ? strings.text('maintenanceDefault')
                            : data.systemConfig.appMaintenanceMessage,
                      );
                    }

                    return RefreshIndicator(
                      onRefresh: _refreshHome,
                      child: ListView(
                        padding: const EdgeInsets.fromLTRB(24, 20, 24, 32),
                        children: [
                          if (!hasVisibleModules) ...[
                            _HomeEmptyState(
                              title: strings.text('emptyHomeTitle'),
                              message: strings.text('emptyHomeBody'),
                            ),
                            const SizedBox(height: 16),
                          ],
                          if (data.systemConfig.requiresForceUpdate(
                            BackendConfig.appVersion,
                          )) ...[
                            _ForceUpdateBanner(
                              strings: strings,
                              targetVersion: data.systemConfig.appVersion,
                              storeLink: data.systemConfig.preferredStoreLink,
                            ),
                            const SizedBox(height: 16),
                          ],
                          DashboardSections(
                            strings: strings,
                            locale: widget.locale,
                            systemConfig: data.systemConfig,
                            categories: data.quizCategories,
                            quizRepository: widget.quizRepository,
                            quizResultRepository: widget.quizResultRepository,
                            currentUser: widget.currentUser,
                            authRepository: widget.authRepository,
                            onUserUpdated: widget.onUserUpdated,
                            onGameReturned: _handleGameReturned,
                            inlineBanner: _bannerAd != null &&
                                    _isBannerReady &&
                                    !QuizAdService.instance.adsRemoved
                                ? SizedBox(
                                    width: _bannerAd!.size.width.toDouble(),
                                    height: _bannerAd!.size.height.toDouble(),
                                    child: AdWidget(ad: _bannerAd!),
                                  )
                                : null,
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _openUserStats(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => UserStatisticsScreen(
          locale: widget.locale,
          user: widget.currentUser,
          repository: widget.userStatisticsRepository,
        ),
      ),
    );
  }
}

class _MainDrawer extends StatelessWidget {
  const _MainDrawer({
    required this.strings,
    required this.currentUser,
    required this.authRepository,
    required this.locale,
    required this.onLogout,
    required this.onUserUpdated,
    required this.userStatisticsRepository,
    required this.leaderboardRepository,
    required this.appContentRepository,
    required this.systemConfigRepository,
    required this.pushNotificationService,
    required this.themeMode,
    required this.onThemeModeChanged,
    required this.onLocaleChanged,
  });

  final AppStrings strings;
  final AppUser currentUser;
  final AuthRepository authRepository;
  final Locale locale;
  final Future<void> Function() onLogout;
  final ValueChanged<AppUser> onUserUpdated;
  final UserStatisticsRepository userStatisticsRepository;
  final LeaderboardRepository leaderboardRepository;
  final AppContentRepository appContentRepository;
  final SystemConfigRepository systemConfigRepository;
  final PushNotificationService pushNotificationService;
  final ThemeMode themeMode;
  final ValueChanged<ThemeMode> onThemeModeChanged;
  final ValueChanged<Locale> onLocaleChanged;

  void _showSoonMessage(BuildContext context, AppStrings strings) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(strings.text('featureComingSoon'))),
    );
  }

  Future<void> _copyText(
    BuildContext context,
    String text,
    AppStrings strings,
  ) async {
    if (text.trim().isEmpty) {
      _showSoonMessage(context, strings);
      return;
    }

    await Clipboard.setData(ClipboardData(text: text));
    if (!context.mounted) {
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(strings.text('copiedMessage'))),
    );
  }

  String _resolveMoreAppsUrl(SystemConfig? config) {
    if (config == null) {
      return '';
    }

    if (defaultTargetPlatform == TargetPlatform.iOS) {
      if (config.iosMoreAppsLink.trim().isNotEmpty) {
        return config.iosMoreAppsLink.trim();
      }
      return config.iosAppLink.trim();
    }

    if (config.moreAppsLink.trim().isNotEmpty) {
      return config.moreAppsLink.trim();
    }
    return config.appLink.trim();
  }

  Future<void> _openMoreApps(
    BuildContext context,
    SystemConfig? config,
    AppStrings strings,
  ) async {
    final url = _resolveMoreAppsUrl(config);
    if (url.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(strings.text('noLinksConfigured'))),
      );
      return;
    }

    final uri = Uri.tryParse(url);
    if (uri == null) {
      _showSoonMessage(context, strings);
      return;
    }

    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!context.mounted) {
      return;
    }
    if (!opened) {
      _showSoonMessage(context, strings);
    }
  }

  Widget _buildSectionTitle(String label) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(22, 14, 22, 8),
      child: Text(
        label.toUpperCase(),
        style: const TextStyle(
          color: Color(0xFF7E8DA0),
          fontSize: 11,
          fontWeight: FontWeight.w800,
          letterSpacing: 1.1,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Drawer(
      child: SafeArea(
        child: FutureBuilder<SystemConfig>(
          future: systemConfigRepository.fetchSystemConfig(),
          builder: (context, snapshot) {
            final config = snapshot.data;
            return ListView(
              padding: EdgeInsets.zero,
              children: [
                Container(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 22),
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        Color(0xFF2B6FB6),
                        Color(0xFF55B8FF),
                      ],
                    ),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Container(
                        width: 64,
                        height: 64,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(18),
                          boxShadow: const [
                            BoxShadow(
                              color: Color(0x220E2741),
                              blurRadius: 14,
                              offset: Offset(0, 8),
                            ),
                          ],
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(8),
                          child: Image.asset('assets/images/app_icon.png'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'QuizMaster',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 17,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              config?.appVersion.isEmpty ?? true
                                  ? 'Flutter Edition'
                                  : 'v${config!.appVersion}',
                              style: const TextStyle(
                                color: Colors.white70,
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                _buildSectionTitle(strings.text('profile')),
                _DrawerItem(
                  icon: Icons.person_rounded,
                  label: strings.text('profile'),
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => ProfileScreen(
                          locale: locale,
                          user: currentUser,
                          authRepository: authRepository,
                          onProfileSaved: onUserUpdated,
                          onAccountDeleted: onLogout,
                        ),
                      ),
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.emoji_events_rounded,
                  label: strings.text('leaderboard'),
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => LeaderboardScreen(
                          locale: locale,
                          currentUser: currentUser,
                          repository: leaderboardRepository,
                        ),
                      ),
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.bar_chart_rounded,
                  label: strings.text('playerStats'),
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => UserStatisticsScreen(
                          locale: locale,
                          user: currentUser,
                          repository: userStatisticsRepository,
                        ),
                      ),
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.bookmark_rounded,
                  label: strings.text('bookmarks'),
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => BookmarksScreen(
                          locale: locale,
                          currentUser: currentUser,
                          repository: RemoteBookmarkRepository(
                            apiClient: PhpApiClient(),
                          ),
                        ),
                      ),
                    );
                  },
                ),
                const Divider(height: 20),
                _buildSectionTitle(strings.text('settings')),
                _DrawerItem(
                  icon: Icons.notifications_rounded,
                  label: strings.text('notifications'),
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => NotificationSettingsScreen(
                          locale: locale,
                          currentUser: currentUser,
                          repository: NotificationPreferencesRepository(
                            apiClient: PhpApiClient(),
                          ),
                          pushService: pushNotificationService,
                        ),
                      ),
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.group_add_rounded,
                  label: strings.text('inviteFriends'),
                  onTap: () {
                    if (config == null) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(
                            locale.languageCode == 'es'
                                ? 'Espera un momento e intenta de nuevo.'
                                : 'Wait a moment and try again.',
                          ),
                        ),
                      );
                      return;
                    }
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => InviteFriendsScreen(
                          locale: locale,
                          currentUser: currentUser,
                          config: config,
                          repository: RemoteReferralRepository(
                            apiClient: PhpApiClient(),
                          ),
                        ),
                      ),
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.menu_book_rounded,
                  label: strings.text('instructions'),
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => AppDocumentScreen(
                          future: appContentRepository.fetchInstructions(
                            locale.languageCode,
                          ),
                        ),
                      ),
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.settings_rounded,
                  label: strings.text('settings'),
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => AppSettingsScreen(
                          locale: locale,
                          onLocaleChanged: onLocaleChanged,
                          themeMode: themeMode,
                          onThemeModeChanged: onThemeModeChanged,
                          systemConfigFuture:
                              systemConfigRepository.fetchSystemConfig(),
                          contentRepository: appContentRepository,
                        ),
                      ),
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.share_rounded,
                  label: strings.text('shareApp'),
                  onTap: () async {
                    Navigator.of(context).pop();
                    await _copyText(
                      context,
                      config?.shareAppText ?? '',
                      strings,
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.apps_rounded,
                  label: strings.text('moreAppsAction'),
                  onTap: () async {
                    Navigator.of(context).pop();
                    await _openMoreApps(context, config, strings);
                  },
                ),
                const Divider(height: 20),
                _buildSectionTitle(strings.text('legalSection')),
                _DrawerItem(
                  icon: Icons.info_outline_rounded,
                  label: strings.text('aboutUs'),
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => AppDocumentScreen(
                          future: appContentRepository.fetchAboutUs(
                            locale.languageCode,
                          ),
                        ),
                      ),
                    );
                  },
                ),
                const Divider(height: 20),
                _DrawerItem(
                  icon: Icons.logout_rounded,
                  label: strings.text('logout'),
                  onTap: () async {
                    Navigator.of(context).pop();
                    await onLogout();
                  },
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _DrawerItem extends StatelessWidget {
  const _DrawerItem({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: Theme.of(context).colorScheme.onSurface),
      title: Text(
        label,
        style: const TextStyle(
          fontWeight: FontWeight.w700,
        ),
      ),
      onTap: onTap,
    );
  }
}

class _FixedBlueHeader extends StatelessWidget {
  const _FixedBlueHeader({
    required this.strings,
    required this.theme,
    required this.locale,
    required this.onLocaleChanged,
    required this.currentUser,
    required this.onStatsTap,
    required this.adsRemoved,
    required this.onRemoveAdsTap,
  });

  final AppStrings strings;
  final ThemeData theme;
  final Locale locale;
  final ValueChanged<Locale> onLocaleChanged;
  final AppUser currentUser;
  final VoidCallback onStatsTap;
  final bool adsRemoved;
  final VoidCallback onRemoveAdsTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 14, 20, 18),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Color(0xFF2B6FB6),
            Color(0xFF3E8FDD),
            Color(0xFF55B8FF),
          ],
        ),
        borderRadius: BorderRadius.vertical(
          bottom: Radius.circular(26),
        ),
      ),
      child: Builder(
        builder: (context) => Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                IconButton(
                  onPressed: () => Scaffold.of(context).openDrawer(),
                  icon: const Icon(
                    Icons.menu_rounded,
                    color: Colors.white,
                    size: 22,
                  ),
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.white.withValues(alpha: 0.14),
                    minimumSize: const Size(40, 40),
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  strings.text('appTitle'),
                  style: theme.textTheme.titleLarge?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 20,
                  ),
                ),
                const Spacer(),
                IconButton(
                  onPressed: adsRemoved ? null : onRemoveAdsTap,
                  tooltip: locale.languageCode == 'es'
                      ? 'Eliminar anuncios'
                      : 'Remove ads',
                  icon: Opacity(
                    opacity: adsRemoved ? 0.45 : 1,
                    child: Image.asset(
                      'assets/images/sopa/NoAds.png',
                      width: 24,
                      height: 24,
                      fit: BoxFit.contain,
                    ),
                  ),
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.white.withValues(alpha: 0.14),
                    minimumSize: const Size(40, 40),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  onPressed: () => _showLanguageSheet(context),
                  icon: const Icon(
                    Icons.translate_rounded,
                    color: Colors.white,
                    size: 20,
                  ),
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.white.withValues(alpha: 0.14),
                    minimumSize: const Size(40, 40),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.75),
                      width: 2,
                    ),
                    image: currentUser.profileUrl.isNotEmpty
                        ? DecorationImage(
                            image: NetworkImage(currentUser.profileUrl),
                            fit: BoxFit.cover,
                          )
                        : const DecorationImage(
                            image: AssetImage('assets/images/user.png'),
                            fit: BoxFit.cover,
                          ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    currentUser.name.isEmpty
                        ? strings.text('guestUser')
                        : 'Hello, ${currentUser.name}',
                    style: theme.textTheme.titleLarge?.copyWith(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _TopStatChip(
                  assetPath: 'assets/images/coins.png',
                  value: '${currentUser.coins}',
                  onTap: onStatsTap,
                ),
                _TopStatChip(
                  icon: Icons.emoji_events_rounded,
                  value: '${currentUser.score}',
                  onTap: onStatsTap,
                ),
                _TopStatChip(
                  icon: Icons.leaderboard_rounded,
                  value: '${currentUser.rank}',
                  onTap: onStatsTap,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _showLanguageSheet(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: AppTheme.cardBackground(context),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  strings.text('languageLabel'),
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: AppTheme.textColor(context),
                  ),
                ),
                const SizedBox(height: 14),
                _LanguageOption(
                  label: strings.text('english'),
                  selected: locale.languageCode == 'en',
                  onTap: () {
                    onLocaleChanged(const Locale('en'));
                    Navigator.of(context).pop();
                  },
                ),
                const SizedBox(height: 10),
                _LanguageOption(
                  label: strings.text('spanish'),
                  selected: locale.languageCode == 'es',
                  onTap: () {
                    onLocaleChanged(const Locale('es'));
                    Navigator.of(context).pop();
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _TopStatChip extends StatelessWidget {
  const _TopStatChip({
    required this.value,
    this.icon,
    this.assetPath,
    this.onTap,
  });

  final String value;
  final IconData? icon;
  final String? assetPath;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.14),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (assetPath != null)
              Image.asset(
                assetPath!,
                width: 16,
                height: 16,
                color: Colors.white,
              )
            else if (icon != null)
              Icon(icon, size: 16, color: Colors.white),
            const SizedBox(width: 6),
            Text(
              value,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LanguageOption extends StatelessWidget {
  const _LanguageOption({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: selected
              ? const Color(0xFF2B80D8).withValues(alpha: 0.18)
              : AppTheme.cardBackground(context),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: selected
                ? const Color(0xFF2B6FB6)
                : AppTheme.borderColor(context),
          ),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(
                label,
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: selected
                      ? const Color(0xFF2B6FB6)
                      : AppTheme.textColor(context),
                ),
              ),
            ),
            if (selected)
              const Icon(
                Icons.check_circle_rounded,
                color: Color(0xFF2B6FB6),
              ),
          ],
        ),
      ),
    );
  }
}

class _MaintenanceView extends StatelessWidget {
  const _MaintenanceView({
    required this.title,
    required this.message,
  });

  final String title;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: AppTheme.cardBackground(context),
            borderRadius: BorderRadius.circular(28),
            boxShadow: const [
              BoxShadow(
                color: Color(0x120E2741),
                blurRadius: 18,
                offset: Offset(0, 10),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(
                Icons.build_circle_outlined,
                size: 46,
                color: AppTheme.primary,
              ),
              const SizedBox(height: 14),
              Text(
                title,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
              const SizedBox(height: 10),
              Text(
                message,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: AppTheme.mutedTextColor(context),
                      height: 1.5,
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ForceUpdateBanner extends StatelessWidget {
  const _ForceUpdateBanner({
    required this.strings,
    required this.targetVersion,
    required this.storeLink,
  });

  final AppStrings strings;
  final String targetVersion;
  final String storeLink;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF4E9),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFFFC98E)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            strings.text('updateRequiredTitle'),
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: const Color(0xFF9D4D00),
                ),
          ),
          const SizedBox(height: 8),
          Text(
            '${strings.text('updateRequiredBody')} $targetVersion',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF9D4D00),
                  height: 1.45,
                ),
          ),
          if (storeLink.isNotEmpty) ...[
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () async {
                await Clipboard.setData(ClipboardData(text: storeLink));
                if (!context.mounted) {
                  return;
                }
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(strings.text('copiedMessage'))),
                );
              },
              icon: const Icon(Icons.copy_rounded),
              label: Text(strings.text('copyStoreLink')),
            ),
          ],
        ],
      ),
    );
  }
}

class _HomeEmptyState extends StatelessWidget {
  const _HomeEmptyState({
    required this.title,
    required this.message,
  });

  final String title;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: AppTheme.cardBackground(context),
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: AppTheme.borderColor(context)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0F12304A),
            blurRadius: 14,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        children: [
          const Icon(
            Icons.view_compact_alt_outlined,
            size: 38,
            color: Color(0xFF5F89B8),
          ),
          const SizedBox(height: 12),
          Text(
            title,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: AppTheme.textColor(context),
                ),
          ),
          const SizedBox(height: 8),
          Text(
            message,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppTheme.mutedTextColor(context),
                  height: 1.45,
                ),
          ),
        ],
      ),
    );
  }
}
