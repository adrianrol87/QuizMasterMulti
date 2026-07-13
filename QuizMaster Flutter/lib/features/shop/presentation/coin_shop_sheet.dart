import 'dart:math';

import 'package:flutter/material.dart';
import 'package:purchases_flutter/purchases_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/config/backend_config.dart';
import '../../../core/l10n/app_strings.dart';
import '../../../core/network/php_api_client.dart';
import '../../../core/purchases/quiz_purchase_service.dart';
import '../../../core/ads/quiz_ad_service.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/data/remote_auth_repository.dart';
import '../../auth/models/app_user.dart';

class CoinShopSheet extends StatefulWidget {
  const CoinShopSheet({
    super.key,
    required this.locale,
    required this.currentUser,
    required this.onUserUpdated,
  });

  final Locale locale;
  final AppUser? currentUser;
  final ValueChanged<AppUser> onUserUpdated;

  static Future<AppUser?> show(
    BuildContext context, {
    required Locale locale,
    required AppUser? currentUser,
    required ValueChanged<AppUser> onUserUpdated,
  }) {
    return Navigator.of(context).push<AppUser>(
      MaterialPageRoute<AppUser>(
        builder: (_) => CoinShopSheet(
          locale: locale,
          currentUser: currentUser,
          onUserUpdated: onUserUpdated,
        ),
      ),
    );
  }

  @override
  State<CoinShopSheet> createState() => _CoinShopSheetState();
}

class _CoinShopSheetState extends State<CoinShopSheet> {
  final PhpApiClient _apiClient = PhpApiClient();
  late final AuthRepository _authRepository =
      RemoteAuthRepository(apiClient: _apiClient);

  Map<String, Package> _packagesById = const <String, Package>{};
  bool _loadingPackages = true;
  String? _activeProductId;
  bool _freeRewardAvailable = true;

  bool get _isSpanish => widget.locale.languageCode == 'es';
  AppStrings get _strings => AppStrings(widget.locale);

  late final List<_CoinShopPack> _packs = <_CoinShopPack>[
    _CoinShopPack(
      productId: 'rewarded_free_100',
      assetPath: 'assets/images/shop/shop1.png',
      titleEs: 'FREE',
      titleEn: 'FREE',
      coinAmount: 100,
      fallbackPrice: 'FREE',
      isRewardedPack: true,
    ),
    _CoinShopPack(
      productId: BackendConfig.revenueCatCoins3000ProductId,
      assetPath: 'assets/images/shop/shop2.png',
      titleEs: 'BOLSILLO PRO',
      titleEn: 'ELITE POCKET',
      coinAmount: 3000,
      fallbackPrice: '\$2.70',
    ),
    _CoinShopPack(
      productId: BackendConfig.revenueCatCoins5000ProductId,
      assetPath: 'assets/images/shop/shop3.png',
      titleEs: 'MEJOR OFERTA',
      titleEn: 'BEST DEAL',
      coinAmount: 5000,
      fallbackPrice: '\$4.20',
      showDealBadge: true,
      dealLabelEs: '+40%',
      dealLabelEn: '+40%',
    ),
    _CoinShopPack(
      productId: BackendConfig.revenueCatCoins8500ProductId,
      assetPath: 'assets/images/shop/shop4.png',
      titleEs: 'ALCANCIA',
      titleEn: 'PIGGY BANK',
      coinAmount: 8500,
      fallbackPrice: '\$7.15',
    ),
    _CoinShopPack(
      productId: BackendConfig.revenueCatCoins10500ProductId,
      assetPath: 'assets/images/shop/shop5.png',
      titleEs: 'BOLSILLO ELITE',
      titleEn: 'ELITE POCKET',
      coinAmount: 10500,
      fallbackPrice: '\$11.55',
    ),
    _CoinShopPack(
      productId: BackendConfig.revenueCatCoins17000ProductId,
      assetPath: 'assets/images/shop/shop6.png',
      titleEs: 'BIG BOSS',
      titleEn: 'BIG BOSS',
      coinAmount: 17000,
      fallbackPrice: '\$14.99',
    ),
  ];

  @override
  void initState() {
    super.initState();
    _loadFreeRewardAvailability();
    _loadPackages();
  }

