import '../models/app_user.dart';

class PhoneAuthChallenge {
  const PhoneAuthChallenge({
    required this.verificationId,
  });

  final String verificationId;
}

abstract class AuthRepository {
  Future<AppUser?> restoreSession();

  Future<AppUser> signIn({
    required String email,
    required String password,
  });

  Future<AppUser> signUp({
    required String name,
    required String email,
    required String password,
    required String mobile,
  });

  Future<AppUser> signInWithGoogle();

  Future<PhoneAuthChallenge> sendPhoneSignInCode({
    required String phoneNumber,
  });

  Future<AppUser> signInWithPhoneCode({
    required String verificationId,
    required String smsCode,
    required String phoneNumber,
  });

  Future<AppUser> refreshUser(String userId);

  Future<AppUser> updateProfile({
    required String userId,
    required String name,
    required String email,
    required String mobile,
    String? profileImagePath,
  });

  Future<void> sendPasswordResetEmail({
    required String email,
  });

  Future<void> deleteAccount({
    required String userId,
  });

  Future<void> signOut();
}
