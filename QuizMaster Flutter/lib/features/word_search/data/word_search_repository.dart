import '../../../core/network/php_api_client.dart';

class WordSearchCategory {
  const WordSearchCategory({
    required this.id,
    required this.languageId,
    required this.title,
    required this.imageUrl,
    required this.totalLevels,
    required this.plan,
    required this.amount,
    required this.isPurchased,
  });

  factory WordSearchCategory.fromApi(Map<String, dynamic> json) {
    return WordSearchCategory(
      id: (json['id'] ?? '').toString(),
      languageId: (json['language_id'] ?? '0').toString(),
      title: (json['title'] ?? '').toString(),
      imageUrl: (json['image'] ?? '').toString(),
      totalLevels: int.tryParse((json['total_levels'] ?? '0').toString()) ?? 0,
      plan: (json['plan'] ?? 'Free').toString(),
      amount: int.tryParse((json['amount'] ?? '0').toString()) ?? 0,
      isPurchased: (json['IsPurchased'] ?? 'false').toString() == 'true',
    );
  }

  final String id;
  final String languageId;
  final String title;
  final String imageUrl;
  final int totalLevels;
  final String plan;
  final int amount;
  final bool isPurchased;

  bool get isPremium => plan.trim().toLowerCase() == 'paid';
}

class WordSearchLevel {
  const WordSearchLevel({
    required this.id,
    required this.categoryId,
    required this.languageId,
    required this.levelNumber,
    required this.boardRows,
    required this.boardCols,
    required this.timeLimit,
    required this.rewardCoins,
    required this.words,
  });

  factory WordSearchLevel.fromApi(Map<String, dynamic> json) {
    final rawWords = json['words'];
    final words = rawWords is List
        ? rawWords.map((item) => item.toString()).where((item) => item.trim().isNotEmpty).toList()
        : <String>[];

    return WordSearchLevel(
      id: (json['id'] ?? '').toString(),
      categoryId: (json['category_id'] ?? '').toString(),
      languageId: (json['language_id'] ?? '0').toString(),
      levelNumber: int.tryParse((json['level_number'] ?? '1').toString()) ?? 1,
      boardRows: int.tryParse((json['board_rows'] ?? '15').toString()) ?? 15,
      boardCols: int.tryParse((json['board_cols'] ?? '15').toString()) ?? 15,
      timeLimit: int.tryParse((json['time_limit'] ?? '120').toString()) ?? 120,
      rewardCoins: int.tryParse((json['reward_coins'] ?? '0').toString()) ?? 0,
      words: words,
    );
  }

  final String id;
  final String categoryId;
  final String languageId;
  final int levelNumber;
  final int boardRows;
  final int boardCols;
  final int timeLimit;
  final int rewardCoins;
  final List<String> words;
}

class WordSearchProgress {
  const WordSearchProgress({
    required this.categoryId,
    required this.completedLevels,
    required this.highestCompletedLevel,
    required this.nextUnlockedLevel,
    required this.bestTimes,
  });

  factory WordSearchProgress.empty(String categoryId) {
    return WordSearchProgress(
      categoryId: categoryId,
      completedLevels: const <int>{},
      highestCompletedLevel: 0,
      nextUnlockedLevel: 1,
      bestTimes: const <int, int>{},
    );
  }

  factory WordSearchProgress.fromApi(Map<String, dynamic> json) {
    final completed = <int>{};
    final rawCompleted = json['completed_levels'];
    if (rawCompleted is List) {
      for (final item in rawCompleted) {
        final parsed = int.tryParse(item.toString());
        if (parsed != null && parsed > 0) {
          completed.add(parsed);
        }
      }
    }

    final bestTimes = <int, int>{};
    final rawBestTimes = json['best_times'];
    if (rawBestTimes is Map) {
      for (final entry in rawBestTimes.entries) {
        final level = int.tryParse(entry.key.toString());
        final time = int.tryParse(entry.value.toString());
        if (level != null && level > 0 && time != null && time > 0) {
          bestTimes[level] = time;
        }
      }
    }

    return WordSearchProgress(
      categoryId: (json['category_id'] ?? '').toString(),
      completedLevels: completed,
      highestCompletedLevel:
          int.tryParse((json['highest_completed_level'] ?? '0').toString()) ?? 0,
      nextUnlockedLevel: int.tryParse((json['next_unlocked_level'] ?? '1').toString()) ?? 1,
      bestTimes: bestTimes,
    );
  }

