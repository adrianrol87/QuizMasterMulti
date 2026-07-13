import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'app_services.dart';
import '../core/ads/quiz_ad_service.dart';
import '../core/l10n/app_strings.dart';
import '../core/purchases/quiz_purchase_service.dart';
import '../core/theme/app_theme.dart';
import '../features/auth/data/auth_repository.dart';
import '../features/auth/models/app_user.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/config/models/system_config.dart';
import '../features/home/presentation/home_shell.dart';
import '../features/quiz/models/quiz_category.dart';
import '../features/quiz/presentation/quiz_zone_screen.dart';
import '../features/quiz/presentation/subcategory_screen.dart';

class QuizMasterApp extends StatefulWidget {
  const QuizMasterApp({super.key});

  static void bootstrap() {
    runApp(const QuizMasterApp());
  }

  @override
  State<QuizMasterApp> createState() => _QuizMasterAppState();
}

class _QuizMasterAppState extends State<QuizMasterApp> {
  final GlobalKey<NavigatorState> _navigatorKey = GlobalKey<NavigatorState>();
  static const _testingUser = AppUser(
    id: 'testing-user',
    firebaseId: 'testing-user',
    name: 'Adrian',
    email: 'testing@quizmaster.local',
    mobile: '',
    profileUrl: '',
    loginType: 'testing',
    coins: 0,
    score: 0,
    rank: 0,
  );
  Locale _locale = AppStrings.supportedLocales.first;
  late final AppServices _services = AppServices.create();
  AppUser? _currentUser;
  bool _isBootstrappingUser = true;
  Map<String, String>? _pendingNotificationPayload;

  @override
  void initState() {
    super.initState();
    _services.pushNotificationService.initialize();
    _services.pushNotificationService.notificationTapStream.listen(
      _handleNotificationPayload,
    );
    _configureAds();
    _bootstrapUser();
  }

  @override
  void dispose() {
    _services.pushNotificationService.dispose();
    super.dispose();
  }

  void _changeLocale(Locale locale) {
    setState(() {
      _locale = locale;
    });
  }

  Future<void> _configureAds() async {
    try {
      final config = await _services.systemConfigRepository.fetchSystemConfig();
      await QuizAdService.instance.configure(config);
    } catch (_) {
      // Ads stay disabled if remote config is unavailable.
    }
  }

  Future<void> _bootstrapUser() async {
    final user = await _services.authRepository.restoreSession();
    await _syncPurchasesForUser(user);
    if (!mounted) {
      return;
    }
    setState(() {
      _currentUser = user;
      _isBootstrappingUser = false;
    });
    if (user != null) {
      _services.pushNotificationService.syncUser(user);
      _flushPendingNotification();
    }
  }

  Future<void> _signIn({
    required String email,
    required String password,
  }) async {
    final user = await _services.authRepository.signIn(
      email: email,
      password: password,
    );
    await _syncPurchasesForUser(user);
    if (!mounted) {
      return;
    }
    setState(() {
      _currentUser = user;
    });
    await _services.pushNotificationService.syncUser(user);
    await _flushPendingNotification();
  }

  Future<void> _signUp({
    required String name,
    required String email,
    required String password,
    required String mobile,
  }) async {
    final user = await _services.authRepository.signUp(
      name: name,
      email: email,
      password: password,
      mobile: mobile,
    );
    await _syncPurchasesForUser(user);
    if (!mounted) {
      return;
    }
    setState(() {
      _currentUser = user;
    });
    await _services.pushNotificationService.syncUser(user);
    await _flushPendingNotification();
  }

  Future<void> _signInWithGoogle() async {
    final user = await _services.authRepository.signInWithGoogle();
    await _syncPurchasesForUser(user);
    if (!mounted) {
      return;
    }
    setState(() {
      _currentUser = user;
    });
    await _services.pushNotificationService.syncUser(user);
    await _flushPendingNotification();
  }

  Future<void> _enterTestingMode() async {
    if (!mounted) {
      return;
    }
    setState(() {
      _currentUser = _testingUser;
    });
    await _syncPurchasesForUser(_testingUser);
  }

  Future<void> _signOut() async {
    await _services.authRepository.signOut();
    await QuizPurchaseService.instance.logOut();
    QuizAdService.instance.setAdsRemoved(false);
    if (!mounted) {
      return;
    }
    setState(() {
      _currentUser = null;
    });
    _services.pushNotificationService.clearUser();
  }

  void _handleUserUpdated(AppUser user) {
    setState(() {
      _currentUser = user;
    });
    _services.pushNotificationService.syncUser(user);
    _syncPurchasesForUser(user);
  }

