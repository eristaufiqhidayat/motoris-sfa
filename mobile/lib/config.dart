/// Alamat API Laravel. Emulator Android memakai 10.0.2.2 untuk localhost komputer.
/// Ganti saat build: flutter run --dart-define=API_URL=https://erp.contoh.co.id/api
const String apiUrl = String.fromEnvironment('API_URL', defaultValue: 'http://10.0.2.2:8000/api');
