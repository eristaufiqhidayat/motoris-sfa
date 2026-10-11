import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../api/api_client.dart';
import '../models/models.dart';

/// State aplikasi: sesi login, data hari ini, dan katalog produk.
class AppState extends ChangeNotifier {
  AppState(this.api);

  final ApiClient api;
  static const _tokenKey = 'token';

  bool booting = true;
  Map<String, dynamic>? user;
  Today? today;
  List<Product> products = [];
  String? error;
  bool loading = false;

  bool get loggedIn => api.token != null && user != null;

  Future<void> boot() async {
    final prefs = await SharedPreferences.getInstance();
    api.token = prefs.getString(_tokenKey);
    if (api.token != null) {
      try {
        user = (await api.get('/me'))['user'];
        await refresh();
      } on ApiException {
        api.token = null;
        await prefs.remove(_tokenKey);
      }
    }
    booting = false;
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    final res = await api.post('/login', {'email': email, 'password': password, 'device': 'oasis-sales'});
    api.token = res['token'];
    user = res['user'];
    (await SharedPreferences.getInstance()).setString(_tokenKey, api.token!);
    await refresh();
  }

  Future<void> logout() async {
    try {
      await api.post('/logout');
    } on ApiException {
      // Token mungkin sudah tidak berlaku; tetap keluar di perangkat.
    }
    api.token = null;
    user = null;
    today = null;
    (await SharedPreferences.getInstance()).remove(_tokenKey);
    notifyListeners();
  }

  Future<void> refresh() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      if (products.isEmpty) {
        products = (await api.get('/products') as List).map((e) => Product.fromJson(e)).toList();
      }
      today = Today.fromJson(await api.get('/today'));
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Product? get galon => products.where((p) => p.returnable).firstOrNull;
  Product productById(int id) => products.firstWhere((p) => p.id == id);

  // ---- kunjungan & order ----

  Future<VisitDetail> checkin(Outlet outlet, {double? lat, double? lng}) async =>
      VisitDetail.fromJson(await api.post('/visits', {'outlet_id': outlet.id, 'lat': lat, 'lng': lng}));

  Future<VisitDetail> simpanStokOutlet(int visitId, Map<int, int> stok) async => VisitDetail.fromJson(
      await api.post('/visits/$visitId/stok-outlet', {'stok': stok.map((k, v) => MapEntry('$k', v))}));

  Future<void> tutupKunjungan(int visitId, String status, {String? catatan}) async {
    await api.post('/visits/$visitId/tutup', {'status': status, 'catatan': catatan});
    await refresh();
  }

  Future<OrderResult> jualLangsung({required int visitId, required Map<int, int> items, required int galonKosong, required String metodeBayar}) async {
    final res = await api.post('/orders/canvas', {
      'visit_id': visitId,
      'items': _items(items),
      'galon_kosong': galonKosong,
      'metode_bayar': metodeBayar,
    });
    await refresh();
    return OrderResult.fromJson(res);
  }

  Future<OrderResult> ambilOrder({required int visitId, required Map<int, int> items, String? catatan}) async {
    final res = await api.post('/orders/taking', {'visit_id': visitId, 'items': _items(items), 'catatan': catatan});
    await refresh();
    return OrderResult.fromJson(res);
  }

  Future<Settlement> setoran() async => Settlement.fromJson(await api.get('/setoran'));

  Future<Settlement> kirimSetoran() async {
    final s = Settlement.fromJson(await api.post('/setoran'));
    await refresh();
    return s;
  }

  List<Map<String, int>> _items(Map<int, int> items) =>
      [for (final e in items.entries) if (e.value > 0) {'product_id': e.key, 'qty': e.value}];
}
