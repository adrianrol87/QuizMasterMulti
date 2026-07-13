import 'package:flutter/material.dart';

import '../../../../core/l10n/app_strings.dart';
import '../../../../core/network/php_api_client.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../auth/data/auth_repository.dart';
import '../../../auth/data/mock_auth_repository.dart';
import '../../../auth/models/app_user.dart';
import '../../../config/models/system_config.dart';
import '../../../game_2048/presentation/game_2048_screen.dart';
import '../../../quiz/data/mock_quiz_repository.dart';
import '../../../quiz/data/quiz_result_repository.dart';
import '../../../quiz/models/quiz_category.dart';
import '../../../quiz/presentation/question_screen.dart';
import '../../../quiz/presentation/quiz_zone_screen.dart';
import '../../../quiz/presentation/subcategory_screen.dart';
import '../../../word_search/data/word_search_repository.dart';
import '../../../word_search/presentation/word_search_preview_screen.dart';

class DashboardSections extends StatelessWidget {
  const DashboardSections({
    super.key,
    required this.strings,
    required this.locale,
    required this.systemConfig,
    required this.categories,
    required this.quizRepository,
    required this.quizResultRepository,
    required this.currentUser,
    required this.authRepository,
    required this.onUserUpdated,
  });

  final AppStrings strings;
  final Locale locale;
  final SystemConfig systemConfig;
  final List<QuizCategory> categories;
  final QuizRepository quizRepository;
  final QuizResultRepository quizResultRepository;
  final AppUser? currentUser;
  final AuthRepository authRepository;
  final ValueChanged<AppUser> onUserUpdated;

