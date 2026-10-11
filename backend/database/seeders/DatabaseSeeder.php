<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\Galon;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\SalesRoute;
use App\Models\User;
use App\Services\LoadingService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh Depo Bekasi Timur, sama dengan mockup.
 * Semua akun memakai kata sandi: password
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $depot = Depot::create(['kode' => 'BKS-T', 'nama' => 'Depo Bekasi Timur', 'alamat' => 'Jl. Raya Narogong km 5, Bekasi']);

        $gudang = Location::create(['depot_id' => $depot->id, 'tipe' => Location::GUDANG, 'kode' => 'BKS-T-GDG', 'nama' => 'Gudang Depo Bekasi Timur']);
        Location::create(['depot_id' => $depot->id, 'tipe' => Location::KARANTINA, 'kode' => 'BKS-T-KRT', 'nama' => 'Zona Karantina']);
        $motorRudi = Location::create(['depot_id' => $depot->id, 'tipe' => Location::KENDARAAN, 'kode' => 'B4521KZE', 'nama' => 'Motor M-03 (B 4521 KZE)']);
        $motorJoko = Location::create(['depot_id' => $depot->id, 'tipe' => Location::KENDARAAN, 'kode' => 'B3310KZF', 'nama' => 'Motor M-05 (B 3310 KZF)']);

        $admin = User::create(['name' => 'Dewi Admin Depo', 'email' => 'admin@oasis.test', 'password' => 'password', 'role' => 'admin', 'depot_id' => $depot->id]);
        User::create(['name' => 'Nanda Gudang', 'email' => 'gudang@oasis.test', 'password' => 'password', 'role' => 'gudang', 'depot_id' => $depot->id]);
        $rudi = User::create(['name' => 'Rudi Hartono', 'email' => 'rudi@oasis.test', 'password' => 'password', 'role' => 'sales', 'kode' => 'M-03', 'depot_id' => $depot->id]);
        $joko = User::create(['name' => 'Joko Susilo', 'email' => 'joko@oasis.test', 'password' => 'password', 'role' => 'sales', 'kode' => 'M-05', 'depot_id' => $depot->id]);

        $p = collect([
            ['kode' => 'GLN19', 'nama' => 'Galon 19 L', 'satuan' => 'galon', 'harga' => 20000, 'deposit' => 50000, 'returnable' => true, 'min_stok' => 1800, 'max_stok' => 3200],
            ['kode' => 'B600', 'nama' => 'Botol 600 ml', 'satuan' => 'dus 24', 'harga' => 52000, 'min_stok' => 300, 'max_stok' => 800],
            ['kode' => 'B1500', 'nama' => 'Botol 1.500 ml', 'satuan' => 'dus 12', 'harga' => 58000, 'min_stok' => 250, 'max_stok' => 600],
            ['kode' => 'CUP220', 'nama' => 'Cup 220 ml', 'satuan' => 'dus 48', 'harga' => 30000, 'min_stok' => 150, 'max_stok' => 400],
        ])->mapWithKeys(fn ($row) => [$row['kode'] => Product::create($row)]);

        $r12 = SalesRoute::create(['depot_id' => $depot->id, 'kode' => 'R-12', 'nama' => 'Bekasi Timur', 'user_id' => $rudi->id, 'vehicle_location_id' => $motorRudi->id]);
        SalesRoute::create(['depot_id' => $depot->id, 'kode' => 'R-14', 'nama' => 'Tambun', 'user_id' => $joko->id, 'vehicle_location_id' => $motorJoko->id]);

        $outlets = [
            ['Toko Berkah Jaya', 'agen', 'Jl. Raya Mustikajaya 41', -6.2961, 107.0236, 15000000, 3200000, 40, ['GLN19' => 60, 'B600' => 20]],
            ['Warung Bu Siti', 'warung', 'Gg. Melati 3, Margahayu', -6.2603, 107.0052, 0, 0, 6, ['GLN19' => 6, 'B600' => 3]],
            ['Kios Pak Darto', 'warung', 'Jl. Cut Mutia 12', -6.2477, 107.0098, 0, 0, 4, ['GLN19' => 5, 'B600' => 2]],
            ['Depot Segar Abadi', 'depot', 'Jl. Pengasinan 7', -6.2850, 107.0170, 2000000, 240000, 10, ['GLN19' => 10]],
            ['Minimarket Sejahtera', 'modern', 'Jl. Pramuka 3', -6.2512, 106.9990, 8000000, 2100000, 0, ['B600' => 40, 'B1500' => 20, 'CUP220' => 12]],
            ['Toko Grosir Abadi', 'grosir', 'Pasar Baru Bekasi Blok C', -6.2380, 107.0010, 6000000, 5400000, 30, ['GLN19' => 40, 'B600' => 25, 'B1500' => 10, 'CUP220' => 20]],
            ['Klinik Pratama Medika', 'kantor', 'Jl. Ir. H. Juanda 88', -6.2420, 107.0035, 1500000, 0, 6, ['GLN19' => 6]],
            ['Warung Mak Ijah', 'warung', 'Jl. Swatantra 2', -6.2905, 107.0201, 0, 0, 3, ['GLN19' => 3, 'CUP220' => 2]],
        ];

        $serial = 418000;
        $galonRows = [];
        foreach ($outlets as $i => [$nama, $tipe, $alamat, $lat, $lng, $limit, $piutang, $pinjam, $rata]) {
            $outlet = Outlet::create([
                'depot_id' => $depot->id, 'sales_route_id' => $r12->id, 'urutan' => $i + 1,
                'kode' => sprintf('OT-%04d', 1201 + $i), 'nama' => $nama, 'tipe' => $tipe, 'alamat' => $alamat,
                'lat' => $lat, 'lng' => $lng, 'limit_kredit' => $limit, 'piutang' => $piutang,
                'galon_dipinjam' => $pinjam, 'rata_jual_mingguan' => $rata,
            ]);
            for ($k = 0; $k < $pinjam; $k++) {
                $galonRows[] = $this->galonRow('GLN-19L-'.$serial++, null, $outlet->id, 'isi');
            }
        }

        // Galon berseri contoh di gudang. Saldo stok gudang lebih besar; sisanya belum dilabel QR.
        for ($k = 0; $k < 400; $k++) {
            $galonRows[] = $this->galonRow('GLN-19L-'.$serial++, $gudang->id, null, 'isi');
        }
        for ($k = 0; $k < 120; $k++) {
            $galonRows[] = $this->galonRow('GLN-19L-'.$serial++, $gudang->id, null, 'kosong');
        }
        foreach (array_chunk($galonRows, 200) as $chunk) {
            Galon::insert($chunk);
        }
        Galon::where('serial', 'GLN-19L-418120')->update(['siklus' => 58]); // contoh mendekati batas siklus

        $stock = app(StockService::class);
        DB::transaction(function () use ($stock, $p, $gudang, $admin) {
            $stock->move($p['GLN19'], 2480, null, $gudang, 'saldo_awal', 'SALDO-AWAL', user: $admin);
            $stock->move($p['GLN19'], 1312, null, $gudang, 'saldo_awal', 'SALDO-AWAL', 'kosong', $admin);
            $stock->move($p['B600'], 410, null, $gudang, 'saldo_awal', 'SALDO-AWAL', user: $admin);
            $stock->move($p['B1500'], 212, null, $gudang, 'saldo_awal', 'SALDO-AWAL', user: $admin);
            $stock->move($p['CUP220'], 96, null, $gudang, 'saldo_awal', 'SALDO-AWAL', user: $admin);
        });

        GoodsReceipt::create(['no' => 'SJ-PLT-2610-0884', 'depot_id' => $depot->id, 'sumber' => 'Pabrik Cibinong', 'batch' => 'B-2610-121', 'product_id' => $p['GLN19']->id, 'qty_kirim' => 480]);

        // Muat pagi untuk Rudi supaya aplikasi langsung bisa dipakai jual.
        app(LoadingService::class)->muat($r12, [$p['GLN19']->id => 30, $p['B600']->id => 10], $admin);
    }

    private function galonRow(string $serial, ?int $locationId, ?int $outletId, string $kondisi): array
    {
        $siklus = random_int(3, 50);

        return [
            'serial' => $serial,
            'siklus' => $siklus,
            'tanggal_produksi' => now()->subDays($siklus * 12 + random_int(0, 60))->toDateString(),
            'kondisi' => $kondisi,
            'location_id' => $locationId,
            'outlet_id' => $outletId,
            'last_moved_at' => now()->subDays(random_int(1, 20)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
