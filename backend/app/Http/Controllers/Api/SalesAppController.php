<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loading;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Visit;
use Illuminate\Http\Request;

/** Data untuk layar Beranda, Rute, dan detail outlet di aplikasi sales. */
class SalesAppController extends Controller
{
    public function today(Request $request)
    {
        $user = $request->user();
        $route = $user->salesRoute;
        $loading = Loading::where('user_id', $user->id)->whereDate('tanggal', today())->latest('id')->first();
        $visits = Visit::where('user_id', $user->id)->whereDate('tanggal', today())->with('order')->get()->keyBy('outlet_id');
        $outlets = $route ? $route->outlets()->get() : collect();

        return [
            'tanggal' => today()->toDateString(),
            'rute' => $route ? ['kode' => $route->kode, 'nama' => $route->nama] : null,
            'muat' => $loading ? [
                'no' => $loading->no,
                'status' => $loading->status, // jalan | setor | selesai
                'muat_awal' => $this->items($loading->muat),
            ] : null,
            'stok_kendaraan' => $route?->vehicle ? $this->vehicleStock($route->vehicle) : [],
            'uang' => $loading?->uang() ?? ['tunai' => 0, 'qris' => 0, 'tempo' => 0],
            'outlets' => $outlets->map(fn (Outlet $o) => $this->outletRow($o, $visits->get($o->id)))->values(),
        ];
    }

    public function products()
    {
        return Product::orderBy('id')->get()->map(fn (Product $p) => [
            'id' => $p->id,
            'kode' => $p->kode,
            'nama' => $p->nama,
            'satuan' => $p->satuan,
            'harga' => $p->harga,
            'deposit' => $p->deposit,
            'returnable' => $p->returnable,
        ]);
    }

    public function outlet(Request $request, Outlet $outlet)
    {
        $visit = Visit::where('user_id', $request->user()->id)->where('outlet_id', $outlet->id)->whereDate('tanggal', today())->latest('id')->first();
        $lastOrders = $outlet->hasMany(\App\Models\SalesOrder::class)->latest('id')->limit(3)->get(['no', 'tipe', 'total', 'status', 'created_at']);

        return $this->outletRow($outlet, $visit) + [
            'riwayat' => $lastOrders,
            'rata_jual_mingguan' => $outlet->rata_jual_mingguan ?? [],
        ];
    }

    public static function outletRow(Outlet $o, ?Visit $v): array
    {
        return [
            'id' => $o->id,
            'kode' => $o->kode,
            'nama' => $o->nama,
            'tipe' => $o->tipe,
            'alamat' => $o->alamat,
            'lat' => $o->lat,
            'lng' => $o->lng,
            'urutan' => $o->urutan,
            'limit_kredit' => $o->limit_kredit,
            'piutang' => $o->piutang,
            'galon_dipinjam' => $o->galon_dipinjam,
            'boleh_tempo' => $o->bolehTempo(),
            'kunjungan' => $v ? [
                'id' => $v->id,
                'status' => $v->status,
                'checkin_at' => $v->checkin_at?->toIso8601String(),
                'order' => $v->order ? ['no' => $v->order->no, 'tipe' => $v->order->tipe, 'total' => $v->order->total, 'status' => $v->order->status] : null,
            ] : null,
        ];
    }

    private function vehicleStock($vehicle): array
    {
        return $vehicle->balances()->with('product')->get()
            ->map(fn ($b) => ['product_id' => $b->product_id, 'kode' => $b->product->kode, 'nama' => $b->product->nama, 'kondisi' => $b->kondisi, 'qty' => $b->qty])
            ->values()->all();
    }

    private function items(array $map): array
    {
        $products = Product::whereIn('id', array_keys($map))->get()->keyBy('id');

        return collect($map)->map(fn ($qty, $id) => ['product_id' => (int) $id, 'nama' => $products[$id]->nama ?? '?', 'qty' => (int) $qty])->values()->all();
    }
}
