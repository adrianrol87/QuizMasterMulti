import '../../config/models/system_config.dart';
import '../../quiz/models/quiz_category.dart';

class HomeBootstrapData {
  const HomeBootstrapData({
    required this.systemConfig,
    required this.quizCategories,
  });

  final SystemConfig systemConfig;
  final List<QuizCategory> quizCategories;
}
