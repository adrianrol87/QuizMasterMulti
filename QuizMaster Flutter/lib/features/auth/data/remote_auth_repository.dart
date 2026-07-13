import 'package:firebase_auth/firebase_auth.dart' as fb_auth;
import 'package:google_sign_in/google_sign_in.dart';
import 'dart:async';

import '../../../core/network/php_api_client.dart';
import '../models/app_user.dart';
import 'auth_repository.dart';

class RemoteAuthRepository implements AuthRepository {
  RemoteAuthRepository({
    required this.apiClient,
    fb_auth.FirebaseAuth? firebaseAuth,
    GoogleSignIn? googleSignIn,
  })  : _firebaseAuth = firebaseAuth ?? fb_auth.FirebaseAuth.instance,
        _googleSignIn = googleSignIn ?? GoogleSignIn(scopes: const ['email']);

  final PhpApiClient apiClient;
  final fb_auth.FirebaseAuth _firebaseAuth;
  final GoogleSignIn _googleSignIn;

  @override
  Future<AppUser?> restoreSession() async {
    final firebaseUser = _firebaseAuth.currentUser;
    if (firebaseUser == null) {
      return null;
    }

    return _syncBackendUser(firebaseUser);
  }

  @override
  Future<AppUser> signIn({
    required String email,
    required String password,
  }) async {
    try {
      final credential = await _firebaseAuth.signInWithEmailAndPassword(
        email: email.trim(),
        password: password,
      );
      final firebaseUser = credential.user;
      if (firebaseUser == null) {
        throw const PhpApiException('Firebase did not return a user session.');
      }
      return _syncBackendUser(firebaseUser);
    } on fb_auth.FirebaseAuthException catch (error) {
      throw PhpApiException(_mapFirebaseError(error));
    }
  }

  @override
  Future<AppUser> signUp({
    required String name,
    required String email,
    required String password,
    required String mobile,
  }) async {
    try {
      final credential = await _firebaseAuth.createUserWithEmailAndPassword(
        email: email.trim(),
        password: password,
      );
      final firebaseUser = credential.user;
      if (firebaseUser == null) {
        throw const PhpApiException('Firebase did not return a user session.');
      }

      await firebaseUser.updateDisplayName(name.trim());
      await firebaseUser.reload();
      final refreshedUser = _firebaseAuth.currentUser ?? firebaseUser;

      return _syncBackendUser(
        refreshedUser,
        fallbackName: name,
        fallbackMobile: mobile,
      );
    } on fb_auth.FirebaseAuthException catch (error) {
      throw PhpApiException(_mapFirebaseError(error));
    }
  }

  @override
  Future<AppUser> signInWithGoogle() async {
    try {
      final googleUser = await _googleSignIn.signIn();
      if (googleUser == null) {
        throw const PhpApiException('Se cancelo el acceso con Google.');
      }

      final googleAuth = await googleUser.authentication;
      final credential = fb_auth.GoogleAuthProvider.credential(
        accessToken: googleAuth.accessToken,
        idToken: googleAuth.idToken,
      );

      final result = await _firebaseAuth.signInWithCredential(credential);
      final firebaseUser = result.user;
      if (firebaseUser == null) {
        throw const PhpApiException('Google no devolvio una sesion valida.');
      }

      return _syncBackendUser(
        firebaseUser,
        loginType: 'gmail',
      );
    } on fb_auth.FirebaseAuthException catch (error) {
      throw PhpApiException(_mapFirebaseError(error));
    }
  }

  @override
  Future<PhoneAuthChallenge> sendPhoneSignInCode({
    required String phoneNumber,
  }) async {
    final completer = Completer<PhoneAuthChallenge>();

    await _firebaseAuth.verifyPhoneNumber(
      phoneNumber: phoneNumber.trim(),
      verificationCompleted: (credential) async {},
      verificationFailed: (error) {
        if (!completer.isCompleted) {
          completer.completeError(
            PhpApiException(_mapFirebaseError(error)),
          );
        }
      },
      codeSent: (verificationId, _) {
        if (!completer.isCompleted) {
          completer.complete(
            PhoneAuthChallenge(verificationId: verificationId),
          );
        }
      },
      codeAutoRetrievalTimeout: (verificationId) {
        if (!completer.isCompleted) {
          completer.complete(
            PhoneAuthChallenge(verificationId: verificationId),
          );
        }
      },
    );

    return completer.future;
  }

  @override
  Future<AppUser> signInWithPhoneCode({
    required String verificationId,
    required String smsCode,
    required String phoneNumber,
  }) async {
    try {
      final credential = fb_auth.PhoneAuthProvider.credential(
        verificationId: verificationId,
        smsCode: smsCode.trim(),
      );
      final result = await _firebaseAuth.signInWithCredential(credential);
      final firebaseUser = result.user;
      if (firebaseUser == null) {
        throw const PhpApiException('Firebase did not return a phone user session.');
      }
      return _syncBackendUser(
        firebaseUser,
        fallbackMobile: phoneNumber,
      );
    } on fb_auth.FirebaseAuthException catch (error) {
      throw PhpApiException(_mapFirebaseError(error));
    }
  }

  @override
  Future<AppUser> refreshUser(String userId) async {
    final userResponse = await apiClient.post({
      'get_user_by_id': '1',
      'id': userId,
    });
    final userData = userResponse['data'];
    if (userData is! Map<String, dynamic>) {
      throw const PhpApiException('Invalid user payload.');
    }

    final baseUser = AppUser.fromUserResponse(userData);

    final coinResponse = await apiClient.post({
      'get_user_coin_score': '1',
      'user_id': userId,
    });
    final coinData = coinResponse['data'];
    if (coinData is! Map<String, dynamic>) {
      return baseUser;
    }

    return baseUser.copyWith(
      coins: int.tryParse((coinData['coins'] ?? '0').toString()) ?? baseUser.coins,
      score: int.tryParse((coinData['score'] ?? '0').toString()) ?? baseUser.score,
    );
  }

