class AppUser {
  const AppUser({
    required this.id,
    required this.firebaseId,
    required this.name,
    required this.email,
    required this.mobile,
    required this.profileUrl,
    required this.loginType,
    required this.coins,
    required this.score,
    required this.rank,
    this.referCode = '',
    this.friendsCode = '',
  });

  factory AppUser.fromSignupResponse(Map<String, dynamic> json) {
    return AppUser(
      id: (json['user_id'] ?? json['id'] ?? '').toString(),
      firebaseId: (json['firebase_id'] ?? '').toString(),
      name: (json['name'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      mobile: (json['mobile'] ?? '').toString(),
      profileUrl: (json['profile'] ?? '').toString(),
      loginType: (json['type'] ?? 'email').toString(),
      coins: int.tryParse((json['coins'] ?? '0').toString()) ?? 0,
      score: int.tryParse((json['score'] ?? '0').toString()) ?? 0,
      rank: int.tryParse(
              (json['all_time_rank'] ?? json['user_rank'] ?? '0').toString()) ??
          0,
      referCode: (json['refer_code'] ?? '').toString(),
      friendsCode: (json['friends_code'] ?? '').toString(),
    );
  }

  factory AppUser.fromUserResponse(Map<String, dynamic> json) {
    return AppUser(
      id: (json['id'] ?? json['user_id'] ?? '').toString(),
      firebaseId: (json['firebase_id'] ?? '').toString(),
      name: (json['name'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      mobile: (json['mobile'] ?? '').toString(),
      profileUrl: (json['profile'] ?? '').toString(),
      loginType: (json['type'] ?? 'email').toString(),
      coins: int.tryParse((json['coins'] ?? '0').toString()) ?? 0,
      score: int.tryParse(
              (json['all_time_score'] ?? json['score'] ?? '0').toString()) ??
          0,
      rank: int.tryParse(
              (json['all_time_rank'] ?? json['user_rank'] ?? '0').toString()) ??
          0,
      referCode: (json['refer_code'] ?? '').toString(),
      friendsCode: (json['friends_code'] ?? '').toString(),
    );
  }

  final String id;
  final String firebaseId;
  final String name;
  final String email;
  final String mobile;
  final String profileUrl;
  final String loginType;
  final int coins;
  final int score;
  final int rank;
  final String referCode;
  final String friendsCode;

  AppUser copyWith({
    String? id,
    String? firebaseId,
    String? name,
    String? email,
    String? mobile,
    String? profileUrl,
    String? loginType,
    int? coins,
    int? score,
    int? rank,
    String? referCode,
    String? friendsCode,
  }) {
    return AppUser(
      id: id ?? this.id,
      firebaseId: firebaseId ?? this.firebaseId,
      name: name ?? this.name,
      email: email ?? this.email,
      mobile: mobile ?? this.mobile,
      profileUrl: profileUrl ?? this.profileUrl,
      loginType: loginType ?? this.loginType,
      coins: coins ?? this.coins,
      score: score ?? this.score,
      rank: rank ?? this.rank,
      referCode: referCode ?? this.referCode,
      friendsCode: friendsCode ?? this.friendsCode,
    );
  }
}
