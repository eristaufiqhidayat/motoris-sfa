<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class PenerimaanController extends Controller
{
    public function __construct(private WarehouseService $warehouse) {}

    public function index(Request $request)
    {
        $receipts = GoodsReceipt::with('product')->latest('id')->limit(30)->get();
        $kosong = $request->user()->depot->gudang()->qty(Product::galon(), 'kosong');

        return view('penerimaan.index', compact('receipts', 'kosong'));
    }

    public function terima(Request $request, GoodsReceipt $receipt)
    {
        $data = $request->validate(['qty_tolak' => ['required', 'integer', 'min:0']]);
        $this->warehouse->terimaPabrik($receipt, $data['qty_tolak'], $request->user());

        return back()->with('ok', "{$receipt->no} diterima: {$receipt->qty_terima} masuk gudang, {$receipt->qty_tolak} ke karantina.");
    }

    public function kirimKosong(Request $request)
    {
        $data = $request->validate(['qty' => ['required', 'integer', 'min:1']]);
        $no = $this->warehouse->kirimKosongKePabrik($request->user()->depot, $data['qty'], $request->user());

        return back()->with('ok', "{$data['qty']} galon kosong dikirim ke pabrik ({$no}).");
    }
}
