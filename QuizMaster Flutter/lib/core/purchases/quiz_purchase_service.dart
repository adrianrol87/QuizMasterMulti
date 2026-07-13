import 'package:flutter/foundation.dart';
import 'package:purchases_flutter/purchases_flutter.dart';

import '../config/backend_config.dart';

class QuizPurchaseService {
  QuizPurchaseService._();

  static final QuizPurchaseService instance = QuizPurchaseService._();

  bool _configured = false;
  String? _activeAppUserId;

  static bool get isSupportedPlatform =>
      !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS);

  bool get isConfigured =>
      _resolveApiKey().trim().isNotEmpty && isSupportedPlatform;

  String _resolveApiKey() {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      return BackendConfig.revenueCatAppleApiKey;
    }
    return BackendConfig.revenueCatGoogleApiKey;
  }

  Future<bool> configureForUser(String appUserId) async {
    if (!isConfigured || appUserId.trim().isEmpty) {
      return false;
    }

    final apiKey = _resolveApiKey().trim();
    if (!_configured) {
      final configuration = PurchasesConfiguration(apiKey)..appUserID = appUserId;
      await Purchases.configure(configuration);
      _configured = true;
      _activeAppUserId = appUserId;
    } else if (_activeAppUserId != appUserId) {
      await Purchases.logIn(appUserId);
      _activeAppUserId = appUserId;
    }

    final customerInfo = await Purchases.getCustomerInfo();
    return hasRemoveAdsEntitlement(customerInfo);
  }

  Future<bool> purchaseRemoveAds() async {
    final customerInfo = await purchaseProductById(
      BackendConfig.revenueCatRemoveAdsProductId,
    );
    return hasRemoveAdsEntitlement(customerInfo);
  }

  Future<Map<String, Package>> fetchAvailablePackagesByProductId() async {
    if (!isConfigured) {
      return const <String, Package>{};
    }

    final offerings = await Purchases.getOfferings();
    final current = offerings.current;
    if (current == null) {
      return const <String, Package>{};
    }

    final packagesById = <String, Package>{};
    for (final package in current.availablePackages) {
      packagesById[package.storeProduct.identifier.trim()] = package;
    }
    return packagesById;
  }

  Future<CustomerInfo> purchaseProductById(String productId) async {
    if (!isConfigured) {
      throw StateError('RevenueCat is not configured.');
    }

    final offerings = await Purchases.getOfferings();
    final current = offerings.current;
    if (current == null) {
      throw StateError('No RevenueCat offering is available.');
    }

    Package? package;
    for (final item in current.availablePackages) {
      final currentProductId = item.storeProduct.identifier.trim();
      if (currentProductId == productId.trim()) {
        package = item;
        break;
      }
    }

    if (package == null) {
      throw StateError('Package was not found for product id: $productId');
    }

    return Purchases.purchasePackage(package);
  }

  Future<bool> restoreRemoveAds() async {
    if (!isConfigured) {
      return false;
    }

    final customerInfo = await Purchases.restorePurchases();
    return hasRemoveAdsEntitlement(customerInfo);
  }

  Future<void> logOut() async {
    if (!_configured) {
      _activeAppUserId = null;
      return;
    }

    try {
      await Purchases.logOut();
    } catch (_) {
      // Ignore anonymous logout issues and keep local flow moving.
    } finally {
      _activeAppUserId = null;
    }
  }

  bool hasRemoveAdsEntitlement(CustomerInfo customerInfo) {
    final entitlement = customerInfo.entitlements.active[
        BackendConfig.revenueCatRemoveAdsEntitlementId];
    return entitlement != null;
  }
}
