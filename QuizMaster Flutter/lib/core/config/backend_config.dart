import 'package:flutter/foundation.dart';

class BackendConfig {
  const BackendConfig._();

  static const accessKey = '6808';
  static const jwtSecretKey = 'quizmaster2026';
  static const appVersion = '0.1.0';

  static const baseUrl = 'https://adrianrol87.com.mx/quizmaster';
  static const apiPath = '/api-v2.php';
  static const bypassLoginForTesting = false;
  static const useFakeTop100Leaderboard = false;
  static const revenueCatGoogleApiKey = 'goog_KVNTfHuqHTgxIDxImIQaRPQwxUZ';
  static const revenueCatAppleApiKey = 'appl_pAQYgVyyjOYlfzpkJhPFxFEEWeH';
  static const revenueCatRemoveAdsEntitlementId = 'remove_ads';
  static const revenueCatRemoveAdsProductId = 'remove_ads';
  static const revenueCatAppleCoinsTier1ProductId = 'coins_tier1';
  static const revenueCatAppleCoinsTier2ProductId = 'coins_tier2';
  static const revenueCatAppleCoinsTier3ProductId = 'coins_tier3';
  static const revenueCatAppleCoinsTier4ProductId = 'coins_tier4';
  static const revenueCatAppleCoinsTier5ProductId = 'coins_tier5';
  static const revenueCatGoogleCoinsTier1ProductId = 'coins_3000';
  static const revenueCatGoogleCoinsTier2ProductId = 'coins_5000';
  static const revenueCatGoogleCoinsTier3ProductId = 'coins_8500';
  static const revenueCatGoogleCoinsTier4ProductId = 'coins_10500';
  static const revenueCatGoogleCoinsTier5ProductId = 'coins_17000';

  static bool get isConfigured => baseUrl.trim().isNotEmpty;

  static bool get _usesAppleProducts =>
      !kIsWeb && defaultTargetPlatform == TargetPlatform.iOS;

  static String get revenueCatCoinsTier1ProductId => _usesAppleProducts
      ? revenueCatAppleCoinsTier1ProductId
      : revenueCatGoogleCoinsTier1ProductId;
  static String get revenueCatCoinsTier2ProductId => _usesAppleProducts
      ? revenueCatAppleCoinsTier2ProductId
      : revenueCatGoogleCoinsTier2ProductId;
  static String get revenueCatCoinsTier3ProductId => _usesAppleProducts
      ? revenueCatAppleCoinsTier3ProductId
      : revenueCatGoogleCoinsTier3ProductId;
  static String get revenueCatCoinsTier4ProductId => _usesAppleProducts
      ? revenueCatAppleCoinsTier4ProductId
      : revenueCatGoogleCoinsTier4ProductId;
  static String get revenueCatCoinsTier5ProductId => _usesAppleProducts
      ? revenueCatAppleCoinsTier5ProductId
      : revenueCatGoogleCoinsTier5ProductId;

  static String get apiUrl {
    final normalizedBase = baseUrl.endsWith('/')
        ? baseUrl.substring(0, baseUrl.length - 1)
        : baseUrl;
    final normalizedPath = apiPath.startsWith('/') ? apiPath : '/$apiPath';
    return '$normalizedBase$normalizedPath';
  }
}
