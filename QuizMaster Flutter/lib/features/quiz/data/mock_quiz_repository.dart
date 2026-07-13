import 'package:flutter/material.dart';

import '../models/quiz_category.dart';
import '../models/quiz_question.dart';
import '../models/quiz_subcategory.dart';

abstract class QuizRepository {
  Future<List<QuizCategory>> fetchCategories({
    required int type,
    String? languageId,
    String? userId,
  });

  Future<List<QuizSubcategory>> fetchSubcategories({
    required String mainCategoryId,
  });

  Future<List<QuizQuestion>> fetchQuestionsBySubcategory({
    required String subcategoryId,
    int? level,
  });

  Future<List<QuizQuestion>> fetchQuestionsByCategory({
    required String categoryId,
    int? level,
  });

  Future<List<QuizQuestion>> fetchDailyQuiz({
    String? languageId,
    String? userId,
  });

  Future<int> fetchSavedLevel({
    required String userId,
    required String categoryId,
    String? subcategoryId,
  });

  Future<void> saveLevelProgress({
    required String userId,
    required String categoryId,
    String? subcategoryId,
    required int level,
  });

  Future<void> unlockPremiumCategory({
    required String userId,
    required String categoryId,
  });

  Future<void> reportQuestion({
    required String userId,
    required String questionId,
    required String message,
  });
}

class MockQuizRepository implements QuizRepository {
  const MockQuizRepository();

  static const _quizCategoryPayload = [
    {
      'id': '31',
      'language_id': '1',
      'category_name': 'General Knowledge',
      'row_order': '1',
      'no_of_que': '312',
      'no_of': '4',
      'maxlevel': '12',
      'plan': 'Free',
      'amount': '0',
      'image': '',
      'IsPurchased': 'true',
    },
    {
      'id': '32',
      'language_id': '1',
      'category_name': 'Movie Quiz',
      'row_order': '2',
      'no_of_que': '441',
      'no_of': '5',
      'maxlevel': '16',
      'plan': 'Free',
      'amount': '0',
      'image': '',
      'IsPurchased': 'true',
    },
    {
      'id': '33',
      'language_id': '1',
      'category_name': 'Science Quiz',
      'row_order': '3',
      'no_of_que': '254',
      'no_of': '3',
      'maxlevel': '9',
      'plan': 'Free',
      'amount': '0',
      'image': '',
      'IsPurchased': 'true',
    },
    {
      'id': '34',
      'language_id': '1',
      'category_name': 'Sports Quiz',
      'row_order': '4',
      'no_of_que': '198',
      'no_of': '4',
      'maxlevel': '7',
      'plan': 'Free',
      'amount': '0',
      'image': '',
      'IsPurchased': 'true',
    },
    {
      'id': '35',
      'language_id': '1',
      'category_name': 'History Quiz',
      'row_order': '5',
      'no_of_que': '286',
      'no_of': '6',
      'maxlevel': '10',
      'plan': 'Paid',
      'amount': '49',
      'image': '',
      'IsPurchased': 'false',
    },
    {
      'id': '36',
      'language_id': '1',
      'category_name': 'Music Quiz',
      'row_order': '6',
      'no_of_que': '174',
      'no_of': '3',
      'maxlevel': '8',
      'plan': 'Free',
      'amount': '0',
      'image': '',
      'IsPurchased': 'true',
    },
  ];

  static const _subcategoryPayload = {
    '31': [
      {
        'id': '101',
        'maincat_id': '31',
        'subcategory_name': 'World Facts',
        'row_order': '1',
        'no_of': '85',
        'maxlevel': '4',
        'image': '',
      },
      {
        'id': '102',
        'maincat_id': '31',
        'subcategory_name': 'Flags and Capitals',
        'row_order': '2',
        'no_of': '76',
        'maxlevel': '3',
        'image': '',
      },
      {
        'id': '103',
        'maincat_id': '31',
        'subcategory_name': 'Famous People',
        'row_order': '3',
        'no_of': '92',
        'maxlevel': '5',
        'image': '',
      },
      {
        'id': '104',
        'maincat_id': '31',
        'subcategory_name': 'Mixed Trivia',
        'row_order': '4',
        'no_of': '59',
        'maxlevel': '3',
        'image': '',
      },
    ],
    '32': [
      {
        'id': '201',
        'maincat_id': '32',
        'subcategory_name': 'Hollywood',
        'row_order': '1',
        'no_of': '103',
        'maxlevel': '4',
        'image': '',
      },
      {
        'id': '202',
        'maincat_id': '32',
        'subcategory_name': 'Animated Films',
        'row_order': '2',
        'no_of': '88',
        'maxlevel': '4',
        'image': '',
      },
      {
        'id': '203',
        'maincat_id': '32',
        'subcategory_name': 'Classic Cinema',
        'row_order': '3',
        'no_of': '91',
        'maxlevel': '3',
        'image': '',
      },
    ],
    '33': [
      {
        'id': '301',
        'maincat_id': '33',
        'subcategory_name': 'Physics',
        'row_order': '1',
        'no_of': '82',
        'maxlevel': '3',
        'image': '',
      },
      {
        'id': '302',
        'maincat_id': '33',
        'subcategory_name': 'Biology',
        'row_order': '2',
        'no_of': '95',
        'maxlevel': '4',
        'image': '',
      },
      {
        'id': '303',
        'maincat_id': '33',
        'subcategory_name': 'Astronomy',
        'row_order': '3',
        'no_of': '77',
        'maxlevel': '2',
        'image': '',
      },
    ],
  };

