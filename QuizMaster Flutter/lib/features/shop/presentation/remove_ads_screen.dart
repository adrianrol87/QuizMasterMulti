import 'package:flutter/material.dart';

import '../../../core/ads/quiz_ad_service.dart';
import '../../../core/config/backend_config.dart';
import '../../../core/purchases/quiz_purchase_service.dart';

class RemoveAdsScreen extends StatefulWidget {
  const RemoveAdsScreen({
    super.key,
    required this.locale,
  });

  final Locale locale;

  static Future<bool> show(
    BuildContext context, {
    required Locale locale,
  }) async {
    return await Navigator.of(context).push<bool>(
          MaterialPageRoute<bool>(
            fullscreenDialog: true,
            builder: (_) => RemoveAdsScreen(locale: locale),
          ),
        ) ??
        false;
  }

  @override
  State<RemoveAdsScreen> createState() => _RemoveAdsScreenState();
}

class _RemoveAdsScreenState extends State<RemoveAdsScreen> {
  bool _loadingPrice = true;
  bool _processing = false;
  String? _price;

  bool get _isSpanish => widget.locale.languageCode == 'es';

  @override
  void initState() {
    super.initState();
    _loadPrice();
  }

  Future<void> _loadPrice() async {
    try {
      final packages = await QuizPurchaseService.instance
          .fetchAvailablePackagesByProductId();
      final package = packages[BackendConfig.revenueCatRemoveAdsProductId];
      if (!mounted) {
        return;
      }
      setState(() {
        _price = package?.storeProduct.priceString;
        _loadingPrice = false;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }
      setState(() {
        _loadingPrice = false;
      });
    }
  }

  Future<void> _purchase() async {
    if (_processing) {
      return;
    }
    setState(() {
      _processing = true;
    });

    try {
      final purchased = await QuizPurchaseService.instance.purchaseRemoveAds();
      if (!mounted) {
        return;
      }
      if (!purchased) {
        _showMessage(
          _isSpanish
              ? 'No se pudo confirmar la compra.'
              : 'The purchase could not be confirmed.',
        );
        return;
      }
      QuizAdService.instance.setAdsRemoved(true);
      Navigator.of(context).pop(true);
    } catch (_) {
      if (mounted) {
        _showMessage(
          _isSpanish
              ? 'La compra no se completó. Inténtalo nuevamente.'
              : 'The purchase was not completed. Please try again.',
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _processing = false;
        });
      }
    }
  }

  Future<void> _restore() async {
    if (_processing) {
      return;
    }
    setState(() {
      _processing = true;
    });

    try {
      final restored = await QuizPurchaseService.instance.restoreRemoveAds();
      if (!mounted) {
        return;
      }
      if (!restored) {
        _showMessage(
          _isSpanish
              ? 'No se encontró una compra anterior de Remove Ads.'
              : 'No previous Remove Ads purchase was found.',
        );
        return;
      }
      QuizAdService.instance.setAdsRemoved(true);
      Navigator.of(context).pop(true);
    } catch (_) {
      if (mounted) {
        _showMessage(
          _isSpanish
              ? 'No se pudieron restaurar las compras.'
              : 'Purchases could not be restored.',
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _processing = false;
        });
      }
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final price = _price ?? 'MX\$99.00';

    return Scaffold(
      backgroundColor: const Color(0xFF75979F),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.white,
        title: Text(_isSpanish ? 'Eliminar anuncios' : 'Remove ads'),
      ),
      body: SafeArea(
        top: false,
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(24, 16, 24, 28),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 520),
              child: Container(
                padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8F1D2),
                  borderRadius: BorderRadius.circular(28),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x33000000),
                      blurRadius: 24,
                      offset: Offset(0, 12),
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Image.asset(
                      'assets/images/sopa/NoAds.png',
                      width: 104,
                      height: 104,
                      fit: BoxFit.contain,
                    ),
                    const SizedBox(height: 14),
                    Text(
                      _isSpanish ? 'Juega sin anuncios' : 'Play without ads',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Color(0xFF172A46),
                        fontSize: 30,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      _isSpanish
                          ? 'Una sola compra. Sin suscripción.'
                          : 'One purchase. No subscription.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Color(0xFF5A5138),
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 24),
                    _BenefitRow(
                      icon: Icons.web_asset_off_rounded,
                      text: _isSpanish
                          ? 'Elimina los banners publicitarios.'
                          : 'Removes advertising banners.',
                    ),
                    const SizedBox(height: 14),
                    _BenefitRow(
                      icon: Icons.skip_next_rounded,
                      text: _isSpanish
                          ? 'Elimina los anuncios entre niveles y partidas.'
                          : 'Removes ads between levels and games.',
                    ),
                    const SizedBox(height: 14),
                    _BenefitRow(
                      icon: Icons.verified_rounded,
                      text: _isSpanish
                          ? 'Compra permanente que puedes restaurar.'
                          : 'A permanent purchase you can restore.',
                    ),
                    const SizedBox(height: 26),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed:
                            _processing || _loadingPrice ? null : _purchase,
                        style: FilledButton.styleFrom(
                          backgroundColor: const Color(0xFF91C900),
                          foregroundColor: const Color(0xFF172A46),
                          disabledBackgroundColor: const Color(0xFFB7C58E),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: _processing || _loadingPrice
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(
                                  strokeWidth: 3,
                                  color: Color(0xFF172A46),
                                ),
                              )
                            : Text(
                                _isSpanish
                                    ? 'Comprar por $price'
                                    : 'Buy for $price',
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w900,
                                ),
                              ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: _processing ? null : _restore,
                      child: Text(
                        _isSpanish ? 'Restaurar compras' : 'Restore purchases',
                        style: const TextStyle(
                          color: Color(0xFF172A46),
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _BenefitRow extends StatelessWidget {
  const _BenefitRow({
    required this.icon,
    required this.text,
  });

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 38,
          height: 38,
          decoration: const BoxDecoration(
            color: Color(0xFFE2EEB9),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: const Color(0xFF598400), size: 22),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(top: 7),
            child: Text(
              text,
              style: const TextStyle(
                color: Color(0xFF3C3A30),
                fontSize: 16,
                fontWeight: FontWeight.w700,
                height: 1.3,
              ),
            ),
          ),
        ),
      ],
    );
  }
}
