import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/backend_config.dart';
import 'jwt_token_provider.dart';

class PhpApiClient {
  PhpApiClient({
    http.Client? client,
  }) : _client = client ?? http.Client();

  final http.Client _client;

  Map<String, String> get _baseHeaders => {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': 'Bearer ${JwtTokenProvider.generateToken()}',
      };

  Future<Map<String, dynamic>> post(Map<String, String> body) async {
    if (!BackendConfig.isConfigured) {
      throw const PhpApiException(
        'Backend URL is not configured yet.',
      );
    }

    final response = await _client.post(
      Uri.parse(BackendConfig.apiUrl),
      headers: _baseHeaders,
      body: {
        'access_key': BackendConfig.accessKey,
        ...body,
      },
    );

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw PhpApiException(
        'Request failed with status ${response.statusCode}.',
      );
    }

    final decoded = jsonDecode(response.body);
    if (decoded is! Map<String, dynamic>) {
      throw const PhpApiException('Unexpected response format.');
    }

    final hasError = decoded['error']?.toString() == 'true';
    if (hasError) {
      throw PhpApiException(
        decoded['message']?.toString() ?? 'Unknown backend error.',
      );
    }

    return decoded;
  }

  Future<Map<String, dynamic>> uploadProfileImage({
    required String userId,
    required String imagePath,
  }) async {
    if (!BackendConfig.isConfigured) {
      throw const PhpApiException('Backend URL is not configured yet.');
    }

    final request = http.MultipartRequest(
      'POST',
      Uri.parse(BackendConfig.apiUrl),
    )
      ..headers['Authorization'] = _baseHeaders['Authorization']!
      ..fields['access_key'] = BackendConfig.accessKey
      ..fields['upload_profile_image'] = '1'
      ..fields['user_id'] = userId
      ..files.add(await http.MultipartFile.fromPath('image', imagePath));

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw PhpApiException(
        'Upload failed with status ${response.statusCode}.',
      );
    }

    final decoded = jsonDecode(response.body);
    if (decoded is! Map<String, dynamic>) {
      throw const PhpApiException('Unexpected upload response format.');
    }

    final hasError = decoded['error']?.toString() == 'true';
    if (hasError) {
      throw PhpApiException(
        decoded['message']?.toString() ?? 'Unknown upload error.',
      );
    }

    return decoded;
  }

  Future<void> updateFcmId({
    required String userId,
    required String fcmId,
  }) async {
    await post({
      'update_fcm_id': '1',
      'user_id': userId,
      'fcm_id': fcmId,
    });
  }
}

class PhpApiException implements Exception {
  const PhpApiException(this.message);

  final String message;

  @override
  String toString() => 'PhpApiException: $message';
}
