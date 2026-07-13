import 'package:flutter/material.dart';

import '../../../core/l10n/app_strings.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/data/mock_auth_repository.dart';
import '../../auth/models/app_user.dart';
import '../../config/data/mock_system_config_repository.dart';
import '../../config/models/system_config.dart';
import '../data/mock_quiz_repository.dart';
import '../data/quiz_result_repository.dart';
import '../models/quiz_category.dart';
import 'subcategory_screen.dart';

class QuizZoneScreen extends StatefulWidget {
  const QuizZoneScreen({
    super.key,
    required this.locale,
    this.quizRepository = const MockQuizRepository(),
    this.quizResultRepository = const MockQuizResultRepository(),
    this.authRepository = const MockAuthRepository(),
    this.systemConfigRepository = const MockSystemConfigRepository(),
    this.currentUser,
    this.onUserUpdated,
  });

  static const routeName = '/quiz-zone';

  final Locale locale;
  final QuizRepository quizRepository;
  final QuizResultRepository quizResultRepository;
  final AuthRepository authRepository;
  final SystemConfigRepository systemConfigRepository;
  final AppUser? currentUser;
  final ValueChanged<AppUser>? onUserUpdated;

  @override
  State<QuizZoneScreen> createState() => _QuizZoneScreenState();
}

class _QuizZoneScreenState extends State<QuizZoneScreen> {
  String _query = '';
  late Future<_QuizZoneViewData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_QuizZoneViewData> _load() async {
    final config = await widget.systemConfigRepository.fetchSystemConfig();
    final languageId = config.languageMode
        ? (widget.locale.languageCode == 'es' ? '1' : '2')
        : null;
    final categories = await widget.quizRepository.fetchCategories(
      type: 1,
      languageId: languageId,
      userId: widget.currentUser?.id,
    );
    return _QuizZoneViewData(
      systemConfig: config,
      categories: categories,
    );
  }

  Future<void> _openCategory(QuizCategory item) async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => SubcategoryScreen(
          locale: widget.locale,
          category: item,
          quizRepository: widget.quizRepository,
          quizResultRepository: widget.quizResultRepository,
          currentUser: widget.currentUser,
          authRepository: widget.authRepository,
          onUserUpdated: widget.onUserUpdated,
        ),
      ),
    );

    if (!mounted) {
      return;
    }

    setState(() {
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(widget.locale);
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: const Color(0xFFF4F7FB),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.ink,
        title: Text(
          strings.text('quizZone'),
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: SafeArea(
        child: FutureBuilder<_QuizZoneViewData>(
          future: _future,
          builder: (context, snapshot) {
            if (!snapshot.hasData) {
              return const Center(child: CircularProgressIndicator());
            }

            final viewData = snapshot.data!;
            final filteredCategories = viewData.categories
                .where(
                  (item) => item.title.toLowerCase().contains(_query.toLowerCase()),
                )
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
                        hintText: strings.text('searchCategory'),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  const SizedBox(height: 18),
                  Expanded(
                    child: GridView.builder(
                      itemCount: filteredCategories.length,
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 2,
                        mainAxisSpacing: 14,
                        crossAxisSpacing: 14,
                        childAspectRatio: 0.9,
                      ),
                      itemBuilder: (context, index) {
                        final item = filteredCategories[index];
                        return _QuizCategoryCard(
                          item: item,
                          strings: strings,
                          onTap: () => _openCategory(item),
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

class _QuizCategoryCard extends StatelessWidget {
  const _QuizCategoryCard({
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
                    color: item.color.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: _RemoteCategoryImage(
                    imageUrl: item.imageUrl,
                    fallback: Center(
                      child: Container(
                        width: 72,
                        height: 72,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: item.color.withValues(alpha: 0.12),
                        ),
                        child: Icon(
                          item.icon,
                          color: item.color,
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
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    item.color,
                    item.color.withValues(alpha: 0.84),
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
                      if (item.isPremium)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.18),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            item.isPurchased
                                ? 'Comprado'
                                : '${item.amount} ${strings.text('coinsLabel')}',
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
                          size: 17,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    item.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    '${item.questionCount} ${strings.text('questions')}',
                    style: const TextStyle(
                      color: Color(0xFFD3E7FB),
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${item.subcategoryCount} ${strings.text('subcategories')}',
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

class _RemoteCategoryImage extends StatelessWidget {
  const _RemoteCategoryImage({
    required this.imageUrl,
    required this.fallback,
  });

  final String imageUrl;
  final Widget fallback;

  @override
  Widget build(BuildContext context) {
    if (imageUrl.trim().isEmpty) {
      return fallback;
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(20),
      child: Image.network(
        imageUrl,
        fit: BoxFit.cover,
        errorBuilder: (context, error, stackTrace) => fallback,
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) {
            return child;
          }
          return fallback;
        },
      ),
    );
  }
}

class _QuizZoneViewData {
  const _QuizZoneViewData({
    required this.systemConfig,
    required this.categories,
  });

  final SystemConfig systemConfig;
  final List<QuizCategory> categories;
}
