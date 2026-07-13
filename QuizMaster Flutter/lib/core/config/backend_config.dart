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
  static const revenueCatAppleApiKey = '';
  static const revenueCatRemoveAdsEntitlementId = 'remove_ads';
  static const revenueCatRemoveAdsProductId = 'remove_ads';
  static const revenueCatCoins1000ProductId = 'coins_1000';
  static const revenueCatCoins3000ProductId = 'coins_3000';
  static const revenueCatCoins5000ProductId = 'coins_5000';
  static const revenueCatCoins8500ProductId = 'coins_8500';
  static const revenueCatCoins10500ProductId = 'coins_10500';
  static const revenueCatCoins17000ProductId = 'coins_17000';

  static bool get isConfigured => baseUrl.trim().isNotEmpty;

  static String get apiUrl {
    final normalizedBase = baseUrl.endsWith('/')
        ? baseUrl.substring(0, baseUrl.length - 1)
        : baseUrl;
    final normalizedPath = apiPath.startsWith('/') ? apiPath : '/$apiPath';
    return '$normalizedBase$normalizedPath';
  }
}
