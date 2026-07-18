import 'dart:async';
import 'dart:math';

import 'package:characters/characters.dart';
import 'package:flutter/material.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';

import '../../../core/ads/quiz_ad_service.dart';
import '../../../core/l10n/app_strings.dart';
import '../../../core/network/php_api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/models/app_user.dart';
import '../../quiz/data/quiz_result_repository.dart';
import '../../shop/presentation/coin_shop_sheet.dart';
import '../../shop/presentation/remove_ads_screen.dart';
import '../data/word_search_repository.dart';

class WordSearchPreviewScreen extends StatelessWidget {
  const WordSearchPreviewScreen({
    super.key,
    required this.locale,
    required this.coins,
    this.currentUser,
    this.onUserUpdated,
    this.wordSearchRepository,
    this.quizResultRepository = const MockQuizResultRepository(),
    this.initialCategoryId,
    this.initialCategory,
  });

  final Locale locale;
  final String coins;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;
  final WordSearchRepository? wordSearchRepository;
  final QuizResultRepository quizResultRepository;
  final String? initialCategoryId;
  final WordSearchCategory? initialCategory;

  @override
  Widget build(BuildContext context) {
    final repository = wordSearchRepository ??
        RemoteWordSearchRepository(apiClient: PhpApiClient());

    if (initialCategory != null) {
      return _WordSearchLevelsScreen(
        locale: locale,
        category: initialCategory!,
        currentUser: currentUser,
        onUserUpdated: onUserUpdated,
        repository: repository,
        quizResultRepository: quizResultRepository,
      );
    }

    return _WordSearchCategoriesScreen(
      locale: locale,
      currentUser: currentUser,
      onUserUpdated: onUserUpdated,
      repository: repository,
      quizResultRepository: quizResultRepository,
      initialCategoryId: initialCategoryId,
    );
  }
}

class _WordSearchCategoriesScreen extends StatefulWidget {
  const _WordSearchCategoriesScreen({
    required this.locale,
    required this.currentUser,
    required this.onUserUpdated,
    required this.repository,
    required this.quizResultRepository,
    required this.initialCategoryId,
  });

  final Locale locale;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;
  final WordSearchRepository repository;
  final QuizResultRepository quizResultRepository;
  final String? initialCategoryId;

  @override
  State<_WordSearchCategoriesScreen> createState() =>
      _WordSearchCategoriesScreenState();
}