  @override
  Widget build(BuildContext context) {
    final wordSearchRepository = RemoteWordSearchRepository(
      apiClient: PhpApiClient(),
    );

    final playZoneItems = <_ModeCardData>[
      if (systemConfig.dailyQuizMode)
        _ModeCardData(
          title: strings.text('dailyQuiz'),
          start: const Color(0xFFFF9E3A),
          end: const Color(0xFFF0842E),
          icon: Icons.lock_outline_rounded,
          onTap: () => _openDailyQuiz(context),
        ),
      if (systemConfig.trueFalseMode)
        _ModeCardData(
          title: strings.text('trueFalse'),
          start: const Color(0xFF65CFEA),
          end: const Color(0xFF3FB0D9),
          icon: Icons.play_arrow_rounded,
          onTap: () => _openFirstQuestionSet(
            context,
            title: strings.text('trueFalse'),
          ),
        ),
      if (systemConfig.spinMode)
        _ModeCardData(
          title: strings.text('randomQuiz'),
          start: const Color(0xFF70C36C),
          end: const Color(0xFF53AB59),
          icon: Icons.lock_outline_rounded,
          onTap: () => Navigator.of(context).pushNamed(
            QuizZoneScreen.routeName,
          ),
        ),
        _ModeCardData(
          title: strings.text('selfChallenge'),
          start: const Color(0xFFC86BE8),
          end: const Color(0xFFAA56DF),
          icon: Icons.play_arrow_rounded,
        onTap: () => _openFirstPlayableCategory(
          context,
            fallbackTitle: strings.text('selfChallenge'),
          ),
        ),
    ];

    final learningZoneItems = <_ModeCardData>[
      if (systemConfig.learningZoneMode)
        _ModeCardData(
          title: strings.text('learningZone'),
          start: const Color(0xFF53B4FF),
          end: const Color(0xFF2F7FDD),
          icon: Icons.menu_book_rounded,
          fullWidth: true,
          onTap: () => _showModePreview(
            context,
            title: strings.text('learningZone'),
          ),
        ),
    ];

    final mathsQuizItems = <_ModeCardData>[
      if (systemConfig.mathsQuizMode)
        _ModeCardData(
          title: strings.text('mathsQuiz'),
          start: const Color(0xFFFFBF4E),
          end: const Color(0xFFF39A2E),
          icon: Icons.functions_rounded,
          fullWidth: true,
          onTap: () => _showModePreview(
            context,
            title: strings.text('mathsQuiz'),
          ),
        ),
    ];

    final battleZoneItems = <_ModeCardData>[
      if (systemConfig.battleGroupCategoryMode)
        _ModeCardData(
          title: strings.text('groupBattle'),
          start: const Color(0xFF4298E9),
          end: const Color(0xFF2678D7),
          icon: Icons.groups_2_rounded,
          fullWidth: true,
          onTap: () => _showModePreview(
            context,
            title: strings.text('groupBattle'),
          ),
        ),
      if (systemConfig.battleRandomCategoryMode)
        _ModeCardData(
          title: strings.text('randomBattle'),
          start: const Color(0xFF5EC7E8),
          end: const Color(0xFF62D0B4),
          icon: Icons.casino_rounded,
          fullWidth: true,
          onTap: () => _showModePreview(
            context,
            title: strings.text('randomBattle'),
          ),
        ),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _SectionHeader(
          title: strings.text('quizZone'),
          action: strings.text('viewAll'),
          onActionTap: () => Navigator.of(context).pushNamed(
            QuizZoneScreen.routeName,
          ),
        ),
        const SizedBox(height: 12),
        if (categories.isEmpty)
          _EmptySectionCard(
            title: strings.text('emptyQuizZoneTitle'),
            body: strings.text('emptyQuizZoneBody'),
          )
        else
          _CategoryScroller(
            strings: strings,
            categories: categories,
            locale: locale,
            quizRepository: quizRepository,
            quizResultRepository: quizResultRepository,
            currentUser: currentUser,
            authRepository: authRepository,
            onUserUpdated: onUserUpdated,
          ),
        const SizedBox(height: 24),
        _SectionHeader(
          title: strings.text('puzzleZone'),
          icon: Icons.grid_4x4_rounded,
        ),
        const SizedBox(height: 14),
        _ModeGrid(
          strings: strings,
          items: [
            _ModeCardData(
              title: strings.text('game2048'),
              start: const Color(0xFF53B4FF),
              end: const Color(0xFF2B80D8),
              icon: Icons.grid_view_rounded,
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => Game2048Screen(
                    locale: locale,
                    coins: (currentUser?.coins ?? '0').toString(),
                    modeKey: 'classic',
                    titleOverride:
                        locale.languageCode == 'es' ? '2048 CLASICO' : '2048 CLASSIC',
                    currentUser: currentUser,
                    onUserUpdated: onUserUpdated,
                  ),
                ),
              ),
            ),
            _ModeCardData(
              title: strings.text('game2048Challenges'),
              start: const Color(0xFF5C8DFF),
              end: const Color(0xFF3659D9),
              icon: Icons.emoji_events_rounded,
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => Game2048ChallengesLevelScreen(
                    locale: locale,
                    coins: (currentUser?.coins ?? '0').toString(),
                    currentUser: currentUser,
                    onUserUpdated: onUserUpdated,
                  ),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        _SectionHeader(
          title: locale.languageCode == 'es' ? 'Sopa de Letras' : 'Word Search',
          action: strings.text('viewAll'),
          onActionTap: () => Navigator.of(context).push(
            MaterialPageRoute<void>(
              builder: (_) => WordSearchPreviewScreen(
                locale: locale,
                coins: (currentUser?.coins ?? '0').toString(),
                currentUser: currentUser,
                onUserUpdated: onUserUpdated,
                wordSearchRepository: wordSearchRepository,
                quizResultRepository: quizResultRepository,
              ),
            ),
          ),
        ),
        const SizedBox(height: 12),
        _WordSearchCategoryScroller(
          locale: locale,
          currentUser: currentUser,
          onUserUpdated: onUserUpdated,
          wordSearchRepository: wordSearchRepository,
          quizResultRepository: quizResultRepository,
        ),
        if (playZoneItems.isNotEmpty) ...[
          const SizedBox(height: 24),
          _SectionHeader(
            title: strings.text('playZone'),
            icon: Icons.play_arrow_rounded,
          ),
          const SizedBox(height: 14),
          _ModeGrid(
            strings: strings,
            items: playZoneItems,
          ),
        ],
        if (battleZoneItems.isNotEmpty) ...[
          const SizedBox(height: 24),
          _SectionHeader(
            title: strings.text('battleZone'),
            icon: Icons.sports_martial_arts_rounded,
          ),
          const SizedBox(height: 14),
          _ModeGrid(
            strings: strings,
            items: battleZoneItems,
          ),
        ],
        if (learningZoneItems.isNotEmpty) ...[
          const SizedBox(height: 24),
          _SectionHeader(
            title: strings.text('learningZone'),
            icon: Icons.menu_book_rounded,
          ),
          const SizedBox(height: 14),
          _ModeGrid(
            strings: strings,
            items: learningZoneItems,
          ),
        ],
        if (mathsQuizItems.isNotEmpty) ...[
          const SizedBox(height: 24),
          _SectionHeader(
            title: strings.text('mathsQuiz'),
            icon: Icons.functions_rounded,
          ),
          const SizedBox(height: 14),
          _ModeGrid(
            strings: strings,
            items: mathsQuizItems,
          ),
        ],
        if (systemConfig.contestMode) ...[
          const SizedBox(height: 24),
          _SectionHeader(
            title: strings.text('contests'),
            icon: Icons.emoji_events_rounded,
          ),
          const SizedBox(height: 14),
          _ContestCard(strings: strings),
        ],
      ],
    );
  }

