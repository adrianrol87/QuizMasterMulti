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
    required this.quizLevelsCompleted,
    required this.wordSearchLevelsCompleted,
    required this.wordSearchBestTimeSeconds,
    required this.game2048LevelsCompleted,
    required this.game2048BestMovesLeft,
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
      quizLevelsCompleted:
          int.tryParse((json['quiz_levels_completed'] ?? '0').toString()) ?? 0,
      wordSearchLevelsCompleted: int.tryParse(
            (json['word_search_levels_completed'] ?? '0').toString(),
          ) ??
          0,
      wordSearchBestTimeSeconds: int.tryParse(
            (json['word_search_best_time_seconds'] ?? '0').toString(),
          ) ??
          0,
      game2048LevelsCompleted: int.tryParse(
            (json['game_2048_levels_completed'] ?? '0').toString(),
          ) ??
          0,
      game2048BestMovesLeft: int.tryParse(
            (json['game_2048_best_moves_left'] ?? '0').toString(),
          ) ??
          0,
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
  final int quizLevelsCompleted;
  final int wordSearchLevelsCompleted;
  final int wordSearchBestTimeSeconds;
  final int game2048LevelsCompleted;
  final int game2048BestMovesLeft;

  bool get hasActivity =>
      questionsAnswered > 0 ||
      quizLevelsCompleted > 0 ||
      wordSearchLevelsCompleted > 0 ||
      game2048LevelsCompleted > 0;

  int get accuracyPercent {
    if (questionsAnswered <= 0) {
      return 0;
    }
    return ((correctAnswers / questionsAnswered) * 100).round();
  }
}
