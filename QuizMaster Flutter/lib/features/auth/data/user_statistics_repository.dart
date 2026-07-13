import '../../../core/network/php_api_client.dart';
import '../models/user_statistics.dart';

abstract class UserStatisticsRepository {
  Future<UserStatistics> fetchUserStatistics(String userId);
}

class RemoteUserStatisticsRepository implements UserStatisticsRepository {
  const RemoteUserStatisticsRepository({
    required this.apiClient,
  });

  final PhpApiClient apiClient;

  @override
  Future<UserStatistics> fetchUserStatistics(String userId) async {
    final response = await apiClient.post({
      'get_users_statistics': '1',
      'user_id': userId,
    });
    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      throw const PhpApiException('Invalid user statistics payload.');
    }
    return UserStatistics.fromJson(data);
  }
}

class MockUserStatisticsRepository implements UserStatisticsRepository {
  const MockUserStatisticsRepository();

  @override
  Future<UserStatistics> fetchUserStatistics(String userId) async {
    return UserStatistics.fromJson(const {
      'user_id': '0',
      'questions_answered': 0,
      'correct_answers': 0,
      'strong_category': '0',
      'ratio1': 0,
      'weak_category': '0',
      'ratio2': 0,
      'best_position': 0,
      'date_created': '',
      'name': 'Player',
      'profile': '',
    });
  }
}
