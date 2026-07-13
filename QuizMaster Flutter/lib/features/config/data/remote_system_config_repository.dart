import '../../../core/network/php_api_client.dart';
import '../models/system_config.dart';
import 'mock_system_config_repository.dart';

class RemoteSystemConfigRepository implements SystemConfigRepository {
  const RemoteSystemConfigRepository({
    required this.apiClient,
  });

  final PhpApiClient apiClient;

  @override
  Future<SystemConfig> fetchSystemConfig() async {
    final response = await apiClient.post(const {
      'get_system_configurations': '1',
    });

    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      throw const PhpApiException('Invalid system configuration payload.');
    }

    return SystemConfig.fromApi(data);
  }
}
