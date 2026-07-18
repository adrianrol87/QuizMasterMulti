import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';

import '../../../core/ads/quiz_ad_service.dart';
import '../../../core/l10n/app_strings.dart';
import '../../../core/network/php_api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/data/mock_auth_repository.dart';
import '../../auth/models/app_user.dart';
import '../../bookmarks/data/bookmark_repository.dart';
import '../data/mock_quiz_repository.dart';
import '../data/quiz_result_repository.dart';
import '../models/quiz_category.dart';
import '../models/quiz_question.dart';
import '../models/quiz_subcategory.dart';

class QuestionScreen extends StatefulWidget {
  const QuestionScreen({
    super.key,
    required this.locale,
    required this.title,
    required this.category,
    this.selectedLevel,
    this.maxAvailableLevel,
    this.subcategory,
    this.quizRepository = const MockQuizRepository(),
    this.quizResultRepository = const MockQuizResultRepository(),
    this.authRepository = const MockAuthRepository(),
    this.currentUser,
    this.onUserUpdated,
    this.dailyQuizLanguageId,
    this.bookmarkRepository,
  });

  final Locale locale;
  final String title;
  final QuizCategory category;
  final int? selectedLevel;
  final int? maxAvailableLevel;
  final QuizSubcategory? subcategory;
  final QuizRepository quizRepository;
  final QuizResultRepository quizResultRepository;
  final AuthRepository authRepository;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;
  final String? dailyQuizLanguageId;
  final BookmarkRepository? bookmarkRepository;

  @override
  State<QuestionScreen> createState() => _QuestionScreenState();
}

class _QuestionScreenState extends State<QuestionScreen> {
  static const _secondsPerQuestion = 20;

  late Future<List<QuizQuestion>> _future;
  late AppUser? _activeUser;
  Timer? _timer;
  BannerAd? _bannerAd;
  bool _isBannerReady = false;
  int _secondsLeft = _secondsPerQuestion;
  int _currentIndex = 0;
  int _correctAnswers = 0;
  int _lastEarnedCoins = 0;
  String? _selectedAnswer;
  bool _answerSubmitted = false;
  bool _timeExpired = false;
  bool _isSavingResult = false;
  bool _rewardMultiplierApplied = false;
  late BookmarkRepository _bookmarkRepository;
  Set<String> _bookmarkedQuestionIds = <String>{};
  bool _updatingBookmark = false;

  @override
  void initState() {
    super.initState();
    _activeUser = widget.currentUser;
    _bookmarkRepository = widget.bookmarkRepository ??
        RemoteBookmarkRepository(apiClient: PhpApiClient());
    _future = _loadQuestions();
    _loadBookmarkIds();
    _prepareQuestionBanner();
  }

  Future<void> _loadBookmarkIds() async {
    final userId = _activeUser?.id ?? '';
    if (userId.isEmpty) return;
    try {
      final ids = await _bookmarkRepository.fetchBookmarkIds(userId);
      if (!mounted) return;
      setState(() => _bookmarkedQuestionIds = ids);
    } catch (_) {
      // Bookmarks do not block gameplay when the server is temporarily offline.
    }
  }

