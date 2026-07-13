import '../../config/data/mock_system_config_repository.dart';
import '../../quiz/data/mock_quiz_repository.dart';
import '../models/home_bootstrap_data.dart';

class HomeBootstrapRepository {
  HomeBootstrapRepository({
    required this.systemConfigRepository,
    required this.quizRepository,
  });

  final SystemConfigRepository systemConfigRepository;
  final QuizRepository quizRepository;

  Future<HomeBootstrapData> load({
    required String languageId,
    String? userId,
  }) async {
    final systemConfig = await systemConfigRepository.fetchSystemConfig();
    final quizCategories = await quizRepository.fetchCategories(
      type: 1,
      languageId: systemConfig.languageMode ? languageId : null,
      userId: userId,
    );

    return HomeBootstrapData(
      systemConfig: systemConfig,
      quizCategories: quizCategories,
    );
  }
}
