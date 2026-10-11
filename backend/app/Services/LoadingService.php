<?php

namespace App\Services;

use App\Models\Loading;
use App\Models\Product;
use App\Models\SalesRoute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Muat pagi (gudang → kendaraan), setoran sore, dan penerimaan setoran di gudang. */
class LoadingService
{
    public function __construct(private StockService $stock, private GalonService $galon) {}

    /** @param  array<int,int>  $items  product_id => qty */
    public function muat(SalesRoute $route, array $items, User $admin): Loading
    {
        return DB::transaction(function () use ($route, $items, $admin) {
            if (Loading::where('user_id', $route->user_id)->whereDate('tanggal', today())->where('status', '!=', 'selesai')->exists()) {
                throw ValidationException::withMessages(['loading' => 'Sales ini masih punya muatan yang belum disetor.']);
            }

            $items = array_filter(array_map('intval', $items));
            $gudang = $route->vehicle->depot->gudang();
            $loading = Loading::create([
                'no' => Nomor::next('MT', 'loadings'),
                'user_id' => $route->user_id,
                'sales_route_id' => $route->id,
                'vehicle_location_id' => $route->vehicle_location_id,
                'tanggal' => today(),
                'muat' => $items,
                'status' => 'jalan',
            ]);

            foreach ($items as $productId => $qty) {
                $product = Product::findOrFail($productId);
                $this->stock->move($product, $qty, $gudang, $route->vehicle, 'muat', $loading->no, user: $admin, keterangan: $route->kode);
                if ($product->returnable) {
                    $this->galon->pindah($gudang, $route->vehicle, $qty, 'isi', 'Dimuat ke kendaraan', $loading->no);
                }
            }

            return $loading;
        });
    }

    public function setor(Loading $loading, User $user): Loading
    {
        if ($loading->user_id !== $user->id) {
            abort(403);
        }
        if ($loading->status !== 'jalan') {
            throw ValidationException::withMessages(['loading' => 'Setoran sudah dikirim.']);
        }

        $loading->update([
            'status' => 'setor',
            'setor_tunai' => $loading->uang()['tunai'],
            'setor_at' => now(),
        ]);

        return $loading;
    }

    /**
     * Gudang menghitung fisik sisa barang di kendaraan. Semua yang dihitung
     * dikembalikan ke gudang; selisih terhadap sistem dicatat.
     *
     * @param  array<string,int>  $fisik  "product_id:kondisi" => qty dihitung
     */
    public function terima(Loading $loading, User $admin, array $fisik = []): Loading
    {
        return DB::transaction(function () use ($loading, $admin, $fisik) {
            if ($loading->status !== 'setor') {
                throw ValidationException::withMessages(['loading' => 'Setoran belum dikirim atau sudah diterima.']);
            }

            $vehicle = $loading->vehicle;
            $gudang = $vehicle->depot->gudang();
            $selisih = [];

            foreach ($vehicle->balances()->with('product')->where('qty', '>', 0)->get() as $balance) {
                $key = $balance->product_id.':'.$balance->kondisi;
                $dihitung = array_key_exists($key, $fisik) ? (int) $fisik[$key] : $balance->qty;

                if ($dihitung !== $balance->qty) {
                    $selisih[] = ['produk' => $balance->product->nama, 'kondisi' => $balance->kondisi, 'sistem' => $balance->qty, 'fisik' => $dihitung];
                    $this->stock->adjust($vehicle, $balance->product, $balance->kondisi, $dihitung - $balance->qty, $loading->no, $admin, 'Selisih setoran');
                }

                $this->stock->move($balance->product, $dihitung, $vehicle, $gudang, 'setoran', $loading->no, $balance->kondisi, $admin);
                if ($balance->product->returnable) {
                    $this->galon->pindah($vehicle, $gudang, $dihitung, $balance->kondisi, 'Kembali ke gudang (setoran)', $loading->no);
                }
            }

            $loading->update([
                'status' => 'selesai',
                'diterima_at' => now(),
                'diterima_oleh' => $admin->id,
                'selisih' => $selisih,
            ]);

            return $loading;
        });
    }
}