  Future<void> _toggleBookmark(QuizQuestion question) async {
    final userId = _activeUser?.id ?? '';
    if (userId.isEmpty || _updatingBookmark) return;

    final shouldBookmark = !_bookmarkedQuestionIds.contains(question.id);
    setState(() => _updatingBookmark = true);
    try {
      final saved = await _bookmarkRepository.setBookmark(
        userId: userId,
        questionId: question.id,
        bookmarked: shouldBookmark,
      );
      if (!mounted) return;
      setState(() {
        _updatingBookmark = false;
        if (saved) {
          _bookmarkedQuestionIds.add(question.id);
        } else {
          _bookmarkedQuestionIds.remove(question.id);
        }
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            widget.locale.languageCode == 'es'
                ? (saved ? 'Pregunta guardada.' : 'Pregunta eliminada.')
                : (saved ? 'Question saved.' : 'Question removed.'),
          ),
        ),
      );
    } catch (_) {
      if (!mounted) return;
      setState(() => _updatingBookmark = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            widget.locale.languageCode == 'es'
                ? 'No se pudo actualizar el marcador.'
                : 'Could not update the bookmark.',
          ),
        ),
      );
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _bannerAd?.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant QuestionScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.currentUser != widget.currentUser) {
      _activeUser = widget.currentUser;
      _loadBookmarkIds();
    }
  }

  Future<List<QuizQuestion>> _loadQuestions() {
    if (widget.dailyQuizLanguageId != null) {
      return widget.quizRepository.fetchDailyQuiz(
        languageId: widget.dailyQuizLanguageId,
        userId: _activeUser?.id,
      );
    }

    return widget.subcategory != null
        ? widget.quizRepository.fetchQuestionsBySubcategory(
            subcategoryId: widget.subcategory!.id,
            level: widget.selectedLevel,
          )
        : widget.quizRepository.fetchQuestionsByCategory(
            categoryId: widget.category.id,
            level: widget.selectedLevel,
          );
  }

  void _startTimer() {
    _timer?.cancel();
    _secondsLeft = _secondsPerQuestion;
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }

      if (_answerSubmitted) {
        timer.cancel();
        return;
      }

      if (_secondsLeft <= 1) {
        timer.cancel();
        setState(() {
          _secondsLeft = 0;
          _answerSubmitted = true;
          _timeExpired = true;
        });
        return;
      }

      setState(() {
        _secondsLeft -= 1;
      });
    });
  }

  void _resetQuestionState() {
    _timer?.cancel();
    _timer = null;
    _selectedAnswer = null;
    _answerSubmitted = false;
    _timeExpired = false;
    _startTimer();
  }

  void _restartQuiz() {
    _timer?.cancel();
    _timer = null;
    setState(() {
      _currentIndex = 0;
      _correctAnswers = 0;
      _lastEarnedCoins = 0;
      _rewardMultiplierApplied = false;
      _future = _loadQuestions();
      _selectedAnswer = null;
      _answerSubmitted = false;
      _timeExpired = false;
      _secondsLeft = _secondsPerQuestion;
    });
  }

  void _submitAnswer(QuizQuestion question) {
    if (_selectedAnswer == null || _answerSubmitted) {
      return;
    }

    final isCorrect = _selectedAnswer == question.correctOptionKey;
    _timer?.cancel();
    setState(() {
      _answerSubmitted = true;
      _timeExpired = false;
      if (isCorrect) {
        _correctAnswers += 1;
      }
    });
  }

  Future<void> _goNext(
    BuildContext context,
    List<QuizQuestion> questions,
  ) async {
    if (_currentIndex < questions.length - 1) {
      setState(() {
        _currentIndex += 1;
      });
      _resetQuestionState();
      return;
    }

    await _persistQuizResult(questions);
    if (!context.mounted) return;
    _timer?.cancel();
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _QuizResultSheet(
        locale: widget.locale,
        totalQuestions: questions.length,
        correctAnswers: _correctAnswers,
        earnedCoins: _lastEarnedCoins,
        canMultiplyCoins: _lastEarnedCoins > 0 &&
            !_rewardMultiplierApplied &&
            QuizAdService.instance.rewardMultiplierAvailable,
        onDoubleCoins: _handleRewardedCoinMultiplier,
        onPlayAgain: () {
          Navigator.of(context).pop();
          _restartQuiz();
        },
        onClose: () {
          Navigator.of(context).pop();
          Navigator.of(context).pop();
        },
      ),
    );
  }

  Future<void> _persistQuizResult(List<QuizQuestion> questions) async {
    final user = _activeUser;
    if (user == null || user.id.isEmpty || _isSavingResult) {
      return;
    }

    final totalQuestions = questions.length;
    if (totalQuestions <= 0) {
      return;
    }

    final earnedCoins = _calculateEarnedCoins(
      totalQuestions: totalQuestions,
      correctAnswers: _correctAnswers,
    );

    final resultCategoryId = questions.first.categoryId.trim().isNotEmpty
        ? questions.first.categoryId
        : widget.category.id;

    _isSavingResult = true;
    try {
      await widget.quizResultRepository.submitQuizResult(
        userId: user.id,
        categoryId: resultCategoryId,
        questionsAnswered: totalQuestions,
        correctAnswers: _correctAnswers,
        earnedCoins: earnedCoins,
      );

      final selectedLevel = widget.selectedLevel;
      final maxAvailableLevel = widget.maxAvailableLevel ?? 0;
      if (selectedLevel != null && maxAvailableLevel > 0) {
        final nextUnlockedLevel = selectedLevel >= maxAvailableLevel
            ? maxAvailableLevel
            : selectedLevel + 1;
        await widget.quizRepository.saveLevelProgress(
          userId: user.id,
          categoryId: widget.category.id,
          subcategoryId: widget.subcategory?.id,
          level: nextUnlockedLevel,
        );
      }

      final updatedUser = await widget.authRepository.refreshUser(user.id);
      _lastEarnedCoins = earnedCoins;
      _activeUser = updatedUser;
      widget.onUserUpdated?.call(updatedUser);
      await QuizAdService.instance.maybeShowInterstitialForLevelCompletion(
        completedLevel: widget.selectedLevel,
      );
    } catch (_) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No se pudo guardar el resultado en este intento.'),
        ),
      );
    } finally {
      _isSavingResult = false;
    }
  }

  Future<void> _prepareQuestionBanner() async {
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

  Future<int?> _handleRewardedCoinMultiplier() async {
    final user = _activeUser;
    if (user == null ||
        user.id.isEmpty ||
        _lastEarnedCoins <= 0 ||
        _rewardMultiplierApplied) {
      return null;
    }

    final rewarded = await QuizAdService.instance.showRewardedToMultiplyCoins();
    if (!rewarded) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content:
                Text(AppStrings(widget.locale).text('rewardAdUnavailable')),
          ),
        );
      }
      return null;
    }

    await widget.quizResultRepository.addBonusCoins(
      userId: user.id,
      coins: _lastEarnedCoins,
    );

    final updatedUser = await widget.authRepository.refreshUser(user.id);
    if (!mounted) {
      return null;
    }

    setState(() {
      _rewardMultiplierApplied = true;
      _lastEarnedCoins = _lastEarnedCoins * 2;
      _activeUser = updatedUser;
    });
    widget.onUserUpdated?.call(updatedUser);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(AppStrings(widget.locale).text('rewardAppliedBody')),
      ),
    );

    return _lastEarnedCoins;
  }

  int _calculateEarnedCoins({
    required int totalQuestions,
    required int correctAnswers,
  }) {
    if (totalQuestions <= 0 || correctAnswers <= 0) {
      return 0;
    }

    final percent = ((correctAnswers / totalQuestions) * 100).round();
    if (percent >= 100) {
      return 20;
    }
    if (percent >= 80) {
      return 15;
    }
    if (percent >= 60) {
      return 10;
    }
    if (percent >= 40) {
      return 6;
    }
    if (percent >= 20) {
      return 3;
    }
    return 0;
  }

  String _resolveAnswerLabel(QuizQuestion question) {
    final answerText = question.correctAnswerText.trim();
    if (answerText.isNotEmpty) {
      return answerText;
    }
    return question.correctOptionKey.trim().toUpperCase();
  }

  Future<void> _reportQuestion(QuizQuestion question) async {
    final user = _activeUser;
    if (user == null || user.id.isEmpty) {
      return;
    }

    final strings = AppStrings(widget.locale);
    final controller = TextEditingController();
    bool isSending = false;

    await showDialog<void>(
      context: context,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(strings.text('reportQuestionTitle')),
              content: TextField(
                controller: controller,
                maxLines: 4,
                textInputAction: TextInputAction.newline,
                decoration: InputDecoration(
                  hintText: strings.text('reportQuestionHint'),
                ),
              ),
              actions: [
                TextButton(
                  onPressed: isSending
                      ? null
                      : () => Navigator.of(dialogContext).pop(),
                  child: Text(strings.text('cancelAction')),
                ),
                FilledButton(
                  onPressed: isSending
                      ? null
                      : () async {
                          final message = controller.text.trim();
                          if (message.isEmpty) {
                            ScaffoldMessenger.of(this.context).showSnackBar(
                              SnackBar(
                                content:
                                    Text(strings.text('reportQuestionEmpty')),
                              ),
                            );
                            return;
                          }

                          setDialogState(() {
                            isSending = true;
                          });

                          try {
                            await widget.quizRepository.reportQuestion(
                              userId: user.id,
                              questionId: question.id,
                              message: message,
                            );
                            if (!mounted || !dialogContext.mounted) {
                              return;
                            }
                            Navigator.of(dialogContext).pop();
                            ScaffoldMessenger.of(this.context).showSnackBar(
                              SnackBar(
                                content:
                                    Text(strings.text('reportQuestionSuccess')),
                              ),
                            );
                          } catch (_) {
                            if (!mounted) {
                              return;
                            }
                            setDialogState(() {
                              isSending = false;
                            });
                            ScaffoldMessenger.of(this.context).showSnackBar(
                              SnackBar(
                                content:
                                    Text(strings.text('reportQuestionFailed')),
                              ),
                            );
                          }
                        },
                  child: Text(strings.text('reportQuestionSubmit')),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final strings = AppStrings(widget.locale);

    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.textColor(context),
        title: Text(
          widget.title,
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: FutureBuilder<List<QuizQuestion>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  '${snapshot.error}',
                  textAlign: TextAlign.center,
                ),
              ),
            );
          }

          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final questions = snapshot.data!;
          if (questions.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(strings.text('emptyQuestionSet')),
              ),
            );
          }

          if (_secondsLeft == _secondsPerQuestion &&
              !_answerSubmitted &&
              _timer == null) {
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (mounted && _timer == null) {
                _startTimer();
              }
            });
          }

          final question = questions[_currentIndex];
          final progress = (_currentIndex + 1) / questions.length;
          final isLast = _currentIndex == questions.length - 1;
          final currentAnswerLabel = _resolveAnswerLabel(question);

          return Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _QuizProgressHeader(
                  category: widget.category,
                  currentIndex: _currentIndex,
                  totalQuestions: questions.length,
                  correctAnswers: _correctAnswers,
                  progress: progress,
                  secondsLeft: _secondsLeft,
                  strings: strings,
                ),
                if (_bannerAd != null && _isBannerReady) ...[
                  const SizedBox(height: 12),
                  Container(
                    alignment: Alignment.center,
                    width: _bannerAd!.size.width.toDouble(),
                    height: _bannerAd!.size.height.toDouble(),
                    child: AdWidget(ad: _bannerAd!),
                  ),
                ],
                const SizedBox(height: 16),
                Expanded(
                  child: ListView(
                    children: [
                      if (widget.subcategory != null ||
                          widget.selectedLevel != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              if (widget.subcategory != null)
                                _ContextChip(
                                  icon: Icons.category_rounded,
                                  label: widget.subcategory!.title,
                                ),
                              if (widget.selectedLevel != null)
                                _ContextChip(
                                  icon: Icons.layers_rounded,
                                  label:
                                      '${strings.text('levelLabel')} ${widget.selectedLevel}',
                                ),
                            ],
                          ),
                        ),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          TextButton.icon(
                            onPressed: _updatingBookmark
                                ? null
                                : () => _toggleBookmark(question),
                            icon: Icon(
                              _bookmarkedQuestionIds.contains(question.id)
                                  ? Icons.bookmark_rounded
                                  : Icons.bookmark_border_rounded,
                            ),
                            label: Text(
                              widget.locale.languageCode == 'es'
                                  ? (_bookmarkedQuestionIds
                                          .contains(question.id)
                                      ? 'Quitar'
                                      : 'Guardar')
                                  : (_bookmarkedQuestionIds
                                          .contains(question.id)
                                      ? 'Remove'
                                      : 'Save'),
                            ),
                          ),
                          TextButton.icon(
                            onPressed: () => _reportQuestion(question),
                            icon: const Icon(Icons.flag_outlined),
                            label: Text(strings.text('reportQuestionAction')),
                          ),
                        ],
                      ),
                      _QuestionCard(
                        category: widget.category,
                        question: question,
                        locale: widget.locale,
                      ),
                      const SizedBox(height: 14),
                      if (!_answerSubmitted)
                        _InlineTimerBanner(
                          secondsLeft: _secondsLeft,
                          strings: strings,
                        )
                      else if (_timeExpired)
                        _InlineAlertBanner(
                          label: strings.text('timeUpLabel'),
                        ),
                      const SizedBox(height: 18),
                      ...question.options.asMap().entries.map((entry) {
                        final optionIndex = entry.key;
                        final option = entry.value;
                        final letter = String.fromCharCode(65 + optionIndex);
                        final optionKey = letter.toLowerCase();
                        final isSelected = _selectedAnswer == optionKey;
                        final isCorrect =
                            question.correctOptionKey == optionKey;
                        final showCorrect = _answerSubmitted && isCorrect;
                        final showWrong =
                            _answerSubmitted && isSelected && !isCorrect;

                        return Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: _AnswerTile(
                            letter: letter,
                            text: option,
                            selected: isSelected,
                            correct: showCorrect,
                            wrong: showWrong,
                            locked: _answerSubmitted,
                            onTap: () {
                              if (_answerSubmitted) {
                                return;
                              }
                              setState(() {
                                _selectedAnswer = optionKey;
                              });
                            },
                          ),
                        );
                      }),
                      if (_answerSubmitted) ...[
                        const SizedBox(height: 8),
                        _AnswerFeedbackCard(
                          strings: strings,
                          isCorrect:
                              _selectedAnswer == question.correctOptionKey,
                          correctAnswerLabel: currentAnswerLabel,
                          note: question.note,
                          timedOut: _timeExpired,
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                if (!_answerSubmitted)
                  FilledButton(
                    onPressed: _selectedAnswer == null
                        ? null
                        : () => _submitAnswer(question),
                    child: Text(strings.text('submitAnswer')),
                  )
                else
                  FilledButton(
                    onPressed: () => _goNext(context, questions),
                    child: Text(
                      isLast
                          ? strings.text('finishQuiz')
                          : strings.text('nextQuestion'),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _QuizProgressHeader extends StatelessWidget {
  const _QuizProgressHeader({
    required this.category,
    required this.currentIndex,
    required this.totalQuestions,
    required this.correctAnswers,
    required this.progress,
    required this.secondsLeft,
    required this.strings,
  });

  final QuizCategory category;
  final int currentIndex;
  final int totalQuestions;
  final int correctAnswers;
  final double progress;
  final int secondsLeft;
  final AppStrings strings;

  @override
  Widget build(BuildContext context) {
    final timerColor = secondsLeft <= 5 ? AppTheme.danger : category.color;
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: const [
          BoxShadow(
            color: Color(0x12162E4A),
            blurRadius: 18,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              _MiniStat(
                label: strings.text('questionLabel'),
                value: '${currentIndex + 1}/$totalQuestions',
              ),
              const SizedBox(width: 12),
              _MiniStat(
                label: strings.text('scoreLabel'),
                value: '$correctAnswers',
              ),
              const SizedBox(width: 12),
              _MiniStat(
                label: strings.text('timeLabel'),
                value: '${secondsLeft}s',
                valueColor: timerColor,
              ),
              const Spacer(),
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: category.color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(category.icon, color: category.color),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ClipRRect(
            borderRadius: BorderRadius.circular(999),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 10,
              backgroundColor: const Color(0xFFE5EEF8),
              valueColor: AlwaysStoppedAnimation<Color>(category.color),
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  const _MiniStat({
    required this.label,
    required this.value,
    this.valueColor,
  });

  final String label;
  final String value;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: const Color(0xFF7A8DA2),
                fontWeight: FontWeight.w700,
              ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                color: valueColor ?? AppTheme.ink,
                fontWeight: FontWeight.w900,
              ),
        ),
      ],
    );
  }
}

class _ContextChip extends StatelessWidget {
  const _ContextChip({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: const Color(0xFFE1E9F3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 16,
            color: AppTheme.primary,
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              color: AppTheme.ink,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _QuestionCard extends StatelessWidget {
  const _QuestionCard({
    required this.category,
    required this.question,
    required this.locale,
  });

  final QuizCategory category;
  final QuizQuestion question;
  final Locale locale;

  @override
  Widget build(BuildContext context) {
    final hasImage = question.imageUrl.trim().isNotEmpty;

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            category.color,
            category.color.withValues(alpha: 0.78),
          ],
        ),
        borderRadius: BorderRadius.circular(26),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1812304A),
            blurRadius: 18,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.16),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Text(
              '${AppStrings(locale).text('levelLabel')} ${question.level == 0 ? 1 : question.level}',
              style: const TextStyle(
                color: Color(0xFFE3F1FF),
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 14),
          if (hasImage) ...[
            ClipRRect(
              borderRadius: BorderRadius.circular(20),
              child: AspectRatio(
                aspectRatio: 16 / 9,
                child: Image.network(
                  question.imageUrl,
                  fit: BoxFit.cover,
                  loadingBuilder: (context, child, loadingProgress) {
                    if (loadingProgress == null) {
                      return child;
                    }

                    return Container(
                      color: Colors.white.withValues(alpha: 0.12),
                      alignment: Alignment.center,
                      child: const CircularProgressIndicator(
                        color: Colors.white,
                      ),
                    );
                  },
                  errorBuilder: (context, error, stackTrace) {
                    return Container(
                      color: Colors.white.withValues(alpha: 0.12),
                      alignment: Alignment.center,
                      child: const Icon(
                        Icons.broken_image_rounded,
                        color: Colors.white,
                        size: 40,
                      ),
                    );
                  },
                ),
              ),
            ),
            const SizedBox(height: 16),
          ],
          Text(
            question.question,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 24,
              height: 1.25,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _InlineTimerBanner extends StatelessWidget {
  const _InlineTimerBanner({
    required this.secondsLeft,
    required this.strings,
  });

  final int secondsLeft;
  final AppStrings strings;

  @override
  Widget build(BuildContext context) {
    final isCritical = secondsLeft <= 5;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: isCritical ? const Color(0xFFFFF1F1) : Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isCritical ? const Color(0xFFFFD2D2) : const Color(0xFFE4ECF5),
        ),
      ),
      child: Row(
        children: [
          Icon(
            Icons.timer_outlined,
            color: isCritical ? AppTheme.danger : AppTheme.primary,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              '${strings.text('timeLabel')}: ${secondsLeft}s',
              style: TextStyle(
                color: isCritical ? AppTheme.danger : AppTheme.ink,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InlineAlertBanner extends StatelessWidget {
  const _InlineAlertBanner({
    required this.label,
  });

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF1F1),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFFFD2D2)),
      ),
      child: Row(
        children: [
          const Icon(Icons.warning_amber_rounded, color: AppTheme.danger),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                color: AppTheme.danger,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AnswerTile extends StatelessWidget {
  const _AnswerTile({
    required this.letter,
    required this.text,
    required this.selected,
    required this.correct,
    required this.wrong,
    required this.locked,
    required this.onTap,
  });

  final String letter;
  final String text;
  final bool selected;
  final bool correct;
  final bool wrong;
  final bool locked;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    Color border = const Color(0xFFE2EAF4);
    Color background = Colors.white;
    Color badge = const Color(0xFFF1F6FB);
    Color badgeText = AppTheme.ink;

    if (selected) {
      border = const Color(0xFF8DBEF4);
      background = const Color(0xFFEAF4FF);
      badge = const Color(0xFFD6E9FF);
    }
    if (correct) {
      border = AppTheme.success;
      background = const Color(0xFFEAFBF0);
      badge = AppTheme.success;
      badgeText = Colors.white;
    }
    if (wrong) {
      border = AppTheme.danger;
      background = const Color(0xFFFFEFEF);
      badge = AppTheme.danger;
      badgeText = Colors.white;
    }

    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: locked ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: background,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: border,
            width: selected || correct || wrong ? 2 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: badge,
                borderRadius: BorderRadius.circular(12),
              ),
              alignment: Alignment.center,
              child: Text(
                letter,
                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w900,
                      color: badgeText,
                    ),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Text(
                text,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      color: AppTheme.ink,
                      fontWeight: FontWeight.w700,
                    ),
              ),
            ),
            if (correct)
              const Icon(Icons.check_circle_rounded, color: AppTheme.success)
            else if (wrong)
              const Icon(Icons.cancel_rounded, color: AppTheme.danger),
          ],
        ),
      ),
    );
  }
}

class _AnswerFeedbackCard extends StatelessWidget {
  const _AnswerFeedbackCard({
    required this.strings,
    required this.isCorrect,
    required this.correctAnswerLabel,
    required this.note,
    required this.timedOut,
  });

  final AppStrings strings;
  final bool isCorrect;
  final String correctAnswerLabel;
  final String note;
  final bool timedOut;

  @override
  Widget build(BuildContext context) {
    final title = isCorrect
        ? strings.text('correctFeedback')
        : timedOut
            ? '${strings.text('timeUpLabel')} ${strings.text('correctAnswerLabel')}: $correctAnswerLabel'
            : '${strings.text('correctAnswerLabel')}: $correctAnswerLabel';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isCorrect ? const Color(0xFFEAFBF0) : const Color(0xFFFFF2F2),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: isCorrect ? const Color(0xFFB8EBC8) : const Color(0xFFFFD0D0),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  color: isCorrect
                      ? const Color(0xFF208447)
                      : const Color(0xFFB64040),
                  fontWeight: FontWeight.w900,
                ),
          ),
          if (note.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              note,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF53687D),
                    height: 1.45,
                  ),
            ),
          ],
        ],
      ),
    );
  }
}

