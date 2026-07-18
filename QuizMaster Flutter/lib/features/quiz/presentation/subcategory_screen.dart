import 'package:flutter/material.dart';

import '../../../core/l10n/app_strings.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/data/mock_auth_repository.dart';
import '../../auth/models/app_user.dart';
import '../data/mock_quiz_repository.dart';
import '../data/quiz_result_repository.dart';
import '../models/quiz_category.dart';
import '../models/quiz_subcategory.dart';
import 'question_screen.dart';

class SubcategoryScreen extends StatefulWidget {
  const SubcategoryScreen({
    super.key,
    required this.locale,
    required this.category,
    this.quizRepository = const MockQuizRepository(),
    this.quizResultRepository = const MockQuizResultRepository(),
    this.authRepository = const MockAuthRepository(),
    this.currentUser,
    this.onUserUpdated,
  });

  final Locale locale;
  final QuizCategory category;
  final QuizRepository quizRepository;
  final QuizResultRepository quizResultRepository;
  final AuthRepository authRepository;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;

  @override
  State<SubcategoryScreen> createState() => _SubcategoryScreenState();
}

class _SubcategoryScreenState extends State<SubcategoryScreen> {
  late Future<List<QuizSubcategory>> _future;
  late AppUser? _currentUser;
  late bool _isUnlocked;
  bool _isUnlockingCategory = false;

  @override
  void initState() {
    super.initState();
    _currentUser = widget.currentUser;
    _isUnlocked = !widget.category.isPremium || widget.category.isPurchased;
    _future = widget.quizRepository.fetchSubcategories(
      mainCategoryId: widget.category.id,
    );
  }

