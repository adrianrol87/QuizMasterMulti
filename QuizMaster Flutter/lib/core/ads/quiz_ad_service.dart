import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';

import '../../features/config/models/system_config.dart';

class QuizAdService {
  QuizAdService._();

  static final QuizAdService instance = QuizAdService._();

  static const _androidTestAppId = 'ca-app-pub-3940256099942544~3347511713';
  static const _iosAppId = 'ca-app-pub-1404008250068138~9277252035';
  static const _androidTestBannerId = 'ca-app-pub-3940256099942544/6300978111';
  static const _iosTestBannerId = 'ca-app-pub-3940256099942544/2934735716';
  static const _iosBannerId = 'ca-app-pub-1404008250068138/6832041774';
  static const _androidTestInterstitialId =
      'ca-app-pub-3940256099942544/1033173712';
  static const _iosTestInterstitialId =
      'ca-app-pub-3940256099942544/4411468910';
  static const _iosInterstitialId = 'ca-app-pub-1404008250068138/9983829049';
  static const _androidTestRewardedId =
      'ca-app-pub-3940256099942544/5224354917';
  static const _iosTestRewardedId = 'ca-app-pub-3940256099942544/1712485313';
  static const _iosRewardedId = 'ca-app-pub-1404008250068138/7677798128';

  bool _mobileAdsInitialized = false;
  bool _adsEnabled = false;
  String _bannerUnitId = '';
  String _interstitialUnitId = '';
  String _rewardedUnitId = '';
  InterstitialAd? _interstitialAd;
  RewardedAd? _rewardedAd;
  Completer<RewardedAd?>? _rewardedLoadCompleter;
  Future<void>? _activeConfiguration;
  int _completedLevelCounter = 0;
  int _gameReturnCounter = 0;
  bool _adsRemoved = false;

