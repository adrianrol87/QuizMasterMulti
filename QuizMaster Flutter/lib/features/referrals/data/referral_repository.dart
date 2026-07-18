import '../../../core/network/php_api_client.dart';

class ReferralInfo {
  const ReferralInfo({
    required this.referralCode,
    required this.usedCode,
    required this.canRedeem,
    required this.referrerReward,
    required this.newUserReward,
  });

  factory ReferralInfo.fromApi(Map<String, dynamic> json) {
    return ReferralInfo(
      referralCode: (json['refer_code'] ?? '').toString(),
      usedCode: (json['friends_code'] ?? '').toString(),
      canRedeem: (json['can_redeem'] ?? '0').toString() == '1',
      referrerReward:
          int.tryParse((json['referrer_reward'] ?? '0').toString()) ?? 0,
      newUserReward:
          int.tryParse((json['new_user_reward'] ?? '0').toString()) ?? 0,
    );
  }

  final String referralCode;
  final String usedCode;
  final bool canRedeem;
  final int referrerReward;
  final int newUserReward;
}

abstract class ReferralRepository {
  Future<ReferralInfo> fetchInfo(String userId);

  Future<ReferralInfo> redeemCode({
    required String userId,
    required String referralCode,
  });
}

class RemoteReferralRepository implements ReferralRepository {
  const RemoteReferralRepository({required this.apiClient});

  final PhpApiClient apiClient;

  @override
  Future<ReferralInfo> fetchInfo(String userId) async {
    final response = await apiClient.post({
      'get_referral_info': '1',
      'user_id': userId,
    });
    final data = response['data'];
    if (data is! Map) {
      throw const PhpApiException('Invalid referral information.');
    }
    return ReferralInfo.fromApi(Map<String, dynamic>.from(data));
  }

  @override
  Future<ReferralInfo> redeemCode({
    required String userId,
    required String referralCode,
  }) async {
    await apiClient.post({
      'redeem_referral_code': '1',
      'user_id': userId,
      'referral_code': referralCode,
    });
    return fetchInfo(userId);
  }
}