  @override
  void didUpdateWidget(covariant SubcategoryScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.currentUser != widget.currentUser) {
      _currentUser = widget.currentUser;
    }
    if (oldWidget.category.id != widget.category.id ||
        oldWidget.category.isPurchased != widget.category.isPurchased ||
        oldWidget.category.plan != widget.category.plan) {
      _isUnlocked = !widget.category.isPremium || widget.category.isPurchased;
    }
  }

  Future<void> _openSubcategory(QuizSubcategory item) async {
    if (!_isUnlocked) {
      await _promptCategoryUnlock();
      if (!_isUnlocked) {
        return;
      }
    }

    if (item.maxLevel > 1) {
      await _openLevelPicker(subcategory: item);
      return;
    }

    _openQuestionScreen(subcategory: item);
  }

  Future<void> _openLevelPicker({
    QuizSubcategory? subcategory,
  }) async {
    final maxLevel = subcategory?.maxLevel ?? widget.category.maxLevel;
    if (maxLevel <= 1) {
      _openQuestionScreen(subcategory: subcategory);
      return;
    }

    var unlockedLevel = 1;
    final user = _currentUser;
    if (user != null && int.tryParse(user.id) != null) {
      try {
        unlockedLevel = await widget.quizRepository.fetchSavedLevel(
          userId: user.id,
          categoryId: widget.category.id,
          subcategoryId: subcategory?.id,
        );
      } catch (_) {
        unlockedLevel = 1;
      }
    }

    if (!mounted) {
      return;
    }

    final selectedLevel = await Navigator.of(context).push<int>(
      MaterialPageRoute(
        builder: (_) => _LevelSelectScreen(
          locale: widget.locale,
          category: widget.category,
          title: subcategory?.title ?? widget.category.title,
          maxLevel: maxLevel,
          unlockedLevel: unlockedLevel.clamp(1, maxLevel) as int,
        ),
      ),
    );

    if (!mounted || selectedLevel == null) {
      return;
    }

    _openQuestionScreen(
      subcategory: subcategory,
      selectedLevel: selectedLevel,
      maxAvailableLevel: maxLevel,
    );
  }

  void _openQuestionScreen({
    QuizSubcategory? subcategory,
    int? selectedLevel,
    int? maxAvailableLevel,
  }) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => QuestionScreen(
          locale: widget.locale,
          title: subcategory?.title ?? widget.category.title,
          category: widget.category,
          selectedLevel: selectedLevel,
          maxAvailableLevel: maxAvailableLevel,
          subcategory: subcategory,
          quizRepository: widget.quizRepository,
          quizResultRepository: widget.quizResultRepository,
          currentUser: _currentUser,
          authRepository: widget.authRepository,
          onUserUpdated: _handleUserUpdated,
        ),
      ),
    );
  }

  void _showPremiumLocked() {
    final strings = AppStrings(widget.locale);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(strings.text('premiumLockedBody')),
      ),
    );
  }

  void _handleUserUpdated(AppUser user) {
    setState(() {
      _currentUser = user;
    });
    widget.onUserUpdated?.call(user);
  }

  Future<void> _promptCategoryUnlock() async {
    final strings = AppStrings(widget.locale);
    final user = _currentUser;
    if (user == null || user.id.isEmpty) {
      _showPremiumLocked();
      return;
    }

    if (user.coins < widget.category.amount) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(strings.text('notEnoughCoinsBody')),
        ),
      );
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(strings.text('unlockPremiumTitle')),
        content: Text(
          strings.text('unlockPremiumBody').replaceFirst(
                '{coins}',
                '${widget.category.amount}',
              ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text(strings.text('cancelAction')),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text(
              strings.text('unlockForCoinsAction').replaceFirst(
                    '{coins}',
                    '${widget.category.amount}',
                  ),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true || _isUnlockingCategory) {
      return;
    }

    setState(() {
      _isUnlockingCategory = true;
    });

    try {
      await widget.quizRepository.unlockPremiumCategory(
        userId: user.id,
        categoryId: widget.category.id,
      );
      final updatedUser = await widget.authRepository.refreshUser(user.id);
      if (!mounted) {
        return;
      }

      setState(() {
        _isUnlocked = true;
        _currentUser = updatedUser;
      });
      widget.onUserUpdated?.call(updatedUser);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(strings.text('categoryUnlockedBody')),
        ),
      );
    } catch (_) {
      if (!mounted) {
        return;
      }
      _showPremiumLocked();
    } finally {
      if (mounted) {
        setState(() {
          _isUnlockingCategory = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(widget.locale);
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.textColor(context),
        title: Text(
          widget.category.title,
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: FutureBuilder<List<QuizSubcategory>>(
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

          final items = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
              _CategoryHeroCard(
                category: widget.category,
                strings: strings,
                isUnlocked: _isUnlocked,
                currentCoins: _currentUser?.coins ?? 0,
                onUnlockTap: widget.category.isPremium && !_isUnlocked
                    ? _promptCategoryUnlock
                    : null,
                isUnlocking: _isUnlockingCategory,
              ),
              const SizedBox(height: 22),
              if (!(widget.category.isPremium && !_isUnlocked)) ...[
                _SectionTitle(
                  title: strings.text('subcategorySectionLabel'),
                  trailing: '${items.length}',
                ),
                const SizedBox(height: 12),
                if (items.isEmpty)
                  _EmptySubcategoryCard(
                    title: strings.text('emptySubcategorySet'),
                  )
                else
                  ...items.asMap().entries.map((entry) {
                    final index = entry.key;
                    final item = entry.value;
                    return Padding(
                      padding: EdgeInsets.only(
                        bottom: index == items.length - 1 ? 0 : 14,
                      ),
                      child: _SubcategoryCard(
                        category: widget.category,
                        item: item,
                        strings: strings,
                        onTap: () => _openSubcategory(item),
                      ),
                    );
                  }),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _CategoryHeroCard extends StatelessWidget {
  const _CategoryHeroCard({
    required this.category,
    required this.strings,
    required this.isUnlocked,
    required this.currentCoins,
    required this.isUnlocking,
    this.onUnlockTap,
  });

  final QuizCategory category;
  final AppStrings strings;
  final bool isUnlocked;
  final int currentCoins;
  final bool isUnlocking;
  final Future<void> Function()? onUnlockTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(22, 22, 22, 20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            category.color,
            category.color.withValues(alpha: 0.82),
          ],
        ),
        borderRadius: BorderRadius.circular(28),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1812304A),
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
              SizedBox(
                width: 54,
                height: 54,
                child: _RemoteCardImage(
                  imageUrl: category.imageUrl,
                  borderRadius: 18,
                  fallback: Container(
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.18),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: Icon(
                      category.icon,
                      color: Colors.white,
                      size: 28,
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            category.title,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.w900,
              height: 1.1,
            ),
          ),
          const SizedBox(height: 18),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Row(
              children: [
                const Icon(
                  Icons.touch_app_rounded,
                  color: Colors.white,
                  size: 18,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    category.isPremium && !isUnlocked
                        ? strings.text('unlockPremiumBody').replaceFirst(
                              '{coins}',
                              '${category.amount}',
                            )
                        : strings.text('chooseSubcategoryBody'),
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (category.isPremium && !isUnlocked && onUnlockTap != null) ...[
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: isUnlocking ? null : () => onUnlockTap!.call(),
                style: FilledButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: category.color,
                ),
                child: Text(
                  isUnlocking
                      ? strings.text('saving')
                      : strings.text('unlockForCoinsAction').replaceFirst(
                            '{coins}',
                            '${category.amount}',
                          ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _PremiumLockedCard extends StatelessWidget {
  const _PremiumLockedCard({
    required this.strings,
    required this.amount,
    required this.currentCoins,
    required this.onUnlockTap,
    required this.isUnlocking,
  });

  final AppStrings strings;
  final int amount;
  final int currentCoins;
  final Future<void> Function() onUnlockTap;
  final bool isUnlocking;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE7EDF5)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1212304A),
            blurRadius: 18,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            strings.text('premiumLockedBody'),
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  color: AppTheme.ink,
                  fontWeight: FontWeight.w800,
                ),
          ),
          const SizedBox(height: 8),
          Text(
            strings
                .text('unlockPremiumBody')
                .replaceFirst('{coins}', '$amount'),
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF5B6B7C),
                  height: 1.4,
                ),
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _LightPill(label: '${strings.text('coinsLabel')}: $currentCoins'),
              _LightPill(label: '${strings.text('premiumLabel')}: $amount'),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: isUnlocking ? null : () => onUnlockTap(),
              child: Text(
                isUnlocking
                    ? strings.text('saving')
                    : strings.text('unlockForCoinsAction').replaceFirst(
                          '{coins}',
                          '$amount',
                        ),
              ),
            ),
          ),
        ],
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
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.18),
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

class _LightPill extends StatelessWidget {
  const _LightPill({
    required this.label,
  });

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
      decoration: BoxDecoration(
        color: const Color(0xFFF1F6FB),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: AppTheme.ink,
          fontWeight: FontWeight.w700,
          fontSize: 12,
        ),
      ),
    );
  }
}

class _InlineInfoBanner extends StatelessWidget {
  const _InlineInfoBanner({
    required this.title,
    required this.body,
  });

  final String title;
  final String body;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE5EDF6)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFFEAF4FF),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(
              Icons.bolt_rounded,
              color: AppTheme.primary,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        color: AppTheme.ink,
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  body,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF60758B),
                        height: 1.45,
                      ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({
    required this.title,
    required this.trailing,
  });

  final String title;
  final String trailing;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w900,
              ),
        ),
        const Spacer(),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: const Color(0xFFEAF4FF),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Text(
            trailing,
            style: const TextStyle(
              color: AppTheme.primary,
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
      ],
    );
  }
}

