import '../../../core/network/php_api_client.dart';

abstract class QuizResultRepository {
  Future<void> submitQuizResult({
    required String userId,
    required String categoryId,
    required int questionsAnswered,
    required int correctAnswers,
    required int earnedCoins,
  });

  Future<void> addBonusCoins({
    required String userId,
    required int coins,
  });
}

class RemoteQuizResultRepository implements QuizResultRepository {
  const RemoteQuizResultRepository({
    required this.apiClient,
  });

  final PhpApiClient apiClient;

  @override
  Future<void> submitQuizResult({
    required String userId,
    required String categoryId,
    required int questionsAnswered,
    required int correctAnswers,
    required int earnedCoins,
  }) async {
    if (userId.trim().isEmpty ||
        categoryId.trim().isEmpty ||
        questionsAnswered <= 0 ||
        int.tryParse(userId) == null ||
        int.tryParse(categoryId) == null) {
      return;
    }

    final ratio = ((correctAnswers / questionsAnswered) * 100).round();
    final score = correctAnswers;

    await apiClient.post({
      'set_monthly_leaderboard': '1',
      'user_id': userId,
      'score': '$score',
    });

    await apiClient.post({
      'set_users_statistics': '1',
      'user_id': userId,
      'questions_answered': '$questionsAnswered',
      'correct_answers': '$correctAnswers',
      'category_id': categoryId,
      'ratio': '$ratio',
    });

    if (earnedCoins > 0) {
      await apiClient.post({
        'set_user_coin_score': '1',
        'user_id': userId,
        'coins': '$earnedCoins',
      });
    }
  }

  @override
  Future<void> addBonusCoins({
    required String userId,
    required int coins,
  }) async {
    if (userId.trim().isEmpty || int.tryParse(userId) == null || coins <= 0) {
      return;
    }

    await apiClient.post({
      'set_user_coin_score': '1',
      'user_id': userId,
      'coins': '$coins',
    });
  }
}

class MockQuizResultRepository implements QuizResultRepository {
  const MockQuizResultRepository();

  @override
  Future<void> submitQuizResult({
    required String userId,
    required String categoryId,
    required int questionsAnswered,
    required int correctAnswers,
    required int earnedCoins,
  }) async {}

  @override
  Future<void> addBonusCoins({
    required String userId,
    required int coins,
  }) async {}
}
