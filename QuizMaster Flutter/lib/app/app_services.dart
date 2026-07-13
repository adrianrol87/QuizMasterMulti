import '../core/config/backend_config.dart';
import '../core/network/php_api_client.dart';
import '../core/notifications/push_notification_service.dart';
import '../features/auth/data/auth_repository.dart';
import '../features/auth/data/leaderboard_repository.dart';
import '../features/auth/data/mock_auth_repository.dart';
import '../features/auth/data/remote_auth_repository.dart';
import '../features/auth/data/user_statistics_repository.dart';
import '../features/config/data/app_content_repository.dart';
import '../features/config/data/mock_app_content_repository.dart';
import '../features/config/data/mock_system_config_repository.dart';
import '../features/config/data/remote_app_content_repository.dart';
import '../features/config/data/remote_system_config_repository.dart';
import '../features/quiz/data/mock_quiz_repository.dart';
import '../features/quiz/data/remote_quiz_repository.dart';
import '../features/quiz/data/quiz_result_repository.dart';

class AppServices {
  AppServices._({
    required this.authRepository,
    required this.systemConfigRepository,
    required this.appContentRepository,
    required this.quizRepository,
    required this.quizResultRepository,
    required this.userStatisticsRepository,
    required this.leaderboardRepository,
    required this.pushNotificationService,
  });

  factory AppServices.create() {
    if (!BackendConfig.isConfigured) {
      return AppServices._(
        authRepository: const MockAuthRepository(),
        systemConfigRepository: const MockSystemConfigRepository(),
        appContentRepository: const MockAppContentRepository(),
        quizRepository: const MockQuizRepository(),
        quizResultRepository: const MockQuizResultRepository(),
        userStatisticsRepository: const MockUserStatisticsRepository(),
        leaderboardRepository: const MockLeaderboardRepository(),
        pushNotificationService: PushNotificationService(),
      );
    }

    final client = PhpApiClient();
    return AppServices._(
      authRepository: RemoteAuthRepository(apiClient: client),
      systemConfigRepository: RemoteSystemConfigRepository(apiClient: client),
      appContentRepository: RemoteAppContentRepository(apiClient: client),
      quizRepository: RemoteQuizRepository(apiClient: client),
      quizResultRepository: RemoteQuizResultRepository(apiClient: client),
      userStatisticsRepository: RemoteUserStatisticsRepository(apiClient: client),
      leaderboardRepository: RemoteLeaderboardRepository(apiClient: client),
      pushNotificationService: PushNotificationService(apiClient: client),
    );
  }

  final AuthRepository authRepository;
  final SystemConfigRepository systemConfigRepository;
  final AppContentRepository appContentRepository;
  final QuizRepository quizRepository;
  final QuizResultRepository quizResultRepository;
  final UserStatisticsRepository userStatisticsRepository;
  final LeaderboardRepository leaderboardRepository;
  final PushNotificationService pushNotificationService;
}
