class SystemConfig {
  const SystemConfig({
    required this.appLink,
    required this.moreAppsLink,
    required this.iosAppLink,
    required this.iosMoreAppsLink,
    required this.referCoin,
    required this.earnCoin,
    required this.rewardCoin,
    required this.welcomeCoin,
    required this.appVersion,
    required this.trueValue,
    required this.falseValue,
    required this.answerMode,
    required this.languageMode,
    required this.optionEMode,
    required this.forceUpdate,
    required this.dailyQuizMode,
    required this.contestMode,
    required this.learningZoneMode,
    required this.mathsQuizMode,
    required this.trueFalseMode,
    required this.spinMode,
    required this.fixQuestionMode,
    required this.totalQuestionPerLevel,
    required this.appMaintenance,
    required this.appMaintenanceMessage,
    required this.shareAppText,
    required this.inAppPurchaseMode,
    required this.battleRandomCategoryMode,
    required this.battleGroupCategoryMode,
    required this.instagramLink,
    required this.facebookLink,
    required this.youtubeLink,
    required this.inAppAdsMode,
    required this.adsType,
    required this.admobRewardedVideoAds,
    required this.admobInterstitialId,
    required this.admobBannerId,
    required this.admobOpenAdsId,
    required this.iosInAppAdsMode,
    required this.iosAdsType,
    required this.iosAdmobRewardedVideoAds,
    required this.iosAdmobInterstitialId,
    required this.iosAdmobBannerId,
    required this.iosAdmobOpenAdsId,
  });

  factory SystemConfig.fromApi(Map<String, dynamic> json) {
    return SystemConfig(
      appLink: _readString(json['app_link']),
      moreAppsLink: _readString(json['more_apps']),
      iosAppLink: _readString(json['ios_app_link']),
      iosMoreAppsLink: _readString(json['ios_more_apps']),
      referCoin: _readInt(json['refer_coin']),
      earnCoin: _readInt(json['earn_coin']),
      rewardCoin: _readInt(json['reward_coin']),
      welcomeCoin: _readInt(json['welcome_coin']),
      appVersion: _readString(json['app_version']),
      trueValue: _readString(json['true_value'], fallback: 'True'),
      falseValue: _readString(json['false_value'], fallback: 'False'),
      answerMode: _isEnabled(json['answer_mode']),
      languageMode: _isEnabled(json['language_mode']),
      optionEMode: _isEnabled(json['option_e_mode']),
      forceUpdate: _isEnabled(json['force_update']),
      dailyQuizMode: _isEnabled(json['daily_quiz_mode']),
      contestMode: _isEnabled(json['contest_mode']),
      learningZoneMode: _isEnabled(json['learning_zone_mode']),
      mathsQuizMode: _isEnabled(json['maths_quiz_mode']),
      trueFalseMode: _isEnabled(json['true_false_mode']),
      spinMode: _isEnabled(json['spin_mode']),
      fixQuestionMode: _isEnabled(json['fix_question']),
      totalQuestionPerLevel: _readInt(json['total_question']),
      appMaintenance: _isEnabled(json['app_maintenance']),
      appMaintenanceMessage: _readString(json['app_maintenance_message']),
      shareAppText: _readString(json['shareapp_text']),
      inAppPurchaseMode: _isEnabled(json['in_app_purchase_mode']),
      battleRandomCategoryMode: _isEnabled(json['battle_random_category_mode']),
      battleGroupCategoryMode: _isEnabled(json['battle_group_category_mode']),
      instagramLink: _readString(json['instagram_link']),
      facebookLink: _readString(json['facebook_link']),
      youtubeLink: _readString(json['youtube_link']),
      inAppAdsMode: _isEnabled(json['in_app_ads_mode']),
      adsType: _readInt(json['ads_type']),
      admobRewardedVideoAds: _readString(json['admob_Rewarded_Video_Ads']),
      admobInterstitialId: _readString(json['admob_interstitial_id']),
      admobBannerId: _readString(json['admob_banner_id']),
      admobOpenAdsId: _readString(json['admob_openads_id']),
      iosInAppAdsMode: _isEnabled(json['ios_in_app_ads_mode']),
      iosAdsType: _readInt(json['ios_ads_type']),
      iosAdmobRewardedVideoAds: _readString(json['ios_admob_Rewarded_Video_Ads']),
      iosAdmobInterstitialId: _readString(json['ios_admob_interstitial_id']),
      iosAdmobBannerId: _readString(json['ios_admob_banner_id']),
      iosAdmobOpenAdsId: _readString(json['ios_admob_openads_id']),
    );
  }

  final String appLink;
  final String moreAppsLink;
  final String iosAppLink;
  final String iosMoreAppsLink;
  final int referCoin;
  final int earnCoin;
  final int rewardCoin;
  final int welcomeCoin;
  final String appVersion;
  final String trueValue;
  final String falseValue;
  final bool answerMode;
  final bool languageMode;
  final bool optionEMode;
  final bool forceUpdate;
  final bool dailyQuizMode;
  final bool contestMode;
  final bool learningZoneMode;
  final bool mathsQuizMode;
  final bool trueFalseMode;
  final bool spinMode;
  final bool fixQuestionMode;
  final int totalQuestionPerLevel;
  final bool appMaintenance;
  final String appMaintenanceMessage;
  final String shareAppText;
  final bool inAppPurchaseMode;
  final bool battleRandomCategoryMode;
  final bool battleGroupCategoryMode;
  final String instagramLink;
  final String facebookLink;
  final String youtubeLink;
  final bool inAppAdsMode;
  final int adsType;
  final String admobRewardedVideoAds;
  final String admobInterstitialId;
  final String admobBannerId;
  final String admobOpenAdsId;
  final bool iosInAppAdsMode;
  final int iosAdsType;
  final String iosAdmobRewardedVideoAds;
  final String iosAdmobInterstitialId;
  final String iosAdmobBannerId;
  final String iosAdmobOpenAdsId;

  bool requiresForceUpdate(String currentVersion) {
    if (!forceUpdate || appVersion.trim().isEmpty) {
      return false;
    }

    return _compareVersions(currentVersion, appVersion) < 0;
  }

  String get preferredStoreLink {
    if (appLink.trim().isNotEmpty) {
      return appLink.trim();
    }
    if (iosAppLink.trim().isNotEmpty) {
      return iosAppLink.trim();
    }
    return '';
  }

  bool get hasSocialLinks =>
      instagramLink.trim().isNotEmpty ||
      facebookLink.trim().isNotEmpty ||
      youtubeLink.trim().isNotEmpty;

  static bool _isEnabled(dynamic value) {
    return value == 1 || value == '1' || value == true || value == 'true';
  }

  static int _readInt(dynamic value) {
    if (value is int) {
      return value;
    }
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  static String _readString(dynamic value, {String fallback = ''}) {
    final text = value?.toString().trim() ?? '';
    return text.isEmpty ? fallback : text;
  }

  static int _compareVersions(String a, String b) {
    final aParts = a.split('.').map((part) => int.tryParse(part) ?? 0).toList();
    final bParts = b.split('.').map((part) => int.tryParse(part) ?? 0).toList();
    final length = aParts.length > bParts.length ? aParts.length : bParts.length;

    for (var index = 0; index < length; index++) {
      final aValue = index < aParts.length ? aParts[index] : 0;
      final bValue = index < bParts.length ? bParts[index] : 0;
      if (aValue != bValue) {
        return aValue < bValue ? -1 : 1;
      }
    }

    return 0;
  }
}