class _QuizResultSheet extends StatefulWidget {
  const _QuizResultSheet({
    required this.locale,
    required this.totalQuestions,
    required this.correctAnswers,
    required this.earnedCoins,
    required this.canMultiplyCoins,
    required this.onDoubleCoins,
    required this.onPlayAgain,
    required this.onClose,
  });

  final Locale locale;
  final int totalQuestions;
  final int correctAnswers;
  final int earnedCoins;
  final bool canMultiplyCoins;
  final Future<int?> Function() onDoubleCoins;
  final VoidCallback onPlayAgain;
  final VoidCallback onClose;

  @override
  State<_QuizResultSheet> createState() => _QuizResultSheetState();
}

class _QuizResultSheetState extends State<_QuizResultSheet> {
  late int _displayCoins;
  bool _isApplyingReward = false;
  bool _rewardApplied = false;

  @override
  void initState() {
    super.initState();
    _displayCoins = widget.earnedCoins;
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(widget.locale);
    final percent = widget.totalQuestions == 0
        ? 0
        : ((widget.correctAnswers / widget.totalQuestions) * 100).round();

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(32)),
      ),
      padding: const EdgeInsets.fromLTRB(24, 18, 24, 30),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 46,
              height: 5,
              decoration: BoxDecoration(
                color: const Color(0xFFD7E2EE),
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            const SizedBox(height: 22),
            Container(
              width: 104,
              height: 104,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: const Color(0xFFEAF4FF),
                border: Border.all(color: const Color(0xFFC6DFFF), width: 8),
              ),
              alignment: Alignment.center,
              child: Text(
                '$percent%',
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      color: AppTheme.primary,
                      fontWeight: FontWeight.w900,
                    ),
              ),
            ),
            const SizedBox(height: 18),
            Text(
              strings.text('quizFinishedTitle'),
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.w900,
                    color: AppTheme.ink,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              strings.text('quizFinishedBody').replaceFirst(
                    '{score}',
                    '${widget.correctAnswers}/${widget.totalQuestions}',
                  ),
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    color: const Color(0xFF587087),
                  ),
            ),
            if (_displayCoins > 0) ...[
              const SizedBox(height: 12),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFFEAF7EF),
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: const Color(0xFFCBE9D3)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.monetization_on_rounded,
                      color: Color(0xFF2E9B4B),
                    ),
                    const SizedBox(width: 10),
                    Text(
                      strings.text('coinsEarnedBody').replaceFirst(
                            '{coins}',
                            '$_displayCoins',
                          ),
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            color: const Color(0xFF267F3D),
                            fontWeight: FontWeight.w800,
                          ),
                    ),
                  ],
                ),
              ),
              if (widget.canMultiplyCoins && !_rewardApplied) ...[
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: _isApplyingReward
                      ? null
                      : () async {
                          setState(() {
                            _isApplyingReward = true;
                          });
                          final updatedCoins = await widget.onDoubleCoins();
                          if (!mounted) {
                            return;
                          }
                          setState(() {
                            _isApplyingReward = false;
                            if (updatedCoins != null) {
                              _displayCoins = updatedCoins;
                              _rewardApplied = true;
                            }
                          });
                        },
                  icon: const Icon(Icons.ondemand_video_rounded),
                  label: Text(
                    _isApplyingReward
                        ? strings.text('saving')
                        : strings.text('doubleCoinsAction'),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  strings.text('doubleCoinsBody'),
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF587087),
                      ),
                ),
              ],
            ],
            const SizedBox(height: 22),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: widget.onClose,
                    child: Text(strings.text('closeAction')),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(
                    onPressed: widget.onPlayAgain,
                    child: Text(strings.text('playAgainAction')),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