  @override
  Future<AppUser> updateProfile({
    required String userId,
    required String name,
    required String email,
    required String mobile,
    String? profileImagePath,
  }) async {
    if (profileImagePath != null && profileImagePath.isNotEmpty) {
      await apiClient.uploadProfileImage(
        userId: userId,
        imagePath: profileImagePath,
      );
    }

    await apiClient.post({
      'update_profile': '1',
      'user_id': userId,
      'name': name,
      'email': email,
      'mobile': mobile,
    });

    final firebaseUser = _firebaseAuth.currentUser;
    if (firebaseUser != null && firebaseUser.displayName != name.trim()) {
      await firebaseUser.updateDisplayName(name.trim());
    }

    return refreshUser(userId);
  }

  @override
  Future<void> sendPasswordResetEmail({
    required String email,
  }) async {
    try {
      await _firebaseAuth.sendPasswordResetEmail(email: email.trim());
    } on fb_auth.FirebaseAuthException catch (error) {
      throw PhpApiException(_mapFirebaseError(error));
    }
  }

  @override
  Future<void> deleteAccount({
    required String userId,
  }) async {
    final firebaseUser = _firebaseAuth.currentUser;

    try {
      if (firebaseUser != null) {
        await firebaseUser.delete();
      }

      await apiClient.post({
        'delete_account': '1',
        'user_id': userId,
      });

      await _googleSignIn.signOut();
      await _firebaseAuth.signOut();
    } on fb_auth.FirebaseAuthException catch (error) {
      throw PhpApiException(_mapFirebaseError(error));
    }
  }

  @override
  Future<void> signOut() async {
    await _googleSignIn.signOut();
    await _firebaseAuth.signOut();
  }

  Future<AppUser> _syncBackendUser(
    fb_auth.User firebaseUser, {
    String? fallbackName,
    String? fallbackMobile,
    String loginType = 'email',
  }) async {
    final response = await apiClient.post({
      'user_signup': '1',
      'firebase_id': firebaseUser.uid,
      'name': _resolveName(firebaseUser, fallbackName),
      'email': firebaseUser.email?.trim() ?? '',
      'mobile': fallbackMobile?.trim() ?? firebaseUser.phoneNumber?.trim() ?? '',
      'profile': firebaseUser.photoURL?.trim() ?? '',
      'type': loginType,
      'fcm_id': '',
      'refer_code': '',
      'friends_code': '',
      'ip_address': '',
      'status': '1',
    });

    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      throw const PhpApiException('Invalid signup payload.');
    }

    final user = AppUser.fromSignupResponse(data);
    if (user.id.isEmpty) {
      throw const PhpApiException('Authentication completed without user id.');
    }

    try {
      return await refreshUser(user.id);
    } on PhpApiException {
      return user;
    }
  }

  String _resolveName(fb_auth.User firebaseUser, String? fallbackName) {
    final displayName = firebaseUser.displayName?.trim() ?? '';
    if (displayName.isNotEmpty) {
      return displayName;
    }
    final safeFallback = fallbackName?.trim() ?? '';
    if (safeFallback.isNotEmpty) {
      return safeFallback;
    }
    final email = firebaseUser.email?.trim() ?? '';
    if (email.isEmpty) {
      return 'Player';
    }

    final local = email.split('@').first.trim();
    if (local.isEmpty) {
      return 'Player';
    }

    return local
        .split(RegExp(r'[._-]+'))
        .where((part) => part.isNotEmpty)
        .map((part) => '${part[0].toUpperCase()}${part.substring(1)}')
        .join(' ');
  }

  String _mapFirebaseError(fb_auth.FirebaseAuthException error) {
    switch (error.code) {
      case 'invalid-email':
        return 'El correo no es valido.';
      case 'user-disabled':
        return 'Esta cuenta fue deshabilitada.';
      case 'missing-email':
        return 'Escribe tu correo primero.';
      case 'user-not-found':
      case 'wrong-password':
      case 'invalid-credential':
        return 'Correo o contrasena incorrectos.';
      case 'invalid-verification-code':
        return 'El codigo SMS no es valido.';
      case 'invalid-verification-id':
        return 'La sesion del codigo ya no es valida.';
      case 'session-expired':
        return 'El codigo expiro. Solicita uno nuevo.';
      case 'missing-verification-code':
        return 'Escribe el codigo SMS.';
      case 'quota-exceeded':
        return 'Firebase agoto la cuota de SMS para este proyecto.';
      case 'account-exists-with-different-credential':
        return 'Ese correo ya existe con otro metodo de acceso.';
      case 'email-already-in-use':
        return 'Ese correo ya esta registrado.';
      case 'weak-password':
        return 'La contrasena es demasiado debil.';
      case 'too-many-requests':
        return 'Demasiados intentos. Intenta de nuevo mas tarde.';
      case 'network-request-failed':
        return 'No se pudo conectar con Firebase.';
      case 'requires-recent-login':
        return 'Por seguridad, vuelve a iniciar sesion antes de eliminar tu cuenta.';
      default:
        return error.message?.trim().isNotEmpty == true
            ? error.message!.trim()
            : 'No se pudo completar la autenticacion.';
    }
  }
}
