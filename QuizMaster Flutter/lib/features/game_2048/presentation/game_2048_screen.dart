import 'dart:async';
import 'dart:math';

import 'package:flutter/material.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/ads/quiz_ad_service.dart';
import '../../../core/l10n/app_strings.dart';
import '../../../core/network/php_api_client.dart';
import '../../../core/purchases/quiz_purchase_service.dart';
import '../../auth/models/app_user.dart';
import '../../shop/presentation/coin_shop_sheet.dart';
import '../data/game_2048_challenge_repository.dart';

const String _game2048ChallengeUnlockedKey = 'game_2048_retos_unlocked_level';

class Game2048ChallengesLevelScreen extends StatefulWidget {
  const Game2048ChallengesLevelScreen({
    super.key,
    required this.locale,
    required this.coins,
    this.currentUser,
    this.onUserUpdated,
  });

  final Locale locale;
  final String coins;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;

  @override
  State<Game2048ChallengesLevelScreen> createState() =>
      _Game2048ChallengesLevelScreenState();
}

class _Game2048ChallengesLevelScreenState
    extends State<Game2048ChallengesLevelScreen> {
  final Game2048ChallengeRepository _challengeRepository =
      RemoteGame2048ChallengeRepository(apiClient: PhpApiClient());

  int _unlockedLevel = 1;
  bool _isLoading = true;
  String? _loadError;
  List<Game2048ChallengeLevel> _levels = const <Game2048ChallengeLevel>[];
  Set<int> _completedLevels = const <int>{};

  bool get _isSpanish => widget.locale.languageCode == 'es';
  AppStrings get strings => AppStrings(widget.locale);

  @override
  void initState() {
    super.initState();
    _loadChallengeData();
  }

  Future<void> _loadChallengeData() async {
    if (mounted) {
      setState(() {
        _isLoading = true;
        _loadError = null;
      });
    }

    try {
      final fetchedLevels = await _challengeRepository.fetchLevels();
      if (fetchedLevels.isEmpty) {
        throw const PhpApiException('No 2048 challenge levels available.');
      }

      final normalizedLevels = fetchedLevels;

      var unlockedLevel = 1;
      var completedLevels = <int>{};
      String? warningMessage;

      if ((widget.currentUser?.id ?? '').isNotEmpty) {
        try {
          final progress = await _challengeRepository.fetchProgress(
            userId: widget.currentUser!.id,
          );
          unlockedLevel = progress.nextUnlockedLevel;
          completedLevels = progress.completedLevels;
        } catch (_) {
          final prefs = await SharedPreferences.getInstance();
          unlockedLevel = prefs.getInt(_game2048ChallengeUnlockedKey) ?? 1;
          warningMessage = _isSpanish
              ? 'Se cargaron los niveles, pero no se pudo sincronizar tu progreso todavía.'
              : 'Levels loaded, but your progress could not be synced yet.';
        }
      } else {
        final prefs = await SharedPreferences.getInstance();
        unlockedLevel = prefs.getInt(_game2048ChallengeUnlockedKey) ?? 1;
      }

      unlockedLevel = max(1, min(unlockedLevel, normalizedLevels.length));

      if (!mounted) {
        return;
      }

      setState(() {
        _levels = normalizedLevels;
        _completedLevels = completedLevels;
        _unlockedLevel = unlockedLevel;
        _loadError = warningMessage;
        _isLoading = false;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }

      setState(() {
        _levels = const <Game2048ChallengeLevel>[];
        _completedLevels = <int>{};
        _unlockedLevel = 1;
        _loadError = _isSpanish
            ? 'No se pudieron cargar los niveles. Intenta de nuevo.'
            : 'Could not load levels. Please try again.';
        _isLoading = false;
      });
    }
  }

  Future<void> _openLevel(Game2048ChallengeLevel level) async {
    if (level.levelNumber > _unlockedLevel) {
      return;
    }

    final result = await Navigator.of(context).push<Object?>(
      MaterialPageRoute<void>(
        builder: (_) => Game2048Screen(
          locale: widget.locale,
          coins: widget.coins,
          modeKey: 'retos_${level.levelNumber}',
          titleOverride: _isSpanish ? '2048 RETOS' : '2048 CHALLENGES',
          currentUser: widget.currentUser,
          onUserUpdated: widget.onUserUpdated,
          totalChallengeLevels: _levels.length,
          challengeLevel: level,
        ),
      ),
    );

    if (!mounted) {
      return;
    }

    await _loadChallengeData();

    if (result is int &&
        result >= 1 &&
        result <= _levels.length &&
        result > level.levelNumber) {
      await _openLevel(_levels[result - 1]);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: Color(0xFF3E3E3E),
        body: Center(
          child: CircularProgressIndicator(color: Colors.white),
        ),
      );
    }

    if (_loadError != null && _levels.isEmpty) {
      return Scaffold(
        backgroundColor: const Color(0xFF3E3E3E),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 28),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.error_outline, color: Colors.white, size: 44),
                const SizedBox(height: 14),
                Text(
                  _loadError!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: _loadChallengeData,
                  child: Text(_isSpanish ? 'Reintentar' : 'Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final currentLevel = _levels[_unlockedLevel - 1];

    return Scaffold(
      backgroundColor: const Color(0xFF3E3E3E),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.of(context).maybePop(),
                    icon: const Icon(
                      Icons.arrow_back,
                      color: Colors.white,
                      size: 28,
                    ),
                    splashRadius: 24,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _isSpanish ? '2048 RETOS' : '2048 CHALLENGES',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 30,
                        color: Colors.white,
                        height: 0.95,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF5C8DFF), Color(0xFF3659D9)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x33000000),
                      blurRadius: 18,
                      offset: Offset(0, 10),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      strings.text('currentLevelUnlocked'),
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${strings.text('levelLabel')} ${currentLevel.levelNumber}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 30,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      _isSpanish
                          ? '${currentLevel.moveLimit} movimientos disponibles'
                          : '${currentLevel.moveLimit} moves available',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: currentLevel.goals
                          .map(
                            (goal) => _ChallengeGoalPill(
                              value: goal.tileValue,
                              progress: 0,
                              target: goal.targetCount,
                            ),
                          )
                          .toList(),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: () => _openLevel(currentLevel),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: const Color(0xFF3659D9),
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: Text(
                          strings.text('continueCurrentLevel'),
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              if (_loadError != null) ...[
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(bottom: 16),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0x26FFFFFF),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0x40FFFFFF)),
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.wifi_off_rounded,
                        color: Colors.white,
                        size: 18,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          _loadError!,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      TextButton(
                        onPressed: _loadChallengeData,
                        child: Text(_isSpanish ? 'Reintentar' : 'Retry'),
                      ),
                    ],
                  ),
                ),
              ],
              Text(
                strings.text('selectLevelTitle'),
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 14),
              Expanded(
                child: GridView.builder(
                  itemCount: _levels.length,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    mainAxisSpacing: 14,
                    crossAxisSpacing: 14,
                    childAspectRatio: 0.9,
                  ),
                  itemBuilder: (context, index) {
                    final level = _levels[index];
                    final isLocked = level.levelNumber > _unlockedLevel;
                    final isCompleted =
                        _completedLevels.contains(level.levelNumber);
                    final isCurrent =
                        !isCompleted && level.levelNumber == _unlockedLevel;
                    return _ChallengeLevelCard(
                      levelNumber: level.levelNumber,
                      isLocked: isLocked,
                      isCurrent: isCurrent,
                      isCompleted: isCompleted,
                      isSpanish: _isSpanish,
                      onTap: () => _openLevel(level),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class Game2048Screen extends StatefulWidget {
  const Game2048Screen({
    super.key,
    required this.locale,
    required this.coins,
    this.modeKey = 'classic',
    this.titleOverride,
    this.currentUser,
    this.onUserUpdated,
    this.totalChallengeLevels,
    this.challengeLevel,
  });

  final Locale locale;
  final String coins;
  final String modeKey;
  final String? titleOverride;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;
  final int? totalChallengeLevels;
  final Game2048ChallengeLevel? challengeLevel;

  @override
  State<Game2048Screen> createState() => _Game2048ScreenState();
}

class _Game2048ScreenState extends State<Game2048Screen> {
  static const int _boardSize = 4;
  static const double _boardSpacing = 10;
  static const Color _gameBackground = Color(0xFF3E3E3E);

  final Game2048ChallengeRepository _challengeRepository =
      RemoteGame2048ChallengeRepository(apiClient: PhpApiClient());
  final Random _random = Random();

  late List<List<int>> _board;
  List<_AnimatedTile> _animatedTiles = const [];
  int _score = 0;
  int _bestScore = 0;
  int _moveAnimationTick = 0;
  int _tileIdSeed = 0;
  int _currentAnimationDurationMs = 0;
  bool _isAnimating = false;
  static const int _maxRewardedRevivesPerRun = 3;
  late int _remainingSeconds;
  int? _remainingMoves;
  Timer? _timer;
  BannerAd? _bannerAd;
  bool _isBannerReady = false;
  bool _adsRemoved = false;
  int _timeExtensionUses = 0;
  int _challengeMoveExtensionUses = 0;
  bool _showPostTimeoutActions = false;
  bool _showingTimeoutOverlay = false;
  bool _challengeResultVisible = false;
  Map<int, int> _challengeProgress = <int, int>{};
  late AppUser? _currentUser;

  AppStrings get strings => AppStrings(widget.locale);
  bool get _isSpanish => widget.locale.languageCode == 'es';
  bool get _isTimeUp => _remainingSeconds <= 0;
  bool get _isChallengeMode => widget.challengeLevel != null;
  bool get _canUseTimeExtension =>
      _timeExtensionUses < _maxRewardedRevivesPerRun;
  bool get _canUseChallengeMoveExtension =>
      _challengeMoveExtensionUses < _maxRewardedRevivesPerRun;
  String get _bestScoreKey => 'game_2048_best_score_${widget.modeKey}';
  String get _coinsDisplay => '${_currentUser?.coins ?? widget.coins}';
  String get _gameTitle =>
      widget.titleOverride ?? (_isSpanish ? '2048 CLASICO' : '2048 CLASSIC');

  @override
  void initState() {
    super.initState();
    _currentUser = widget.currentUser;
    _remainingSeconds = widget.challengeLevel?.timeLimitSeconds ?? 300;
    _remainingMoves = widget.challengeLevel?.moveLimit;
    _adsRemoved = QuizAdService.instance.adsRemoved;
    _board = _emptyBoard();
    _spawnTile();
    _spawnTile();
    _syncAnimatedTilesFromBoard();
    _loadBestScore();
    if (!_isChallengeMode) {
      _startTimer();
    }
    _prepareBanner();
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

  @override
  void dispose() {
    _timer?.cancel();
    _bannerAd?.dispose();
    super.dispose();
  }

  List<List<int>> _emptyBoard() {
    return List.generate(
      _boardSize,
      (_) => List<int>.filled(_boardSize, 0),
    );
  }

  void _resetGame() {
    _board = _emptyBoard();
    _score = 0;
    _isAnimating = false;
    _currentAnimationDurationMs = 0;
    _remainingSeconds = widget.challengeLevel?.timeLimitSeconds ?? 300;
    _remainingMoves = widget.challengeLevel?.moveLimit;
    _timeExtensionUses = 0;
    _challengeMoveExtensionUses = 0;
    _showPostTimeoutActions = false;
    _showingTimeoutOverlay = false;
    _challengeResultVisible = false;
    _challengeProgress = <int, int>{};
    _spawnTile();
    _spawnTile();
    _syncAnimatedTilesFromBoard();
    if (!_isChallengeMode) {
      _startTimer();
    }
    setState(() {});
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

    if (banner == null) {
      return;
    }

    setState(() {
      _bannerAd = banner;
    });
  }

  Future<void> _loadBestScore() async {
    final prefs = await SharedPreferences.getInstance();
    final savedValue = prefs.getInt(_bestScoreKey) ?? 0;
    if (!mounted) {
      return;
    }
    setState(() {
      _bestScore = savedValue;
    });
  }

  Future<void> _saveBestScore() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_bestScoreKey, _bestScore);
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      if (_remainingSeconds <= 0) {
        timer.cancel();
        return;
      }
      if (_remainingSeconds <= 1) {
        timer.cancel();
        setState(() {
          _remainingSeconds = 0;
        });
        if (_canUseTimeExtension) {
          _showTimeoutOverlay();
        } else {
          setState(() {
            _showPostTimeoutActions = true;
          });
        }
        return;
      }
      setState(() {
        _remainingSeconds -= 1;
      });
    });
  }

  Future<void> _showTimeoutOverlay() async {
    if (!mounted || _showingTimeoutOverlay) {
      return;
    }

    _showingTimeoutOverlay = true;
    final adAsset = _isSpanish
        ? 'assets/images/sopa/AddEsp.png'
        : 'assets/images/sopa/AddEng.png';

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Material(
          color: const Color(0xCC62A1A7),
          child: SafeArea(
            child: Stack(
              children: [
                Positioned.fill(
                  child: Center(
                    child: Image.asset(
                      adAsset,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                Positioned(
                  top: 24,
                  right: 24,
                  child: GestureDetector(
                    onTap: () {
                      Navigator.of(dialogContext).pop();
                      if (!mounted) {
                        return;
                      }
                      setState(() {
                        _showPostTimeoutActions = true;
                      });
                    },
                    child: Image.asset(
                      'assets/images/sopa/Close.png',
                      width: 42,
                      height: 42,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                Positioned(
                  left: 0,
                  right: 0,
                  bottom: 84,
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
                                      strings.text('rewardAdUnavailable'),
                                    ),
                                  ),
                                );
                                return;
                              }

                              setState(() {
                                _timeExtensionUses += 1;
                                _showPostTimeoutActions = false;
                                _remainingSeconds += 30;
                              });
                              _startTimer();
                              Navigator.of(dialogContext).pop();
                            },
                      child: Opacity(
                        opacity: _canUseTimeExtension ? 1 : 0.45,
                        child: Image.asset(
                          'assets/images/sopa/Play.png',
                          width: 140,
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

    _showingTimeoutOverlay = false;
  }

  Future<void> _showChallengeMovesOverlay() async {
    if (!mounted || _showingTimeoutOverlay) {
      return;
    }

    _showingTimeoutOverlay = true;
    final adAsset = _isSpanish
        ? 'assets/images/sopa/AddMoveESP.png'
        : 'assets/images/sopa/AddMoveENG.png';

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Material(
          color: const Color(0xCC62A1A7),
          child: SafeArea(
            child: Stack(
              children: [
                Positioned.fill(
                  child: Center(
                    child: Image.asset(
                      adAsset,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                Positioned(
                  top: 24,
                  right: 24,
                  child: GestureDetector(
                    onTap: () {
                      Navigator.of(dialogContext).pop();
                      if (!mounted) {
                        return;
                      }
                      setState(() {
                        _showPostTimeoutActions = true;
                      });
                    },
                    child: Image.asset(
                      'assets/images/sopa/Close.png',
                      width: 42,
                      height: 42,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                Positioned(
                  left: 0,
                  right: 0,
                  bottom: 84,
                  child: Center(
                    child: GestureDetector(
                      onTap: !_canUseChallengeMoveExtension
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
                                      strings.text('rewardAdUnavailable'),
                                    ),
                                  ),
                                );
                                return;
                              }

                              setState(() {
                                _challengeMoveExtensionUses += 1;
                                _showPostTimeoutActions = false;
                                _remainingMoves = (_remainingMoves ?? 0) + 5;
                              });
                              Navigator.of(dialogContext).pop();
                            },
                      child: Opacity(
                        opacity: _canUseChallengeMoveExtension ? 1 : 0.45,
                        child: Image.asset(
                          'assets/images/sopa/Play.png',
                          width: 140,
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

    _showingTimeoutOverlay = false;
  }

  Future<void> _showChallengeCompleteDialog() async {
    if (!mounted || !_isChallengeMode || _challengeResultVisible) {
      return;
    }

    _challengeResultVisible = true;
    _timer?.cancel();
    final level = widget.challengeLevel!;
    final nextLevel = level.levelNumber + 1;
    final totalChallengeLevels =
        widget.totalChallengeLevels ?? _fallbackGame2048ChallengeLevels.length;
    final prefs = await SharedPreferences.getInstance();
    final currentUnlocked = prefs.getInt(_game2048ChallengeUnlockedKey) ?? 1;
    if (nextLevel > currentUnlocked) {
      await prefs.setInt(
        _game2048ChallengeUnlockedKey,
        nextLevel,
      );
    }

    if ((widget.currentUser?.id ?? '').isNotEmpty) {
      try {
        await _challengeRepository.saveProgress(
          userId: widget.currentUser!.id,
          levelNumber: level.levelNumber,
          isCompleted: true,
          bestMovesLeft: max(0, _remainingMoves ?? 0),
        );
      } catch (_) {}
    }

    if (!mounted) {
      return;
    }

    final winAsset = _randomChallengeWinAsset();

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Scaffold(
          backgroundColor: const Color(0xFF3E3E3E),
          body: Stack(
            fit: StackFit.expand,
            children: [
              Container(
                color: const Color(0xFF3E3E3E),
                alignment: Alignment.center,
                child: Image.asset(
                  winAsset,
                  fit: BoxFit.contain,
                ),
              ),
              SafeArea(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(24, 24, 24, 134),
                  child: Column(
                    children: [
                      const Spacer(),
                      Row(
                        children: [
                          Expanded(
                            child: GestureDetector(
                              onTap: () {
                                Navigator.of(dialogContext).pop();
                                Navigator.of(context).pop();
                              },
                              child: _Game2048ActionButton(
                                assetPath: 'assets/images/sopa/BotonClose.png',
                                label: _isSpanish ? 'Salir' : 'Exit',
                              ),
                            ),
                          ),
                          const SizedBox(width: 18),
                          Expanded(
                            child: GestureDetector(
                              onTap: () {
                                Navigator.of(dialogContext).pop();
                                if (nextLevel <= totalChallengeLevels) {
                                  Navigator.of(context).pop(nextLevel);
                                } else {
                                  Navigator.of(context).pop();
                                }
                              },
                              child: _Game2048ActionButton(
                                assetPath: 'assets/images/sopa/BotonNext.png',
                                label: _isSpanish ? 'Siguiente' : 'Next',
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );

    _challengeResultVisible = false;
  }

  String _randomChallengeWinAsset() {
    final suffix = _isSpanish ? 'esp' : 'eng';
    final index = Random().nextInt(4) + 1;
    return 'assets/images/win_screens/win${index}_$suffix.png';
  }

  Future<void> _showChallengeFailedDialog() async {
    if (!mounted || !_isChallengeMode || _challengeResultVisible) {
      return;
    }

    _challengeResultVisible = true;
    _timer?.cancel();

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return AlertDialog(
          title: Text(
            _isSpanish ? 'Nivel no completado' : 'Level failed',
          ),
          content: Text(
            _isSpanish
                ? 'Se acabaron los movimientos disponibles para este reto.'
                : 'You ran out of moves for this challenge.',
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(dialogContext).pop();
                Navigator.of(context).pop();
              },
              child: Text(_isSpanish ? 'Salir' : 'Exit'),
            ),
            TextButton(
              onPressed: () {
                Navigator.of(dialogContext).pop();
                _resetGame();
              },
              child: Text(_isSpanish ? 'Reintentar' : 'Retry'),
            ),
          ],
        );
      },
    );

    _challengeResultVisible = false;
  }

  Future<void> _removeAds() async {
    if (!QuizPurchaseService.instance.isConfigured) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(strings.text('removeAdsNotReady'))),
      );
      return;
    }

    try {
      final purchased = await QuizPurchaseService.instance.purchaseRemoveAds();
      if (!mounted) {
        return;
      }

      if (!purchased) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(strings.text('removeAdsPurchaseFailed'))),
        );
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
        SnackBar(content: Text(strings.text('removeAdsPurchased'))),
      );
    } catch (_) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(strings.text('removeAdsPurchaseFailed'))),
      );
    }
  }

  Point<int>? _spawnTile() {
    final empty = <Point<int>>[];
    for (var row = 0; row < _boardSize; row++) {
      for (var col = 0; col < _boardSize; col++) {
        if (_board[row][col] == 0) {
          empty.add(Point(row, col));
        }
      }
    }

    if (empty.isEmpty) {
      return null;
    }

    final target = empty[_random.nextInt(empty.length)];
    _board[target.x][target.y] = _random.nextInt(10) == 0 ? 4 : 2;
    return target;
  }

  void _syncAnimatedTilesFromBoard() {
    final tiles = <_AnimatedTile>[];
    for (var row = 0; row < _boardSize; row++) {
      for (var col = 0; col < _boardSize; col++) {
        final value = _board[row][col];
        if (value == 0) {
          continue;
        }
        tiles.add(
          _AnimatedTile(
            id: _tileIdSeed++,
            value: value,
            fromRow: row,
            fromCol: col,
            toRow: row,
            toCol: col,
          ),
        );
      }
    }
    _animatedTiles = tiles;
  }

  Future<void> _move(_MoveDirection direction) async {
    if (_isAnimating ||
        _isTimeUp ||
        (_isChallengeMode && (_remainingMoves ?? 0) <= 0)) {
      return;
    }

    final result = _buildMove(direction);
    if (!result.moved) {
      return;
    }

    _score += result.gainedScore;
    if (_score > _bestScore) {
      _bestScore = _score;
      _saveBestScore();
    }

    final animationDuration = 180 + (result.maxDistance * 140);

    _isAnimating = true;
    _moveAnimationTick += 1;
    _currentAnimationDurationMs = animationDuration;
    _animatedTiles = result.tiles;
    setState(() {});

    await Future<void>.delayed(Duration(milliseconds: animationDuration));

    if (!mounted) {
      return;
    }

    _board = result.board;
    _spawnTile();
    _currentAnimationDurationMs = 0;
    _isAnimating = false;
    if (_isChallengeMode && _remainingMoves != null) {
      _remainingMoves = max(0, _remainingMoves! - 1);
      result.createdValues.forEach((value, count) {
        _challengeProgress[value] = (_challengeProgress[value] ?? 0) + count;
      });
    }
    _syncAnimatedTilesFromBoard();
    setState(() {});

    if (_isChallengeMode) {
      if (_challengeGoalsCompleted()) {
        await _showChallengeCompleteDialog();
        return;
      }
      if ((_remainingMoves ?? 0) <= 0) {
        if (_canUseChallengeMoveExtension) {
          await _showChallengeMovesOverlay();
          return;
        }
        if (mounted) {
          setState(() {
            _showPostTimeoutActions = true;
          });
        }
        return;
      }
    }

    if (_isGameOver()) {
      await Future<void>.delayed(const Duration(milliseconds: 140));
      if (!mounted) {
        return;
      }
      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(strings.text('gameOver')),
          content: Text('${strings.text('currentScore')}: $_score'),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                _resetGame();
              },
              child: Text(strings.text('play2048Again')),
            ),
          ],
        ),
      );
    }
  }

  _MoveResult _buildMove(_MoveDirection direction) {
    final nextBoard = _emptyBoard();
    final animatedTiles = <_AnimatedTile>[];
    var gainedScore = 0;
    var moved = false;
    var maxDistance = 0;
    final createdValues = <int, int>{};

    switch (direction) {
      case _MoveDirection.left:
        for (var row = 0; row < _boardSize; row++) {
          final result = _collapseLine(_board[row]);
          nextBoard[row] = result.line;
          gainedScore += result.score;
          result.createdValues.forEach((value, count) {
            createdValues[value] = (createdValues[value] ?? 0) + count;
          });
          for (final motion in result.motions) {
            animatedTiles.add(
              _AnimatedTile(
                id: _tileIdSeed++,
                value: motion.value,
                fromRow: row,
                fromCol: motion.fromIndex,
                toRow: row,
                toCol: motion.toIndex,
              ),
            );
            final distance = (motion.fromIndex - motion.toIndex).abs();
            if (distance > 0) {
              moved = true;
            }
            if (distance > maxDistance) {
              maxDistance = distance;
            }
          }
        }
        break;
      case _MoveDirection.right:
        for (var row = 0; row < _boardSize; row++) {
          final reversed = _board[row].reversed.toList();
          final result = _collapseLine(reversed);
          nextBoard[row] = result.line.reversed.toList();
          gainedScore += result.score;
          result.createdValues.forEach((value, count) {
            createdValues[value] = (createdValues[value] ?? 0) + count;
          });
          for (final motion in result.motions) {
            final fromCol = (_boardSize - 1) - motion.fromIndex;
            final toCol = (_boardSize - 1) - motion.toIndex;
            animatedTiles.add(
              _AnimatedTile(
                id: _tileIdSeed++,
                value: motion.value,
                fromRow: row,
                fromCol: fromCol,
                toRow: row,
                toCol: toCol,
              ),
            );
            final distance = (fromCol - toCol).abs();
            if (distance > 0) {
              moved = true;
            }
            if (distance > maxDistance) {
              maxDistance = distance;
            }
          }
        }
        break;
      case _MoveDirection.up:
        for (var col = 0; col < _boardSize; col++) {
          final column = [
            for (var row = 0; row < _boardSize; row++) _board[row][col],
          ];
          final result = _collapseLine(column);
          for (var row = 0; row < _boardSize; row++) {
            nextBoard[row][col] = result.line[row];
          }
          gainedScore += result.score;
          result.createdValues.forEach((value, count) {
            createdValues[value] = (createdValues[value] ?? 0) + count;
          });
          for (final motion in result.motions) {
            animatedTiles.add(
              _AnimatedTile(
                id: _tileIdSeed++,
                value: motion.value,
                fromRow: motion.fromIndex,
                fromCol: col,
                toRow: motion.toIndex,
                toCol: col,
              ),
            );
            final distance = (motion.fromIndex - motion.toIndex).abs();
            if (distance > 0) {
              moved = true;
            }
            if (distance > maxDistance) {
              maxDistance = distance;
            }
          }
        }
        break;
      case _MoveDirection.down:
        for (var col = 0; col < _boardSize; col++) {
          final reversed = [
            for (var row = 0; row < _boardSize; row++) _board[row][col],
          ].reversed.toList();
          final result = _collapseLine(reversed);
          final rebuilt = result.line.reversed.toList();
          for (var row = 0; row < _boardSize; row++) {
            nextBoard[row][col] = rebuilt[row];
          }
          gainedScore += result.score;
          result.createdValues.forEach((value, count) {
            createdValues[value] = (createdValues[value] ?? 0) + count;
          });
          for (final motion in result.motions) {
            final fromRow = (_boardSize - 1) - motion.fromIndex;
            final toRow = (_boardSize - 1) - motion.toIndex;
            animatedTiles.add(
              _AnimatedTile(
                id: _tileIdSeed++,
                value: motion.value,
                fromRow: fromRow,
                fromCol: col,
                toRow: toRow,
                toCol: col,
              ),
            );
            final distance = (fromRow - toRow).abs();
            if (distance > 0) {
              moved = true;
            }
            if (distance > maxDistance) {
              maxDistance = distance;
            }
          }
        }
        break;
    }

    moved = moved || !_sameBoard(_board, nextBoard);

    return _MoveResult(
      board: nextBoard,
      tiles: animatedTiles,
      gainedScore: gainedScore,
      moved: moved,
      maxDistance: maxDistance,
      createdValues: createdValues,
    );
  }

  bool _challengeGoalsCompleted() {
    final level = widget.challengeLevel;
    if (level == null) {
      return false;
    }
    for (final goal in level.goals) {
      if ((_challengeProgress[goal.tileValue] ?? 0) < goal.targetCount) {
        return false;
      }
    }
    return true;
  }

  bool _isGameOver() {
    for (var row = 0; row < _boardSize; row++) {
      for (var col = 0; col < _boardSize; col++) {
        if (_board[row][col] == 0) {
          return false;
        }
        if (col < _boardSize - 1 && _board[row][col] == _board[row][col + 1]) {
          return false;
        }
        if (row < _boardSize - 1 && _board[row][col] == _board[row + 1][col]) {
          return false;
        }
      }
    }
    return true;
  }

  _LineResult _collapseLine(List<int> source) {
    final values = <_IndexedValue>[];
    for (var index = 0; index < source.length; index++) {
      if (source[index] != 0) {
        values.add(_IndexedValue(index, source[index]));
      }
    }

    final output = List<int>.filled(_boardSize, 0);
    final motions = <_LineMotion>[];
    final createdValues = <int, int>{};
    var gainedScore = 0;
    var readIndex = 0;
    var writeIndex = 0;

    while (readIndex < values.length) {
      final current = values[readIndex];
      if (readIndex < values.length - 1 &&
          current.value == values[readIndex + 1].value) {
        final next = values[readIndex + 1];
        motions.add(_LineMotion(current.fromIndex, writeIndex, current.value));
        motions.add(_LineMotion(next.fromIndex, writeIndex, next.value));
        final mergedValue = current.value * 2;
        output[writeIndex] = mergedValue;
        gainedScore += mergedValue;
        createdValues[mergedValue] = (createdValues[mergedValue] ?? 0) + 1;
        readIndex += 2;
      } else {
        motions.add(_LineMotion(current.fromIndex, writeIndex, current.value));
        output[writeIndex] = current.value;
        readIndex += 1;
      }
      writeIndex += 1;
    }

    return _LineResult(output, gainedScore, motions, createdValues);
  }

  bool _sameBoard(List<List<int>> a, List<List<int>> b) {
    for (var row = 0; row < _boardSize; row++) {
      for (var col = 0; col < _boardSize; col++) {
        if (a[row][col] != b[row][col]) {
          return false;
        }
      }
    }
    return true;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _gameBackground,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 10, 20, 18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.only(top: 10),
                    child: IconButton(
                      onPressed: () => Navigator.of(context).maybePop(),
                      icon: const Icon(
                        Icons.arrow_back,
                        color: Colors.white,
                        size: 28,
                      ),
                      splashRadius: 24,
                    ),
                  ),
                  const Spacer(),
                  _CoinsPill2048(
                    coins: _coinsDisplay,
                    onTap: _openCoinShop,
                  ),
                ],
              ),
              const SizedBox(height: 23),
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Expanded(
                    child: Text(
                      _gameTitle,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontFamily: 'Mikado',
                        fontSize: 28,
                        height: 0.95,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  if (!_isChallengeMode) ...[
                    const SizedBox(width: 14),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        SizedBox(
                          width: 42,
                          height: 42,
                          child: Stack(
                            alignment: Alignment.center,
                            children: [
                              Container(
                                width: 30,
                                height: 30,
                                decoration: const BoxDecoration(
                                  color: Colors.white,
                                  shape: BoxShape.circle,
                                ),
                              ),
                              Image.asset(
                                'assets/images/sopa/cronometro.png',
                                width: 42,
                                height: 42,
                                fit: BoxFit.contain,
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '${_remainingSeconds}s',
                          style: const TextStyle(
                            fontFamily: 'Mikado',
                            fontSize: 28,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 20),
              Expanded(
                child: Column(
                  children: [
                    if (_isChallengeMode) ...[
                      Row(
                        children: [
                          Expanded(
                            child: _ScoreBadge(
                              label: strings.text('levelLabel').toUpperCase(),
                              value: '${widget.challengeLevel!.levelNumber}',
                              width: 126,
                              height: 52,
                              labelFontSize: 13,
                              valueFontSize: 28,
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: _ScoreBadge(
                              label: _isSpanish ? 'MOVES' : 'MOVES',
                              value: '${_remainingMoves ?? 0}',
                              width: 126,
                              height: 52,
                              labelFontSize: 13,
                              valueFontSize: 28,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        alignment: WrapAlignment.center,
                        spacing: 8,
                        runSpacing: 8,
                        children: widget.challengeLevel!.goals
                            .map(
                              (goal) => _ChallengeGoalPill(
                                value: goal.tileValue,
                                progress:
                                    _challengeProgress[goal.tileValue] ?? 0,
                                target: goal.targetCount,
                              ),
                            )
                            .toList(),
                      ),
                      const SizedBox(height: 14),
                    ],
                    if (!_isChallengeMode)
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          _ScoreBadge(
                            label: 'SCORE',
                            value: '$_score',
                          ),
                          const SizedBox(width: 14),
                          _ScoreBadge(
                            label: strings.text('bestScore').toUpperCase(),
                            value: '$_bestScore',
                          ),
                        ],
                      ),
                    const SizedBox(height: 24),
                    Align(
                      alignment: Alignment.topCenter,
                      child: FractionallySizedBox(
                        widthFactor: 0.8,
                        child: AspectRatio(
                          aspectRatio: 1,
                          child: GestureDetector(
                            onHorizontalDragEnd: (details) {
                              final velocity = details.primaryVelocity ?? 0;
                              if (velocity.abs() < 120) {
                                return;
                              }
                              _move(
                                velocity < 0
                                    ? _MoveDirection.left
                                    : _MoveDirection.right,
                              );
                            },
                            onVerticalDragEnd: (details) {
                              final velocity = details.primaryVelocity ?? 0;
                              if (velocity.abs() < 120) {
                                return;
                              }
                              _move(
                                velocity < 0
                                    ? _MoveDirection.up
                                    : _MoveDirection.down,
                              );
                            },
                            child: Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: const Color(0xFF6A6A6A),
                                borderRadius: BorderRadius.circular(28),
                                boxShadow: const [
                                  BoxShadow(
                                    color: Color(0x33000000),
                                    blurRadius: 18,
                                    offset: Offset(0, 10),
                                  ),
                                ],
                              ),
                              child: LayoutBuilder(
                                builder: (context, constraints) {
                                  final cellSize =
                                      (constraints.maxWidth -
                                          (_boardSpacing * 3)) /
                                      _boardSize;
                                  return Stack(
                                    children: [
                                      for (
                                        var row = 0;
                                        row < _boardSize;
                                        row++
                                      )
                                        for (
                                          var col = 0;
                                          col < _boardSize;
                                          col++
                                        )
                                          Positioned(
                                            left:
                                                col *
                                                (cellSize + _boardSpacing),
                                            top:
                                                row *
                                                (cellSize + _boardSpacing),
                                            width: cellSize,
                                            height: cellSize,
                                            child: Container(
                                              decoration: BoxDecoration(
                                                color:
                                                    const Color(0xFF8B8B8B),
                                                borderRadius:
                                                    BorderRadius.circular(18),
                                              ),
                                            ),
                                          ),
                                      for (final tile in _animatedTiles)
                                        _BoardTile(
                                          key: ValueKey(
                                            '${tile.id}-$_moveAnimationTick',
                                          ),
                                          tile: _TileVisual.fromValue(
                                            tile.value,
                                          ),
                                          fromRow: tile.fromRow,
                                          fromCol: tile.fromCol,
                                          toRow: tile.toRow,
                                          toCol: tile.toCol,
                                          cellSize: cellSize,
                                          spacing: _boardSpacing,
                                          durationMs:
                                              _currentAnimationDurationMs,
                                        ),
                                    ],
                                  );
                                },
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Center(
                      child: SizedBox(
                        width: (_bannerAd?.size.width ?? AdSize.banner.width)
                            .toDouble(),
                        height:
                            (_bannerAd?.size.height ?? AdSize.banner.height)
                                .toDouble(),
                        child: _bannerAd != null && _isBannerReady
                            ? AdWidget(ad: _bannerAd!)
                            : const SizedBox.shrink(),
                      ),
                    ),
                    if (_showPostTimeoutActions &&
                        (_isTimeUp ||
                            (_isChallengeMode &&
                                (_remainingMoves ?? 0) <= 0))) ...[
                      const SizedBox(height: 14),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          GestureDetector(
                            onTap: () => Navigator.of(context).maybePop(),
                            child: _Game2048ActionButton(
                              assetPath: 'assets/images/sopa/BotonClose.png',
                              label: _isSpanish ? 'Salir' : 'Exit',
                            ),
                          ),
                          const SizedBox(width: 16),
                          GestureDetector(
                            onTap: _resetGame,
                            child: _Game2048ActionButton(
                              assetPath: 'assets/images/sopa/BotonNext.png',
                              label: _isChallengeMode
                                  ? (_isSpanish ? 'Reintentar' : 'Retry')
                                  : (_isSpanish ? 'Siguiente' : 'Next'),
                            ),
                          ),
                        ],
                      ),
                    ],
                    const Spacer(),
                  ],
                ),
              ),
              const SizedBox(height: 10),
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
            ],
          ),
        ),
      ),
    );
  }
}

class _CoinsPill2048 extends StatelessWidget {
  const _CoinsPill2048({
    required this.coins,
    required this.onTap,
  });

  final String coins;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: SizedBox(
        width: 168,
        height: 64,
        child: Stack(
          clipBehavior: Clip.none,
          children: [
            Positioned(
              left: 0,
              top: 6,
              child: Container(
                width: 146,
                height: 56,
                padding: const EdgeInsets.symmetric(horizontal: 12),
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
                        coins,
                        maxLines: 1,
                        overflow: TextOverflow.visible,
                        textAlign: TextAlign.left,
                        style: const TextStyle(
                          fontFamily: 'Mikado',
                          fontSize: 22,
                          color: Color(0xFF1C2B39),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Positioned(
              right: 0,
              top: 0,
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
    );
  }
}

class _Game2048ActionButton extends StatelessWidget {
  const _Game2048ActionButton({
    required this.assetPath,
    required this.label,
  });

  final String assetPath;
  final String label;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 120,
      height: 54,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Image.asset(
            assetPath,
            width: 120,
            height: 54,
            fit: BoxFit.contain,
          ),
          Text(
            label,
            style: const TextStyle(
              fontFamily: 'Mikado',
              fontSize: 20,
              color: Color(0xFF1C2B39),
            ),
          ),
        ],
      ),
    );
  }
}

class _ScoreBadge extends StatelessWidget {
  const _ScoreBadge({
    required this.label,
    required this.value,
    this.width = 150,
    this.height = 58,
    this.labelFontSize = 16,
    this.valueFontSize = 34,
  });

  final String label;
  final String value;
  final double width;
  final double height;
  final double labelFontSize;
  final double valueFontSize;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: width,
      child: Column(
        children: [
          Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontFamily: 'Mikado',
              fontSize: labelFontSize,
              letterSpacing: 1.4,
              color: const Color(0xFFD3D3D3),
            ),
          ),
          const SizedBox(height: 6),
          Container(
            width: width,
            height: height,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: const Color(0xFF575757),
              borderRadius: BorderRadius.circular(20),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x26000000),
                  blurRadius: 12,
                  offset: Offset(0, 6),
                ),
              ],
              border: Border.all(
                color: const Color(0xFF8D8D8D),
                width: 1.2,
              ),
            ),
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                child: Text(
                  value,
                  maxLines: 1,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontFamily: 'Mikado',
                    fontSize: valueFontSize,
                    color: Colors.white,
                    height: 1,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _BoardTile extends StatelessWidget {
  const _BoardTile({
    super.key,
    required this.tile,
    required this.fromRow,
    required this.fromCol,
    required this.toRow,
    required this.toCol,
    required this.cellSize,
    required this.spacing,
    required this.durationMs,
  });

  final _TileVisual tile;
  final int fromRow;
  final int fromCol;
  final int toRow;
  final int toCol;
  final double cellSize;
  final double spacing;
  final int durationMs;

  @override
  Widget build(BuildContext context) {
    final start = Offset(
      fromCol * (cellSize + spacing),
      fromRow * (cellSize + spacing),
    );
    final end = Offset(
      toCol * (cellSize + spacing),
      toRow * (cellSize + spacing),
    );
    final fontSize = tile.value >= 1024 ? 26.0 : 32.0;

    return TweenAnimationBuilder<Offset>(
      tween: Tween<Offset>(begin: start, end: end),
      duration: Duration(milliseconds: durationMs),
      curve: Curves.easeInOutCubic,
      builder: (context, offset, child) {
        return Positioned(
          left: offset.dx,
          top: offset.dy,
          width: cellSize,
          height: cellSize,
          child: child!,
        );
      },
      child: ClipRRect(
        borderRadius: BorderRadius.circular(18),
        child: Stack(
          fit: StackFit.expand,
          children: [
            Image.asset(
              tile.assetPath,
              fit: BoxFit.cover,
            ),
            Center(
              child: Text(
                '${tile.value}',
                style: TextStyle(
                  fontFamily: 'Mikado',
                  color: tile.textColor,
                  fontSize: fontSize,
                  fontWeight: FontWeight.w900,
                  letterSpacing: -1.1,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TileVisual {
  const _TileVisual({
    required this.value,
    required this.assetPath,
    required this.textColor,
  });

  final int value;
  final String assetPath;
  final Color textColor;

  static _TileVisual fromValue(int value) {
    const dark = Color(0xFF173554);

    switch (value) {
      case 2:
        return const _TileVisual(
          value: 2,
          assetPath: 'assets/images/2048/azulito.png',
          textColor: Color(0xFF0E4D92),
        );
      case 4:
        return const _TileVisual(
          value: 4,
          assetPath: 'assets/images/2048/azul.png',
          textColor: Color(0xFF9BE7FF),
        );
      case 8:
        return const _TileVisual(
          value: 8,
          assetPath: 'assets/images/2048/cian.png',
          textColor: Color(0xFF00796B),
        );
      case 16:
        return const _TileVisual(
          value: 16,
          assetPath: 'assets/images/2048/verde.png',
          textColor: Color(0xFFE8FFE8),
        );
      case 32:
        return const _TileVisual(
          value: 32,
          assetPath: 'assets/images/2048/amarillo.png',
          textColor: Color(0xFF8A5A00),
        );
      case 64:
        return const _TileVisual(
          value: 64,
          assetPath: 'assets/images/2048/naranja.png',
          textColor: Color(0xFFFFF1D6),
        );
      case 128:
        return const _TileVisual(
          value: 128,
          assetPath: 'assets/images/2048/rojo.png',
          textColor: Color(0xFFFFD7D7),
        );
      case 256:
        return const _TileVisual(
          value: 256,
          assetPath: 'assets/images/2048/magenta.png',
          textColor: Color(0xFFFFE1FF),
        );
      case 512:
        return const _TileVisual(
          value: 512,
          assetPath: 'assets/images/2048/cafe.png',
          textColor: Color(0xFFFFE8CF),
        );
      case 1024:
        return const _TileVisual(
          value: 1024,
          assetPath: 'assets/images/2048/blanco.png',
          textColor: Color(0xFF6A1B9A),
        );
      default:
        return _TileVisual(
          value: value,
          assetPath: 'assets/images/2048/gris.png',
          textColor: dark,
        );
    }
  }
}

class _MoveResult {
  const _MoveResult({
    required this.board,
    required this.tiles,
    required this.gainedScore,
    required this.moved,
    required this.maxDistance,
    required this.createdValues,
  });

  final List<List<int>> board;
  final List<_AnimatedTile> tiles;
  final int gainedScore;
  final bool moved;
  final int maxDistance;
  final Map<int, int> createdValues;
}

class _AnimatedTile {
  const _AnimatedTile({
    required this.id,
    required this.value,
    required this.fromRow,
    required this.fromCol,
    required this.toRow,
    required this.toCol,
  });

  final int id;
  final int value;
  final int fromRow;
  final int fromCol;
  final int toRow;
  final int toCol;
}

class _IndexedValue {
  const _IndexedValue(this.fromIndex, this.value);

  final int fromIndex;
  final int value;
}

class _LineMotion {
  const _LineMotion(this.fromIndex, this.toIndex, this.value);

  final int fromIndex;
  final int toIndex;
  final int value;
}

class _LineResult {
  const _LineResult(this.line, this.score, this.motions, this.createdValues);

  final List<int> line;
  final int score;
  final List<_LineMotion> motions;
  final Map<int, int> createdValues;
}

class _ChallengeGoalPill extends StatelessWidget {
  const _ChallengeGoalPill({
    required this.value,
    required this.progress,
    required this.target,
  });

  final int value;
  final int progress;
  final int target;

  @override
  Widget build(BuildContext context) {
    final visual = _TileVisual.fromValue(value);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.16),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              image: DecorationImage(
                image: AssetImage(visual.assetPath),
                fit: BoxFit.cover,
              ),
              borderRadius: BorderRadius.circular(8),
            ),
            alignment: Alignment.center,
            child: Text(
              '$value',
              style: TextStyle(
                fontSize: 14,
                color: visual.textColor,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const SizedBox(width: 8),
          Text(
            '$progress / $target',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _ChallengeLevelCard extends StatelessWidget {
  const _ChallengeLevelCard({
    required this.levelNumber,
    required this.isLocked,
    required this.isCurrent,
    required this.isCompleted,
    required this.isSpanish,
    required this.onTap,
  });

  final int levelNumber;
  final bool isLocked;
  final bool isCurrent;
  final bool isCompleted;
  final bool isSpanish;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final borderColor = isCurrent
        ? const Color(0xFF6FB3FF)
        : Colors.white.withValues(alpha: 0.14);
    final cardColor = isCurrent
        ? const Color(0xFF4C5967)
        : const Color(0xFF4A4A4A);
    final status = isLocked
        ? (isSpanish ? 'Bloqueado' : 'Locked')
        : isCurrent
            ? (isSpanish ? 'Actual' : 'Current')
            : (isSpanish ? 'Completado' : 'Completed');

    return GestureDetector(
      onTap: isLocked ? null : onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
        decoration: BoxDecoration(
          color: cardColor,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: borderColor, width: isCurrent ? 2 : 1),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              isLocked
                  ? Icons.lock_rounded
                  : isCompleted
                      ? Icons.check_circle_rounded
                      : Icons.gps_fixed_rounded,
              color: isCurrent ? const Color(0xFF8FD0FF) : Colors.white70,
              size: 24,
            ),
            const SizedBox(height: 12),
            Text(
              isSpanish ? 'Nivel $levelNumber' : 'Level $levelNumber',
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              status,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: isCurrent ? const Color(0xFF8FD0FF) : Colors.white70,
                fontSize: 13,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

const List<Game2048ChallengeLevel> _fallbackGame2048ChallengeLevels = [
  Game2048ChallengeLevel(
    levelNumber: 1,
    moveLimit: 27,
    goals: [
      Game2048ChallengeGoal(tileValue: 8, targetCount: 7),
      Game2048ChallengeGoal(tileValue: 16, targetCount: 3),
      Game2048ChallengeGoal(tileValue: 32, targetCount: 1),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 2,
    moveLimit: 25,
    goals: [
      Game2048ChallengeGoal(tileValue: 8, targetCount: 5),
      Game2048ChallengeGoal(tileValue: 16, targetCount: 4),
      Game2048ChallengeGoal(tileValue: 32, targetCount: 2),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 3,
    moveLimit: 24,
    goals: [
      Game2048ChallengeGoal(tileValue: 16, targetCount: 6),
      Game2048ChallengeGoal(tileValue: 32, targetCount: 2),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 4,
    moveLimit: 22,
    goals: [
      Game2048ChallengeGoal(tileValue: 16, targetCount: 4),
      Game2048ChallengeGoal(tileValue: 32, targetCount: 3),
      Game2048ChallengeGoal(tileValue: 64, targetCount: 1),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 5,
    moveLimit: 20,
    goals: [
      Game2048ChallengeGoal(tileValue: 32, targetCount: 5),
      Game2048ChallengeGoal(tileValue: 64, targetCount: 1),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 6,
    moveLimit: 18,
    goals: [
      Game2048ChallengeGoal(tileValue: 32, targetCount: 3),
      Game2048ChallengeGoal(tileValue: 64, targetCount: 2),
      Game2048ChallengeGoal(tileValue: 128, targetCount: 1),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 7,
    moveLimit: 17,
    goals: [
      Game2048ChallengeGoal(tileValue: 64, targetCount: 3),
      Game2048ChallengeGoal(tileValue: 128, targetCount: 1),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 8,
    moveLimit: 16,
    goals: [
      Game2048ChallengeGoal(tileValue: 64, targetCount: 2),
      Game2048ChallengeGoal(tileValue: 128, targetCount: 2),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 9,
    moveLimit: 14,
    goals: [
      Game2048ChallengeGoal(tileValue: 64, targetCount: 2),
      Game2048ChallengeGoal(tileValue: 128, targetCount: 2),
      Game2048ChallengeGoal(tileValue: 256, targetCount: 1),
    ],
  ),
  Game2048ChallengeLevel(
    levelNumber: 10,
    moveLimit: 12,
    goals: [
      Game2048ChallengeGoal(tileValue: 128, targetCount: 3),
      Game2048ChallengeGoal(tileValue: 256, targetCount: 1),
    ],
  ),
];

enum _MoveDirection {
  left,
  right,
  up,
  down,
}
