import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/config/backend_config.dart';
import '../../../core/l10n/app_strings.dart';
import '../../../core/network/php_api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../../config/data/app_content_repository.dart';
import '../../config/data/mock_app_content_repository.dart';
import '../../config/data/mock_system_config_repository.dart';
import '../../auth/data/mock_auth_repository.dart';
import '../../auth/data/auth_repository.dart';
import '../../config/models/system_config.dart';
import '../../config/presentation/app_document_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({
    super.key,
    required this.locale,
    required this.onLocaleChanged,
    required this.onLogin,
    required this.onSignUp,
    required this.onGoogleLogin,
    required this.onBypassLogin,
    this.authRepository = const MockAuthRepository(),
    this.systemConfigRepository = const MockSystemConfigRepository(),
    this.appContentRepository = const MockAppContentRepository(),
  });

  static const routeName = '/login';

  final Locale locale;
  final ValueChanged<Locale> onLocaleChanged;
  final Future<void> Function({
    required String email,
    required String password,
  }) onLogin;
  final Future<void> Function({
    required String name,
    required String email,
    required String password,
    required String mobile,
  }) onSignUp;
  final Future<void> Function() onGoogleLogin;
  final Future<void> Function() onBypassLogin;
  final AuthRepository authRepository;
  final SystemConfigRepository systemConfigRepository;
  final AppContentRepository appContentRepository;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _loginFormKey = GlobalKey<FormState>();
  final _signupFormKey = GlobalKey<FormState>();
  late final TextEditingController _loginEmailController;
  late final TextEditingController _loginPasswordController;
  late final TextEditingController _signupNameController;
  late final TextEditingController _signupEmailController;
  late final TextEditingController _signupPhoneController;
  late final TextEditingController _signupPasswordController;
  late final TextEditingController _signupConfirmPasswordController;
  bool _isLoginMode = true;
  bool _isSubmitting = false;
  bool _loginPasswordHidden = true;
  bool _signupPasswordHidden = true;
  bool _signupConfirmPasswordHidden = true;
  String _selectedDialCode = '+52';
  late Future<SystemConfig> _configFuture;

  static const _dialCodes = ['+52', '+1', '+34', '+54', '+57'];

  @override
  void initState() {
    super.initState();
    _loginEmailController = TextEditingController();
    _loginPasswordController = TextEditingController();
    _signupNameController = TextEditingController();
    _signupEmailController = TextEditingController();
    _signupPhoneController = TextEditingController();
    _signupPasswordController = TextEditingController();
    _signupConfirmPasswordController = TextEditingController();
    _configFuture = widget.systemConfigRepository.fetchSystemConfig();
  }

  @override
  void dispose() {
    _loginEmailController.dispose();
    _loginPasswordController.dispose();
    _signupNameController.dispose();
    _signupEmailController.dispose();
    _signupPhoneController.dispose();
    _signupPasswordController.dispose();
    _signupConfirmPasswordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(widget.locale);
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      body: FutureBuilder<SystemConfig>(
        future: _configFuture,
        builder: (context, snapshot) {
          final config = snapshot.data;
          if (snapshot.connectionState != ConnectionState.done &&
              config == null) {
            return const SafeArea(
              child: Center(child: CircularProgressIndicator()),
            );
          }

          return SafeArea(
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 460),
                child: Column(
                  children: [
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.fromLTRB(24, 22, 24, 26),
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Color(0xFF2B6FB6),
                            Color(0xFF3E8FDD),
                            Color(0xFF55B8FF),
                          ],
                        ),
                        borderRadius: BorderRadius.vertical(
                          bottom: Radius.circular(30),
                        ),
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              const Spacer(),
                              IconButton(
                                onPressed: () =>
                                    _showLanguageSheet(context, strings, theme),
                                icon: const Icon(
                                  Icons.translate_rounded,
                                  color: Colors.white,
                                ),
                                style: IconButton.styleFrom(
                                  backgroundColor:
                                      Colors.white.withValues(alpha: 0.14),
                                ),
                                tooltip: strings.text('selectLanguage'),
                              ),
                            ],
                          ),
                          Container(
                            width: 82,
                            height: 82,
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.18),
                              borderRadius: BorderRadius.circular(26),
                            ),
                            child: Image.asset('assets/images/app_icon.png'),
                          ),
                          const SizedBox(height: 18),
                          Text(
                            strings.text('appTitle'),
                            textAlign: TextAlign.center,
                            style: theme.textTheme.displaySmall?.copyWith(
                              color: Colors.white,
                              fontSize: 30,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            strings.text('tagline'),
                            textAlign: TextAlign.center,
                            style: theme.textTheme.bodyLarge?.copyWith(
                              color: const Color(0xFFD8EEFF),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: SingleChildScrollView(
                        padding: const EdgeInsets.fromLTRB(24, 18, 24, 24),
                        child: Column(
                          children: [
                            if (config != null && config.appMaintenance)
                              _StatusCard(
                                title: strings.text('maintenanceTitle'),
                                body: config.appMaintenanceMessage.isEmpty
                                    ? strings.text('maintenanceDefault')
                                    : config.appMaintenanceMessage,
                                actionLabel: null,
                                onAction: null,
                              )
                            else if (config != null &&
                                config.requiresForceUpdate(
                                  BackendConfig.appVersion,
                                ))
                              _StatusCard(
                                title: strings.text('updateRequiredTitle'),
                                body:
                                    '${strings.text('updateRequiredBody')} ${config.appVersion}',
                                actionLabel: strings.text('copyStoreLink'),
                                onAction: config.preferredStoreLink.isEmpty
                                    ? null
                                    : () => _copyStoreLink(
                                          config.preferredStoreLink,
                                          strings,
                                        ),
                              )
                            else
                              Container(
                                padding: const EdgeInsets.all(22),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(28),
                                  boxShadow: const [
                                    BoxShadow(
                                      color: Color(0x160A2742),
                                      blurRadius: 28,
                                      offset: Offset(0, 14),
                                    ),
                                  ],
                                ),
                                child: Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.stretch,
                                  children: [
                                    Row(
                                      children: [
                                        Expanded(
                                          child: _AuthTabButton(
                                            label: strings.text('loginTab'),
                                            selected: _isLoginMode,
                                            onTap: () {
                                              setState(() {
                                                _isLoginMode = true;
                                              });
                                            },
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: _AuthTabButton(
                                            label: strings.text('signupTab'),
                                            selected: !_isLoginMode,
                                            onTap: () {
                                              setState(() {
                                                _isLoginMode = false;
                                              });
                                            },
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 16),
                                    _isLoginMode
                                        ? _LoginForm(
                                            formKey: _loginFormKey,
                                            emailController:
                                                _loginEmailController,
                                            passwordController:
                                                _loginPasswordController,
                                            passwordHidden:
                                                _loginPasswordHidden,
                                            onPasswordToggle: () {
                                              setState(() {
                                                _loginPasswordHidden =
                                                    !_loginPasswordHidden;
                                              });
                                            },
                                            strings: strings,
                                          )
                                        : _SignupForm(
                                            formKey: _signupFormKey,
                                            nameController:
                                                _signupNameController,
                                            emailController:
                                                _signupEmailController,
                                            phoneController:
                                                _signupPhoneController,
                                            selectedDialCode: _selectedDialCode,
                                            dialCodes: _dialCodes,
                                            onDialCodeChanged: (value) {
                                              setState(() {
                                                _selectedDialCode = value;
                                              });
                                            },
                                            passwordController:
                                                _signupPasswordController,
                                            confirmPasswordController:
                                                _signupConfirmPasswordController,
                                            passwordHidden:
                                                _signupPasswordHidden,
                                            confirmPasswordHidden:
                                                _signupConfirmPasswordHidden,
                                            onPasswordToggle: () {
                                              setState(() {
                                                _signupPasswordHidden =
                                                    !_signupPasswordHidden;
                                              });
                                            },
                                            onConfirmPasswordToggle: () {
                                              setState(() {
                                                _signupConfirmPasswordHidden =
                                                    !_signupConfirmPasswordHidden;
                                              });
                                            },
                                            strings: strings,
                                          ),
                                    const SizedBox(height: 10),
                                    if (_isLoginMode)
                                      Align(
                                        alignment: Alignment.centerRight,
                                        child: TextButton(
                                          onPressed: _isSubmitting
                                              ? null
                                              : () => _handleForgotPassword(
                                                    strings,
                                                  ),
                                          child: Text(
                                            strings.text('forgotPassword'),
                                          ),
                                        ),
                                      ),
                                    const SizedBox(height: 6),
                                    FilledButton(
                                      onPressed: _isSubmitting ? null : _submit,
                                      child: Text(
                                        _isSubmitting
                                            ? strings.text('saving')
                                            : (_isLoginMode
                                                ? strings.text('loginAction')
                                                : strings.text(
                                                    'createAccountAction',
                                                  )),
                                      ),
                                    ),
                                    const SizedBox(height: 18),
                                    _GoogleLoginButton(
                                      label: strings.text('googleLoginAction'),
                                      busyLabel: strings.text('saving'),
                                      busy: _isSubmitting,
                                      onTap: () => _handleGoogleLogin(
                                        strings,
                                      ),
                                    ),
                                    if (_isLoginMode) ...[
                                      const SizedBox(height: 10),
                                      Center(
                                        child: TextButton(
                                          onPressed: _isSubmitting
                                              ? null
                                              : () => _handleBypassLogin(
                                                    strings,
                                                  ),
                                          child: Text(
                                            strings.text('testModeAction'),
                                          ),
                                        ),
                                      ),
                                    ],
                                    const SizedBox(height: 24),
                                    Text.rich(
                                      TextSpan(
                                        style:
                                            theme.textTheme.bodySmall?.copyWith(
                                          color: const Color(0xFF687B91),
                                          fontWeight: FontWeight.w600,
                                        ),
                                        children: [
                                          TextSpan(
                                            text:
                                                '${strings.text('termsPrefix')} ',
                                          ),
                                          WidgetSpan(
                                            alignment:
                                                PlaceholderAlignment.middle,
                                            child: _InlineLink(
                                              label: strings.text(
                                                'termsOfService',
                                              ),
                                              onTap: () {
                                                Navigator.of(context).push(
                                                  MaterialPageRoute<void>(
                                                    builder: (_) =>
                                                        AppDocumentScreen(
                                                      future: widget
                                                          .appContentRepository
                                                          .fetchTermsOfService(
                                                        widget.locale
                                                            .languageCode,
                                                      ),
                                                    ),
                                                  ),
                                                );
                                              },
                                            ),
                                          ),
                                          TextSpan(
                                            text: ' ${strings.text('and')} ',
                                          ),
                                          WidgetSpan(
                                            alignment:
                                                PlaceholderAlignment.middle,
                                            child: _InlineLink(
                                              label: strings.text(
                                                'privacyPolicy',
                                              ),
                                              onTap: () {
                                                Navigator.of(context).push(
                                                  MaterialPageRoute<void>(
                                                    builder: (_) =>
                                                        AppDocumentScreen(
                                                      future: widget
                                                          .appContentRepository
                                                          .fetchPrivacyPolicy(
                                                        widget.locale
                                                            .languageCode,
                                                      ),
                                                    ),
                                                  ),
                                                );
                                              },
                                            ),
                                          ),
                                        ],
                                      ),
                                      textAlign: TextAlign.center,
                                    ),
                                  ],
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _submit() async {
    final activeFormKey = _isLoginMode ? _loginFormKey : _signupFormKey;
    if (!(activeFormKey.currentState?.validate() ?? false)) {
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    try {
      if (_isLoginMode) {
        await widget.onLogin(
          email: _loginEmailController.text.trim(),
          password: _loginPasswordController.text,
        );
      } else {
        final mobile =
            '${_selectedDialCode.trim()} ${_signupPhoneController.text.trim()}'
                .trim();
        await widget.onSignUp(
          name: _signupNameController.text.trim(),
          email: _signupEmailController.text.trim(),
          password: _signupPasswordController.text,
          mobile: mobile,
        );
      }
    } on PhpApiException catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.message),
        ),
      );
    } catch (_) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No se pudo iniciar sesion. Intenta de nuevo.'),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  void _showComingSoon(AppStrings strings) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(strings.text('featureComingSoon')),
      ),
    );
  }

  Future<void> _handleGoogleLogin(AppStrings strings) async {
    setState(() {
      _isSubmitting = true;
    });

    try {
      await widget.onGoogleLogin();
    } on PhpApiException catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  Future<void> _handleBypassLogin(AppStrings strings) async {
    setState(() {
      _isSubmitting = true;
    });

    try {
      await widget.onBypassLogin();
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(strings.text('testModeEntered'))),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  Future<void> _copyStoreLink(String link, AppStrings strings) async {
    await Clipboard.setData(ClipboardData(text: link));
    if (!mounted) {
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(strings.text('copiedMessage'))),
    );
  }

  Future<void> _handleForgotPassword(AppStrings strings) async {
    final emailController = TextEditingController(
      text: _loginEmailController.text.trim(),
    );
    final email = await showDialog<String>(
      context: context,
      builder: (context) => _ForgotPasswordDialog(
        strings: strings,
        controller: emailController,
      ),
    );
    emailController.dispose();

    if (email == null) {
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    try {
      await widget.authRepository.sendPasswordResetEmail(email: email);
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(strings.text('resetPasswordSent'))),
      );
    } on PhpApiException catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  void _showLanguageSheet(
    BuildContext context,
    AppStrings strings,
    ThemeData theme,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  strings.text('languageLabel'),
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: AppTheme.ink,
                  ),
                ),
                const SizedBox(height: 14),
                _LanguageOption(
                  label: strings.text('english'),
                  selected: widget.locale.languageCode == 'en',
                  onTap: () {
                    widget.onLocaleChanged(const Locale('en'));
                    Navigator.of(context).pop();
                  },
                ),
                const SizedBox(height: 10),
                _LanguageOption(
                  label: strings.text('spanish'),
                  selected: widget.locale.languageCode == 'es',
                  onTap: () {
                    widget.onLocaleChanged(const Locale('es'));
                    Navigator.of(context).pop();
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _StatusCard extends StatelessWidget {
  const _StatusCard({
    required this.title,
    required this.body,
    required this.actionLabel,
    required this.onAction,
  });

  final String title;
  final String body;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        boxShadow: const [
          BoxShadow(
            color: Color(0x160A2742),
            blurRadius: 28,
            offset: Offset(0, 14),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: AppTheme.ink,
                ),
          ),
          const SizedBox(height: 12),
          Text(
            body,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF41576E),
                  height: 1.5,
                ),
          ),
          if (actionLabel != null) ...[
            const SizedBox(height: 18),
            FilledButton(
              onPressed: onAction,
              child: Text(actionLabel!),
            ),
          ],
        ],
      ),
    );
  }
}

class _InputField extends StatelessWidget {
  const _InputField({
    required this.controller,
    required this.icon,
    required this.hintText,
    this.validator,
    this.keyboardType,
    this.obscureText = false,
    this.suffixIcon,
  });

  final TextEditingController controller;
  final IconData icon;
  final String hintText;
  final String? Function(String?)? validator;
  final TextInputType? keyboardType;
  final bool obscureText;
  final Widget? suffixIcon;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      validator: validator,
      keyboardType: keyboardType,
      obscureText: obscureText,
      decoration: InputDecoration(
        prefixIcon: Icon(icon, color: AppTheme.primary),
        hintText: hintText,
        suffixIcon: suffixIcon,
        filled: true,
        fillColor: const Color(0xFFF1F6FB),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide.none,
        ),
      ),
    );
  }
}