  final String categoryId;
  final Set<int> completedLevels;
  final int highestCompletedLevel;
  final int nextUnlockedLevel;
  final Map<int, int> bestTimes;

  bool isCompleted(int levelNumber) => completedLevels.contains(levelNumber);
  bool isUnlocked(int levelNumber) => levelNumber <= nextUnlockedLevel;
}

abstract class WordSearchRepository {
  Future<List<WordSearchCategory>> fetchCategories({
    required String languageId,
    String? userId,
  });

  Future<List<WordSearchLevel>> fetchLevels({
    required String categoryId,
    required String languageId,
  });

  Future<WordSearchLevel> fetchLevel({
    required String categoryId,
    required int levelNumber,
    required String languageId,
  });

  Future<WordSearchProgress> fetchProgress({
    required String userId,
    required String categoryId,
  });

  Future<WordSearchProgress> saveProgress({
    required String userId,
    required String categoryId,
    required int levelNumber,
    required bool isCompleted,
    required int bestTimeSeconds,
  });

  Future<void> unlockPremiumCategory({
    required String userId,
    required String categoryId,
  });
}

class RemoteWordSearchRepository implements WordSearchRepository {
  const RemoteWordSearchRepository({
    required this.apiClient,
  });

  final PhpApiClient apiClient;

  @override
  Future<List<WordSearchCategory>> fetchCategories({
    required String languageId,
    String? userId,
  }) async {
    final body = <String, String>{
      'get_word_search_categories': '1',
      'language_id': languageId,
    };
    if (userId != null && userId.isNotEmpty) {
      body['user_id'] = userId;
    }
    final response = await apiClient.post(body);

    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid word search categories payload.');
    }

    return data
        .map((item) => WordSearchCategory.fromApi(Map<String, dynamic>.from(item as Map)))
        .toList();
  }

  @override
  Future<List<WordSearchLevel>> fetchLevels({
    required String categoryId,
    required String languageId,
  }) async {
    final response = await apiClient.post({
      'get_word_search_levels': '1',
      'category_id': categoryId,
      'language_id': languageId,
    });

    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid word search levels payload.');
    }

    return data
        .map((item) => WordSearchLevel.fromApi(Map<String, dynamic>.from(item as Map)))
        .toList();
  }

  @override
  Future<WordSearchLevel> fetchLevel({
    required String categoryId,
    required int levelNumber,
    required String languageId,
  }) async {
    final response = await apiClient.post({
      'get_word_search_level': '1',
      'category_id': categoryId,
      'level_number': '$levelNumber',
      'language_id': languageId,
    });

    final data = response['data'];
    if (data is! Map) {
      throw const PhpApiException('Invalid word search level payload.');
    }

    return WordSearchLevel.fromApi(Map<String, dynamic>.from(data));
  }

  @override
  Future<WordSearchProgress> fetchProgress({
    required String userId,
    required String categoryId,
  }) async {
    final response = await apiClient.post({
      'get_word_search_progress': '1',
      'user_id': userId,
      'category_id': categoryId,
    });

    final data = response['data'];
    if (data is! Map) {
      throw const PhpApiException('Invalid word search progress payload.');
    }

    return WordSearchProgress.fromApi(Map<String, dynamic>.from(data));
  }

  @override
  Future<WordSearchProgress> saveProgress({
    required String userId,
    required String categoryId,
    required int levelNumber,
    required bool isCompleted,
    required int bestTimeSeconds,
  }) async {
    final response = await apiClient.post({
      'set_word_search_progress': '1',
      'user_id': userId,
      'category_id': categoryId,
      'level_number': '$levelNumber',
      'is_completed': isCompleted ? '1' : '0',
      'best_time_seconds': '$bestTimeSeconds',
    });

    final data = response['data'];
    if (data is! Map) {
      throw const PhpApiException('Invalid saved word search progress payload.');
    }

    return WordSearchProgress.fromApi(Map<String, dynamic>.from(data));
  }

  @override
  Future<void> unlockPremiumCategory({
    required String userId,
    required String categoryId,
  }) async {
    await apiClient.post({
      'user_purchased_word_search_category': '1',
      'user_id': userId,
      'cate_id': categoryId,
    });
  }
}
