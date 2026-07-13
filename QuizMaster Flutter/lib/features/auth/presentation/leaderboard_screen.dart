import 'package:flutter/material.dart';

import '../../../core/l10n/app_strings.dart';
import '../../../core/theme/app_theme.dart';
import '../data/leaderboard_repository.dart';
import '../models/app_user.dart';
import '../models/leaderboard_data.dart';
import '../models/leaderboard_entry.dart';

class LeaderboardScreen extends StatefulWidget {
  const LeaderboardScreen({
    super.key,
    required this.locale,
    required this.currentUser,
    required this.repository,
  });

  final Locale locale;
  final AppUser currentUser;
  final LeaderboardRepository repository;

  @override
  State<LeaderboardScreen> createState() => _LeaderboardScreenState();
}

class _LeaderboardScreenState extends State<LeaderboardScreen> {
  late LeaderboardRange _range;
  late Future<LeaderboardData> _future;

  @override
  void initState() {
    super.initState();
    _range = LeaderboardRange.month;
    _future = _load();
  }

  Future<LeaderboardData> _load() {
    return widget.repository.fetchLeaderboard(
      userId: widget.currentUser.id,
      range: _range,
    );
  }

  void _changeRange(LeaderboardRange range) {
    if (_range == range) {
      return;
    }
    setState(() {
      _range = range;
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(widget.locale);

    return Scaffold(
      backgroundColor: const Color(0xFFF4F7FB),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.ink,
        title: Text(
          strings.text('leaderboard'),
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
              ),
        ),
      ),
      body: FutureBuilder<LeaderboardData>(
        future: _future,
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

          final data = snapshot.data!;
          final entries = data.entries;
          final currentUser = data.currentUser;
          final topThree = entries.take(3).toList();
          final topList = entries.take(99).toList();
          final currentUserId = currentUser?.userId.trim().isNotEmpty == true
              ? currentUser!.userId.trim()
              : widget.currentUser.id.trim();
          final currentUserName = (currentUser?.name ?? widget.currentUser.name)
              .trim()
              .toLowerCase();

          return Column(
            children: [
              Container(
                margin: const EdgeInsets.fromLTRB(16, 0, 16, 0),
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
                      color: Color(0x180E2741),
                      blurRadius: 20,
                      offset: Offset(0, 12),
                    ),
                  ],
                ),
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: _RangeChip(
                              label: strings.text('todayLabel'),
                              selected: _range == LeaderboardRange.today,
                              onTap: () => _changeRange(LeaderboardRange.today),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _RangeChip(
                              label: strings.text('monthLabel'),
                              selected: _range == LeaderboardRange.month,
                              onTap: () => _changeRange(LeaderboardRange.month),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _RangeChip(
                              label: strings.text('allLabel'),
                              selected: _range == LeaderboardRange.all,
                              onTap: () => _changeRange(LeaderboardRange.all),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 22),
                      if (entries.isEmpty)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 24),
                          child: Text(
                            strings.text('noDataYet'),
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        )
                      else
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Expanded(
                              child: _PodiumAvatar(
                                entry: topThree.length > 1 ? topThree[1] : null,
                                place: 2,
                                big: false,
                              ),
                            ),
                            Expanded(
                              child: _PodiumAvatar(
                                entry: topThree.isNotEmpty ? topThree[0] : null,
                                place: 1,
                                big: true,
                              ),
                            ),
                            Expanded(
                              child: _PodiumAvatar(
                                entry: topThree.length > 2 ? topThree[2] : null,
                                place: 3,
                                big: false,
                              ),
                            ),
                          ],
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 14),
              Expanded(
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(28),
                    boxShadow: const [
                      BoxShadow(
                        color: Color(0x120E2741),
                        blurRadius: 18,
                        offset: Offset(0, 10),
                      ),
                    ],
                  ),
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(14, 12, 14, 96),
                    itemBuilder: (context, index) {
                      final entry = topList[index];
                      final isCurrentUser = entry.userId.trim() == currentUserId ||
                          entry.name.trim().toLowerCase() == currentUserName;
                      return _LeaderboardRow(
                        entry: entry,
                        highlight: isCurrentUser,
                      );
                    },
                    separatorBuilder: (context, index) => const Divider(height: 1),
                    itemCount: topList.length,
                  ),
                ),
              ),
              if (currentUser != null)
                SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 10, 16, 16),
                    child: Container(
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          begin: Alignment.centerLeft,
                          end: Alignment.centerRight,
                          colors: [
                            Color(0xFF2B6FB6),
                            Color(0xFF55B8FF),
                          ],
                        ),
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: const [
                          BoxShadow(
                            color: Color(0x180E2741),
                            blurRadius: 18,
                            offset: Offset(0, 10),
                          ),
                        ],
                      ),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 12,
                        ),
                        child: Row(
                          children: [
                            SizedBox(
                              width: 28,
                              child: Text(
                                '${currentUser!.rank}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                            _Avatar(profileUrl: currentUser.profileUrl, radius: 20),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                currentUser.name,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            Text(
                              '${currentUser.score}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 18,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

class _RangeChip extends StatelessWidget {
  const _RangeChip({
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
        height: 42,
        decoration: BoxDecoration(
          color: selected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: Colors.white.withValues(alpha: selected ? 0 : 0.9),
            width: 1.6,
          ),
        ),
        alignment: Alignment.center,
        child: Text(
          label,
          style: TextStyle(
            color: selected ? const Color(0xFF2B6FB6) : Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
    );
  }
}

class _PodiumAvatar extends StatelessWidget {
  const _PodiumAvatar({
    required this.entry,
    required this.place,
    required this.big,
  });

  final LeaderboardEntry? entry;
  final int place;
  final bool big;

  @override
  Widget build(BuildContext context) {
    if (entry == null) {
      return const SizedBox(height: 160);
    }

    final avatarRadius = big ? 48.0 : 38.0;
    final medalColor = switch (place) {
      1 => const Color(0xFFFFD54F),
      2 => const Color(0xFFE5ECF5),
      _ => const Color(0xFFD58A5B),
    };
    final medalAccent = switch (place) {
      1 => const Color(0xFFF4B400),
      2 => const Color(0xFFB8C4D4),
      _ => const Color(0xFFB56A3C),
    };

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (place == 1)
          Padding(
            padding: EdgeInsets.only(bottom: 8),
            child: Icon(
              Icons.workspace_premium_rounded,
              color: medalColor,
              size: 30,
            ),
          ),
        Stack(
          clipBehavior: Clip.none,
          children: [
            Container(
              width: avatarRadius * 2,
              height: avatarRadius * 2,
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    medalColor,
                    medalAccent,
                  ],
                ),
                boxShadow: [
                  BoxShadow(
                    color: medalAccent.withValues(alpha: 0.35),
                    blurRadius: 16,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: _Avatar(
                profileUrl: entry!.profileUrl,
                radius: avatarRadius - 4,
                borderColor: Colors.white.withValues(alpha: 0.95),
                borderWidth: 3,
                fallbackText: entry!.name,
                fallbackBackground: const Color(0xFFF6F8FC),
                fallbackForeground: medalAccent,
              ),
            ),
            Positioned(
              left: -2,
              bottom: -2,
              child: Container(
                width: 28,
                height: 28,
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  border: Border.all(color: medalAccent, width: 2),
                ),
                alignment: Alignment.center,
                child: Text(
                  '$place',
                  style: TextStyle(
                    color: medalAccent,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text(
          entry!.name,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.white,
            fontSize: big ? 16 : 14,
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          '${entry!.score}',
          style: const TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    );
  }
}

class _LeaderboardRow extends StatelessWidget {
  const _LeaderboardRow({
    required this.entry,
    required this.highlight,
  });

  final LeaderboardEntry entry;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(vertical: 6),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: highlight ? const Color(0xFFEAF4FF) : Colors.transparent,
        borderRadius: BorderRadius.circular(18),
        border: highlight
            ? Border.all(color: const Color(0xFF55B8FF), width: 1.4)
            : null,
      ),
      child: Row(
        children: [
          SizedBox(
            width: 28,
            child: Text(
              '${entry.rank}',
              style: TextStyle(
                color: highlight ? const Color(0xFF1D6CBA) : const Color(0xFF2B6FB6),
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const SizedBox(width: 8),
          _Avatar(profileUrl: entry.profileUrl, radius: 22),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              entry.name,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: highlight ? const Color(0xFF1D6CBA) : AppTheme.ink,
                fontSize: 17,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(width: 12),
          if (highlight)
            Container(
              margin: const EdgeInsets.only(right: 10),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFF1D6CBA),
                borderRadius: BorderRadius.circular(999),
              ),
              child: const Text(
                'Tú',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          Text(
            '${entry.score}',
            style: TextStyle(
              color: highlight ? const Color(0xFF1D6CBA) : const Color(0xFF2B6FB6),
              fontSize: 18,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({
    required this.profileUrl,
    required this.radius,
    this.borderColor,
    this.borderWidth = 2,
    this.fallbackText,
    this.fallbackBackground,
    this.fallbackForeground,
  });

  final String profileUrl;
  final double radius;
  final Color? borderColor;
  final double borderWidth;
  final String? fallbackText;
  final Color? fallbackBackground;
  final Color? fallbackForeground;

  @override
  Widget build(BuildContext context) {
    final hasImage = profileUrl.trim().isNotEmpty;
    final initials = _initialsFromName(fallbackText);
    return Container(
      width: radius * 2,
      height: radius * 2,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: borderColor == null
            ? null
            : Border.all(color: borderColor!, width: borderWidth),
        image: hasImage
            ? DecorationImage(
                image: NetworkImage(profileUrl),
                fit: BoxFit.cover,
              )
            : null,
        color: hasImage ? null : (fallbackBackground ?? const Color(0xFFE8EEF7)),
      ),
      child: hasImage
          ? null
          : Center(
              child: Text(
                initials,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: fallbackForeground ?? const Color(0xFF7E8DA0),
                  fontSize: radius * 0.7,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
    );
  }

  String _initialsFromName(String? name) {
    final clean = (name ?? '').trim();
    if (clean.isEmpty) {
      return 'A';
    }

    final parts = clean
        .split(RegExp(r'\s+'))
        .where((part) => part.isNotEmpty)
        .take(2)
        .toList();
    if (parts.isEmpty) {
      return clean.substring(0, 1).toUpperCase();
    }

    return parts.map((part) => part.substring(0, 1).toUpperCase()).join();
  }
}