class _AuthTabButton extends StatelessWidget {
  const _AuthTabButton({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(14),
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Column(
          children: [
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                label,
                textAlign: TextAlign.center,
                maxLines: 1,
                style: TextStyle(
                  color: selected ? AppTheme.ink : const Color(0xFF9AA8B8),
                  fontWeight: FontWeight.w800,
                  fontSize: 14,
                ),
              ),
            ),
            const SizedBox(height: 10),
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              height: 3,
              width: double.infinity,
              decoration: BoxDecoration(
                color: selected ? AppTheme.ink : Colors.transparent,
                borderRadius: BorderRadius.circular(99),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ForgotPasswordDialog extends StatefulWidget {
  const _ForgotPasswordDialog({
    required this.strings,
    required this.controller,
  });

  final AppStrings strings;
  final TextEditingController controller;

  @override
  State<_ForgotPasswordDialog> createState() => _ForgotPasswordDialogState();
}

class _ForgotPasswordDialogState extends State<_ForgotPasswordDialog> {
  final _formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(24),
      ),
      title: Text(
        widget.strings.text('forgotPassword'),
        style: Theme.of(context).textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w800,
              color: AppTheme.ink,
            ),
      ),
      content: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.strings.text('forgotPasswordBody'),
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF5C6F84),
                    height: 1.4,
                  ),
            ),
            const SizedBox(height: 16),
            _InputField(
              controller: widget.controller,
              icon: Icons.mail_outline_rounded,
              hintText: widget.strings.text('emailHint'),
              keyboardType: TextInputType.emailAddress,
              validator: (value) {
                final text = value?.trim() ?? '';
                final emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');
                if (!emailRegex.hasMatch(text)) {
                  return widget.strings.text('invalidEmail');
                }
                return null;
              },
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: Text(widget.strings.text('understoodAction')),
        ),
        FilledButton(
          onPressed: () {
            if (!(_formKey.currentState?.validate() ?? false)) {
              return;
            }
            Navigator.of(context).pop(widget.controller.text.trim());
          },
          child: Text(widget.strings.text('sendAction')),
        ),
      ],
    );
  }
}

