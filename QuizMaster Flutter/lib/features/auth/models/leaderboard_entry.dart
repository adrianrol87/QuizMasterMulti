class LeaderboardEntry {
  const LeaderboardEntry({
    required this.userId,
    required this.name,
    required this.profileUrl,
    required this.score,
    required this.rank,
  });

  factory LeaderboardEntry.fromJson(Map<String, dynamic> json) {
    return LeaderboardEntry(
      userId: (json['user_id'] ?? '').toString(),
      name: (json['name'] ?? '').toString(),
      profileUrl: (json['profile'] ?? '').toString(),
      score: int.tryParse((json['score'] ?? '0').toString()) ?? 0,
      rank: int.tryParse((json['user_rank'] ?? '0').toString()) ?? 0,
    );
  }

  final String userId;
  final String name;
  final String profileUrl;
  final int score;
  final int rank;
}