class _WordSearchCategoriesScreenState
    extends State<_WordSearchCategoriesScreen> {
  late Future<List<WordSearchCategory>> _future;
  late AppUser? _currentUser;
  String _query = '';
  bool _didOpenInitialCategory = false;

  String get _languageId => widget.locale.languageCode == 'es' ? '1' : '2';
  bool get _isSpanish => widget.locale.languageCode == 'es';
  String _friendlyError(Object error) =>
      _wordSearchFriendlyError(error, _isSpanish);

  @override
  void initState() {
    super.initState();
    _currentUser = widget.currentUser;
    _future = widget.repository.fetchCategories(
      languageId: _languageId,
      userId: _currentUser?.id,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F7FF),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.transparent,
        foregroundColor: AppTheme.ink,
        title: Text(_isSpanish ? 'Sopa de Letras' : 'Word Search'),
      ),
      body: FutureBuilder<List<WordSearchCategory>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return _MessageState(
              title: _isSpanish
                  ? 'No se pudieron cargar las categorias'
                  : 'Could not load categories',
              subtitle: _friendlyError(snapshot.error!),
              onRetry: () {
                setState(() {
                  _future = widget.repository.fetchCategories(
                    languageId: _languageId,
                    userId: _currentUser?.id,
                  );
                });
              },
            );
          }

          final categories = snapshot.data ?? const <WordSearchCategory>[];
          if (categories.isEmpty) {
            return _MessageState(
              title: _isSpanish
                  ? 'No hay categorias de sopa de letras'
                  : 'No word search categories yet',
              subtitle: _isSpanish
                  ? 'Importa niveles desde el admin para empezar a probar este modo.'
                  : 'Import levels from the admin panel to start testing this mode.',
            );
          }

          final initialCategoryId = widget.initialCategoryId;
          if (!_didOpenInitialCategory &&
              initialCategoryId != null &&
              initialCategoryId.isNotEmpty) {
            final matchingCategory =
                categories.where((item) => item.id == initialCategoryId);
            if (matchingCategory.isNotEmpty) {
              _didOpenInitialCategory = true;
              WidgetsBinding.instance.addPostFrameCallback((_) async {
                if (!mounted) {
                  return;
                }
                await _openCategory(matchingCategory.first);
              });
            }
          }

          final filteredCategories = categories
              .where((item) =>
                  item.title.toLowerCase().contains(_query.toLowerCase()))
              .toList();

          return Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2EAF4)),
                  ),
                  child: TextField(
                    onChanged: (value) {
                      setState(() {
                        _query = value;
                      });
                    },
                    decoration: InputDecoration(
                      icon: const Icon(Icons.search_rounded),
                      hintText:
                          _isSpanish ? 'Buscar categoria' : 'Search category',
                      border: InputBorder.none,
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Expanded(
                  child: GridView.builder(
                    itemCount: filteredCategories.length,
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      mainAxisSpacing: 14,
                      crossAxisSpacing: 14,
                      childAspectRatio: 0.9,
                    ),
                    itemBuilder: (context, index) {
                      final category = filteredCategories[index];
                      return _CategoryCard(
                        category: category,
                        locale: widget.locale,
                        onTap: () => _handleCategoryTap(category),
                      );
                    },
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _handleCategoryTap(WordSearchCategory category) async {
    if (!category.isPremium || category.isPurchased) {
      await _openCategory(category);
      return;
    }

    final user = _currentUser;
    if (user == null || user.id.isEmpty) {
      _showSnack(_isSpanish
          ? 'Esta categoria premium todavia no esta desbloqueada.'
          : 'This premium category is not unlocked yet.');
      return;
    }

    if (user.coins < category.amount) {
      _showSnack(_isSpanish
          ? 'No tienes monedas suficientes todavia.'
          : 'You do not have enough coins yet.');
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(_isSpanish
            ? 'Desbloquear categoria premium'
            : 'Unlock premium category'),
        content: Text(
          (_isSpanish
                  ? 'Gasta {coins} monedas para desbloquear esta categoria y dejarla disponible para este usuario.'
                  : 'Spend {coins} coins to unlock this category and keep it available for this user.')
              .replaceFirst('{coins}', '${category.amount}'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text(_isSpanish ? 'Cancelar' : 'Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text(
              (_isSpanish
                      ? 'Desbloquear por {coins} monedas'
                      : 'Unlock for {coins} coins')
                  .replaceFirst('{coins}', '${category.amount}'),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      return;
    }

    await widget.repository.unlockPremiumCategory(
      userId: user.id,
      categoryId: category.id,
    );

    final updatedUser = user.copyWith(coins: user.coins - category.amount);
    setState(() {
      _currentUser = updatedUser;
      _future = widget.repository.fetchCategories(
        languageId: _languageId,
        userId: updatedUser.id,
      );
    });
    widget.onUserUpdated?.call(updatedUser);

    await _openCategory(
      WordSearchCategory(
        id: category.id,
        languageId: category.languageId,
        title: category.title,
        imageUrl: category.imageUrl,
        totalLevels: category.totalLevels,
        plan: category.plan,
        amount: category.amount,
        isPurchased: true,
      ),
    );
  }

  Future<void> _openCategory(WordSearchCategory category) async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _WordSearchLevelsScreen(
          locale: widget.locale,
          category: category,
          currentUser: _currentUser,
          onUserUpdated: widget.onUserUpdated,
          repository: widget.repository,
          quizResultRepository: widget.quizResultRepository,
        ),
      ),
    );
  }

  void _showSnack(String message) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }
}

class _WordSearchLevelsScreen extends StatefulWidget {
  const _WordSearchLevelsScreen({
    required this.locale,
    required this.category,
    required this.currentUser,
    required this.onUserUpdated,
    required this.repository,
    required this.quizResultRepository,
  });

  final Locale locale;
  final WordSearchCategory category;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;
  final WordSearchRepository repository;
  final QuizResultRepository quizResultRepository;

  @override
  State<_WordSearchLevelsScreen> createState() =>
      _WordSearchLevelsScreenState();
}

class _WordSearchLevelsScreenState extends State<_WordSearchLevelsScreen> {
  static const _crossAxisCount = 3;
  static const _cardHeight = 146.0;
  static const _spacing = 12.0;

  late Future<_WordSearchLevelsPayload> _future;
  late AppUser? _currentUser;
  late final ScrollController _scrollController;
  late bool _isUnlocked;
  bool _isUnlockingCategory = false;

  String get _languageId => widget.locale.languageCode == 'es' ? '1' : '2';
  bool get _isSpanish => widget.locale.languageCode == 'es';
  String _friendlyError(Object error) =>
      _wordSearchFriendlyError(error, _isSpanish);

  @override
  void initState() {
    super.initState();
    _currentUser = widget.currentUser;
    _isUnlocked = !widget.category.isPremium || widget.category.isPurchased;
    _future = _load();
    _scrollController = ScrollController();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<_WordSearchLevelsPayload> _load() async {
    final levels = await widget.repository.fetchLevels(
      categoryId: widget.category.id,
      languageId: _languageId,
    );

    WordSearchProgress progress = WordSearchProgress.empty(widget.category.id);
    final user = _currentUser;
    if (user != null && user.id.isNotEmpty) {
      try {
        progress = await widget.repository.fetchProgress(
          userId: user.id,
          categoryId: widget.category.id,
        );
      } catch (_) {
        progress = WordSearchProgress.empty(widget.category.id);
      }
    }

    return _WordSearchLevelsPayload(levels: levels, progress: progress);
  }

  Future<void> _openLevel(
    WordSearchLevel level,
    WordSearchProgress progress,
    List<WordSearchLevel> allLevels,
  ) async {
    final wasCompleted = progress.isCompleted(level.levelNumber);

    final result = await Navigator.of(context).push<_LevelResult>(
      MaterialPageRoute(
        builder: (_) => _WordSearchBoardScreen(
          locale: widget.locale,
          category: widget.category,
          level: level,
          repository: widget.repository,
          currentUser: _currentUser,
          onUserUpdated: widget.onUserUpdated,
          wasPreviouslyCompleted: wasCompleted,
          quizResultRepository: widget.quizResultRepository,
        ),
      ),
    );

    if (!mounted || result == null) {
      return;
    }

    if (result.updatedUser != null) {
      _currentUser = result.updatedUser;
      widget.onUserUpdated?.call(result.updatedUser!);
    }

    setState(() {
      _future = _load();
    });

    if (result.openNextLevel) {
      final nextLevelNumber = level.levelNumber + 1;
      final nextLevel =
          allLevels.where((item) => item.levelNumber == nextLevelNumber);
      if (nextLevel.isNotEmpty) {
        final refreshedProgress = await widget.repository.fetchProgress(
          userId: _currentUser?.id ?? '',
          categoryId: widget.category.id,
        );
        if (!mounted) {
          return;
        }
        await _openLevel(nextLevel.first, refreshedProgress, allLevels);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F7FF),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.transparent,
        foregroundColor: AppTheme.ink,
        title: Text(widget.category.title),
      ),
      body: FutureBuilder<_WordSearchLevelsPayload>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return _MessageState(
              title: _isSpanish
                  ? 'No se pudieron cargar los niveles'
                  : 'Could not load levels',
              subtitle: _friendlyError(snapshot.error!),
              onRetry: () {
                setState(() {
                  _future = _load();
                });
              },
            );
          }

          final payload = snapshot.data;
          final levels = payload?.levels ?? const <WordSearchLevel>[];
          final progress =
              payload?.progress ?? WordSearchProgress.empty(widget.category.id);

          if (!_isUnlocked) {
            return Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _CategoryHero(
                    category: widget.category,
                    locale: widget.locale,
                    progress: progress,
                  ),
                  const SizedBox(height: 18),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(22),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: const [
                        BoxShadow(
                          color: Color(0x1412304A),
                          blurRadius: 20,
                          offset: Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _isSpanish
                              ? 'Desbloquea esta categoria para jugar sus niveles.'
                              : 'Unlock this category to play its levels.',
                          style: const TextStyle(
                            color: AppTheme.ink,
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          (_isSpanish
                                  ? 'Gasta {coins} monedas para dejarla disponible para este usuario.'
                                  : 'Spend {coins} coins to keep it available for this user.')
                              .replaceFirst(
                                  '{coins}', '${widget.category.amount}'),
                          style: const TextStyle(
                            color: Color(0xFF5D7188),
                            fontSize: 15,
                            height: 1.45,
                          ),
                        ),
                        const SizedBox(height: 14),
                        Wrap(
                          spacing: 10,
                          runSpacing: 10,
                          children: [
                            _HeroPill(
                                label:
                                    '${_isSpanish ? 'Monedas' : 'Coins'}: ${_currentUser?.coins ?? 0}'),
                            _HeroPill(
                                label:
                                    '${_isSpanish ? 'Premium' : 'Premium'}: ${widget.category.amount}'),
                          ],
                        ),
                        const SizedBox(height: 18),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: _isUnlockingCategory
                                ? null
                                : _promptCategoryUnlock,
                            child: Text(
                              (_isSpanish
                                      ? 'Desbloquear por {coins} monedas'
                                      : 'Unlock for {coins} coins')
                                  .replaceFirst(
                                      '{coins}', '${widget.category.amount}'),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }

          if (levels.isEmpty) {
            return _MessageState(
              title: _isSpanish ? 'No hay niveles todavia' : 'No levels yet',
              subtitle: _isSpanish
                  ? 'Importa niveles para esta categoria desde el admin panel.'
                  : 'Import levels for this category from the admin panel.',
            );
          }

          WidgetsBinding.instance.addPostFrameCallback((_) {
            _focusUnlockedLevel(progress.nextUnlockedLevel, levels.length);
          });

          return Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _CategoryHero(
                  category: widget.category,
                  locale: widget.locale,
                  progress: progress,
                ),
                const SizedBox(height: 18),
                Text(
                  _isSpanish ? 'Selecciona un nivel' : 'Select a level',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        color: AppTheme.ink,
                        fontWeight: FontWeight.w900,
                      ),
                ),
                const SizedBox(height: 12),
                Expanded(
                  child: GridView.builder(
                    controller: _scrollController,
                    itemCount: levels.length,
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: _crossAxisCount,
                      mainAxisSpacing: _spacing,
                      crossAxisSpacing: _spacing,
                      mainAxisExtent: _cardHeight,
                    ),
                    itemBuilder: (context, index) {
                      final level = levels[index];
                      final unlocked = progress.isUnlocked(level.levelNumber);
                      final completed = progress.isCompleted(level.levelNumber);
                      final isCurrent =
                          level.levelNumber == progress.nextUnlockedLevel;
                      final bestTime = progress.bestTimes[level.levelNumber];

                      return _LevelCard(
                        locale: widget.locale,
                        level: level,
                        unlocked: unlocked,
                        completed: completed,
                        isCurrent: isCurrent,
                        bestTimeSeconds: bestTime,
                        onTap: unlocked
                            ? () => _openLevel(level, progress, levels)
                            : null,
                      );
                    },
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  void _focusUnlockedLevel(int unlockedLevel, int maxLevel) {
    if (!_scrollController.hasClients || maxLevel <= 0) {
      return;
    }

    final safeUnlocked = unlockedLevel.clamp(1, maxLevel);
    final rowIndex = ((safeUnlocked - 1) / _crossAxisCount).floor();
    final targetOffset = rowIndex * (_cardHeight + _spacing);
    final maxOffset = _scrollController.position.maxScrollExtent;
    _scrollController.jumpTo(targetOffset.clamp(0, maxOffset));
  }

  Future<void> _promptCategoryUnlock() async {
    final user = _currentUser;
    if (user == null || user.id.isEmpty) {
      _showSnack(_isSpanish
          ? 'Esta categoria premium todavia no esta desbloqueada.'
          : 'This premium category is not unlocked yet.');
      return;
    }

    if (user.coins < widget.category.amount) {
      _showSnack(_isSpanish
          ? 'No tienes monedas suficientes todavia.'
          : 'You do not have enough coins yet.');
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(_isSpanish
            ? 'Desbloquear categoria premium'
            : 'Unlock premium category'),
        content: Text(
          (_isSpanish
                  ? 'Gasta {coins} monedas para desbloquear esta categoria y dejarla disponible para este usuario.'
                  : 'Spend {coins} coins to unlock this category and keep it available for this user.')
              .replaceFirst('{coins}', '${widget.category.amount}'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text(_isSpanish ? 'Cancelar' : 'Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text(
              (_isSpanish
                      ? 'Desbloquear por {coins} monedas'
                      : 'Unlock for {coins} coins')
                  .replaceFirst('{coins}', '${widget.category.amount}'),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      return;
    }

    setState(() {
      _isUnlockingCategory = true;
    });

    try {
      await widget.repository.unlockPremiumCategory(
        userId: user.id,
        categoryId: widget.category.id,
      );
      final updatedUser =
          user.copyWith(coins: user.coins - widget.category.amount);
      if (!mounted) {
        return;
      }
      setState(() {
        _currentUser = updatedUser;
        _isUnlocked = true;
      });
      widget.onUserUpdated?.call(updatedUser);
    } finally {
      if (mounted) {
        setState(() {
          _isUnlockingCategory = false;
        });
      }
    }
  }

  void _showSnack(String message) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }
}

class _WordSearchBoardScreen extends StatefulWidget {
  const _WordSearchBoardScreen({
    required this.locale,
    required this.category,
    required this.level,
    required this.repository,
    required this.currentUser,
    required this.onUserUpdated,
    required this.wasPreviouslyCompleted,
    required this.quizResultRepository,
  });

  final Locale locale;
  final WordSearchCategory category;
  final WordSearchLevel level;
  final WordSearchRepository repository;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;
  final bool wasPreviouslyCompleted;
  final QuizResultRepository quizResultRepository;

  @override
  State<_WordSearchBoardScreen> createState() => _WordSearchBoardScreenState();
}

class _WordSearchBoardScreenState extends State<_WordSearchBoardScreen> {
  static const _white = Colors.white;
  static const _ink = Color(0xFF24405B);
  static const double _gridSpacing = 4;

  late final _GeneratedBoard _board;
  late AppUser? _currentUser;
  late int _remainingSeconds;
  Timer? _timer;
  BannerAd? _bannerAd;
  bool _isBannerReady = false;
  bool _adsRemoved = false;

  final List<_CellPoint> _selection = <_CellPoint>[];
  final Set<String> _foundWords = <String>{};
  final Set<String> _correctCells = <String>{};
  bool _isSaving = false;
  bool _isCompleted = false;
  static const int _maxRewardedRevivesPerRun = 3;
  int _timeExtensionUses = 0;

  bool get _isSpanish => widget.locale.languageCode == 'es';
  String get _coinsDisplay => '${_currentUser?.coins ?? 0}'.padLeft(1, '0');
  bool get _canUseTimeExtension =>
      _timeExtensionUses < _maxRewardedRevivesPerRun;
  int get _effectiveTimeLimit => widget.level.timeLimit;

  @override
  void initState() {
    super.initState();
    _currentUser = widget.currentUser;
    _adsRemoved = QuizAdService.instance.adsRemoved;
    _remainingSeconds = _effectiveTimeLimit;
    _board = _WordSearchGenerator.generate(
      rows: widget.level.boardRows,
      cols: widget.level.boardCols,
      words: widget.level.words,
      locale: widget.locale,
    );
    _prepareBanner();
    _startTimer();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _bannerAd?.dispose();
    super.dispose();
  }

  Future<void> _prepareBanner() async {
    if (_adsRemoved) {
      return;
    }

    final banner = QuizAdService.instance.createQuestionBanner(
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
          _isBannerReady = false;
        });
      },
    );

    if (banner == null || !mounted) {
      return;
    }

    setState(() {
      _bannerAd = banner;
    });
  }

  Future<void> _openCoinShop() async {
    await CoinShopSheet.show(
      context,
      locale: widget.locale,
      currentUser: _currentUser,
      onUserUpdated: (updatedUser) {
        if (!mounted) {
          return;
        }
        setState(() {
          _currentUser = updatedUser;
        });
        widget.onUserUpdated?.call(updatedUser);
      },
    );
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted || _isCompleted) {
        timer.cancel();
        return;
      }

      if (_remainingSeconds <= 1) {
        timer.cancel();
        setState(() {
          _remainingSeconds = 0;
        });
        if (_canUseTimeExtension) {
          _showTimeoutDialog();
        } else {
          if (mounted) {
            Navigator.of(context).pop();
          }
        }
        return;
      }

      setState(() {
        _remainingSeconds -= 1;
      });
    });
  }

  Future<void> _showTimeoutDialog() async {
    if (!mounted) {
      return;
    }

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        final addAsset = _isSpanish
            ? 'assets/images/sopa/AddEsp.png'
            : 'assets/images/sopa/AddEng.png';

        return Material(
          color: const Color(0xCC62A1A7),
          child: SafeArea(
            child: Stack(
              children: [
                Positioned(
                  top: 20,
                  right: 20,
                  child: GestureDetector(
                    onTap: () {
                      Navigator.of(dialogContext).pop();
                      Navigator.of(context).pop();
                    },
                    child: Image.asset(
                      'assets/images/sopa/Close.png',
                      width: 48,
                      height: 48,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                Center(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    child: Image.asset(
                      addAsset,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                Positioned(
                  left: 0,
                  right: 0,
                  bottom: 42,
                  child: Center(
                    child: GestureDetector(
                      onTap: !_canUseTimeExtension
                          ? null
                          : () async {
                              final rewarded = await QuizAdService.instance
                                  .showRewardedToMultiplyCoins();
                              if (!mounted) {
                                return;
                              }

                              if (!rewarded) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(
                                      AppStrings(widget.locale).text(
                                        'rewardAdUnavailable',
                                      ),
                                    ),
                                  ),
                                );
                                return;
                              }

                              setState(() {
                                _timeExtensionUses += 1;
                                _remainingSeconds += 30;
                              });
                              _startTimer();
                              Navigator.of(dialogContext).pop();
                            },
                      child: Opacity(
                        opacity: _canUseTimeExtension ? 1 : 0.55,
                        child: Image.asset(
                          'assets/images/sopa/Play.png',
                          width: 138,
                          fit: BoxFit.contain,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<void> _removeAds() async {
    final purchased = await RemoveAdsScreen.show(
      context,
      locale: widget.locale,
    );
    if (!mounted || !purchased) {
      return;
    }

    QuizAdService.instance.setAdsRemoved(true);
    _bannerAd?.dispose();
    setState(() {
      _bannerAd = null;
      _isBannerReady = false;
      _adsRemoved = true;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          AppStrings(widget.locale).text('removeAdsPurchased'),
        ),
      ),
    );
  }

  void _onCellTap(int row, int col) {
    if (_isCompleted) {
      return;
    }

    final point = _CellPoint(row, col);

    setState(() {
      if (_selection.isEmpty) {
        _selection.add(point);
      } else if (_selection.length == 1) {
        if (_selection.first == point) {
          _selection.clear();
        } else if (_isAdjacent(_selection.first, point)) {
          _selection.add(point);
        } else {
          _selection
            ..clear()
            ..add(point);
        }
      } else {
        final first = _selection.first;
        final direction = _direction(first, _selection[1]);
        final last = _selection.last;

        if (point == last) {
          _selection.removeLast();
        } else if (_selection.length == 1 ||
            _canExtendSelection(last, point, direction)) {
          _selection.add(point);
        } else {
          _selection
            ..clear()
            ..add(point);
        }
      }
    });

    _tryResolveSelection();
  }

  void _onGridPanStart(
      Offset localPosition, int rows, int cols, Size boardSize) {
    if (_isCompleted) {
      return;
    }

    final point = _cellFromDragPosition(
      localPosition: localPosition,
      rows: rows,
      cols: cols,
      boardSize: boardSize,
    );
    if (point == null) {
      return;
    }

    setState(() {
      _selection
        ..clear()
        ..add(point);
    });
  }

  void _onGridPanUpdate(
      Offset localPosition, int rows, int cols, Size boardSize) {
    if (_isCompleted || _selection.isEmpty) {
      return;
    }

    final point = _cellFromDragPosition(
      localPosition: localPosition,
      rows: rows,
      cols: cols,
      boardSize: boardSize,
    );
    if (point == null) {
      return;
    }

    final path = _buildStraightPath(_selection.first, point);
    if (path == null) {
      return;
    }

    setState(() {
      _selection
        ..clear()
        ..addAll(path);
    });
  }

  void _onGridPanEnd() {
    _tryResolveSelection();
  }

  _CellPoint? _cellFromLocalPosition({
    required Offset localPosition,
    required int rows,
    required int cols,
    required Size boardSize,
  }) {
    if (localPosition.dx < 0 ||
        localPosition.dy < 0 ||
        localPosition.dx > boardSize.width ||
        localPosition.dy > boardSize.height) {
      return null;
    }

    final cellWidth = (boardSize.width - ((cols - 1) * _gridSpacing)) / cols;
    final cellHeight = (boardSize.height - ((rows - 1) * _gridSpacing)) / rows;
    final spanWidth = cellWidth + _gridSpacing;
    final spanHeight = cellHeight + _gridSpacing;

    final col = (localPosition.dx / spanWidth).floor();
    final row = (localPosition.dy / spanHeight).floor();

    if (row < 0 || row >= rows || col < 0 || col >= cols) {
      return null;
    }

    final offsetInsideCol = localPosition.dx - (col * spanWidth);
    final offsetInsideRow = localPosition.dy - (row * spanHeight);
    if (offsetInsideCol > cellWidth || offsetInsideRow > cellHeight) {
      return null;
    }

    return _CellPoint(row, col);
  }

  _CellPoint? _cellFromDragPosition({
    required Offset localPosition,
    required int rows,
    required int cols,
    required Size boardSize,
  }) {
    if (localPosition.dx < 0 ||
        localPosition.dy < 0 ||
        localPosition.dx > boardSize.width ||
        localPosition.dy > boardSize.height) {
      return null;
    }

    final cellWidth = (boardSize.width - ((cols - 1) * _gridSpacing)) / cols;
    final cellHeight = (boardSize.height - ((rows - 1) * _gridSpacing)) / rows;
    final spanWidth = cellWidth + _gridSpacing;
    final spanHeight = cellHeight + _gridSpacing;

    final col = (((localPosition.dx - (cellWidth / 2)) / spanWidth).round())
        .clamp(0, cols - 1);
    final row = (((localPosition.dy - (cellHeight / 2)) / spanHeight).round())
        .clamp(0, rows - 1);

    return _CellPoint(row, col);
  }

  List<_CellPoint>? _buildStraightPath(_CellPoint start, _CellPoint end) {
    final rowDiff = end.row - start.row;
    final colDiff = end.col - start.col;

    if (rowDiff == 0 && colDiff == 0) {
      return <_CellPoint>[start];
    }

    final isHorizontal = rowDiff == 0;
    final isVertical = colDiff == 0;
    final isDiagonal = rowDiff.abs() == colDiff.abs();
    if (!isHorizontal && !isVertical && !isDiagonal) {
      return null;
    }

    final stepRow = rowDiff.sign;
    final stepCol = colDiff.sign;
    final steps = max(rowDiff.abs(), colDiff.abs());

    return List<_CellPoint>.generate(
      steps + 1,
      (index) => _CellPoint(
        start.row + (stepRow * index),
        start.col + (stepCol * index),
      ),
    );
  }

  bool _isAdjacent(_CellPoint a, _CellPoint b) {
    final rowDiff = (a.row - b.row).abs();
    final colDiff = (a.col - b.col).abs();
    return rowDiff <= 1 && colDiff <= 1 && (rowDiff + colDiff) > 0;
  }

  _CellPoint _direction(_CellPoint from, _CellPoint to) {
    final row = (to.row - from.row).sign;
    final col = (to.col - from.col).sign;
    return _CellPoint(row, col);
  }

  bool _canExtendSelection(
      _CellPoint last, _CellPoint next, _CellPoint direction) {
    if (_selection.any((point) => point == next)) {
      return false;
    }

    final nextDirection = _direction(last, next);
    return nextDirection.row == direction.row &&
        nextDirection.col == direction.col &&
        _isAdjacent(last, next);
  }

  void _tryResolveSelection() {
    if (_selection.length < 2) {
      return;
    }

    for (final placedWord in _board.words) {
      if (_foundWords.contains(placedWord.word)) {
        continue;
      }

      if (_matchesPath(_selection, placedWord.path) ||
          _matchesPath(_selection, placedWord.path.reversed.toList())) {
        setState(() {
          _foundWords.add(placedWord.word);
          for (final point in placedWord.path) {
            _correctCells.add(point.key);
          }
          _selection.clear();
        });

        if (_foundWords.length == _board.words.length) {
          _completeLevel();
        }
        return;
      }
    }
  }

  bool _matchesPath(List<_CellPoint> current, List<_CellPoint> target) {
    if (current.length != target.length) {
      return false;
    }

    for (var index = 0; index < current.length; index++) {
      if (current[index] != target[index]) {
        return false;
      }
    }
    return true;
  }

  Future<void> _completeLevel() async {
    if (_isCompleted || _isSaving) {
      return;
    }

    _timer?.cancel();
    _isCompleted = true;
    _isSaving = true;

    final elapsed = max(0, _effectiveTimeLimit - _remainingSeconds);
    AppUser? updatedUser = _currentUser;

    final user = _currentUser;
    if (user != null && user.id.isNotEmpty) {
      await widget.repository.saveProgress(
        userId: user.id,
        categoryId: widget.category.id,
        levelNumber: widget.level.levelNumber,
        isCompleted: true,
        bestTimeSeconds: elapsed,
      );

      if (!widget.wasPreviouslyCompleted) {
        if (widget.level.rewardCoins > 0) {
          await widget.quizResultRepository.addBonusCoins(
            userId: user.id,
            coins: widget.level.rewardCoins,
          );
        }

        updatedUser = user.copyWith(
          coins: user.coins + widget.level.rewardCoins,
          score: user.score + 1,
        );
        _currentUser = updatedUser;
      }
    }

    if (!mounted) {
      return;
    }

    final result = updatedUser;
    await Navigator.of(context).push<_LevelResult>(
      MaterialPageRoute(
        builder: (context) => _WordSearchWinScreen(
          locale: widget.locale,
          onClose: () {
            Navigator.of(context).pop();
            Navigator.of(context).pop(_LevelResult(updatedUser: result));
          },
          onNext: () {
            Navigator.of(context).pop();
            Navigator.of(context).pop(
              _LevelResult(
                updatedUser: result,
                openNextLevel: true,
              ),
            );
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final rows = widget.level.boardRows;
    final cols = widget.level.boardCols;
    final screenHeight = MediaQuery.sizeOf(context).height;
    final maxBoardHeight = screenHeight * 0.46;

    return Scaffold(
      backgroundColor: const Color(0xFF62A1A7),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 10, 16, 16),
          child: Column(
            children: [
              Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.arrow_back_rounded),
                    color: Colors.white,
                  ),
                  const Spacer(),
                  Transform.translate(
                    offset: const Offset(10, 0),
                    child: SizedBox(
                      width: 236,
                      height: 72,
                      child: Stack(
                        clipBehavior: Clip.none,
                        children: [
                          Positioned(
                            left: 48,
                            top: 8,
                            child: GestureDetector(
                              onTap: _openCoinShop,
                              behavior: HitTestBehavior.opaque,
                              child: Container(
                                width: 166,
                                height: 56,
                                padding:
                                    const EdgeInsets.symmetric(horizontal: 12),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(22),
                                ),
                                child: Row(
                                  children: [
                                    Image.asset(
                                      'assets/images/sopa/addMonedas.png',
                                      width: 26,
                                      height: 26,
                                      fit: BoxFit.contain,
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Text(
                                        _coinsDisplay,
                                        maxLines: 1,
                                        overflow: TextOverflow.visible,
                                        textAlign: TextAlign.left,
                                        style: const TextStyle(
                                          fontFamily: 'Mikado',
                                          fontSize: 22,
                                          color: _ink,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                          Positioned(
                            right: 0,
                            top: -4,
                            child: Image.asset(
                              'assets/images/sopa/monedas.png',
                              width: 70,
                              height: 70,
                              fit: BoxFit.contain,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Expanded(
                child: Column(
                  children: [
                    const SizedBox(height: 14),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        Expanded(
                          child: Text(
                            widget.category.title.toUpperCase(),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontFamily: 'Mikado',
                              fontSize: 28,
                              height: 0.95,
                              color: Colors.black,
                            ),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Image.asset(
                              'assets/images/sopa/cronometro.png',
                              width: 42,
                              height: 42,
                              fit: BoxFit.contain,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              '${_remainingSeconds}s',
                              style: const TextStyle(
                                fontFamily: 'Mikado',
                                fontSize: 28,
                                color: Colors.black,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 36),
                    ConstrainedBox(
                      constraints: BoxConstraints(
                        maxHeight: maxBoardHeight,
                      ),
                      child: AspectRatio(
                        aspectRatio: cols / rows,
                        child: LayoutBuilder(
                          builder: (context, constraints) {
                            final boardSize = Size(
                              constraints.maxWidth,
                              constraints.maxHeight,
                            );

                            return GestureDetector(
                              behavior: HitTestBehavior.opaque,
                              onPanStart: (details) => _onGridPanStart(
                                details.localPosition,
                                rows,
                                cols,
                                boardSize,
                              ),
                              onPanUpdate: (details) => _onGridPanUpdate(
                                details.localPosition,
                                rows,
                                cols,
                                boardSize,
                              ),
                              onPanEnd: (_) => _onGridPanEnd(),
                              child: Column(
                                children: List.generate(rows, (row) {
                                  return Expanded(
                                    child: Padding(
                                      padding: EdgeInsets.only(
                                        bottom:
                                            row == rows - 1 ? 0 : _gridSpacing,
                                      ),
                                      child: Row(
                                        children: List.generate(cols, (col) {
                                          final point = _CellPoint(row, col);
                                          final key = point.key;
                                          final selected = _selection
                                              .any((item) => item == point);
                                          final correct =
                                              _correctCells.contains(key);

                                          return Expanded(
                                            child: Padding(
                                              padding: EdgeInsets.only(
                                                right: col == cols - 1
                                                    ? 0
                                                    : _gridSpacing,
                                              ),
                                              child: _LetterTile(
                                                letter: _board.cells[row][col],
                                                selected: selected,
                                                correct: correct,
                                                onTap: () =>
                                                    _onCellTap(row, col),
                                              ),
                                            ),
                                          );
                                        }),
                                      ),
                                    ),
                                  );
                                }),
                              ),
                            );
                          },
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Center(
                      child: SizedBox(
                        width: (_bannerAd?.size.width ?? AdSize.banner.width)
                            .toDouble(),
                        height: (_bannerAd?.size.height ?? AdSize.banner.height)
                            .toDouble(),
                        child: _bannerAd != null && _isBannerReady
                            ? AdWidget(ad: _bannerAd!)
                            : const SizedBox.shrink(),
                      ),
                    ),
                    const Spacer(),
                    const SizedBox(height: 8),
                    Align(
                      alignment: Alignment.centerRight,
                      child: GestureDetector(
                        onTap: _adsRemoved ? null : _removeAds,
                        child: Opacity(
                          opacity: _adsRemoved ? 0.45 : 1,
                          child: Image.asset(
                            'assets/images/sopa/NoAds.png',
                            width: 44,
                            height: 44,
                            fit: BoxFit.contain,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.18),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: LayoutBuilder(
                        builder: (context, constraints) {
                          const columnCount = 3;
                          final words = _board.words;
                          final itemsPerColumn =
                              (words.length / columnCount).ceil();

                          return Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Padding(
                                padding:
                                    const EdgeInsets.only(top: 2, right: 10),
                                child: RotatedBox(
                                  quarterTurns: 3,
                                  child: Text(
                                    _isSpanish ? 'BUSCA' : 'FIND',
                                    style: TextStyle(
                                      fontFamily: 'Mikado',
                                      fontSize: 28,
                                      color:
                                          Colors.black.withValues(alpha: 0.18),
                                      letterSpacing: 2,
                                    ),
                                  ),
                                ),
                              ),
                              Expanded(
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children:
                                      List.generate(columnCount, (columnIndex) {
                                    final start = columnIndex * itemsPerColumn;
                                    final end = min(
                                        start + itemsPerColumn, words.length);
                                    final columnWords = start >= words.length
                                        ? <_PlacedWord>[]
                                        : words.sublist(start, end);

                                    return Expanded(
                                      child: Padding(
                                        padding: EdgeInsets.only(
                                          right: columnIndex == columnCount - 1
                                              ? 0
                                              : 10,
                                        ),
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: columnWords.map((item) {
                                            final found =
                                                _foundWords.contains(item.word);
                                            return Padding(
                                              padding: const EdgeInsets.only(
                                                  bottom: 2),
                                              child: Text(
                                                item.word,
                                                style: TextStyle(
                                                  fontFamily: 'Mikado',
                                                  fontSize: 17,
                                                  height: 1.02,
                                                  color: found
                                                      ? Colors.white
                                                      : Colors.black.withValues(
                                                          alpha: 0.9),
                                                  decoration: found
                                                      ? TextDecoration
                                                          .lineThrough
                                                      : null,
                                                  decorationColor: Colors.white,
                                                  decorationThickness: 2,
                                                ),
                                              ),
                                            );
                                          }).toList(),
                                        ),
                                      ),
                                    );
                                  }),
                                ),
                              ),
                            ],
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CategoryCard extends StatelessWidget {
  const _CategoryCard({
    required this.category,
    required this.locale,
    required this.onTap,
  });

  final WordSearchCategory category;
  final Locale locale;
  final VoidCallback onTap;

  bool get _isSpanish => locale.languageCode == 'es';

  @override
  Widget build(BuildContext context) {
    const accent = Color(0xFF2F7FDD);
    return InkWell(
      borderRadius: BorderRadius.circular(22),
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: const Color(0xFFE6EEF7)),
          boxShadow: const [
            BoxShadow(
              color: Color(0x0F12304A),
              blurRadius: 16,
              offset: Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          children: [
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
                child: Container(
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(20),
                    child: category.imageUrl.isNotEmpty
                        ? Image.network(
                            category.imageUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) =>
                                Center(
                              child: Container(
                                width: 72,
                                height: 72,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: accent.withValues(alpha: 0.12),
                                ),
                                child: const Icon(
                                  Icons.grid_view_rounded,
                                  color: accent,
                                  size: 38,
                                ),
                              ),
                            ),
                          )
                        : Center(
                            child: Container(
                              width: 72,
                              height: 72,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: accent.withValues(alpha: 0.12),
                              ),
                              child: const Icon(
                                Icons.grid_view_rounded,
                                color: accent,
                                size: 38,
                              ),
                            ),
                          ),
                  ),
                ),
              ),
            ),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    accent,
                    Color(0xFF55B8FF),
                  ],
                ),
                borderRadius: const BorderRadius.vertical(
                  bottom: Radius.circular(22),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      if (category.isPremium)
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.18),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            category.isPurchased
                                ? (_isSpanish ? 'Comprado' : 'Purchased')
                                : '${category.amount} ${_isSpanish ? 'monedas' : 'coins'}',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                      const Spacer(),
                      Container(
                        width: 30,
                        height: 30,
                        decoration: BoxDecoration(
                          color: category.isPremium && !category.isPurchased
                              ? const Color(0xFFE74C3C)
                              : Colors.white.withValues(alpha: 0.22),
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color:
                                  (category.isPremium && !category.isPurchased
                                          ? const Color(0xFFE74C3C)
                                          : Colors.white)
                                      .withValues(alpha: 0.28),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Icon(
                          category.isPremium
                              ? (category.isPurchased
                                  ? Icons.check_rounded
                                  : Icons.lock_rounded)
                              : Icons.arrow_forward_rounded,
                          color: Colors.white,
                          size: 17,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    category.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _isSpanish
                        ? '${category.totalLevels} niveles'
                        : '${category.totalLevels} levels',
                    style: const TextStyle(
                      color: Color(0xFFD3E7FB),
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CategoryHero extends StatelessWidget {
  const _CategoryHero({
    required this.category,
    required this.locale,
    required this.progress,
  });

  final WordSearchCategory category;
  final Locale locale;
  final WordSearchProgress progress;

  bool get _isSpanish => locale.languageCode == 'es';

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2F7FDD), Color(0xFF55B8FF)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(28),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _CategoryThumb(imageUrl: category.imageUrl, size: 72),
              const Spacer(),
              _HeroPill(
                label: _isSpanish
                    ? 'Desbloqueado: ${progress.nextUnlockedLevel}'
                    : 'Unlocked: ${progress.nextUnlockedLevel}',
              ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            category.title,
            style: const TextStyle(
              fontSize: 32,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _HeroPill(
                label: _isSpanish
                    ? 'Completados: ${progress.completedLevels.length}'
                    : 'Completed: ${progress.completedLevels.length}',
              ),
              _HeroPill(
                label: _isSpanish
                    ? 'Mejor nivel: ${progress.highestCompletedLevel}'
                    : 'Best level: ${progress.highestCompletedLevel}',
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _LevelCard extends StatelessWidget {
  const _LevelCard({
    required this.locale,
    required this.level,
    required this.unlocked,
    required this.completed,
    required this.isCurrent,
    required this.bestTimeSeconds,
    required this.onTap,
  });

  final Locale locale;
  final WordSearchLevel level;
  final bool unlocked;
  final bool completed;
  final bool isCurrent;
  final int? bestTimeSeconds;
  final VoidCallback? onTap;

  bool get _isSpanish => locale.languageCode == 'es';

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(22),
        onTap: onTap,
        child: Ink(
          decoration: BoxDecoration(
            color: !unlocked
                ? const Color(0xFFF4F7FB)
                : isCurrent
                    ? const Color(0xFF2F7FDD).withValues(alpha: 0.12)
                    : Colors.white,
            borderRadius: BorderRadius.circular(22),
            border: Border.all(
              color: !unlocked
                  ? const Color(0xFFE2EAF4)
                  : isCurrent
                      ? const Color(0xFF2F7FDD)
                      : completed
                          ? const Color(0xFF3BC27D)
                          : const Color(0xFFD8E5F2),
              width: isCurrent ? 2.5 : 1.2,
            ),
            boxShadow: !unlocked
                ? null
                : const [
                    BoxShadow(
                      color: Color(0x0F12304A),
                      blurRadius: 12,
                      offset: Offset(0, 6),
                    ),
                  ],
          ),
          padding: const EdgeInsets.all(12),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                !unlocked
                    ? Icons.lock_rounded
                    : isCurrent
                        ? Icons.my_location_rounded
                        : completed
                            ? Icons.check_circle_rounded
                            : Icons.play_circle_fill_rounded,
                color: !unlocked
                    ? const Color(0xFF8CA0B4)
                    : const Color(0xFF2F7FDD),
              ),
              const SizedBox(height: 8),
              Text(
                '${_isSpanish ? 'Nivel' : 'Level'} ${level.levelNumber}',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: !unlocked ? const Color(0xFF7A8DA2) : AppTheme.ink,
                  fontWeight: FontWeight.w900,
                  fontSize: 15,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                !unlocked
                    ? (_isSpanish ? 'Bloqueado' : 'Locked')
                    : isCurrent
                        ? (_isSpanish ? 'Actual' : 'Current')
                        : completed
                            ? (_isSpanish ? 'Completado' : 'Completed')
                            : (_isSpanish ? 'Listo' : 'Ready'),
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: !unlocked
                      ? const Color(0xFF7A8DA2)
                      : const Color(0xFF2F7FDD),
                  fontWeight: FontWeight.w700,
                  fontSize: 12,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                '${level.words.length} ${_isSpanish ? 'palabras' : 'words'}',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Color(0xFF607891),
                  fontWeight: FontWeight.w700,
                  fontSize: 11,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                bestTimeSeconds != null
                    ? (_isSpanish
                        ? 'Mejor ${bestTimeSeconds}s'
                        : 'Best ${bestTimeSeconds}s')
                    : '+${level.rewardCoins} ${_isSpanish ? 'monedas' : 'coins'}',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Color(0xFF607891),
                  fontWeight: FontWeight.w700,
                  fontSize: 11,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CategoryThumb extends StatelessWidget {
  const _CategoryThumb({
    required this.imageUrl,
    this.size = 82,
  });

  final String imageUrl;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(22),
      ),
      clipBehavior: Clip.antiAlias,
      child: imageUrl.isNotEmpty
          ? Image.network(
              imageUrl,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => const Icon(
                Icons.grid_view_rounded,
                color: Colors.white,
                size: 34,
              ),
            )
          : const Icon(
              Icons.grid_view_rounded,
              color: Colors.white,
              size: 34,
            ),
    );
  }
}

class _HeroPill extends StatelessWidget {
  const _HeroPill({
    required this.label,
  });

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w700,
          color: Colors.white,
        ),
      ),
    );
  }
}

class _MiniPill extends StatelessWidget {
  const _MiniPill({
    required this.label,
  });

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: const Color(0xFFF1F6FD),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w700,
          color: Color(0xFF627B97),
        ),
      ),
    );
  }
}

String _wordSearchFriendlyError(Object error, bool isSpanish) {
  final message = error.toString().toLowerCase();
  final isConnectionIssue = message.contains('clientexception') ||
      message.contains('connection closed') ||
      message.contains('socketexception') ||
      message.contains('timed out') ||
      message.contains('timeout') ||
      message.contains('failed host lookup');

  if (isConnectionIssue) {
    return isSpanish
        ? 'Hubo un problema de conexion con el servidor. Intenta de nuevo en unos segundos.'
        : 'There was a connection problem with the server. Please try again in a few seconds.';
  }

  return isSpanish
      ? 'Ocurrio un error al cargar la informacion. Intenta nuevamente.'
      : 'There was an error loading the information. Please try again.';
}

class _MessageState extends StatelessWidget {
  const _MessageState({
    required this.title,
    required this.subtitle,
    this.onRetry,
  });

  final String title;
  final String subtitle;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.search_off_rounded,
              size: 48,
              color: Color(0xFF8BA1BB),
            ),
            const SizedBox(height: 16),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: AppTheme.ink,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              subtitle,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 15,
                color: Color(0xFF5D7188),
              ),
            ),
            if (onRetry != null) ...[
              const SizedBox(height: 18),
              FilledButton(
                onPressed: onRetry,
                child: const Text('Retry'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _LetterTile extends StatelessWidget {
  const _LetterTile({
    required this.letter,
    required this.selected,
    required this.correct,
    required this.onTap,
  });

  final String letter;
  final bool selected;
  final bool correct;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final backgroundAsset = correct
        ? 'assets/images/sopa/LetraCorrecta.png'
        : selected
            ? 'assets/images/sopa/LetraSeleccionada.png'
            : 'assets/images/sopa/FondoLetra.png';

    final letterColor =
        (selected || correct) ? Colors.white : const Color(0xFF24405B);

    return LayoutBuilder(
      builder: (context, constraints) {
        final tileSize = min(constraints.maxWidth, constraints.maxHeight);
        final fontSize = (tileSize * 0.42).clamp(16.0, 34.0);

        return GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: onTap,
          child: Stack(
            fit: StackFit.expand,
            children: [
              Image.asset(
                backgroundAsset,
                fit: BoxFit.cover,
              ),
              Center(
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Text(
                    letter,
                    style: TextStyle(
                      fontFamily: 'Mikado',
                      fontSize: fontSize,
                      color: letterColor,
                      shadows: [
                        Shadow(
                          color: Colors.black.withValues(
                              alpha: (selected || correct) ? 0.18 : 0.08),
                          blurRadius: 2,
                          offset: const Offset(0, 1),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _WordSearchLevelsPayload {
  const _WordSearchLevelsPayload({
    required this.levels,
    required this.progress,
  });

  final List<WordSearchLevel> levels;
  final WordSearchProgress progress;
}

class _LevelResult {
  const _LevelResult({
    this.updatedUser,
    this.openNextLevel = false,
  });

  final AppUser? updatedUser;
  final bool openNextLevel;
}

class _WordSearchWinScreen extends StatefulWidget {
  const _WordSearchWinScreen({
    required this.locale,
    required this.onClose,
    required this.onNext,
  });

  final Locale locale;
  final VoidCallback onClose;
  final VoidCallback onNext;

  @override
  State<_WordSearchWinScreen> createState() => _WordSearchWinScreenState();
}

class _WordSearchWinScreenState extends State<_WordSearchWinScreen> {
  bool get _isSpanish => widget.locale.languageCode == 'es';

  @override
  Widget build(BuildContext context) {
    final word = _isSpanish ? 'GANASTE' : 'YOU WIN';
    final closeLabel = _isSpanish ? 'Salir' : 'Exit';
    final nextLabel = _isSpanish ? 'Siguiente' : 'Next';

    return Scaffold(
      backgroundColor: const Color(0xFFB4A8CF),
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            final buttonWidth = constraints.maxWidth * 0.24;
            final boardWidth = constraints.maxWidth * 0.72;
            final boardHeight = constraints.maxHeight * 0.31;
            final letterCount = word.replaceAll(' ', '').length;
            final letterSize =
                (boardWidth / max(letterCount, 1) / 1.42).clamp(28.0, 48.0);

            return Stack(
              fit: StackFit.expand,
              children: [
                Image.asset(
                  'assets/images/sopa/ScreenWin.png',
                  fit: BoxFit.cover,
                ),
                Positioned(
                  left: (constraints.maxWidth - boardWidth) / 2,
                  top: constraints.maxHeight * 0.47,
                  width: boardWidth,
                  height: boardHeight,
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      Image.asset(
                        'assets/images/sopa/tableroBlanco.png',
                        width: boardWidth,
                        height: boardHeight,
                        fit: BoxFit.fill,
                      ),
                      Padding(
                        padding: EdgeInsets.symmetric(
                          horizontal: boardWidth * 0.08,
                          vertical: boardHeight * 0.12,
                        ),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Wrap(
                              alignment: WrapAlignment.center,
                              crossAxisAlignment: WrapCrossAlignment.center,
                              spacing: letterSize * 0.08,
                              runSpacing: letterSize * 0.08,
                              children: word.characters.map((letter) {
                                if (letter == ' ') {
                                  return SizedBox(width: letterSize * 0.24);
                                }
                                return _WinLetterTile(
                                  letter: letter,
                                  size: letterSize,
                                );
                              }).toList(),
                            ),
                            SizedBox(height: boardHeight * 0.12),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                GestureDetector(
                                  onTap: widget.onClose,
                                  child: _WinButton(
                                    assetPath:
                                        'assets/images/sopa/BotonClose.png',
                                    width: buttonWidth,
                                    label: closeLabel,
                                  ),
                                ),
                                SizedBox(width: boardWidth * 0.05),
                                GestureDetector(
                                  onTap: widget.onNext,
                                  child: _WinButton(
                                    assetPath:
                                        'assets/images/sopa/BotonNext.png',
                                    width: buttonWidth,
                                    label: nextLabel,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _WinLetterTile extends StatelessWidget {
  const _WinLetterTile({
    required this.letter,
    required this.size,
  });

  final String letter;
  final double size;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Image.asset(
            'assets/images/sopa/LetraWin.png',
            width: size,
            height: size,
            fit: BoxFit.contain,
          ),
          Text(
            letter,
            style: TextStyle(
              fontFamily: 'Mikado',
              color: const Color(0xFF17324A),
              fontSize: size * 0.82,
              fontWeight: FontWeight.w900,
              height: 1,
            ),
          ),
        ],
      ),
    );
  }
}

class _WinButton extends StatelessWidget {
  const _WinButton({
    required this.assetPath,
    required this.width,
    required this.label,
  });

  final String assetPath;
  final double width;
  final String label;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: width,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Image.asset(
            assetPath,
            width: width,
            fit: BoxFit.contain,
          ),
          Text(
            label,
            style: TextStyle(
              fontFamily: 'Mikado',
              fontSize: width * 0.13,
              fontWeight: FontWeight.w900,
              color: const Color(0xFF17324A),
            ),
          ),
        ],
      ),
    );
  }
}

class _CellPoint {
  const _CellPoint(this.row, this.col);

  final int row;
  final int col;

  String get key => '$row-$col';

  @override
  bool operator ==(Object other) {
    return other is _CellPoint && other.row == row && other.col == col;
  }

  @override
  int get hashCode => Object.hash(row, col);
}

class _PlacedWord {
  const _PlacedWord({
    required this.word,
    required this.path,
  });

  final String word;
  final List<_CellPoint> path;
}

class _GeneratedBoard {
  const _GeneratedBoard({
    required this.cells,
    required this.words,
  });

  final List<List<String>> cells;
  final List<_PlacedWord> words;
}

class _WordSearchGenerator {
  static final Random _random = Random();
  static const List<_CellPoint> _directions = <_CellPoint>[
    _CellPoint(0, 1),
    _CellPoint(1, 0),
    _CellPoint(1, 1),
    _CellPoint(-1, 1),
    _CellPoint(0, -1),
    _CellPoint(-1, 0),
    _CellPoint(-1, -1),
    _CellPoint(1, -1),
  ];

  static _GeneratedBoard generate({
    required int rows,
    required int cols,
    required List<String> words,
    required Locale locale,
  }) {
    final board = List.generate(rows, (_) => List<String>.filled(cols, ''));
    final placedWords = <_PlacedWord>[];
    final usableWords = words
        .map(_normalizeWord)
        .where((word) => word.isNotEmpty)
        .toList()
      ..sort((a, b) => b.length.compareTo(a.length));

    for (final word in usableWords) {
      final placed = _placeWord(board, word);
      if (placed != null) {
        placedWords.add(placed);
      }
    }

    final alphabet = locale.languageCode == 'es'
        ? const [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z'
          ]
        : const [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z'
          ];

    for (var row = 0; row < rows; row++) {
      for (var col = 0; col < cols; col++) {
        if (board[row][col].isEmpty) {
          board[row][col] = alphabet[_random.nextInt(alphabet.length)];
        }
      }
    }

    return _GeneratedBoard(cells: board, words: placedWords);
  }

  static String _normalizeWord(String word) {
    final normalized = word
        .trim()
        .toUpperCase()
        .replaceAll(RegExp(r'\s+'), '')
        .replaceAll('Á', 'A')
        .replaceAll('É', 'E')
        .replaceAll('Í', 'I')
        .replaceAll('Ó', 'O')
        .replaceAll('Ú', 'U');
    return normalized;
  }

  static _PlacedWord? _placeWord(List<List<String>> board, String word) {
    final rows = board.length;
    final cols = board.first.length;
    final letters = word.characters.toList();

    for (var attempt = 0; attempt < 200; attempt++) {
      final direction = _directions[_random.nextInt(_directions.length)];
      final startRow = _random.nextInt(rows);
      final startCol = _random.nextInt(cols);
      final path = <_CellPoint>[];
      var fits = true;

      for (var index = 0; index < letters.length; index++) {
        final row = startRow + (direction.row * index);
        final col = startCol + (direction.col * index);

        if (row < 0 || row >= rows || col < 0 || col >= cols) {
          fits = false;
          break;
        }

        final current = board[row][col];
        if (current.isNotEmpty && current != letters[index]) {
          fits = false;
          break;
        }

        path.add(_CellPoint(row, col));
      }

      if (!fits || path.length != letters.length) {
        continue;
      }

      for (var index = 0; index < path.length; index++) {
        final point = path[index];
        board[point.row][point.col] = letters[index];
      }

      return _PlacedWord(word: word, path: path);
    }

    return null;
  }
}
