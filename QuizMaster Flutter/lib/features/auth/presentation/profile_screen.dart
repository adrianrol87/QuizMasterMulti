import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/l10n/app_strings.dart';
import '../../../core/network/php_api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../data/auth_repository.dart';
import '../models/app_user.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({
    super.key,
    required this.locale,
    required this.user,
    required this.authRepository,
    required this.onProfileSaved,
    required this.onAccountDeleted,
  });

  final Locale locale;
  final AppUser user;
  final AuthRepository authRepository;
  final ValueChanged<AppUser> onProfileSaved;
  final Future<void> Function() onAccountDeleted;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  static const _dialCodes = ['+52', '+1', '+34', '+54', '+57'];
  late final TextEditingController _nameController;
  late final TextEditingController _emailController;
  late final TextEditingController _mobileController;
  final ImagePicker _imagePicker = ImagePicker();
  bool _isSaving = false;
  XFile? _selectedImage;
  late String _selectedDialCode;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.user.name);
    _emailController = TextEditingController(text: widget.user.email);
    final parsedMobile = _parseMobile(widget.user.mobile);
    _selectedDialCode = parsedMobile.$1;
    _mobileController = TextEditingController(text: parsedMobile.$2);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _mobileController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings(widget.locale);
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: const Color(0xFFF4F7FB),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.ink,
        title: Text(
          strings.text('profile'),
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        children: [
          Center(
            child: Column(
              children: [
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    Container(
                      width: 92,
                      height: 92,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: const Color(0xFFB7D8FA),
                          width: 3,
                        ),
                        image: DecorationImage(
                          image: _buildProfileImage(),
                          fit: BoxFit.cover,
                        ),
                      ),
                    ),
                    Positioned(
                      right: -2,
                      bottom: -2,
                      child: Material(
                        color: Colors.transparent,
                        child: InkWell(
                          onTap: _pickProfileImage,
                          borderRadius: BorderRadius.circular(16),
                          child: Container(
                            width: 32,
                            height: 32,
                            decoration: BoxDecoration(
                              color: AppTheme.primary,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                color: Colors.white,
                                width: 2,
                              ),
                            ),
                            child: const Icon(
                              Icons.photo_camera_rounded,
                              color: Colors.white,
                              size: 17,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                TextButton(
                  onPressed: _pickProfileImage,
                  child: Text(strings.text('changePhoto')),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x110A2742),
                  blurRadius: 18,
                  offset: Offset(0, 10),
                ),
              ],
            ),
            child: Column(
              children: [
                _ProfileField(
                  controller: _nameController,
                  icon: Icons.person_outline_rounded,
                  hintText: strings.text('nameHint'),
                ),
                const SizedBox(height: 12),
                _ProfileField(
                  controller: _emailController,
                  icon: Icons.mail_outline_rounded,
                  hintText: strings.text('emailHint'),
                  keyboardType: TextInputType.emailAddress,
                ),
                const SizedBox(height: 12),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 96,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F6FB),
                        borderRadius: BorderRadius.circular(18),
                      ),
                      child: DropdownButtonFormField<String>(
                        value: _selectedDialCode,
                        decoration: const InputDecoration(
                          border: InputBorder.none,
                          contentPadding: EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 18,
                          ),
                        ),
                        icon: const Icon(Icons.arrow_drop_down_rounded),
                        items: _dialCodes
                            .map(
                              (code) => DropdownMenuItem<String>(
                                value: code,
                                child: Text(code),
                              ),
                            )
                            .toList(),
                        onChanged: (value) {
                          if (value == null) {
                            return;
                          }
                          setState(() {
                            _selectedDialCode = value;
                          });
                        },
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _ProfileField(
                        controller: _mobileController,
                        icon: Icons.phone_outlined,
                        hintText: strings.text('phoneOptional'),
                        keyboardType: TextInputType.phone,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: _isSaving ? null : _save,
                    child: Text(
                      _isSaving
                          ? strings.text('saving')
                          : strings.text('saveProfile'),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    onPressed: _isSaving ? null : _confirmDeleteAccount,
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFFC62828),
                      side: const BorderSide(color: Color(0xFFC62828)),
                    ),
                    child: Text(strings.text('deleteAccount')),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _save() async {
    setState(() {
      _isSaving = true;
    });

    try {
      final user = await widget.authRepository.updateProfile(
        userId: widget.user.id,
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        mobile: _composeMobile(),
        profileImagePath: _selectedImage?.path,
      );
      widget.onProfileSaved(user);
      if (mounted) {
        Navigator.of(context).pop();
      }
    } on PhpApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSaving = false;
        });
      }
    }
  }

  Future<void> _confirmDeleteAccount() async {
    final strings = AppStrings(widget.locale);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(strings.text('deleteAccountTitle')),
        content: Text(strings.text('deleteAccountBody')),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text(strings.text('cancelAction')),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFC62828),
            ),
            child: Text(strings.text('deleteAccountConfirm')),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      return;
    }

    setState(() {
      _isSaving = true;
    });

    try {
      await widget.authRepository.deleteAccount(userId: widget.user.id);
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(strings.text('deleteAccountSuccess'))),
      );
      await widget.onAccountDeleted();
    } on PhpApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSaving = false;
        });
      }
    }
  }

  Future<void> _pickProfileImage() async {
    try {
      final picked = await _imagePicker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 88,
        maxWidth: 1200,
      );

      if (picked == null || !mounted) {
        return;
      }

      setState(() {
        _selectedImage = picked;
      });
    } catch (_) {
      if (!mounted) return;
      final strings = AppStrings(widget.locale);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(strings.text('pickImageFailed'))),
      );
    }
  }

  ImageProvider _buildProfileImage() {
    if (_selectedImage != null) {
      return FileImage(File(_selectedImage!.path));
    }

    if (widget.user.profileUrl.isNotEmpty) {
      return NetworkImage(widget.user.profileUrl);
    }

    return const AssetImage('assets/images/user.png');
  }

  String _composeMobile() {
    final number = _mobileController.text.trim();
    if (number.isEmpty) {
      return '';
    }

    return '$_selectedDialCode $number'.trim();
  }

  (String, String) _parseMobile(String rawMobile) {
    final trimmed = rawMobile.trim();
    if (trimmed.isEmpty) {
      return ('+52', '');
    }

    for (final code in _dialCodes) {
      if (trimmed.startsWith('$code ')) {
        return (code, trimmed.substring(code.length).trim());
      }
      if (trimmed == code) {
        return (code, '');
      }
    }

    if (trimmed.startsWith('+')) {
      final parts = trimmed.split(RegExp(r'\s+'));
      if (parts.isNotEmpty) {
        final first = parts.first;
        final rest = parts.skip(1).join(' ').trim();
        return (_dialCodes.contains(first) ? first : '+52', rest);
      }
    }

    return ('+52', trimmed);
  }
}

class _ProfileField extends StatelessWidget {
  const _ProfileField({
    required this.controller,
    required this.icon,
    required this.hintText,
    this.keyboardType,
  });

  final TextEditingController controller;
  final IconData icon;
  final String hintText;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      decoration: InputDecoration(
        prefixIcon: Icon(icon, color: AppTheme.primary),
        hintText: hintText,
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