  Future<void> _loadFreeRewardAvailability() async {
    final userId = widget.currentUser?.id;
    if (userId == null || userId.isEmpty) {
      if (!mounted) {
        return;
      }
      setState(() {
        _freeRewardAvailable = true;
      });
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    final key = 'coin_shop_free_reward_last_claim_$userId';
    final today = _todayKey();
    final lastClaim = prefs.getString(key);

    if (!mounted) {
      return;
    }

    setState(() {
      _freeRewardAvailable = lastClaim != today;
    });
  }

  String _todayKey() {
    final now = DateTime.now();
    return '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
  }

  Future<void> _loadPackages() async {
    try {
      final userId = widget.currentUser?.id ?? '';
      if (QuizPurchaseService.instance.isConfigured && userId.isNotEmpty) {
        await QuizPurchaseService.instance.configureForUser(userId);
        final packages =
            await QuizPurchaseService.instance.fetchAvailablePackagesByProductId();
        if (!mounted) {
          return;
        }
        setState(() {
          _packagesById = packages;
          _loadingPackages = false;
        });
        return;
      }
    } catch (_) {}

    if (!mounted) {
      return;
    }

    setState(() {
      _packagesById = const <String, Package>{};
      _loadingPackages = false;
    });
  }

  Future<void> _buyPack(_CoinShopPack pack) async {
    final user = widget.currentUser;
    if (user == null || user.id.isEmpty) {
      _showSnack(
        _isSpanish
            ? 'Primero inicia sesion para comprar monedas.'
            : 'Sign in first to buy coins.',
      );
      return;
    }

    if (_activeProductId != null) {
      return;
    }

    if (pack.isRewardedPack && !_freeRewardAvailable) {
      _showSnack(
        _isSpanish
            ? 'El pack gratis solo se puede reclamar una vez al dia.'
            : 'The free pack can only be claimed once per day.',
      );
      return;
    }

    setState(() {
      _activeProductId = pack.productId;
    });

    try {
      if (pack.isRewardedPack) {
        final rewarded = await QuizAdService.instance.showRewardedToMultiplyCoins();
        if (!rewarded) {
          if (mounted) {
            _showSnack(
              _isSpanish
                  ? 'No se pudo mostrar el video en este momento.'
                  : 'The video could not be shown right now.',
            );
          }
          return;
        }
      } else {
        await QuizPurchaseService.instance.configureForUser(user.id);
        await QuizPurchaseService.instance.purchaseProductById(pack.productId);
      }

      await _apiClient.post({
        'set_user_coin_score': '1',
        'user_id': user.id,
        'coins': '${pack.coinAmount}',
      });

      if (pack.isRewardedPack) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(
          'coin_shop_free_reward_last_claim_${user.id}',
          _todayKey(),
        );
        if (mounted) {
          setState(() {
            _freeRewardAvailable = false;
          });
        }
      }

      final updatedUser = await _authRepository.refreshUser(user.id);
      widget.onUserUpdated(updatedUser);

      if (!mounted) {
        return;
      }

      Navigator.of(context).pop(updatedUser);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isSpanish
                ? 'Se agregaron ${pack.coinAmount} monedas.'
                : '${pack.coinAmount} coins were added.',
          ),
        ),
      );
    } catch (error) {
      if (!mounted) {
        return;
      }
      _showSnack(
        _isSpanish
            ? 'No se pudo completar la compra en este momento.'
            : 'The purchase could not be completed right now.',
      );
      debugPrint('Coin pack purchase failed: $error');
    } finally {
      if (mounted) {
        setState(() {
          _activeProductId = null;
        });
      }
    }
  }

  void _showSnack(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF6F8F98),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 18, 18, 14),
              child: Row(
                children: [
                  Expanded(
                    child: Align(
                      alignment: Alignment.centerLeft,
                      child: _ShopCoinsPill(
                        coins: '${widget.currentUser?.coins ?? 0}',
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(
                      Icons.close_rounded,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
            if (_loadingPackages)
              const Padding(
                padding: EdgeInsets.only(bottom: 12),
                child: LinearProgressIndicator(minHeight: 2),
              ),
            Expanded(
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.fromLTRB(18, 24, 18, 24),
                child: SizedBox(
                  width: 490,
                  child: GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _packs.length,
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 3,
                      mainAxisSpacing: 12,
                      crossAxisSpacing: 12,
                      childAspectRatio: 0.72,
                    ),
                    itemBuilder: (context, index) {
                      final pack = _packs[index];
                      final package = _packagesById[pack.productId];
                      final isBusy = _activeProductId == pack.productId;
                      return _CoinShopCard(
                        pack: pack,
                        locale: widget.locale,
                        priceLabel:
                            package?.storeProduct.priceString ??
                            pack.fallbackPrice,
                        isBusy: isBusy,
                        freeRewardAvailable: _freeRewardAvailable,
                        onPressed: () => _buyPack(pack),
                      );
                    },
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ShopCoinsPill extends StatelessWidget {
  const _ShopCoinsPill({required this.coins});

  final String coins;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 188,
      height: 58,
      padding: const EdgeInsets.only(left: 18, right: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        boxShadow: const [
          BoxShadow(
            color: Color(0x22000000),
            blurRadius: 14,
            offset: Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              coins,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: Color(0xFF1E2E45),
                fontSize: 28,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          const SizedBox(width: 8),
          Image.asset(
            'assets/images/sopa/monedas.png',
            width: 50,
            height: 50,
            fit: BoxFit.contain,
          ),
        ],
      ),
    );
  }
}

class _CoinShopCard extends StatelessWidget {
  const _CoinShopCard({
    required this.pack,
    required this.locale,
    required this.priceLabel,
    required this.isBusy,
    required this.freeRewardAvailable,
    required this.onPressed,
  });

  final _CoinShopPack pack;
  final Locale locale;
  final String priceLabel;
  final bool isBusy;
  final bool freeRewardAvailable;
  final VoidCallback onPressed;

  bool get _isSpanish => locale.languageCode == 'es';

  @override
  Widget build(BuildContext context) {
    final title = _isSpanish ? pack.titleEs : pack.titleEn;
    final amountLabel =
        '${pack.coinAmount} ${_isSpanish ? 'Monedas' : 'Coins'}';
    final isDisabled = pack.isRewardedPack && !freeRewardAvailable;
    final buttonLabel = pack.isRewardedPack
        ? (isDisabled
            ? (_isSpanish ? 'Canjeado hoy' : 'Claimed today')
            : (_isSpanish ? 'Ver video' : 'Watch ad'))
        : (_isSpanish ? 'Comprar' : 'Buy');

    return Stack(
      clipBehavior: Clip.none,
      children: [
        Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(24),
            boxShadow: const [
              BoxShadow(
                color: Color(0x22000000),
                blurRadius: 18,
                offset: Offset(0, 10),
              ),
            ],
          ),
          child: Stack(
            fit: StackFit.expand,
            children: [
              Image.asset(
                'assets/images/shop/backgroundshop.png',
                fit: BoxFit.fill,
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 12, 12, 10),
                child: Column(
                  children: [
                    Text(
                      title,
                      textAlign: TextAlign.center,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.3,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Expanded(
                      child: Center(
                        child: Image.asset(
                          pack.assetPath,
                          fit: BoxFit.contain,
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 5,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF4D3),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        pack.isRewardedPack
                            ? amountLabel
                            : amountLabel,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Color(0xFF3B2C1A),
                          fontSize: 11,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.2,
                        ),
                      ),
                    ),
                    const SizedBox(height: 5),
                    GestureDetector(
                      onTap: (isBusy || isDisabled) ? null : onPressed,
                      child: SizedBox(
                        width: double.infinity,
                        height: 44,
                        child: Stack(
                          alignment: Alignment.center,
                          children: [
                            Image.asset(
                              'assets/images/shop/button shop.png',
                              width: double.infinity,
                              height: 44,
                              fit: BoxFit.fill,
                              color: isDisabled
                                  ? const Color(0xCC8F8F8F)
                                  : null,
                              colorBlendMode: isDisabled
                                  ? BlendMode.modulate
                                  : null,
                            ),
                            Text(
                              pack.isRewardedPack
                                  ? buttonLabel
                                  : '$buttonLabel  $priceLabel',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 14,
                                fontWeight: FontWeight.w900,
                                letterSpacing: 0.2,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        if (pack.showDealBadge)
          Positioned(
            top: 28,
            right: 4,
            child: SizedBox(
              width: 60,
              height: 60,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Image.asset(
                    'assets/images/shop/dealshop.png',
                    width: 60,
                    height: 60,
                    fit: BoxFit.contain,
                  ),
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      _isSpanish ? pack.dealLabelEs : pack.dealLabelEn,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.w900,
                        height: 1.05,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

class _CoinShopPack {
  const _CoinShopPack({
    required this.productId,
    required this.assetPath,
    required this.titleEs,
    required this.titleEn,
    required this.coinAmount,
    required this.fallbackPrice,
    this.isRewardedPack = false,
    this.showDealBadge = false,
    this.dealLabelEs = '',
    this.dealLabelEn = '',
  });

  final String productId;
  final String assetPath;
  final String titleEs;
  final String titleEn;
  final int coinAmount;
  final String fallbackPrice;
  final bool isRewardedPack;
  final bool showDealBadge;
  final String dealLabelEs;
  final String dealLabelEn;
}