  Future<void> _syncPurchasesForUser(AppUser? user) async {
    if (user == null || user.id.isEmpty) {
      QuizAdService.instance.setAdsRemoved(false);
      return;
    }

    try {
      final adsRemoved =
          await QuizPurchaseService.instance.configureForUser(user.id);
      QuizAdService.instance.setAdsRemoved(adsRemoved);
    } catch (_) {
      QuizAdService.instance.setAdsRemoved(false);
    }
  }

  Future<void> _handleNotificationPayload(Map<String, String> payload) async {
    if (_currentUser == null) {
      _pendingNotificationPayload = payload;
      return;
    }

    final navigator = _navigatorKey.currentState;
    if (navigator == null) {
      _pendingNotificationPayload = payload;
      return;
    }

    final type = (payload['type'] ?? 'default').trim().toLowerCase();
    final typeId = (payload['type_id'] ?? '').trim();

    if (type == 'category' && typeId.isNotEmpty) {
      final category = await _findCategoryById(typeId);
      if (category == null) {
        navigator.pushNamed(QuizZoneScreen.routeName);
        return;
      }

        navigator.push(
        MaterialPageRoute<void>(
          builder: (_) => SubcategoryScreen(
            locale: _locale,
            category: category,
            quizRepository: _services.quizRepository,
            quizResultRepository: _services.quizResultRepository,
            currentUser: _currentUser,
            authRepository: _services.authRepository,
            onUserUpdated: _handleUserUpdated,
          ),
        ),
      );
      return;
    }

    navigator.popUntil((route) => route.isFirst);
  }

  Future<void> _flushPendingNotification() async {
    final payload = _pendingNotificationPayload;
    if (payload == null) {
      return;
    }

    _pendingNotificationPayload = null;
    await _handleNotificationPayload(payload);
  }

  Future<QuizCategory?> _findCategoryById(String categoryId) async {
    try {
      final config = await _services.systemConfigRepository.fetchSystemConfig();
      final languageId = config.languageMode
          ? (_locale.languageCode == 'es' ? '1' : '2')
          : null;
      final categories = await _services.quizRepository.fetchCategories(
        type: 1,
        languageId: languageId,
        userId: _currentUser?.id,
      );
      for (final category in categories) {
        if (category.id == categoryId) {
          return category;
        }
      }
    } catch (_) {}
    return null;
  }

  @override
  Widget build(BuildContext context) {
    if (_isBootstrappingUser) {
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        home: const Scaffold(
          body: Center(
            child: CircularProgressIndicator(),
          ),
        ),
      );
    }

    return MaterialApp(
      navigatorKey: _navigatorKey,
      title: 'QuizMaster Flutter',
      debugShowCheckedModeBanner: false,
      locale: _locale,
      supportedLocales: AppStrings.supportedLocales,
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ],
      theme: AppTheme.light(),
      home: _currentUser == null
          ? LoginScreen(
              locale: _locale,
              onLocaleChanged: _changeLocale,
              onLogin: _signIn,
              onSignUp: _signUp,
              onGoogleLogin: _signInWithGoogle,
              onBypassLogin: _enterTestingMode,
              authRepository: _services.authRepository,
              systemConfigRepository: _services.systemConfigRepository,
              appContentRepository: _services.appContentRepository,
            )
          : HomeShell(
              locale: _locale,
              onLocaleChanged: _changeLocale,
              currentUser: _currentUser!,
              authRepository: _services.authRepository,
              onLogout: _signOut,
              onUserUpdated: _handleUserUpdated,
              userStatisticsRepository: _services.userStatisticsRepository,
              leaderboardRepository: _services.leaderboardRepository,
              systemConfigRepository: _services.systemConfigRepository,
              appContentRepository: _services.appContentRepository,
              quizRepository: _services.quizRepository,
              quizResultRepository: _services.quizResultRepository,
            ),
      routes: {
        LoginScreen.routeName: (_) => LoginScreen(
              locale: _locale,
              onLocaleChanged: _changeLocale,
              onLogin: _signIn,
              onSignUp: _signUp,
              onGoogleLogin: _signInWithGoogle,
              onBypassLogin: _enterTestingMode,
              authRepository: _services.authRepository,
              systemConfigRepository: _services.systemConfigRepository,
              appContentRepository: _services.appContentRepository,
            ),
        QuizZoneScreen.routeName: (_) => QuizZoneScreen(
              locale: _locale,
              systemConfigRepository: _services.systemConfigRepository,
              quizRepository: _services.quizRepository,
              quizResultRepository: _services.quizResultRepository,
              currentUser: _currentUser,
              authRepository: _services.authRepository,
              onUserUpdated: _currentUser == null ? null : _handleUserUpdated,
            ),
      },
    );
  }
}