class _SubcategoryCard extends StatelessWidget {
  const _SubcategoryCard({
    required this.category,
    required this.item,
    required this.strings,
    required this.onTap,
  });

  final QuizCategory category;
  final QuizSubcategory item;
  final AppStrings strings;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(24),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: const Color(0xFFE5EDF6)),
          boxShadow: const [
            BoxShadow(
              color: Color(0x0F12304A),
              blurRadius: 14,
              offset: Offset(0, 8),
            ),
          ],
        ),
        child: Row(
          children: [
            SizedBox(
              width: 62,
              height: 62,
              child: _RemoteCardImage(
                imageUrl: item.imageUrl,
                borderRadius: 20,
                fallback: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        category.color.withValues(alpha: 0.94),
                        category.color.withValues(alpha: 0.72),
                      ],
                    ),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Icon(
                    category.icon,
                    color: Colors.white,
                    size: 30,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.title,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          color: AppTheme.ink,
                          fontWeight: FontWeight.w900,
                        ),
                  ),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _StatChip(
                        label:
                            '${item.questionCount} ${strings.text('questions')}',
                      ),
                      _StatChip(
                        label:
                            '${item.maxLevel == 0 ? 1 : item.maxLevel} ${strings.text('levels')}',
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Icon(
                        Icons.play_circle_fill_rounded,
                        size: 18,
                        color: category.color,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        strings.text('playNow'),
                        style: TextStyle(
                          color: category.color,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Icon(
              Icons.chevron_right_rounded,
              color: category.color,
              size: 28,
            ),
          ],
        ),
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  const _StatChip({
    required this.label,
  });

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: const Color(0xFFF1F6FB),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Color(0xFF667A90),
          fontWeight: FontWeight.w700,
          fontSize: 12,
        ),
      ),
    );
  }
}

class _EmptySubcategoryCard extends StatelessWidget {
  const _EmptySubcategoryCard({
    required this.title,
  });

  final String title;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE5EDF6)),
      ),
      child: Column(
        children: [
          const Icon(
            Icons.view_carousel_rounded,
            size: 34,
            color: AppTheme.primary,
          ),
          const SizedBox(height: 10),
          Text(
            title,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  color: AppTheme.ink,
                  fontWeight: FontWeight.w800,
                ),
          ),
        ],
      ),
    );
  }
}

class _LevelSelectScreen extends StatefulWidget {
  const _LevelSelectScreen({
    required this.locale,
    required this.category,
    required this.title,
    required this.maxLevel,
    required this.unlockedLevel,
  });

  final Locale locale;
  final QuizCategory category;
  final String title;
  final int maxLevel;
  final int unlockedLevel;

  @override
  State<_LevelSelectScreen> createState() => _LevelSelectScreenState();
}