  void _openFirstPlayableCategory(
    BuildContext context, {
    required String fallbackTitle,
  }) {
    if (categories.isEmpty) {
      _showNoContentSnack(context);
      return;
    }

    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => SubcategoryScreen(
          locale: locale,
          category: categories.first,
          quizRepository: quizRepository,
          quizResultRepository: quizResultRepository,
          currentUser: currentUser,
          authRepository: authRepository,
          onUserUpdated: onUserUpdated,
        ),
      ),
    );
  }

  void _openDailyQuiz(BuildContext context) {
    final fallbackCategory = categories.isNotEmpty
        ? categories.first
        : QuizCategory(
            id: '0',
            languageId: locale.languageCode == 'es' ? '1' : '2',
            title: strings.text('dailyQuiz'),
            questionCount: 0,
            subcategoryCount: 0,
            maxLevel: 0,
            plan: 'Free',
            amount: 0,
            isPurchased: true,
            rowOrder: 0,
            imageUrl: '',
            color: const Color(0xFFFF9E3A),
            icon: Icons.today_rounded,
          );

    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => QuestionScreen(
          locale: locale,
          title: strings.text('dailyQuiz'),
          category: fallbackCategory,
          quizRepository: quizRepository,
          quizResultRepository: quizResultRepository,
          currentUser: currentUser,
          authRepository: authRepository,
          onUserUpdated: onUserUpdated,
          dailyQuizLanguageId: locale.languageCode == 'es' ? '1' : '2',
        ),
      ),
    );
  }

  void _openFirstQuestionSet(
    BuildContext context, {
    required String title,
  }) {
    if (categories.isEmpty) {
      _showNoContentSnack(context);
      return;
    }

    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => QuestionScreen(
          locale: locale,
          title: title,
          category: categories.first,
          quizRepository: quizRepository,
          quizResultRepository: quizResultRepository,
          currentUser: currentUser,
          authRepository: authRepository,
          onUserUpdated: onUserUpdated,
        ),
      ),
    );
  }

  void _showModePreview(
    BuildContext context, {
    required String title,
  }) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
      ),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: AppTheme.ink,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  strings.text('modePreviewBody'),
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF5B6B7C),
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 18),
                FilledButton(
                  onPressed: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).pushNamed(QuizZoneScreen.routeName);
                  },
                  child: Text(strings.text('openQuizZone')),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showNoContentSnack(BuildContext context) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(strings.text('emptyQuizZoneBody')),
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({
    required this.title,
    this.action,
    this.icon,
    this.onActionTap,
  });

  final String title;
  final String? action;
  final IconData? icon;
  final VoidCallback? onActionTap;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        if (icon != null) ...[
          Icon(icon, size: 22, color: AppTheme.ink),
          const SizedBox(width: 8),
        ],
        Text(
          title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
        ),
        const Spacer(),
        if (action != null)
          InkWell(
            borderRadius: BorderRadius.circular(10),
            onTap: onActionTap,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
              child: Text(
                action!,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: const Color(0xFF495569),
                    ),
              ),
            ),
          ),
      ],
    );
  }
}