  static const _questionsPayload = {
    '101': [
      {
        'id': '1',
        'category': '31',
        'subcategory': '101',
        'question': 'What color is the sky on a clear day?',
        'question_type': '1',
        'optiona': 'Blue',
        'optionb': 'Green',
        'optionc': 'Red',
        'optiond': 'Yellow',
        'answer': 'Blue',
        'level': '1',
        'note': 'A clear sky usually looks blue because of light scattering.',
        'image': '',
      },
      {
        'id': '2',
        'category': '31',
        'subcategory': '101',
        'question': 'How many days are in a week?',
        'question_type': '1',
        'optiona': '5',
        'optionb': '6',
        'optionc': '7',
        'optiond': '8',
        'answer': '7',
        'level': '1',
        'note': '',
        'image': '',
      },
    ],
  };

  static const palette = [
    (Color(0xFF2A7FD4), Icons.psychology_alt_rounded),
    (Color(0xFFC56B20), Icons.local_movies_rounded),
    (Color(0xFF2E94C5), Icons.science_rounded),
    (Color(0xFF4AAE59), Icons.sports_basketball_rounded),
    (Color(0xFF8A5CE6), Icons.account_balance_rounded),
    (Color(0xFFFF7C45), Icons.music_note_rounded),
  ];

  @override
  Future<List<QuizCategory>> fetchCategories({
    required int type,
    String? languageId,
    String? userId,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 180));
    final filtered = _quizCategoryPayload.where((item) {
      if (type != 1) {
        return false;
      }
      if (languageId == null) {
        return true;
      }
      return item['language_id'] == languageId;
    }).toList();

    return filtered.asMap().entries.map((entry) {
      final palette = MockQuizRepository.palette[entry.key % MockQuizRepository.palette.length];
      return QuizCategory.fromApi(
        entry.value,
        color: palette.$1,
        icon: palette.$2,
      );
    }).toList();
  }

  @override
  Future<List<QuizSubcategory>> fetchSubcategories({
    required String mainCategoryId,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 160));
    final rawItems = _subcategoryPayload[mainCategoryId] ?? const [];
    return rawItems.map(QuizSubcategory.fromApi).toList();
  }

  @override
  Future<List<QuizQuestion>> fetchQuestionsBySubcategory({
    required String subcategoryId,
    int? level,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 140));
    final rawItems = (_questionsPayload[subcategoryId] ?? const []).where((item) {
      if (level == null) {
        return true;
      }
      return int.tryParse((item['level'] ?? '0').toString()) == level;
    }).toList();
    return rawItems.map(QuizQuestion.fromApi).toList();
  }

  @override
  Future<List<QuizQuestion>> fetchQuestionsByCategory({
    required String categoryId,
    int? level,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 140));
    final combined = _questionsPayload.values
        .expand((group) => group)
        .where((item) {
          if (item['category'] != categoryId) {
            return false;
          }
          if (level == null) {
            return true;
          }
          return int.tryParse((item['level'] ?? '0').toString()) == level;
        })
        .toList();
    return combined.map(QuizQuestion.fromApi).toList();
  }

  @override
  Future<List<QuizQuestion>> fetchDailyQuiz({
    String? languageId,
    String? userId,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 140));
    final fallbackCategory = languageId == '2' ? '31' : '31';
    return fetchQuestionsByCategory(categoryId: fallbackCategory);
  }

  @override
  Future<int> fetchSavedLevel({
    required String userId,
    required String categoryId,
    String? subcategoryId,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 100));
    return 1;
  }

  @override
  Future<void> saveLevelProgress({
    required String userId,
    required String categoryId,
    String? subcategoryId,
    required int level,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 100));
  }

  @override
  Future<void> unlockPremiumCategory({
    required String userId,
    required String categoryId,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 160));
  }

  @override
  Future<void> reportQuestion({
    required String userId,
    required String questionId,
    required String message,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
  }
}
