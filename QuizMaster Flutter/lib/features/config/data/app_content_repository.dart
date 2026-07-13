import '../models/app_document.dart';

abstract class AppContentRepository {
  Future<AppDocument> fetchAboutUs(String languageCode);
  Future<AppDocument> fetchInstructions(String languageCode);
  Future<AppDocument> fetchPrivacyPolicy(String languageCode);
  Future<AppDocument> fetchTermsOfService(String languageCode);
}
