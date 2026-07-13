class UserStatistics {
  const UserStatistics({
    required this.userId,
    required this.questionsAnswered,
    required this.correctAnswers,
    required this.strongCategory,
    required this.strongRatio,
    required this.weakCategory,
    required this.weakRatio,
    required this.bestPosition,
    required this.dateCreated,
    required this.name,
    required this.profileUrl,
  });

  factory UserStatistics.fromJson(Map<String, dynamic> json) {
    return UserStatistics(
      userId: (json['user_id'] ?? '').toString(),
      questionsAnswered:
          int.tryParse((json['questions_answered'] ?? '0').toString()) ?? 0,
      correctAnswers:
          int.tryParse((json['correct_answers'] ?? '0').toString()) ?? 0,
      strongCategory: (json['strong_category'] ?? '0').toString(),
      strongRatio:
          double.tryParse((json['ratio1'] ?? '0').toString())?.round() ?? 0,
      weakCategory: (json['weak_category'] ?? '0').toString(),
      weakRatio:
          double.tryParse((json['ratio2'] ?? '0').toString())?.round() ?? 0,
      bestPosition:
          int.tryParse((json['best_position'] ?? '0').toString()) ?? 0,
      dateCreated: (json['date_created'] ?? '').toString(),
      name: (json['name'] ?? '').toString(),
      profileUrl: (json['profile'] ?? '').toString(),
    );
  }

  final String userId;
  final int questionsAnswered;
  final int correctAnswers;
  final String strongCategory;
  final int strongRatio;
  final String weakCategory;
  final int weakRatio;
  final int bestPosition;
  final String dateCreated;
  final String name;
  final String profileUrl;

  int get accuracyPercent {
    if (questionsAnswered <= 0) {
      return 0;
    }
    return ((correctAnswers / questionsAnswered) * 100).round();
  }
}
