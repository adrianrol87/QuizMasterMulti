import 'package:flutter/material.dart';

import '../../../core/l10n/app_strings.dart';
import '../../../core/theme/app_theme.dart';
import '../data/user_statistics_repository.dart';
import '../models/app_user.dart';
import '../models/user_statistics.dart';

class UserStatisticsScreen extends StatefulWidget {
  const UserStatisticsScreen({
    super.key,
    required this.locale,
    required this.user,
    required this.repository,
  });

  final Locale locale;
  final AppUser user;
  final UserStatisticsRepository repository;

  @override
  State<UserStatisticsScreen> createState() => _UserStatisticsScreenState();
}

class _UserStatisticsScreenState extends State<UserStatisticsScreen> {
  late Future<UserStatistics> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.fetchUserStatistics(widget.user.id);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = widget.repository.fetchUserStatistics(widget.user.id);
    });
    await _future;
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
          strings.text('playerStats'),
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: FutureBuilder<UserStatistics>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.bar_chart_rounded, size: 42, color: AppTheme.primary),
                    const SizedBox(height: 12),
                    Text(
                      strings.text('statsUnavailable'),
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${snapshot.error}',
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF5F748C),
                      ),
                    ),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: _refresh,
                      child: Text(strings.text('refreshStats')),
                    ),
                  ],
                ),
              ),
            );
          }

          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final stats = snapshot.data!;
          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
              children: [
                _StatsHero(
                  user: widget.user,
                  stats: stats,
                  strings: strings,
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    Expanded(
                      child: _StatCard(
                        label: strings.text('questionsAnswered'),
                        value: '${stats.questionsAnswered}',
                        icon: Icons.quiz_rounded,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _StatCard(
                        label: strings.text('correctAnswers'),
                        value: '${stats.correctAnswers}',
                        icon: Icons.check_circle_rounded,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _StatCard(
                        label: strings.text('accuracyLabel'),
                        value: '${stats.accuracyPercent}%',
                        icon: Icons.gps_fixed_rounded,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _StatCard(
                        label: strings.text('bestPositionLabel'),
                        value: stats.bestPosition > 0 ? '#${stats.bestPosition}' : '-',
                        icon: Icons.emoji_events_rounded,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                _CategoryInsightCard(
                  title: strings.text('strongCategoryLabel'),
                  value: stats.strongCategory == '0'
                      ? strings.text('noDataYet')
                      : stats.strongCategory,
                  ratio: stats.strongRatio,
                  accent: const Color(0xFF1F9D61),
                ),
                const SizedBox(height: 12),
                _CategoryInsightCard(
                  title: strings.text('weakCategoryLabel'),
                  value: stats.weakCategory == '0'
                      ? strings.text('noDataYet')
                      : stats.weakCategory,
                  ratio: stats.weakRatio,
                  accent: const Color(0xFFCB4C4C),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _StatsHero extends StatelessWidget {
  const _StatsHero({
    required this.user,
    required this.stats,
    required this.strings,
  });

  final AppUser user;
  final UserStatistics stats;
  final AppStrings strings;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color(0xFF2B6FB6),
            Color(0xFF55B8FF),
          ],
        ),
        borderRadius: BorderRadius.circular(28),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1612304A),
            blurRadius: 20,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 28,
                backgroundColor: Colors.white.withValues(alpha: 0.18),
                backgroundImage:
                    user.profileUrl.isNotEmpty ? NetworkImage(user.profileUrl) : null,
                child: user.profileUrl.isEmpty
                    ? const Icon(Icons.person_rounded, color: Colors.white, size: 28)
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      user.name,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      strings
                          .text('statsSummaryBody')
                          .replaceFirst('{score}', '${user.score}')
                          .replaceFirst(
                            '{rank}',
                            user.rank > 0 ? '#${user.rank}' : '-',
                          ),
                      style: const TextStyle(
                        color: Color(0xFFD7ECFF),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _HeroPill(label: '${strings.text('scoreLabel')}: ${user.score}'),
              _HeroPill(
                label:
                    '${strings.text('leaderboard')}: ${user.rank > 0 ? '#${user.rank}' : '-'}',
              ),
              _HeroPill(label: '${strings.text('coinsLabel')}: ${user.coins}'),
            ],
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

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.label,
    required this.value,
    required this.icon,
  });

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2EDF7)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFFEAF4FF),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: AppTheme.primary),
          ),
          const SizedBox(height: 14),
          Text(
            value,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                  color: AppTheme.ink,
                ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF5F748C),
                ),
          ),
        ],
      ),
    );
  }
}

class _CategoryInsightCard extends StatelessWidget {
  const _CategoryInsightCard({
    required this.title,
    required this.value,
    required this.ratio,
    required this.accent,
  });

  final String title;
  final String value;
  final int ratio;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2EDF7)),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(14),
            ),
            alignment: Alignment.center,
            child: Text(
              '${ratio.clamp(0, 999)}%',
              style: TextStyle(
                color: accent,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF5F748C),
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        color: AppTheme.ink,
                        fontWeight: FontWeight.w800,
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
