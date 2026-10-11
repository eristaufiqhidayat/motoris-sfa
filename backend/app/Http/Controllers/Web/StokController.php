<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Loading;
use App\Models\Location;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $depot = $request->user()->depot;
        $gudang = $depot->gudang();
        $kendaraan = $depot->locations()->where('tipe', Location::KENDARAAN)->pluck('id');
        $products = Product::orderBy('id')->get();

        $sum = fn ($locIds, $productId, $kondisi) => (int) StockBalance::whereIn('location_id', (array) $locIds)
            ->where('product_id', $productId)->where('kondisi', $kondisi)->sum('qty');

        // Rata-rata penjualan harian 7 hari terakhir untuk perkiraan "cukup berapa hari".
        $jual7 = StockMovement::where('jenis', 'jual')->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('product_id, sum(qty) as q')->groupBy('product_id')->pluck('q', 'product_id');

        $rows = $products->map(function (Product $p) use ($sum, $gudang, $kendaraan, $jual7) {
            $g = $sum($gudang->id, $p->id, 'isi');
            $harian = ($jual7[$p->id] ?? 0) / 7;

            return [
                'product' => $p,
                'gudang' => $g,
                'jalan' => $sum($kendaraan->all(), $p->id, 'isi'),
                'kosong' => $p->returnable ? $sum($gudang->id, $p->id, 'kosong') : null,
                'cukup_hari' => $harian > 0 ? round($g / $harian, 1) : null,
                'status' => $g < $p->min_stok * 0.7 ? 'kritis' : ($g < $p->min_stok ? 'rendah' : 'aman'),
            ];
        });

        $alerts = [];
        foreach (GoodsReceipt::where('status', 'transit')->get() as $gr) {
            $alerts[] = ['info', "Kiriman {$gr->no} dalam perjalanan", "{$gr->qty_kirim} {$gr->product->satuan} dari {$gr->sumber}.", route('penerimaan')];
        }
        foreach (Loading::where('status', 'setor')->with('user')->get() as $l) {
            $alerts[] = ['info', "Setoran {$l->user->kode} {$l->user->name} menunggu dicek", 'Hitung fisik sisa barang lalu terima.', route('muat')];
        }
        $pending = SalesOrder::where('status', 'menunggu_approval')->count();
        if ($pending) {
            $alerts[] = ['warn', "{$pending} order menunggu persetujuan", 'Order melewati limit kredit outlet.', route('order')];
        }
        foreach ($rows->where('status', '!=', 'aman') as $r) {
            $alerts[] = [$r['status'] === 'kritis' ? 'bad' : 'warn', "{$r['product']->nama} ".($r['status'] === 'kritis' ? 'kritis' : 'di bawah stok minimum'), "{$r['gudang']} {$r['product']->satuan}, minimum {$r['product']->min_stok}.", null];
        }

        $movements = StockMovement::with(['product', 'from', 'to', 'user'])->latest('id')->limit(25)->get();

        return view('stok.index', compact('rows', 'alerts', 'movements', 'depot'));
    }
}
