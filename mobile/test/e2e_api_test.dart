// Tes ujung ke ujung terhadap server Laravel yang sedang jalan.
// Jalankan: php artisan migrate:fresh --seed && php artisan serve
//           flutter test test/e2e_api_test.dart --dart-define=E2E_API=http://127.0.0.1:8000/api
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:oasis_sales/api/api_client.dart';
import 'package:oasis_sales/state/app_state.dart';
import 'package:shared_preferences/shared_preferences.dart';

const e2e = String.fromEnvironment('E2E_API');

void main() {
  setUpAll(() {
    HttpOverrides.global = null; // izinkan HTTP sungguhan di flutter test
    SharedPreferences.setMockInitialValues({});
  });

  test('login, jual langsung, ambil order, setoran', () async {
    final app = AppState(ApiClient(baseUrl: e2e));
    await app.login('rudi@oasis.test', 'password');
    expect(app.today!.ruteKode, 'R-12');
    final galon = app.galon!;
    final awal = app.today!.stokIsi(galon.id);

    // Motoris: jual langsung di Depot Segar Abadi.
    final depot = app.today!.outlets.firstWhere((o) => o.nama == 'Depot Segar Abadi');
    final v1 = await app.checkin(depot, lat: depot.lat, lng: depot.lng);
    final so1 = await app.jualLangsung(visitId: v1.id, items: {galon.id: 4}, galonKosong: 3, metodeBayar: 'tunai');
    expect(so1.status, 'selesai');
    expect(so1.total, 4 * 20000 + 50000);
    expect(app.today!.stokIsi(galon.id), awal - 4);
    expect(app.today!.uang['tunai'], so1.total);

    // SFA: ambil order di grosir yang hampir penuh limit kreditnya.
    final grosir = app.today!.outlets.firstWhere((o) => o.nama == 'Toko Grosir Abadi');
    final v2 = await app.checkin(grosir, lat: grosir.lat, lng: grosir.lng);
    final saran = await app.simpanStokOutlet(v2.id, {galon.id: 10});
    expect(saran.saranOrder[galon.id], 30);
    final so2 = await app.ambilOrder(visitId: v2.id, items: saran.saranOrder);
    expect(so2.status, 'menunggu_approval');

    // Tempo di warung tanpa limit ditolak dengan pesan dari server.
    final warung = app.today!.outlets.firstWhere((o) => o.nama == 'Warung Bu Siti');
    final v3 = await app.checkin(warung);
    await expectLater(
      app.jualLangsung(visitId: v3.id, items: {galon.id: 1}, galonKosong: 1, metodeBayar: 'tempo'),
      throwsA(isA<ApiException>().having((e) => e.message, 'message', contains('limit kredit'))),
    );
    await app.tutupKunjungan(v3.id, 'tanpa_order');

    final setoran = await app.kirimSetoran();
    expect(setoran.status, 'setor');
    expect(app.today!.bisaJualLangsung, isFalse);
  }, skip: e2e.isEmpty ? 'Set --dart-define=E2E_API untuk menjalankan' : false);
}
