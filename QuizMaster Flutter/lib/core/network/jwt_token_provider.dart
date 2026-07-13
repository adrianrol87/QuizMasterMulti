import 'dart:convert';

import 'package:crypto/crypto.dart';

import '../config/backend_config.dart';

class JwtTokenProvider {
  const JwtTokenProvider._();

  static String generateToken() {
    final now = DateTime.now().millisecondsSinceEpoch ~/ 1000;
    final payload = <String, dynamic>{
      'iat': now,
      'iss': 'quiz',
      'exp': now + (30 * 60 * 60),
      'sub': 'quiz Authentication',
    };

    const header = <String, dynamic>{
      'typ': 'JWT',
      'alg': 'HS256',
    };

    final headerPart = _base64UrlEncode(jsonEncode(header));
    final payloadPart = _base64UrlEncode(jsonEncode(payload));
    final content = '$headerPart.$payloadPart';
    final signature = Hmac(
      sha256,
      utf8.encode(BackendConfig.jwtSecretKey),
    ).convert(utf8.encode(content));

    final signaturePart = base64Url.encode(signature.bytes).replaceAll('=', '');
    return '$content.$signaturePart';
  }

  static String _base64UrlEncode(String value) {
    return base64Url.encode(utf8.encode(value)).replaceAll('=', '');
  }
}
