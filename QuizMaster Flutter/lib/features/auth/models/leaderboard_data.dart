import 'leaderboard_entry.dart';

class LeaderboardData {
  const LeaderboardData({
    required this.entries,
    required this.currentUser,
  });

  factory LeaderboardData.fromJson(Map<String, dynamic> json) {
    final rawEntries = json['entries'];
    final rawCurrentUser = json['current_user'];

    return LeaderboardData(
      entries: rawEntries is List
          ? rawEntries
              .whereType<Map<String, dynamic>>()
              .map(LeaderboardEntry.fromJson)
              .toList()
          : const [],
      currentUser: rawCurrentUser is Map<String, dynamic>
          ? LeaderboardEntry.fromJson(rawCurrentUser)
          : null,
    );
  }

  final List<LeaderboardEntry> entries;
  final LeaderboardEntry? currentUser;
}