class _CategoryScroller extends StatelessWidget {
  const _CategoryScroller({
    required this.strings,
    required this.categories,
    required this.locale,
    required this.quizRepository,
    required this.quizResultRepository,
    required this.currentUser,
    required this.authRepository,
    required this.onUserUpdated,
  });

  final AppStrings strings;
  final List<QuizCategory> categories;
  final Locale locale;
  final QuizRepository quizRepository;
  final QuizResultRepository quizResultRepository;
  final AppUser? currentUser;
  final AuthRepository authRepository;
  final ValueChanged<AppUser> onUserUpdated;

  @override
  Widget build(BuildContext context) {
    final visibleItems = categories.length > 5 ? categories.take(5).toList() : categories;
    return SizedBox(
      height: 160,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: visibleItems.length,
        separatorBuilder: (context, index) => const SizedBox(width: 14),
        itemBuilder: (context, index) {
          final item = visibleItems[index];
          return _QuizZoneBanner(
            item: item,
            strings: strings,
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => SubcategoryScreen(
                    locale: locale,
                    category: item,
                    quizRepository: quizRepository,
                    quizResultRepository: quizResultRepository,
                    currentUser: currentUser,
                    authRepository: authRepository,
                    onUserUpdated: onUserUpdated,
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class _WordSearchCategoryScroller extends StatelessWidget {
  const _WordSearchCategoryScroller({
    required this.locale,
    required this.currentUser,
    required this.onUserUpdated,
    required this.wordSearchRepository,
    required this.quizResultRepository,
  });

  final Locale locale;
  final AppUser? currentUser;
  final ValueChanged<AppUser> onUserUpdated;
  final WordSearchRepository wordSearchRepository;
  final QuizResultRepository quizResultRepository;

  @override
  Widget build(BuildContext context) {
    final languageId = locale.languageCode == 'es' ? '1' : '2';

    return FutureBuilder<List<WordSearchCategory>>(
      future: wordSearchRepository.fetchCategories(
        languageId: languageId,
        userId: currentUser?.id,
      ),
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const SizedBox(
            height: 160,
            child: Center(child: CircularProgressIndicator()),
          );
        }

        if (snapshot.hasError) {
          return _EmptySectionCard(
            title: locale.languageCode == 'es'
                ? 'Sopa de Letras no disponible'
                : 'Word Search unavailable',
            body: locale.languageCode == 'es'
                ? 'No se pudieron cargar las categorias de este modo por ahora.'
                : 'The categories for this mode could not be loaded right now.',
          );
        }

        final items = snapshot.data ?? const <WordSearchCategory>[];
        if (items.isEmpty) {
          return _EmptySectionCard(
            title: locale.languageCode == 'es'
                ? 'Aun no hay categorias de Sopa de Letras'
                : 'No Word Search categories yet',
            body: locale.languageCode == 'es'
                ? 'Carga categorias y niveles desde el admin panel para mostrarlas aqui.'
                : 'Upload categories and levels from the admin panel to show them here.',
          );
        }

        final visibleItems = items.length > 5 ? items.take(5).toList() : items;
        return SizedBox(
          height: 160,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: visibleItems.length,
            separatorBuilder: (context, index) => const SizedBox(width: 14),
            itemBuilder: (context, index) {
              final item = visibleItems[index];
              return _WordSearchBanner(
                item: item,
                locale: locale,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => WordSearchPreviewScreen(
                        locale: locale,
                        coins: (currentUser?.coins ?? '0').toString(),
                        currentUser: currentUser,
                        onUserUpdated: onUserUpdated,
                        wordSearchRepository: wordSearchRepository,
                        quizResultRepository: quizResultRepository,
                        initialCategoryId: item.id,
                        initialCategory: item,
                      ),
                    ),
                  );
                },
              );
            },
          ),
        );
      },
    );
  }
}

class _ModeGrid extends StatelessWidget {
  const _ModeGrid({
    required this.strings,
    required this.items,
  });

  final AppStrings strings;
  final List<_ModeCardData> items;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final rows = <Widget>[];
        var pending = <_ModeCardData>[];

