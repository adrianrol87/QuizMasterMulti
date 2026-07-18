import '../../../core/network/php_api_client.dart';
import '../../quiz/models/quiz_question.dart';

class BookmarkedQuestion {
  const BookmarkedQuestion({
    required this.question,
    required this.categoryName,
    required this.bookmarkedAt,
  });

  factory BookmarkedQuestion.fromApi(Map<String, dynamic> json) {
    return BookmarkedQuestion(
      question: QuizQuestion.fromApi(json),
      categoryName: (json['category_name'] ?? '').toString(),
      bookmarkedAt: (json['bookmarked_at'] ?? '').toString(),
    );
  }

  final QuizQuestion question;
  final String categoryName;
  final String bookmarkedAt;
}

abstract class BookmarkRepository {
  Future<List<BookmarkedQuestion>> fetchBookmarks(String userId);

  Future<Set<String>> fetchBookmarkIds(String userId);

  Future<bool> setBookmark({
    required String userId,
    required String questionId,
    required bool bookmarked,
  });
}

class RemoteBookmarkRepository implements BookmarkRepository {
  const RemoteBookmarkRepository({required this.apiClient});

  final PhpApiClient apiClient;

  @override
  Future<List<BookmarkedQuestion>> fetchBookmarks(String userId) async {
    final response = await apiClient.post({
      'get_bookmarked_questions': '1',
      'user_id': userId,
    });
    final data = response['data'];
    if (data is! List) {
      throw const PhpApiException('Invalid bookmarks payload.');
    }
    return data
        .whereType<Map>()
        .map(
          (item) => BookmarkedQuestion.fromApi(
            Map<String, dynamic>.from(item),
          ),
        )
        .toList();
  }

  @override
  Future<Set<String>> fetchBookmarkIds(String userId) async {
    final response = await apiClient.post({
      'get_bookmarked_question_ids': '1',
      'user_id': userId,
    });
    final data = response['data'];
    if (data is! List) {
      return <String>{};
    }
    return data.map((item) => item.toString()).toSet();
  }

  @override
  Future<bool> setBookmark({
    required String userId,
    required String questionId,
    required bool bookmarked,
  }) async {
    final response = await apiClient.post({
      'set_question_bookmark': '1',
      'user_id': userId,
      'question_id': questionId,
      'bookmarked': bookmarked ? '1' : '0',
    });
    final data = response['data'];
    if (data is! Map) {
      return bookmarked;
    }
    return (data['bookmarked'] ?? '0').toString() == '1';
  }
}