class _LevelSelectScreenState extends State<_LevelSelectScreen> {
  static const _crossAxisCount = 3;
  static const _cardHeight = 118.0;
  static const _spacing = 12.0;

  late final ScrollController _scrollController;

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController();
    WidgetsBinding.instance.addPostFrameCallback((_) => _focusUnlockedLevel());
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _focusUnlockedLevel() {
    if (!_scrollController.hasClients) {
      return;
    }

    final rowIndex = ((widget.unlockedLevel - 1) / _crossAxisCount).floor();
    final targetOffset = rowIndex * (_cardHeight + _spacing);
    final maxOffset = _scrollController.position.maxScrollExtent;
    _scrollController.jumpTo(targetOffset.clamp(0, maxOffset));
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(widget.locale);

    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.textColor(context),
        title: Text(
          widget.title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w900,
              ),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      widget.category.color,
                      widget.category.color.withValues(alpha: 0.82),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(28),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x1812304A),
                      blurRadius: 22,
                      offset: Offset(0, 12),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      strings.text('levelProgressTitle'),
                      style: const TextStyle(
                        color: Color(0xFFDDF0FF),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${strings.text('currentLevelUnlocked')}: ${widget.unlockedLevel}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 24,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: () =>
                            Navigator.of(context).pop(widget.unlockedLevel),
                        style: FilledButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: widget.category.color,
                        ),
                        child: Text(strings.text('continueCurrentLevel')),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),
              Text(
                strings.text('selectLevelTitle'),
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      color: AppTheme.ink,
                      fontWeight: FontWeight.w900,
                    ),
              ),
              const SizedBox(height: 12),
              Expanded(
                child: GridView.builder(
                  controller: _scrollController,
                  itemCount: widget.maxLevel,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: _crossAxisCount,
                    mainAxisSpacing: _spacing,
                    crossAxisSpacing: _spacing,
                    mainAxisExtent: _cardHeight,
                  ),
                  itemBuilder: (context, index) {
                    final level = index + 1;
                    final isLocked = level > widget.unlockedLevel;
                    final isCurrent = level == widget.unlockedLevel;

                    return InkWell(
                      borderRadius: BorderRadius.circular(22),
                      onTap: isLocked
                          ? null
                          : () => Navigator.of(context).pop(level),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 180),
                        decoration: BoxDecoration(
                          color: isLocked
                              ? const Color(0xFFF4F7FB)
                              : isCurrent
                                  ? widget.category.color
                                      .withValues(alpha: 0.12)
                                  : Colors.white,
                          borderRadius: BorderRadius.circular(22),
                          border: Border.all(
                            color: isLocked
                                ? const Color(0xFFE2EAF4)
                                : isCurrent
                                    ? widget.category.color
                                    : const Color(0xFFD8E5F2),
                            width: isCurrent ? 2.5 : 1.2,
                          ),
                          boxShadow: isLocked
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
                              isLocked
                                  ? Icons.lock_rounded
                                  : isCurrent
                                      ? Icons.my_location_rounded
                                      : Icons.check_circle_rounded,
                              color: isLocked
                                  ? const Color(0xFF8CA0B4)
                                  : widget.category.color,
                            ),
                            const SizedBox(height: 8),
                            Text(
                              '${strings.text('levelLabel')} $level',
                              textAlign: TextAlign.center,
                              style: Theme.of(context)
                                  .textTheme
                                  .titleSmall
                                  ?.copyWith(
                                    color: isLocked
                                        ? const Color(0xFF7A8DA2)
                                        : AppTheme.ink,
                                    fontWeight: FontWeight.w900,
                                  ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              isLocked
                                  ? strings.text('levelLocked')
                                  : isCurrent
                                      ? strings.text('levelCurrentLabel')
                                      : strings.text('levelCompletedLabel'),
                              textAlign: TextAlign.center,
                              style: Theme.of(context)
                                  .textTheme
                                  .bodySmall
                                  ?.copyWith(
                                    color: isLocked
                                        ? const Color(0xFF7A8DA2)
                                        : widget.category.color,
                                    fontWeight: FontWeight.w700,
                                  ),
                            ),
                          ],
                        ),
                      ),
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

class _RemoteCardImage extends StatelessWidget {
  const _RemoteCardImage({
    required this.imageUrl,
    required this.borderRadius,
    required this.fallback,
  });

  final String imageUrl;
  final double borderRadius;
  final Widget fallback;

  @override
  Widget build(BuildContext context) {
    final hasImage = imageUrl.trim().isNotEmpty;
    if (!hasImage) {
      return fallback;
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(borderRadius),
      child: Image.network(
        imageUrl,
        fit: BoxFit.cover,
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) {
            return child;
          }
          return fallback;
        },
        errorBuilder: (context, error, stackTrace) => fallback,
      ),
    );
  }
}
