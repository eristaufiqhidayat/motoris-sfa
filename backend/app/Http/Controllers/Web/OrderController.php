<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Services\SalesService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private SalesService $sales) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $orders = SalesOrder::with(['outlet', 'user', 'items.product'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(25)->withQueryString();

        return view('order.index', compact('orders', 'status'));
    }

    public function approve(Request $request, SalesOrder $order)
    {
        $this->sales->approve($order, $request->user());

        return back()->with('ok', "{$order->no} disetujui.");
    }

    public function reject(Request $request, SalesOrder $order)
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'max:255']]);
        $this->sales->reject($order, $request->user(), $data['alasan']);

        return back()->with('ok', "{$order->no} ditolak.");
    }
}
