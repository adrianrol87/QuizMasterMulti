import '../models/app_user.dart';
import 'auth_repository.dart';

class MockAuthRepository implements AuthRepository {
  const MockAuthRepository();

  static AppUser? _sessionUser = const AppUser(
    id: '1',
    firebaseId: 'mock-demo-user',
    name: 'Blacksmith Asgar',
    email: 'blacksmith@example.com',
    mobile: '5551234567',
    profileUrl: '',
    loginType: 'email',
    coins: 2450,
    score: 1820,
    rank: 12,
  );

  @override
  Future<AppUser?> restoreSession() async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
    return _sessionUser;
  }

  @override
  Future<AppUser> signIn({
    required String email,
    required String password,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 180));
    _sessionUser = (_sessionUser ??
            const AppUser(
              id: '1',
              firebaseId: 'mock-demo-user',
              name: '',
              email: '',
              mobile: '',
              profileUrl: '',
              loginType: 'email',
              coins: 0,
              score: 0,
              rank: 0,
            ))
        .copyWith(
      email: email,
      name: _sessionUser?.name.isNotEmpty == true ? _sessionUser!.name : 'Player',
    );
    return _sessionUser!;
  }

  @override
  Future<AppUser> signUp({
    required String name,
    required String email,
    required String password,
    required String mobile,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 220));
    _sessionUser = (_sessionUser ??
            const AppUser(
              id: '1',
              firebaseId: 'mock-demo-user',
              name: '',
              email: '',
              mobile: '',
              profileUrl: '',
              loginType: 'email',
              coins: 0,
              score: 0,
              rank: 0,
            ))
        .copyWith(
      name: name,
      email: email,
      mobile: mobile,
    );
    return _sessionUser!;
  }

  @override
  Future<AppUser> signInWithGoogle() async {
    await Future<void>.delayed(const Duration(milliseconds: 220));
    _sessionUser = (_sessionUser ??
            const AppUser(
              id: '1',
              firebaseId: 'mock-google-user',
              name: '',
              email: '',
              mobile: '',
              profileUrl: '',
              loginType: 'gmail',
              coins: 0,
              score: 0,
              rank: 0,
            ))
        .copyWith(
      name: _sessionUser?.name.isNotEmpty == true ? _sessionUser!.name : 'Player',
      email: _sessionUser?.email.isNotEmpty == true
          ? _sessionUser!.email
          : 'player@gmail.com',
      loginType: 'gmail',
    );
    return _sessionUser!;
  }

  @override
  Future<PhoneAuthChallenge> sendPhoneSignInCode({
    required String phoneNumber,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 180));
    return const PhoneAuthChallenge(verificationId: 'mock-verification-id');
  }

  @override
  Future<AppUser> signInWithPhoneCode({
    required String verificationId,
    required String smsCode,
    required String phoneNumber,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 220));
    _sessionUser = (_sessionUser ??
            const AppUser(
              id: '1',
              firebaseId: 'mock-phone-user',
              name: '',
              email: '',
              mobile: '',
              profileUrl: '',
              loginType: 'phone',
              coins: 0,
              score: 0,
              rank: 0,
            ))
        .copyWith(
      mobile: phoneNumber,
      loginType: 'phone',
      name: _sessionUser?.name.isNotEmpty == true ? _sessionUser!.name : 'Player',
    );
    return _sessionUser!;
  }

  @override
  Future<AppUser> refreshUser(String userId) async {
    await Future<void>.delayed(const Duration(milliseconds: 100));
    return _sessionUser!;
  }

  @override
  Future<AppUser> updateProfile({
    required String userId,
    required String name,
    required String email,
    required String mobile,
    String? profileImagePath,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 160));
    _sessionUser = _sessionUser!.copyWith(
      name: name,
      email: email,
      mobile: mobile,
      profileUrl: profileImagePath ?? _sessionUser!.profileUrl,
    );
    return _sessionUser!;
  }

  @override
  Future<void> sendPasswordResetEmail({
    required String email,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
  }

  @override
  Future<void> deleteAccount({
    required String userId,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 150));
    _sessionUser = null;
  }

  @override
  Future<void> signOut() async {
    await Future<void>.delayed(const Duration(milliseconds: 80));
    _sessionUser = null;
  }
}
