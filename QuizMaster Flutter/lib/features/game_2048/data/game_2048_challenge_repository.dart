import '../../../core/network/php_api_client.dart';

class Game2048ChallengeGoal {
  const Game2048ChallengeGoal({
    required this.tileValue,
    required this.targetCount,
  });

  factory Game2048ChallengeGoal.fromApi(Map<String, dynamic> json) {
    return Game2048ChallengeGoal(
      tileValue: int.tryParse((json['tile_value'] ?? '0').toString()) ?? 0,
      targetCount:
          int.tryParse((json['target_count'] ?? '0').toString()) ?? 0,
    );
  }

  final int tileValue;
  final int targetCount;
}

class Game2048ChallengeLevel {
  const Game2048ChallengeLevel({
    required this.levelNumber,
    required this.moveLimit,
    required this.goals,
    this.timeLimitSeconds = 300,
  });

  factory Game2048ChallengeLevel.fromApi(Map<String, dynamic> json) {
    final rawGoals = json['goals'];
    final goals = rawGoals is List
        ? rawGoals
            .map(
              (item) => Game2048ChallengeGoal.fromApi(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .where((goal) => goal.tileValue > 0 && goal.targetCount > 0)
            .toList()
        : <Game2048ChallengeGoal>[];

    return Game2048ChallengeLevel(
      levelNumber: int.tryParse((json['level_number'] ?? '1').toString()) ?? 1,
      moveLimit: int.tryParse((json['move_limit'] ?? '0').toString()) ?? 0,
      goals: goals,
      timeLimitSeconds:
          int.tryParse((json['time_limit_seconds'] ?? '300').toString()) ?? 300,
    );
  }

  final int levelNumber;
  final int moveLimit;
  final List<Game2048ChallengeGoal> goals;
  final int timeLimitSeconds;
}

class Game2048ChallengeProgress {
  const Game2048ChallengeProgress({
    required this.completedLevels,
    required this.highestCompletedLevel,
    required this.nextUnlockedLevel,
    required this.bestMovesLeft,
  });

  factory Game2048ChallengeProgress.empty() {
    return const Game2048ChallengeProgress(
      completedLevels: <int>{},
      highestCompletedLevel: 0,
      nextUnlockedLevel: 1,
      bestMovesLeft: <int, int>{},
    );
  }

  factory Game2048ChallengeProgress.fromApi(Map<String, dynamic> json) {
    final completedLevels = <int>{};
    final rawCompleted = json['completed_levels'];
    if (rawCompleted is List) {
      for (final item in rawCompleted) {
        final parsed = int.tryParse(item.toString());
        if (parsed != null && parsed > 0) {
          completedLevels.add(parsed);
        }
      }
    }

    final bestMovesLeft = <int, int>{};
    final rawBestMovesLeft = json['best_moves_left'];
    if (rawBestMovesLeft is Map) {
      for (final entry in rawBestMovesLeft.entries) {
        final level = int.tryParse(entry.key.toString());
        final moves = int.tryParse(entry.value.toString());
        if (level != null && moves != null) {
          bestMovesLeft[level] = moves;
        }
      }
    }

    return Game2048ChallengeProgress(
      completedLevels: completedLevels,
      highestCompletedLevel:
          int.tryParse((json['highest_completed_level'] ?? '0').toString()) ??
              0,
      nextUnlockedLevel:
          int.tryParse((json['next_unlocked_level'] ?? '1').toString()) ?? 1,
      bestMovesLeft: bestMovesLeft,
    );
  }

  final Set<int> completedLevels;
  final int highestCompletedLevel;
  final int nextUnlockedLevel;
  final Map<int, int> bestMovesLeft;
}

abstract class Game2048ChallengeRepository {
  Future<List<Game2048ChallengeLevel>> fetchLevels();

  Future<Game2048ChallengeProgress> fetchProgress({
    required String userId,
  });

  Future<Game2048ChallengeProgress> saveProgress({
    required String userId,
    required int levelNumber,
    required bool isCompleted,
    required int bestMovesLeft,
  });
}

class RemoteGame2048ChallengeRepository
    implements Game2048ChallengeRepository {
  const RemoteGame2048ChallengeRepository({
    required this.apiClient,
  });

  final PhpApiClient apiClient;

  @override
  Future<List<Game2048ChallengeLevel>> fetchLevels() async {
    final response = await apiClient.post({
      'get_game_2048_challenge_levels': '1',
    });

    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid 2048 challenge levels payload.');
    }

    return data
        .map(
          (item) => Game2048ChallengeLevel.fromApi(
            Map<String, dynamic>.from(item as Map),
          ),
        )
        .toList();
  }

  @override
  Future<Game2048ChallengeProgress> fetchProgress({
    required String userId,
  }) async {
    final response = await apiClient.post({
      'get_game_2048_challenge_progress': '1',
      'user_id': userId,
    });

    final data = response['data'];
    if (data is! Map) {
      throw const PhpApiException('Invalid 2048 challenge progress payload.');
    }

    return Game2048ChallengeProgress.fromApi(Map<String, dynamic>.from(data));
  }

  @override
  Future<Game2048ChallengeProgress> saveProgress({
    required String userId,
    required int levelNumber,
    required bool isCompleted,
    required int bestMovesLeft,
  }) async {
    final response = await apiClient.post({
      'set_game_2048_challenge_progress': '1',
      'user_id': userId,
      'level_number': '$levelNumber',
      'is_completed': isCompleted ? '1' : '0',
      'best_moves_left': '$bestMovesLeft',
    });

    final data = response['data'];
    if (data is! Map) {
      throw const PhpApiException(
        'Invalid saved 2048 challenge progress payload.',
      );
    }

    return Game2048ChallengeProgress.fromApi(Map<String, dynamic>.from(data));
  }
}
