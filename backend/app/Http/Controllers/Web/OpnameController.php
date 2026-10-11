<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class OpnameController extends Controller
{
    public function __construct(private WarehouseService $warehouse) {}

    public function index()
    {
        $opnames = StockOpname::with(['items', 'location'])->latest('id')->limit(20)->get();

        return view('opname.index', compact('opnames'));
    }

    public function store(Request $request)
    {
        $opname = $this->warehouse->buatOpname($request->user()->depot->gudang(), $request->user());

        return redirect()->route('opname.show', $opname);
    }

    public function show(StockOpname $opname)
    {
        $opname->load('items.product', 'location');

        return view('opname.show', compact('opname'));
    }

    public function post(Request $request, StockOpname $opname)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.qty_fisik' => ['required', 'integer', 'min:0'],
            'items.*.penyebab' => ['nullable', 'string', 'max:255'],
        ]);
        $this->warehouse->postOpname($opname, $data['items'], $request->user());

        return redirect()->route('opname.show', $opname)->with('ok', "{$opname->no} diposting. Stok disesuaikan.");
    }
}
