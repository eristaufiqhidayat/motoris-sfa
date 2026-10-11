# Motoris & SFA · Oasis

Sistem penjualan lapangan dan inventori depo untuk distributor air minum galon 19 L.

| Folder | Isi |
|---|---|
| [`backend/`](backend/README.md) | Laravel 13: web inventori depo (stok, galon tracker, penerimaan pabrik, stock opname, muat & setoran, approval order) dan API untuk aplikasi |
| [`mobile/`](mobile/README.md) | Flutter: aplikasi **Oasis Sales**, motoris (jual langsung dari motor) dan SFA (ambil order) dalam satu aplikasi |

## Mulai cepat

```bash
# Backend
cd backend
composer install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000     # web: admin@oasis.test / password

# Aplikasi (Flutter 3.41+)
cd ../mobile
flutter pub get
flutter run --dart-define=API_URL=http://<ip-komputer>:8000/api   # sales: rudi@oasis.test / password
```

Data contoh: Depo Bekasi Timur, rute R-12 dengan 8 outlet, sales Rudi (M-03) sudah dimuat 30 galon dan 10 dus 600 ml.
