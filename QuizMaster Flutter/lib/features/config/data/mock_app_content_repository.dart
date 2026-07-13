import '../models/app_document.dart';
import 'app_content_repository.dart';

class MockAppContentRepository implements AppContentRepository {
  const MockAppContentRepository();

  @override
  Future<AppDocument> fetchAboutUs(String languageCode) async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
    return AppDocument(
      title: languageCode == 'es' ? 'Sobre nosotros' : 'About Us',
      content: languageCode == 'es'
          ? 'El contenido de Sobre nosotros se cargara desde el panel admin.'
          : 'About Us content will be loaded from the admin panel.',
    );
  }

  @override
  Future<AppDocument> fetchInstructions(String languageCode) async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
    return AppDocument(
      title: languageCode == 'es' ? 'Instrucciones' : 'Instructions',
      content: languageCode == 'es'
          ? 'Las instrucciones se cargaran desde el panel admin.'
          : 'Instructions content will be loaded from the admin panel.',
    );
  }

  @override
  Future<AppDocument> fetchPrivacyPolicy(String languageCode) async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
    return AppDocument(
      title: languageCode == 'es' ? 'Politica de privacidad' : 'Privacy Policy',
      content: languageCode == 'es'
          ? 'La politica de privacidad se cargara desde el panel admin.'
          : 'Privacy policy content will be loaded from the admin panel.',
    );
  }

  @override
  Future<AppDocument> fetchTermsOfService(String languageCode) async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
    return AppDocument(
      title:
          languageCode == 'es' ? 'Terminos del servicio' : 'Terms of Service',
      content: languageCode == 'es'
          ? 'Los terminos y condiciones se cargaran desde el panel admin.'
          : 'Terms and conditions content will be loaded from the admin panel.',
    );
  }
}
