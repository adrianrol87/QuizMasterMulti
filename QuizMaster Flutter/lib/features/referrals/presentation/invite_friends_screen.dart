import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/network/php_api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/models/app_user.dart';
import '../../config/models/system_config.dart';
import '../data/referral_repository.dart';

class InviteFriendsScreen extends StatefulWidget {
  const InviteFriendsScreen({
    super.key,
    required this.locale,
    required this.currentUser,
    required this.config,
    required this.repository,
  });

  final Locale locale;
  final AppUser currentUser;
  final SystemConfig config;
  final ReferralRepository repository;

  @override
  State<InviteFriendsScreen> createState() => _InviteFriendsScreenState();
}

class _InviteFriendsScreenState extends State<InviteFriendsScreen> {
  final TextEditingController _codeController = TextEditingController();
  late Future<ReferralInfo> _future;
  bool _redeeming = false;

  bool get _isSpanish => widget.locale.languageCode == 'es';

  @override
  void initState() {
    super.initState();
    _future = widget.repository.fetchInfo(widget.currentUser.id);
  }

  @override
  void dispose() {
    _codeController.dispose();
    super.dispose();
  }

  String get _storeLink {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      return widget.config.iosAppLink.trim();
    }
    return widget.config.appLink.trim();
  }

  String _shareMessage(ReferralInfo info) {
    final configured = widget.config.shareAppText.trim();
    final accurateRewardText = _isSpanish
        ? 'Yo recibiré ${info.referrerReward} monedas y tú recibirás ${info.newUserReward} monedas.'
        : 'I will receive ${info.referrerReward} coins and you will receive ${info.newUserReward} coins.';

    var message = configured.isEmpty
        ? (_isSpanish
            ? '¡Descarga QuizMaster y usa mi código {code}! {rewards} {link}'
            : 'Download QuizMaster and use my code {code}! {rewards} {link}')
        : configured;

    message = message.replaceAll('{code}', info.referralCode);
    message = message.replaceAll('{link}', _storeLink);
    message = message.replaceAll('{referrer_reward}', '${info.referrerReward}');
    message = message.replaceAll('{new_user_reward}', '${info.newUserReward}');
    message = message.replaceAll('{rewards}', accurateRewardText);

    final bothRewardPattern = RegExp(
      r'Ambos recibiremos\s*\{reward\}\s*monedas\.?',
      caseSensitive: false,
    );
    if (bothRewardPattern.hasMatch(message) &&
        info.referrerReward != info.newUserReward) {
      message = message.replaceAll(bothRewardPattern, accurateRewardText);
    } else {
      message = message.replaceAll('{reward}', '${info.referrerReward}');
    }

    return message.replaceAll(RegExp(r'\s+'), ' ').trim();
  }

  Future<void> _copy(String text, String confirmation) async {
    await Clipboard.setData(ClipboardData(text: text));
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(confirmation)),
    );
  }

  Future<void> _share(ReferralInfo info) async {
    final box = context.findRenderObject() as RenderBox?;
    await Share.share(
      _shareMessage(info),
      subject: _isSpanish ? 'Invitación a QuizMaster' : 'QuizMaster invitation',
      sharePositionOrigin:
          box == null ? null : box.localToGlobal(Offset.zero) & box.size,
    );
  }

  Future<void> _redeem() async {
    final code = _codeController.text.trim().toUpperCase();
    if (code.isEmpty || _redeeming) return;

    setState(() => _redeeming = true);
    try {
      final info = await widget.repository.redeemCode(
        userId: widget.currentUser.id,
        referralCode: code,
      );
      if (!mounted) return;
      setState(() {
        _future = Future.value(info);
        _redeeming = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isSpanish
                ? 'Código aplicado. Tu amigo recibió ${info.referrerReward} monedas.'
                : 'Code applied. Your friend received ${info.referrerReward} coins.',
          ),
        ),
      );
    } on PhpApiException catch (error) {
      if (!mounted) return;
      setState(() => _redeeming = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_localizedError(error.message))),
      );
    } catch (_) {
      if (!mounted) return;
      setState(() => _redeeming = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isSpanish
                ? 'No se pudo aplicar el código. Intenta de nuevo.'
                : 'Could not apply the code. Please try again.',
          ),
        ),
      );
    }
  }

  String _localizedError(String message) {
    if (!_isSpanish) return message;
    if (message.contains('already used')) {
      return 'Esta cuenta ya utilizó un código de invitación.';
    }
    if (message.contains('own referral')) {
      return 'No puedes utilizar tu propio código.';
    }
    if (message.contains('Invalid referral')) {
      return 'El código de invitación no es válido.';
    }
    return 'No se pudo aplicar el código. Intenta de nuevo.';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      appBar: AppBar(
        title: Text(_isSpanish ? 'Invitar amigos' : 'Invite friends'),
        backgroundColor: Colors.transparent,
        foregroundColor: AppTheme.textColor(context),
        elevation: 0,
      ),
      body: FutureBuilder<ReferralInfo>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return _ErrorView(
              label: _isSpanish
                  ? 'No se pudo cargar. Intenta de nuevo.'
                  : 'Could not load. Please try again.',
              retryLabel: _isSpanish ? 'Reintentar' : 'Retry',
              onRetry: () {
                setState(() {
                  _future = widget.repository.fetchInfo(widget.currentUser.id);
                });
              },
            );
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final info = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Container(
                padding: const EdgeInsets.all(22),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF2B6FB6), Color(0xFF55B8FF)],
                  ),
                  borderRadius: BorderRadius.circular(28),
                ),
                child: Column(
                  children: [
                    const Icon(Icons.group_add_rounded,
                        color: Colors.white, size: 52),
                    const SizedBox(height: 12),
                    Text(
                      _isSpanish
                          ? 'Comparte QuizMaster y gana monedas'
                          : 'Share QuizMaster and earn coins',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      _isSpanish
                          ? 'Tú recibes ${info.referrerReward} monedas y tu amigo nuevo recibe ${info.newUserReward}.'
                          : 'You receive ${info.referrerReward} coins and your new friend receives ${info.newUserReward}.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),
              _SectionCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      _isSpanish ? 'Tu código' : 'Your code',
                      style: const TextStyle(
                        color: AppTheme.ink,
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 12),
                    SelectableText(
                      info.referralCode,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Color(0xFF2B6FB6),
                        fontSize: 28,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 2,
                      ),
                    ),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () => _copy(
                              info.referralCode,
                              _isSpanish ? 'Código copiado.' : 'Code copied.',
                            ),
                            icon: const Icon(Icons.copy_rounded),
                            label: Text(_isSpanish ? 'Copiar' : 'Copy'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: FilledButton.icon(
                            onPressed: () => _share(info),
                            icon: const Icon(Icons.share_rounded),
                            label: Text(_isSpanish ? 'Compartir' : 'Share'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),
              _SectionCard(
                child: info.canRedeem
                    ? Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            _isSpanish
                                ? '¿Te invitó un amigo?'
                                : 'Were you invited by a friend?',
                            style: const TextStyle(
                              color: AppTheme.ink,
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _codeController,
                            textCapitalization: TextCapitalization.characters,
                            decoration: InputDecoration(
                              labelText: _isSpanish
                                  ? 'Código de invitación'
                                  : 'Invitation code',
                              border: const OutlineInputBorder(),
                            ),
                          ),
                          const SizedBox(height: 12),
                          FilledButton(
                            onPressed: _redeeming ? null : _redeem,
                            child: Text(
                              _redeeming
                                  ? (_isSpanish
                                      ? 'Aplicando...'
                                      : 'Applying...')
                                  : (_isSpanish
                                      ? 'Aplicar código'
                                      : 'Apply code'),
                            ),
                          ),
                        ],
                      )
                    : Row(
                        children: [
                          const Icon(Icons.check_circle_rounded,
                              color: Color(0xFF2FA56B)),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _isSpanish
                                  ? 'Ya utilizaste el código ${info.usedCode}.'
                                  : 'You already used code ${info.usedCode}.',
                              style: const TextStyle(
                                color: AppTheme.ink,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ],
                      ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        boxShadow: const [
          BoxShadow(
            color: Color(0x120E2741),
            blurRadius: 18,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({
    required this.label,
    required this.retryLabel,
    required this.onRetry,
  });

  final String label;
  final String retryLabel;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_rounded, size: 42),
            const SizedBox(height: 12),
            Text(label, textAlign: TextAlign.center),
            const SizedBox(height: 14),
            FilledButton(onPressed: onRetry, child: Text(retryLabel)),
          ],
        ),
      ),
    );
  }
}
