import '../../../core/network/php_api_client.dart';
import '../models/app_document.dart';
import 'app_content_repository.dart';

class RemoteAppContentRepository implements AppContentRepository {
  const RemoteAppContentRepository({
    required this.apiClient,
  });

  final PhpApiClient apiClient;

  String _title(String languageCode, String english, String spanish) {
    return languageCode == 'es' ? spanish : english;
  }

  @override
  Future<AppDocument> fetchAboutUs(String languageCode) async {
    final response = await apiClient.post({
      'get_about_us': '1',
      'language_code': languageCode,
    });
    return AppDocument(
      title: _title(languageCode, 'About Us', 'Sobre nosotros'),
      content: response['data']?.toString().trim() ?? '',
    );
  }

  @override
  Future<AppDocument> fetchInstructions(String languageCode) async {
    final response = await apiClient.post({
      'get_instructions': '1',
      'language_code': languageCode,
    });
    return AppDocument(
      title: _title(languageCode, 'Instructions', 'Instrucciones'),
      content: response['data']?.toString().trim() ?? '',
    );
  }

  @override
  Future<AppDocument> fetchPrivacyPolicy(String languageCode) async {
    final response = await apiClient.post({
      'privacy_policy_settings': '1',
      'language_code': languageCode,
    });
    return AppDocument(
      title: _title(languageCode, 'Privacy Policy', 'Politica de privacidad'),
      content: response['data']?.toString().trim() ?? '',
    );
  }

  @override
  Future<AppDocument> fetchTermsOfService(String languageCode) async {
    final response = await apiClient.post({
      'get_terms_conditions_settings': '1',
      'language_code': languageCode,
    });
    return AppDocument(
      title: _title(languageCode, 'Terms of Service', 'Terminos del servicio'),
      content: response['data']?.toString().trim() ?? '',
    );
  }
}
