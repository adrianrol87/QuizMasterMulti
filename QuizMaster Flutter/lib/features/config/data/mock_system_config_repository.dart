import '../models/system_config.dart';

abstract class SystemConfigRepository {
  Future<SystemConfig> fetchSystemConfig();
}

class MockSystemConfigRepository implements SystemConfigRepository {
  const MockSystemConfigRepository();

  @override
  Future<SystemConfig> fetchSystemConfig() async {
    await Future<void>.delayed(const Duration(milliseconds: 180));
    return SystemConfig.fromApi(const {
      'app_link': 'https://play.google.com/store/apps/details?id=com.adrianrol87.quizmaster',
      'more_apps': 'https://play.google.com/store/apps/developer?id=Adrian',
      'ios_app_link': '',
      'ios_more_apps': '',
      'refer_coin': '10',
      'earn_coin': '5',
      'reward_coin': '15',
      'welcome_coin': '25',
      'app_version': '0.1.0',
      'true_value': 'True',
      'false_value': 'False',
      'answer_mode': '1',
      'language_mode': '1',
      'option_e_mode': '0',
      'force_update': '0',
      'daily_quiz_mode': '1',
      'contest_mode': '1',
      'learning_zone_mode': '1',
      'maths_quiz_mode': '1',
      'true_false_mode': '1',
      'spin_mode': '0',
      'fix_question': '0',
      'total_question': '10',
      'app_maintenance': '0',
      'app_maintenance_message': '',
      'shareapp_text': 'Try QuizMaster and challenge your friends.',
      'in_app_purchase_mode': '0',
      'battle_random_category_mode': '1',
      'battle_group_category_mode': '1',
      'instagram_link': '',
      'facebook_link': '',
      'youtube_link': '',
      'in_app_ads_mode': '0',
      'ads_type': '1',
      'admob_Rewarded_Video_Ads': '',
      'admob_interstitial_id': '',
      'admob_banner_id': '',
      'admob_openads_id': '',
      'ios_in_app_ads_mode': '0',
      'ios_ads_type': '1',
      'ios_admob_Rewarded_Video_Ads': '',
      'ios_admob_interstitial_id': '',
      'ios_admob_banner_id': '',
      'ios_admob_openads_id': '',
    });
  }
}
