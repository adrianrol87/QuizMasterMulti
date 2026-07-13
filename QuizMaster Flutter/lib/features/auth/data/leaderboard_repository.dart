import '../../../core/network/php_api_client.dart';
import '../../../core/config/backend_config.dart';
import '../models/leaderboard_data.dart';

enum LeaderboardRange { today, month, all }

abstract class LeaderboardRepository {
  Future<LeaderboardData> fetchLeaderboard({
    required String userId,
    required LeaderboardRange range,
  });
}

class RemoteLeaderboardRepository implements LeaderboardRepository {
  const RemoteLeaderboardRepository({
    required this.apiClient,
  });

  final PhpApiClient apiClient;

  @override
  Future<LeaderboardData> fetchLeaderboard({
    required String userId,
    required LeaderboardRange range,
  }) async {
    if (BackendConfig.useFakeTop100Leaderboard) {
      return _buildFakeTop100(userId, range);
    }

    final response = await apiClient.post({
      switch (range) {
        LeaderboardRange.today => 'get_datewise_leaderboard',
        LeaderboardRange.month => 'get_monthly_leaderboard',
        LeaderboardRange.all => 'get_global_leaderboard',
      }: '1',
      'user_id': userId,
      'limit': '50',
    });

    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      throw const PhpApiException('Invalid leaderboard payload.');
    }

    return LeaderboardData.fromJson(data);
  }

  LeaderboardData _buildFakeTop100(String userId, LeaderboardRange range) {
    final rangeBoost = switch (range) {
      LeaderboardRange.today => 0,
      LeaderboardRange.month => 8,
      LeaderboardRange.all => 20,
    };

    final entries = <Map<String, String>>[];
    for (var rank = 1; rank <= 100; rank++) {
      final isCurrentUser = rank == 37;
      final score = (210 - rank - rangeBoost).clamp(1, 999);
      entries.add({
        'user_id': isCurrentUser ? userId : 'demo_$rank',
        'name': isCurrentUser ? 'Adrian Rodriguez Llorens' : 'Player $rank',
        'profile': '',
        'score': '$score',
        'user_rank': '$rank',
      });
    }

    final currentUser = entries.firstWhere(
      (entry) => entry['user_id'] == userId,
      orElse: () => {
        'user_id': userId,
        'name': 'Adrian Rodriguez Llorens',
        'profile': '',
        'score': '173',
        'user_rank': '37',
      },
    );

    return LeaderboardData.fromJson({
      'entries': entries,
      'current_user': currentUser,
    });
  }
}

class MockLeaderboardRepository implements LeaderboardRepository {
  const MockLeaderboardRepository();

  @override
  Future<LeaderboardData> fetchLeaderboard({
    required String userId,
    required LeaderboardRange range,
  }) async {
    return LeaderboardData.fromJson({
      'entries': [
        {
          'user_id': '12',
          'name': 'Share Notes',
          'profile': '',
          'score': '44',
          'user_rank': '1',
        },
        {
          'user_id': '9',
          'name': 'Olabode James',
          'profile': '',
          'score': '34',
          'user_rank': '2',
        },
        {
          'user_id': '14',
          'name': 'Muhammad Lawal',
          'profile': '',
          'score': '34',
          'user_rank': '3',
        },
        {
          'user_id': '4',
          'name': 'Wrteam Dev',
          'profile': '',
          'score': '28',
          'user_rank': '4',
        },
        {
          'user_id': '5',
          'name': 'Rathika Arunkumar',
          'profile': '',
          'score': '22',
          'user_rank': '5',
        },
        {
          'user_id': userId,
          'name': 'Adrian',
          'profile': '',
          'score': '8',
          'user_rank': '8',
        },
      ],
      'current_user': {
        'user_id': userId,
        'name': 'Adrian',
        'profile': '',
        'score': '8',
        'user_rank': '8',
      },
    });
  }
}
