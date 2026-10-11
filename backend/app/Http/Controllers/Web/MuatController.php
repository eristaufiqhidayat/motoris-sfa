<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Loading;
use App\Models\Product;
use App\Models\SalesRoute;
use App\Services\LoadingService;
use Illuminate\Http\Request;

class MuatController extends Controller
{
    public function __construct(private LoadingService $loadings) {}

    public function index(Request $request)
    {
        $depot = $request->user()->depot;
        $routes = SalesRoute::where('depot_id', $depot->id)->with(['user', 'vehicle'])->get();
        $today = Loading::whereDate('tanggal', today())->with(['user', 'route', 'vehicle.balances.product'])->latest('id')->get();
        $products = Product::orderBy('id')->get();
        $sibuk = $today->where('status', '!=', 'selesai')->pluck('user_id')->all();

        return view('muat.index', compact('routes', 'today', 'products', 'sibuk'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_route_id' => ['required', 'exists:sales_routes,id'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0'],
        ]);
        $loading = $this->loadings->muat(SalesRoute::findOrFail($data['sales_route_id']), $data['items'], $request->user());

        return back()->with('ok', "Muat {$loading->no} tercatat.");
    }

    public function terima(Request $request, Loading $loading)
    {
        $data = $request->validate(['fisik' => ['array'], 'fisik.*' => ['integer', 'min:0']]);
        $loading = $this->loadings->terima($loading, $request->user(), $data['fisik'] ?? []);
        $msg = $loading->selisih ? 'Setoran diterima dengan selisih '.count($loading->selisih).' baris.' : 'Setoran diterima, fisik cocok dengan sistem.';

        return back()->with('ok', $msg);
    }
}
