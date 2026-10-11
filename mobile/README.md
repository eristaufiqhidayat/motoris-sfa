# Oasis Sales (Flutter)

Satu aplikasi untuk sales lapangan yang menggabungkan:

- **Motoris / canvas**: jual langsung dari stok di motor, tarik galon kosong, terima tunai / QRIS / tempo.
- **SFA / taking order**: catat order yang dikirim kemudian dari gudang, dengan cek limit kredit.

Di setiap kunjungan sales memilih **Jual langsung** atau **Ambil order**. Backend-nya adalah proyek Laravel `oasis-erp`.

## Layar

1. **Masuk** dengan akun sales (contoh `rudi@oasis.test` / `password`).
2. **Beranda**: progres rute, stok di kendaraan, uang tunai/QRIS/tempo hari ini, tombol ke outlet berikutnya.
3. **Rute**: daftar outlet berurutan dengan status kunjungan dan nomor order.
4. **Kunjungan**: check-in GPS → isi sisa stok di toko → saran order → pilih Jual langsung / Ambil order → tagihan (deposit galon dihitung otomatis) → konfirmasi.
5. **Setoran**: sisa barang di kendaraan dan uang, lalu kirim setoran ke gudang.

## Menjalankan

Diuji dengan Flutter 3.41.6 (Dart 3.11) dan 3.47.7 (Dart 3.13). Jalankan backend Laravel dulu (`php artisan serve --host=0.0.0.0`).

```bash
flutter pub get
# Emulator Android: localhost komputer = 10.0.2.2 (nilai bawaan)
flutter run
# HP sungguhan / server lain:
flutter run --dart-define=API_URL=http://192.168.1.10:8000/api
```

Untuk rilis, pakai HTTPS dan hapus `android:usesCleartextTraffic="true"` di `android/app/src/main/AndroidManifest.xml`.

## Tes

```bash
flutter analyze
flutter test                                   # tes model
# Tes ujung ke ujung ke server Laravel yang berjalan dengan data seed baru:
flutter test test/e2e_api_test.dart --dart-define=E2E_API=http://127.0.0.1:8000/api
```

## Struktur

```
lib/config.dart              alamat API (--dart-define=API_URL)
lib/api/api_client.dart      HTTP + token Sanctum + pesan error dari Laravel
lib/models/models.dart       Product, Outlet, Today, VisitDetail, OrderResult, Settlement
lib/state/app_state.dart     sesi login, data hari ini, aksi kunjungan/order/setoran (Provider)
lib/screens/                 login, beranda, rute, kunjungan, setoran
lib/widgets/common.dart      kartu bagian, stepper jumlah, dll.
```
