<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\Visit;
use App\Services\SalesService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private SalesService $sales) {}

    /** Motoris: jual langsung dari stok di motor. */
    public function canvas(Request $request)
    {
        $data = $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:0'],
            'galon_kosong' => ['required', 'integer', 'min:0'],
            'metode_bayar' => ['required', 'in:tunai,qris,tempo'],
            'serial_keluar' => ['array'],
            'serial_keluar.*' => ['string'],
            'serial_masuk' => ['array'],
            'serial_masuk.*' => ['string'],
        ]);

        $so = $this->sales->canvas(
            $request->user(),
            Visit::findOrFail($data['visit_id']),
            $this->map($data['items']),
            $data['galon_kosong'],
            $data['metode_bayar'],
            $data['serial_keluar'] ?? [],
            $data['serial_masuk'] ?? [],
        );

        return response($this->payload($so), 201);
    }

    /** SFA: ambil order, dikirim kemudian dari gudang. */
    public function takingOrder(Request $request)
    {
        $data = $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $so = $this->sales->takingOrder($request->user(), Visit::findOrFail($data['visit_id']), $this->map($data['items']), $data['catatan'] ?? null);

        return response($this->payload($so), 201);
    }

    public function index(Request $request)
    {
        return SalesOrder::where('user_id', $request->user()->id)
            ->whereDate('created_at', today())
            ->with(['items.product', 'outlet'])->latest('id')->get()
            ->map(fn ($so) => $this->payload($so));
    }

    private function map(array $items): array
    {
        $out = [];
        foreach ($items as $i) {
            $out[(int) $i['product_id']] = ($out[(int) $i['product_id']] ?? 0) + (int) $i['qty'];
        }

        return $out;
    }

    private function payload(SalesOrder $so): array
    {
        return [
            'no' => $so->no,
            'tipe' => $so->tipe,
            'status' => $so->status,
            'outlet' => $so->outlet->nama,
            'metode_bayar' => $so->metode_bayar,
            'galon_kosong_ditarik' => $so->galon_kosong_ditarik,
            'subtotal' => $so->subtotal,
            'deposit' => $so->deposit,
            'total' => $so->total,
            'alasan' => $so->alasan,
            'items' => $so->items->map(fn ($i) => ['product_id' => $i->product_id, 'nama' => $i->product->nama, 'qty' => $i->qty, 'harga' => $i->harga, 'subtotal' => $i->subtotal])->values(),
        ];
    }
}
