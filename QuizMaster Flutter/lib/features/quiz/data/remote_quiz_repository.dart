import '../../../core/network/php_api_client.dart';
import '../models/quiz_category.dart';
import '../models/quiz_question.dart';
import '../models/quiz_subcategory.dart';
import 'mock_quiz_repository.dart';

class RemoteQuizRepository implements QuizRepository {
  const RemoteQuizRepository({
    required this.apiClient,
  });

  final PhpApiClient apiClient;

  static const _fallbackPalette = MockQuizRepository.palette;

  @override
  Future<List<QuizCategory>> fetchCategories({
    required int type,
    String? languageId,
    String? userId,
  }) async {
    Future<Map<String, dynamic>> fetch(Map<String, String> body) {
      return apiClient.post(body);
    }

    Map<String, dynamic> response;
    if (languageId != null && languageId.isNotEmpty) {
      try {
        response = await fetch({
          'get_categories_by_language': '1',
          'language_id': languageId,
          'type': '$type',
          if (userId != null && userId.isNotEmpty) 'user_id': userId,
        });
      } on PhpApiException {
        response = await fetch({
          'get_categories': '1',
          'type': '$type',
          if (userId != null && userId.isNotEmpty) 'user_id': userId,
        });
      }
    } else {
      response = await fetch({
        'get_categories': '1',
        'type': '$type',
        if (userId != null && userId.isNotEmpty) 'user_id': userId,
      });
    }

    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid category payload.');
    }

    final filteredData = languageId == null || languageId.isEmpty
        ? data
        : data.where((item) {
            final raw = Map<String, dynamic>.from(item as Map);
            final itemLanguageId = raw['language_id']?.toString().trim() ?? '';
            return itemLanguageId.isEmpty || itemLanguageId == '0'
                ? true
                : itemLanguageId == languageId;
          }).toList();

    return filteredData.asMap().entries.map((entry) {
      final raw = Map<String, dynamic>.from(entry.value as Map);
      final palette = _fallbackPalette[entry.key % _fallbackPalette.length];
      return QuizCategory.fromApi(
        raw,
        color: palette.$1,
        icon: palette.$2,
      );
    }).toList();
  }

  @override
  Future<List<QuizSubcategory>> fetchSubcategories({
    required String mainCategoryId,
  }) async {
    final response = await apiClient.post({
      'get_subcategory_by_maincategory': '1',
      'main_id': mainCategoryId,
    });

    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid subcategory payload.');
    }

    return data
        .map((item) => QuizSubcategory.fromApi(Map<String, dynamic>.from(item as Map)))
        .toList();
  }

  @override
  Future<List<QuizQuestion>> fetchQuestionsBySubcategory({
    required String subcategoryId,
    int? level,
  }) async {
    if (level != null && level > 0) {
      return fetchQuestionsByLevel(
        level: level,
        subcategoryId: subcategoryId,
      );
    }

    final response = await apiClient.post({
      'get_questions_by_subcategory': '1',
      'subcategory': subcategoryId,
    });

    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid question payload.');
    }

    return data
        .map((item) => QuizQuestion.fromApi(Map<String, dynamic>.from(item as Map)))
        .toList();
  }

  @override
  Future<List<QuizQuestion>> fetchQuestionsByCategory({
    required String categoryId,
    int? level,
  }) async {
    if (level != null && level > 0) {
      return fetchQuestionsByLevel(
        level: level,
        categoryId: categoryId,
      );
    }

    final response = await apiClient.post({
      'get_questions_by_category': '1',
      'category': categoryId,
    });

    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid question payload.');
    }

    return data
        .map((item) => QuizQuestion.fromApi(Map<String, dynamic>.from(item as Map)))
        .toList();
  }

  @override
  Future<List<QuizQuestion>> fetchDailyQuiz({
    String? languageId,
    String? userId,
  }) async {
    final response = await apiClient.post({
      'get_daily_quiz': '1',
      if (languageId != null && languageId.isNotEmpty) 'language_id': languageId,
      if (userId != null && userId.isNotEmpty) 'user_id': userId,
    });

    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid daily quiz payload.');
    }

    return data
        .map((item) => QuizQuestion.fromApi(Map<String, dynamic>.from(item as Map)))
        .toList();
  }

  Future<List<QuizQuestion>> fetchQuestionsByLevel({
    required int level,
    String? categoryId,
    String? subcategoryId,
  }) async {
    final body = <String, String>{
      'get_questions_by_level': '1',
      'level': '$level',
    };

    if (subcategoryId != null && subcategoryId.isNotEmpty) {
      body['subcategory'] = subcategoryId;
    } else if (categoryId != null && categoryId.isNotEmpty) {
      body['category'] = categoryId;
    } else {
      throw const PhpApiException('Missing category or subcategory for level request.');
    }

    final response = await apiClient.post(body);
    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid question payload.');
    }

    return data
        .map((item) => QuizQuestion.fromApi(Map<String, dynamic>.from(item as Map)))
        .toList();
  }

  @override
  Future<int> fetchSavedLevel({
    required String userId,
    required String categoryId,
    String? subcategoryId,
  }) async {
    final response = await apiClient.post({
      'get_level_data': '1',
      'user_id': userId,
      'category': categoryId,
      'subcategory': subcategoryId ?? '0',
    });

    final data = response['data'];
    if (data is! Map) {
      return 1;
    }

    return int.tryParse('${data['level'] ?? '1'}') ?? 1;
  }

  @override
  Future<void> saveLevelProgress({
    required String userId,
    required String categoryId,
    String? subcategoryId,
    required int level,
  }) async {
    await apiClient.post({
      'set_level_data': '1',
      'user_id': userId,
      'category': categoryId,
      'subcategory': subcategoryId ?? '0',
      'level': '$level',
    });
  }

  @override
  Future<void> unlockPremiumCategory({
    required String userId,
    required String categoryId,
  }) async {
    await apiClient.post({
      'user_purchased_category': '1',
      'user_id': userId,
      'cate_id': categoryId,
    });
  }

  @override
  Future<void> reportQuestion({
    required String userId,
    required String questionId,
    required String message,
  }) async {
    await apiClient.post({
      'report_question': '1',
      'user_id': userId,
      'question_id': questionId,
      'message': message,
    });
  }
}
