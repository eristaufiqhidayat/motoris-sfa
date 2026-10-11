# Oasis ERP · Inventori depo + API sales

Backend Laravel untuk distributor air galon (AMDK 19 L):

- **Web back-office inventori** untuk admin depo dan staf gudang: stok depo, galon tracker (nomor seri & siklus), penerimaan dari pabrik, kirim galon kosong ke pabrik, stock opname, muat & setoran sales, dan persetujuan sales order.
- **API (Laravel Sanctum)** untuk aplikasi Flutter **Oasis Sales**, yang menggabungkan motoris (jual langsung dari motor) dan SFA (ambil order, dikirim kemudian).

## Menjalankan

Butuh PHP 8.3+ dan Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # default SQLite; ganti DB_* di .env untuk MySQL/PostgreSQL
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Buka http://localhost:8000 dan masuk.

| Akun | Peran | Dipakai di |
|---|---|---|
| admin@oasis.test | Admin depo | Web |
| gudang@oasis.test | Staf gudang | Web |
| rudi@oasis.test | Sales M-03, rute R-12 (sudah dimuat 30 galon + 10 dus) | Aplikasi Oasis Sales |
| joko@oasis.test | Sales M-05, rute R-14 (belum dimuat) | Aplikasi Oasis Sales |

Semua kata sandi: `password`.

Tes: `php artisan test`

## Alur yang tercakup

1. **Muat pagi** (web, Muat & setoran): gudang → kendaraan sales. Galon berseri ikut berpindah custody.
2. **Kunjungan** (aplikasi): check-in dengan GPS (maks. 150 m dari outlet), catat sisa stok toko, sistem memberi saran order (rata-rata jual mingguan − sisa).
3. **Jual langsung / canvas**: stok motor berkurang, galon kosong ditarik masuk ke motor, deposit galon baru dihitung, piutang bertambah bila tempo (dicek terhadap limit kredit). Nomor seri galon pindah ke outlet.
4. **Ambil order / taking order**: order masuk ke Sales order; bila melewati limit kredit statusnya *menunggu approval* dan disetujui/ditolak di web.
5. **Setoran sore**: sales mengirim setoran dari aplikasi; gudang menghitung fisik sisa barang dan menerima. Selisih tercatat dan disesuaikan.
6. **Gudang**: terima surat jalan pabrik (yang ditolak masuk zona karantina), kirim galon kosong ke pabrik, stock opname dengan penyebab selisih.

Semua perubahan stok lewat `App\Services\StockService`, jadi setiap saldo punya baris kartu stok (`stock_movements`).

## Struktur penting

```
app/Services/        StockService, GalonService, SalesService, LoadingService, WarehouseService
app/Http/Controllers/Api/   endpoint aplikasi sales
app/Http/Controllers/Web/   halaman web inventori
database/migrations/2026_10_11_1000*   tabel domain
database/seeders/DatabaseSeeder.php    data contoh Depo Bekasi Timur
tests/Feature/SalesFlowTest.php        tes alur ujung ke ujung
```

## API ringkas (`/api`, header `Authorization: Bearer <token>`)

| Metode | Path | Fungsi |
|---|---|---|
| POST | /login | `{email, password}` → token (hanya akun peran sales) |
| GET | /today | rute, status muat, stok kendaraan, uang, daftar outlet + status kunjungan |
| GET | /products | katalog & harga |
| GET | /outlets/{id} | detail outlet + riwayat order |
| POST | /visits | check-in `{outlet_id, lat, lng}` |
| POST | /visits/{id}/stok-outlet | `{stok: {product_id: sisa}}` → saran order |
| POST | /visits/{id}/tutup | `{status: tutup \| tanpa_order}` |
| POST | /orders/canvas | jual langsung `{visit_id, items[{product_id, qty}], galon_kosong, metode_bayar, serial_keluar?, serial_masuk?}` |
| POST | /orders/taking | ambil order `{visit_id, items[...]}` |
| GET/POST | /setoran | lihat / kirim setoran hari ini |

Pesan error mengikuti format validasi Laravel (`422` + `errors`), dalam bahasa Indonesia.

## Belum dibuat (langkah berikutnya)

- Scan QR galon di aplikasi (API sudah menerima `serial_keluar` / `serial_masuk`; tanpa itu sistem memilih galon FIFO).
- Pengiriman taking order lewat truk (DO, muat truk, serah terima driver).
- Finance: jurnal otomatis, pelunasan piutang, verifikasi kasir untuk setoran tunai.
- Hak akses per depo yang lebih rinci, mode offline aplikasi, dan laporan.
