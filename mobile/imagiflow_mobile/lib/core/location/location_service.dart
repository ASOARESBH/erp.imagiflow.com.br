import 'package:geolocator/geolocator.dart';

import '../api/imagiflow_api_client.dart';

class LocationService {
  LocationService(this._api);
  final ImagiFlowApiClient _api;

  Future<void> sendPoint({
    required String context,
    String? referenceTable,
    int? referenceId,
  }) async {
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) permission = await Geolocator.requestPermission();
    if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
      throw ApiFailure('A localização é necessária apenas para registrar esta atividade de campo. Você pode habilitá-la nas configurações do dispositivo.');
    }
    if (!await Geolocator.isLocationServiceEnabled()) {
      throw ApiFailure('Ative os serviços de localização do dispositivo para registrar este ponto.');
    }
    final position = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.high));
    await _api.post('/api/mobile/v1/localizacoes', data: {
      'latitude': position.latitude,
      'longitude': position.longitude,
      'accuracy_meters': position.accuracy,
      'captured_at': position.timestamp?.toIso8601String(),
      'contexto': context,
      if (referenceTable != null) 'referencia_tabela': referenceTable,
      if (referenceId != null) 'referencia_id': referenceId,
    });
  }
}