        void flushPending() {
          if (pending.isEmpty) {
            return;
          }
          rows.add(
            Padding(
              padding: const EdgeInsets.only(bottom: 14),
              child: Row(
                children: [
                  Expanded(
                    child: _ModeCard(
                      strings: strings,
                      item: pending[0],
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: pending.length > 1
                        ? _ModeCard(
                            strings: strings,
                            item: pending[1],
                          )
                        : const SizedBox.shrink(),
                  ),
                ],
              ),
            ),
          );
          pending = <_ModeCardData>[];
        }

        for (final item in items) {
          if (item.fullWidth) {
            flushPending();
            rows.add(
              Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: _ModeCard(
                  strings: strings,
                  item: item,
                ),
              ),
            );
            continue;
          }

          pending.add(item);
          if (pending.length == 2) {
            flushPending();
          }
        }

        flushPending();
        return Column(
          children: rows,
        );
      },
    );
  }
}

class _WordSearchBanner extends StatelessWidget {
  const _WordSearchBanner({
    required this.item,
    required this.locale,
    required this.onTap,
  });

  final WordSearchCategory item;
  final Locale locale;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isSpanish = locale.languageCode == 'es';

    return InkWell(
      borderRadius: BorderRadius.circular(28),
      onTap: onTap,
      child: Container(
        width: 292,
        padding: const EdgeInsets.fromLTRB(22, 20, 22, 20),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Color(0xFF2F7FDD),
              Color(0xFF55B8FF),
            ],
          ),
          borderRadius: BorderRadius.circular(28),
          boxShadow: const [
            BoxShadow(
              color: Color(0x1A10253D),
              blurRadius: 22,
              offset: Offset(0, 12),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                if (item.isPremium)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.16),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Text(
                      item.isPurchased
                          ? (isSpanish ? 'Comprado' : 'Purchased')
                          : '${item.amount} ${isSpanish ? 'monedas' : 'coins'}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                        fontSize: 11,
                      ),
                    ),
                  ),
                const Spacer(),
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: item.isPremium && !item.isPurchased
                        ? const Color(0xFFE74C3C)
                        : Colors.white.withValues(alpha: 0.22),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    item.isPremium
                        ? (item.isPurchased ? Icons.check_rounded : Icons.lock_rounded)
                        : Icons.arrow_forward_rounded,
                    color: Colors.white,
                    size: 18,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              item.title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
            const Spacer(),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _InfoPill(
                  label: locale.languageCode == 'es'
                      ? '${item.totalLevels} niveles'
                      : '${item.totalLevels} levels',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _QuizZoneBanner extends StatelessWidget {
  const _QuizZoneBanner({
    required this.item,
    required this.strings,
    required this.onTap,
  });

  final QuizCategory item;
  final AppStrings strings;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(28),
      onTap: onTap,
      child: Container(
        width: 292,
        padding: const EdgeInsets.fromLTRB(22, 20, 22, 20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              item.color.withValues(alpha: 0.96),
              item.color.withValues(alpha: 0.78),
            ],
          ),
          borderRadius: BorderRadius.circular(28),
          boxShadow: const [
            BoxShadow(
              color: Color(0x1A10253D),
              blurRadius: 22,
              offset: Offset(0, 12),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                if (item.isPremium)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.16),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Text(
                      item.isPurchased
                          ? 'Comprado'
                          : '${item.amount} ${strings.text('coinsLabel')}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                        fontSize: 11,
                      ),
                    ),
                  ),
                const Spacer(),
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: item.isPremium && !item.isPurchased
                        ? const Color(0xFFE74C3C)
                        : Colors.white.withValues(alpha: 0.22),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: (item.isPremium && !item.isPurchased
                                ? const Color(0xFFE74C3C)
                                : Colors.white)
                            .withValues(alpha: 0.28),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Icon(
                    item.isPremium
                        ? (item.isPurchased
                              ? Icons.check_rounded
                              : Icons.lock_rounded)
                        : Icons.check_rounded,
                    color: Colors.white,
                    size: 18,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              item.title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
            const Spacer(),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _InfoPill(
                  label: '${item.questionCount} ${strings.text('questionsShort')}',
                ),
                _InfoPill(
                  label: '${item.subcategoryCount} ${strings.text('categoriesShort')}',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ModeCard extends StatelessWidget {
  const _ModeCard({
    required this.strings,
    required this.item,
  });

  final AppStrings strings;
  final _ModeCardData item;

  @override
  Widget build(BuildContext context) {
    final ornamentSize = item.fullWidth ? 88.0 : 42.0;
    return InkWell(
      borderRadius: BorderRadius.circular(26),
      onTap: item.onTap,
      child: Container(
        width: double.infinity,
        constraints: BoxConstraints(minHeight: item.fullWidth ? 138 : 154),
        padding: const EdgeInsets.fromLTRB(20, 18, 18, 18),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [item.start, item.end],
          ),
          borderRadius: BorderRadius.circular(26),
          boxShadow: const [
            BoxShadow(
              color: Color(0x1412304A),
              blurRadius: 18,
              offset: Offset(0, 10),
            ),
          ],
        ),
        child: Stack(
          children: [
            Positioned(
              right: item.fullWidth ? 8 : 0,
              top: item.fullWidth ? 18 : 0,
              child: Icon(
                item.icon,
                size: ornamentSize,
                color: Colors.white.withValues(alpha: item.fullWidth ? 0.18 : 0.30),
              ),
            ),
            Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.title,
                  maxLines: item.fullWidth ? 1 : 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: item.fullWidth ? 24 : 16,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 10),
                Container(
                  width: 30,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.45),
                    borderRadius: BorderRadius.circular(99),
                  ),
                ),
                SizedBox(height: item.fullWidth ? 30 : 24),
                Row(
                  children: [
                    const Icon(
                      Icons.play_circle_fill_rounded,
                      color: Colors.white,
                      size: 20,
                    ),
                    const SizedBox(width: 8),
                    Text(
                      strings.text('playNow'),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ContestCard extends StatelessWidget {
  const _ContestCard({
    required this.strings,
  });

  final AppStrings strings;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(26),
      onTap: () {
        showModalBottomSheet<void>(
          context: context,
          backgroundColor: Colors.white,
          shape: const RoundedRectangleBorder(
            borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
          ),
          builder: (context) {
            return SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      strings.text('contests'),
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                        color: AppTheme.ink,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      strings.text('modePreviewBody'),
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF5B6B7C),
                        height: 1.45,
                      ),
                    ),
                    const SizedBox(height: 18),
                    FilledButton(
                      onPressed: () => Navigator.of(context).pop(),
                      child: Text(strings.text('understoodAction')),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
      child: Container(
        width: double.infinity,
        constraints: const BoxConstraints(minHeight: 148),
        padding: const EdgeInsets.fromLTRB(24, 20, 22, 18),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFFF4A93D), Color(0xFFE57C2A)],
          ),
          borderRadius: BorderRadius.circular(26),
          boxShadow: const [
            BoxShadow(
              color: Color(0x1412304A),
              blurRadius: 18,
              offset: Offset(0, 10),
            ),
          ],
        ),
        child: Stack(
          children: [
            Positioned(
              right: 0,
              bottom: -8,
              child: Icon(
                Icons.emoji_events_rounded,
                size: 86,
                color: Colors.white.withValues(alpha: 0.18),
              ),
            ),
            Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  strings.text('contests'),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 56),
                Row(
                  children: [
                    const Icon(Icons.play_circle_fill_rounded, color: Colors.white, size: 20),
                    const SizedBox(width: 8),
                    Text(
                      strings.text('playNow'),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({
    required this.label,
  });

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w700,
          fontSize: 12,
        ),
      ),
    );
  }
}

class _EmptySectionCard extends StatelessWidget {
  const _EmptySectionCard({
    required this.title,
    required this.body,
  });

  final String title;
  final String body;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE3EBF4)),
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
            Icons.quiz_outlined,
            size: 34,
            color: Color(0xFF5F89B8),
          ),
          const SizedBox(height: 10),
          Text(
            title,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: AppTheme.ink,
                ),
          ),
          const SizedBox(height: 8),
          Text(
            body,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF5E748C),
                  height: 1.45,
                ),
          ),
        ],
      ),
    );
  }
}

class _ModeCardData {
  const _ModeCardData({
    required this.title,
    required this.start,
    required this.end,
    required this.icon,
    required this.onTap,
    this.fullWidth = false,
  });

  final String title;
  final Color start;
  final Color end;
  final IconData icon;
  final VoidCallback onTap;
  final bool fullWidth;
}
