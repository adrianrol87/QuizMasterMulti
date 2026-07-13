import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';

import '../../features/config/models/system_config.dart';

class QuizAdService {
  QuizAdService._();

  static final QuizAdService instance = QuizAdService._();

  static const _androidTestAppId = 'ca-app-pub-3940256099942544~3347511713';
  static const _iosTestAppId = 'ca-app-pub-3940256099942544~1458002511';
  static const _androidTestBannerId = 'ca-app-pub-3940256099942544/6300978111';
  static const _iosTestBannerId = 'ca-app-pub-3940256099942544/2934735716';
  static const _androidTestInterstitialId = 'ca-app-pub-3940256099942544/1033173712';
  static const _iosTestInterstitialId = 'ca-app-pub-3940256099942544/4411468910';
  static const _androidTestRewardedId = 'ca-app-pub-3940256099942544/5224354917';
  static const _iosTestRewardedId = 'ca-app-pub-3940256099942544/1712485313';

  bool _mobileAdsInitialized = false;
  bool _adsEnabled = false;
  String _bannerUnitId = '';
  String _interstitialUnitId = '';
  String _rewardedUnitId = '';
  InterstitialAd? _interstitialAd;
  RewardedAd? _rewardedAd;
  int _completedLevelCounter = 0;
  bool _adsRemoved = false;

  static bool get isSupportedPlatform =>
      !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS);

  static String get testAppId => defaultTargetPlatform == TargetPlatform.iOS
      ? _iosTestAppId
      : _androidTestAppId;

  bool get adsEnabled => !_adsRemoved && _adsEnabled && isSupportedPlatform;

  bool get adsRemoved => _adsRemoved;

  bool get rewardMultiplierAvailable => adsEnabled && _rewardedUnitId.isNotEmpty;

  Future<void> configure(SystemConfig config) async {
    if (!isSupportedPlatform) {
      _adsEnabled = false;
      return;
    }

    final isAndroid = defaultTargetPlatform == TargetPlatform.android;
    final inAppAdsMode = isAndroid ? config.inAppAdsMode : config.iosInAppAdsMode;
    final adsType = isAndroid ? config.adsType : config.iosAdsType;

    _adsEnabled = inAppAdsMode && adsType == 1;
    _bannerUnitId = _resolveBannerId(config);
    _interstitialUnitId = _resolveInterstitialId(config);
    _rewardedUnitId = _resolveRewardedId(config);

    if (!_adsEnabled) {
      _disposeLoadedAds();
      return;
    }

    if (!_mobileAdsInitialized) {
      await MobileAds.instance.initialize();
      _mobileAdsInitialized = true;
    }

    _primeInterstitial();
    _primeRewarded();
  }

  void setAdsRemoved(bool value) {
    _adsRemoved = value;
    if (_adsRemoved) {
      _disposeLoadedAds();
    }
  }

  BannerAd? createQuestionBanner({
    AdSize size = AdSize.banner,
    VoidCallback? onLoaded,
    VoidCallback? onFailed,
  }) {
    if (!adsEnabled || _bannerUnitId.isEmpty) {
      return null;
    }

    final ad = BannerAd(
      adUnitId: _bannerUnitId,
      request: const AdRequest(),
      size: size,
      listener: BannerAdListener(
        onAdLoaded: (_) => onLoaded?.call(),
        onAdFailedToLoad: (ad, error) {
          ad.dispose();
          onFailed?.call();
        },
      ),
    );

    ad.load();
    return ad;
  }

  Future<void> maybeShowInterstitialForLevelCompletion({
    required int? completedLevel,
  }) async {
    if (!adsEnabled || completedLevel == null || completedLevel <= 0) {
      return;
    }

    _completedLevelCounter += 1;
    if (_completedLevelCounter % 3 != 0) {
      return;
    }

    final ad = _interstitialAd;
    if (ad == null) {
      _primeInterstitial();
      return;
    }

    _interstitialAd = null;
    final completer = Completer<void>();
    ad.fullScreenContentCallback = FullScreenContentCallback(
      onAdDismissedFullScreenContent: (ad) {
        ad.dispose();
        _primeInterstitial();
        if (!completer.isCompleted) {
          completer.complete();
        }
      },
      onAdFailedToShowFullScreenContent: (ad, error) {
        ad.dispose();
        _primeInterstitial();
        if (!completer.isCompleted) {
          completer.complete();
        }
      },
    );

    ad.show();
    await completer.future;
  }

  Future<bool> showRewardedToMultiplyCoins() async {
    if (!rewardMultiplierAvailable) {
      return false;
    }

    final ad = _rewardedAd;
    if (ad == null) {
      _primeRewarded();
      return false;
    }

    _rewardedAd = null;
    final completer = Completer<bool>();
    var earnedReward = false;

    ad.fullScreenContentCallback = FullScreenContentCallback(
      onAdDismissedFullScreenContent: (ad) {
        ad.dispose();
        _primeRewarded();
        if (!completer.isCompleted) {
          completer.complete(earnedReward);
        }
      },
      onAdFailedToShowFullScreenContent: (ad, error) {
        ad.dispose();
        _primeRewarded();
        if (!completer.isCompleted) {
          completer.complete(false);
        }
      },
    );

    await ad.show(
      onUserEarnedReward: (_, __) {
        earnedReward = true;
      },
    );

    return completer.future;
  }

  void _primeInterstitial() {
    if (!adsEnabled || _interstitialUnitId.isEmpty || _interstitialAd != null) {
      return;
    }

    InterstitialAd.load(
      adUnitId: _interstitialUnitId,
      request: const AdRequest(),
      adLoadCallback: InterstitialAdLoadCallback(
        onAdLoaded: (ad) {
          _interstitialAd = ad;
        },
        onAdFailedToLoad: (_) {
          _interstitialAd = null;
        },
      ),
    );
  }

  void _primeRewarded() {
    if (!adsEnabled || _rewardedUnitId.isEmpty || _rewardedAd != null) {
      return;
    }

    RewardedAd.load(
      adUnitId: _rewardedUnitId,
      request: const AdRequest(),
      rewardedAdLoadCallback: RewardedAdLoadCallback(
        onAdLoaded: (ad) {
          _rewardedAd = ad;
        },
        onAdFailedToLoad: (_) {
          _rewardedAd = null;
        },
      ),
    );
  }

  String _resolveBannerId(SystemConfig config) {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      return config.iosAdmobBannerId.isNotEmpty
          ? config.iosAdmobBannerId
          : _iosTestBannerId;
    }
    return config.admobBannerId.isNotEmpty
        ? config.admobBannerId
        : _androidTestBannerId;
  }

  String _resolveInterstitialId(SystemConfig config) {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      return config.iosAdmobInterstitialId.isNotEmpty
          ? config.iosAdmobInterstitialId
          : _iosTestInterstitialId;
    }
    return config.admobInterstitialId.isNotEmpty
        ? config.admobInterstitialId
        : _androidTestInterstitialId;
  }

  String _resolveRewardedId(SystemConfig config) {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      return config.iosAdmobRewardedVideoAds.isNotEmpty
          ? config.iosAdmobRewardedVideoAds
          : _iosTestRewardedId;
    }
    return config.admobRewardedVideoAds.isNotEmpty
        ? config.admobRewardedVideoAds
        : _androidTestRewardedId;
  }

  void _disposeLoadedAds() {
    _interstitialAd?.dispose();
    _interstitialAd = null;
    _rewardedAd?.dispose();
    _rewardedAd = null;
  }
}