class _GoogleLoginButton extends StatelessWidget {
  const _GoogleLoginButton({
    required this.label,
    required this.busyLabel,
    required this.busy,
    required this.onTap,
  });

  final String label;
  final String busyLabel;
  final bool busy;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton(
        onPressed: busy ? null : onTap,
        style: OutlinedButton.styleFrom(
          foregroundColor: AppTheme.ink,
          backgroundColor: Colors.white,
          side: const BorderSide(color: Color(0xFFD8E4F0)),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: const Color(0xFFF5F8FC),
                borderRadius: BorderRadius.circular(10),
              ),
              alignment: Alignment.center,
              child: Text(
                'G',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      color: const Color(0xFFDB4437),
                      fontWeight: FontWeight.w900,
                    ),
              ),
            ),
            const SizedBox(width: 12),
            Flexible(
              child: Text(
                busy ? busyLabel : label,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 15,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LoginForm extends StatelessWidget {
  const _LoginForm({
    super.key,
    required this.formKey,
    required this.emailController,
    required this.passwordController,
    required this.passwordHidden,
    required this.onPasswordToggle,
    required this.strings,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController emailController;
  final TextEditingController passwordController;
  final bool passwordHidden;
  final VoidCallback onPasswordToggle;
  final AppStrings strings;

  @override
  Widget build(BuildContext context) {
    return Form(
      key: formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _InputField(
            controller: emailController,
            icon: Icons.mail_outline_rounded,
            hintText: strings.text('emailHint'),
            keyboardType: TextInputType.emailAddress,
            validator: (value) {
              final text = value?.trim() ?? '';
              final emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');
              if (!emailRegex.hasMatch(text)) {
                return strings.text('invalidEmail');
              }
              return null;
            },
          ),
          const SizedBox(height: 12),
          _InputField(
            controller: passwordController,
            icon: Icons.lock_outline_rounded,
            hintText: strings.text('passwordHint'),
            obscureText: passwordHidden,
            validator: (value) {
              if ((value ?? '').trim().length < 6) {
                return strings.text('invalidPassword');
              }
              return null;
            },
            suffixIcon: IconButton(
              onPressed: onPasswordToggle,
              icon: Icon(
                passwordHidden
                    ? Icons.visibility_off_rounded
                    : Icons.visibility_rounded,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SignupForm extends StatelessWidget {
  const _SignupForm({
    super.key,
    required this.formKey,
    required this.nameController,
    required this.emailController,
    required this.phoneController,
    required this.selectedDialCode,
    required this.dialCodes,
    required this.onDialCodeChanged,
    required this.passwordController,
    required this.confirmPasswordController,
    required this.passwordHidden,
    required this.confirmPasswordHidden,
    required this.onPasswordToggle,
    required this.onConfirmPasswordToggle,
    required this.strings,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController nameController;
  final TextEditingController emailController;
  final TextEditingController phoneController;
  final String selectedDialCode;
  final List<String> dialCodes;
  final ValueChanged<String> onDialCodeChanged;
  final TextEditingController passwordController;
  final TextEditingController confirmPasswordController;
  final bool passwordHidden;
  final bool confirmPasswordHidden;
  final VoidCallback onPasswordToggle;
  final VoidCallback onConfirmPasswordToggle;
  final AppStrings strings;

  @override
  Widget build(BuildContext context) {
    return Form(
      key: formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _InputField(
            controller: nameController,
            icon: Icons.person_outline_rounded,
            hintText: strings.text('nameHint'),
            validator: (value) {
              if ((value ?? '').trim().isEmpty) {
                return strings.text('nameRequired');
              }
              return null;
            },
          ),
          const SizedBox(height: 12),
          _InputField(
            controller: emailController,
            icon: Icons.mail_outline_rounded,
            hintText: strings.text('emailHint'),
            keyboardType: TextInputType.emailAddress,
            validator: (value) {
              final text = value?.trim() ?? '';
              final emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');
              if (!emailRegex.hasMatch(text)) {
                return strings.text('invalidEmail');
              }
              return null;
            },
          ),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 96,
                child: _DialCodePickerButton(
                  value: selectedDialCode,
                  dialCodes: dialCodes,
                  onSelected: onDialCodeChanged,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _InputField(
                  controller: phoneController,
                  icon: Icons.phone_android_rounded,
                  hintText: strings.text('phoneOptional'),
                  keyboardType: TextInputType.phone,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          _InputField(
            controller: passwordController,
            icon: Icons.lock_outline_rounded,
            hintText: strings.text('passwordHint'),
            obscureText: passwordHidden,
            validator: (value) {
              if ((value ?? '').trim().length < 6) {
                return strings.text('invalidPassword');
              }
              return null;
            },
            suffixIcon: IconButton(
              onPressed: onPasswordToggle,
              icon: Icon(
                passwordHidden
                    ? Icons.visibility_off_rounded
                    : Icons.visibility_rounded,
              ),
            ),
          ),
          const SizedBox(height: 12),
          _InputField(
            controller: confirmPasswordController,
            icon: Icons.lock_person_outlined,
            hintText: strings.text('confirmPasswordHint'),
            obscureText: confirmPasswordHidden,
            validator: (value) {
              if ((value ?? '') != passwordController.text) {
                return strings.text('passwordMismatch');
              }
              return null;
            },
            suffixIcon: IconButton(
              onPressed: onConfirmPasswordToggle,
              icon: Icon(
                confirmPasswordHidden
                    ? Icons.visibility_off_rounded
                    : Icons.visibility_rounded,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _DialCodeField extends StatelessWidget {
  const _DialCodeField({
    required this.value,
    required this.onTap,
  });

  final String value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: const Color(0xFFF1F6FB),
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 18),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                value,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: AppTheme.ink,
                    ),
              ),
              const Icon(Icons.arrow_drop_down_rounded,
                  color: AppTheme.primary),
            ],
          ),
        ),
      ),
    );
  }
}

class _DialCodePickerButton extends StatelessWidget {
  const _DialCodePickerButton({
    required this.value,
    required this.dialCodes,
    required this.onSelected,
  });

  final String value;
  final List<String> dialCodes;
  final ValueChanged<String> onSelected;

  Future<void> _openPicker(BuildContext context) async {
    final selected = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: dialCodes
              .map(
                (code) => ListTile(
                  title: Text(code),
                  trailing: code == value
                      ? const Icon(Icons.check_rounded, color: AppTheme.primary)
                      : null,
                  onTap: () => Navigator.of(context).pop(code),
                ),
              )
              .toList(),
        ),
      ),
    );

    if (selected != null) {
      onSelected(selected);
    }
  }

  @override
  Widget build(BuildContext context) {
    return _DialCodeField(
      value: value,
      onTap: () => _openPicker(context),
    );
  }
}

class _SocialButton extends StatelessWidget {
  const _SocialButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        width: 72,
        height: 72,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFE2EAF4)),
          boxShadow: const [
            BoxShadow(
              color: Color(0x120E2741),
              blurRadius: 16,
              offset: Offset(0, 8),
            ),
          ],
        ),
        child: Icon(icon, color: AppTheme.primary, size: 34),
      ),
    );
  }
}

class _InlineLink extends StatelessWidget {
  const _InlineLink({
    required this.label,
    required this.onTap,
  });

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Text(
        label,
        style: const TextStyle(
          color: Color(0xFFD85D6E),
          fontWeight: FontWeight.w800,
          decoration: TextDecoration.underline,
        ),
      ),
    );
  }
}

class _LanguageOption extends StatelessWidget {
  const _LanguageOption({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFFEAF4FF) : Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: selected ? const Color(0xFF2B6FB6) : const Color(0xFFE2EAF4),
          ),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(
                label,
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: selected ? const Color(0xFF2B6FB6) : AppTheme.ink,
                ),
              ),
            ),
            if (selected)
              const Icon(
                Icons.check_circle_rounded,
                color: Color(0xFF2B6FB6),
              ),
          ],
        ),
      ),
    );
  }
}