  static bool get isSupportedPlatform =>
      !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS);

  static String get testAppId => defaultTargetPlatform == TargetPlatform.iOS
      ? _iosAppId
      : _androidTestAppId;

  bool get adsEnabled => !_adsRemoved && _adsEnabled && isSupportedPlatform;

  bool get adsRemoved => _adsRemoved;

  bool get rewardMultiplierAvailable =>
      adsEnabled && _rewardedUnitId.isNotEmpty;

  Future<void> configure(SystemConfig config) async {
    final activeConfiguration = _activeConfiguration;
    if (activeConfiguration != null) {
      await activeConfiguration;
      return;
    }

    final configuration = _configure(config);
    _activeConfiguration = configuration;
    try {
      await configuration;
    } finally {
      _activeConfiguration = null;
    }
  }

  Future<void> _configure(SystemConfig config) async {
    if (!isSupportedPlatform) {
      _adsEnabled = false;
      return;
    }

    final isAndroid = defaultTargetPlatform == TargetPlatform.android;
    final inAppAdsMode =
        isAndroid ? config.inAppAdsMode : config.iosInAppAdsMode;
    final adsType = isAndroid ? config.adsType : config.iosAdsType;

    _adsEnabled = inAppAdsMode && adsType == 1;
    _bannerUnitId = _resolveBannerId(config);
    _interstitialUnitId = _resolveInterstitialId(config);
    _rewardedUnitId = _resolveRewardedId(config);

    debugPrint(
      'AdMob configuration: enabled=$_adsEnabled, platform='
      '${defaultTargetPlatform.name}, banner=${_bannerUnitId.isNotEmpty}, '
      'interstitial=${_interstitialUnitId.isNotEmpty}, '
      'rewarded=${_rewardedUnitId.isNotEmpty}.',
    );

    if (!_adsEnabled) {
      debugPrint(
        'AdMob disabled: inAppAdsMode=$inAppAdsMode, adsType=$adsType.',
      );
      _disposeLoadedAds();
      return;
    }

    if (!_mobileAdsInitialized) {
      if (kDebugMode) {
        await MobileAds.instance.updateRequestConfiguration(
          RequestConfiguration(
            testDeviceIds: ['de93e11bb193bdfbd98f91670e6a00b5'],
          ),
        );
      }
      await MobileAds.instance.initialize();
      _mobileAdsInitialized = true;
    }

    _primeInterstitial();
    unawaited(_loadRewarded());
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
        onAdLoaded: (_) {
          debugPrint('AdMob banner loaded.');
          onLoaded?.call();
        },
        onAdFailedToLoad: (ad, error) {
          debugPrint('AdMob banner failed to load: $error');
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

  Future<void> maybeShowInterstitialAfterGameReturn() async {
    if (!adsEnabled) {
      return;
    }

    _gameReturnCounter += 1;
    if (_gameReturnCounter < 3) {
      return;
    }

    final ad = _interstitialAd;
    if (ad == null) {
      _primeInterstitial();
      return;
    }

    _gameReturnCounter = 0;
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

    var ad = _rewardedAd;
    if (ad == null) {
      ad = await _loadRewarded();
      if (ad == null) {
        return false;
      }
    }

    _rewardedAd = null;
    final completer = Completer<bool>();
    var earnedReward = false;

    ad.fullScreenContentCallback = FullScreenContentCallback(
      onAdDismissedFullScreenContent: (ad) {
        ad.dispose();
        unawaited(_loadRewarded());
        if (!completer.isCompleted) {
          completer.complete(earnedReward);
        }
      },
      onAdFailedToShowFullScreenContent: (ad, error) {
        ad.dispose();
        unawaited(_loadRewarded());
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
          debugPrint('AdMob interstitial loaded.');
          _interstitialAd = ad;
        },
        onAdFailedToLoad: (error) {
          _interstitialAd = null;
          debugPrint('AdMob interstitial failed to load: $error');
        },
      ),
    );
  }

  Future<RewardedAd?> _loadRewarded() async {
    if (!adsEnabled || _rewardedUnitId.isEmpty) {
      return null;
    }
    if (_rewardedAd != null) {
      return _rewardedAd;
    }
    final activeLoad = _rewardedLoadCompleter;
    if (activeLoad != null) {
      return activeLoad.future;
    }

    final completer = Completer<RewardedAd?>();
    _rewardedLoadCompleter = completer;
    RewardedAd.load(
      adUnitId: _rewardedUnitId,
      request: const AdRequest(),
      rewardedAdLoadCallback: RewardedAdLoadCallback(
        onAdLoaded: (ad) {
          if (adsEnabled) {
            _rewardedAd = ad;
            completer.complete(ad);
          } else {
            ad.dispose();
            completer.complete(null);
          }
          _rewardedLoadCompleter = null;
        },
        onAdFailedToLoad: (error) {
          _rewardedAd = null;
          debugPrint('AdMob rewarded failed to load: $error');
          completer.complete(null);
          _rewardedLoadCompleter = null;
        },
      ),
    );
    return completer.future;
  }

  String _resolveBannerId(SystemConfig config) {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      if (kDebugMode) {
        return _iosTestBannerId;
      }
      return config.iosAdmobBannerId.isNotEmpty
          ? config.iosAdmobBannerId
          : _iosBannerId;
    }
    return config.admobBannerId.isNotEmpty
        ? config.admobBannerId
        : _androidTestBannerId;
  }

  String _resolveInterstitialId(SystemConfig config) {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      if (kDebugMode) {
        return _iosTestInterstitialId;
      }
      return config.iosAdmobInterstitialId.isNotEmpty
          ? config.iosAdmobInterstitialId
          : _iosInterstitialId;
    }
    return config.admobInterstitialId.isNotEmpty
        ? config.admobInterstitialId
        : _androidTestInterstitialId;
  }

  String _resolveRewardedId(SystemConfig config) {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      if (kDebugMode) {
        return _iosTestRewardedId;
      }
      return config.iosAdmobRewardedVideoAds.isNotEmpty
          ? config.iosAdmobRewardedVideoAds
          : _iosRewardedId;
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
