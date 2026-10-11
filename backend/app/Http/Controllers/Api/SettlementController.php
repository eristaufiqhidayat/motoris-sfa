<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loading;
use App\Services\LoadingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettlementController extends Controller
{
    public function __construct(private LoadingService $loadings) {}

    public function show(Request $request)
    {
        $loading = $this->current($request);

        return $this->payload($loading);
    }

    public function store(Request $request)
    {
        $loading = $this->loadings->setor($this->current($request), $request->user());

        return $this->payload($loading->fresh());
    }

    private function current(Request $request): Loading
    {
        return Loading::where('user_id', $request->user()->id)->whereDate('tanggal', today())->latest('id')->first()
            ?? throw ValidationException::withMessages(['loading' => 'Belum ada muat hari ini.']);
    }

    private function payload(Loading $loading): array
    {
        $sisa = $loading->vehicle->balances()->with('product')->get()
            ->map(fn ($b) => ['nama' => $b->product->nama, 'kondisi' => $b->kondisi, 'qty' => $b->qty])->values();

        return [
            'no' => $loading->no,
            'status' => $loading->status,
            'muat' => $loading->muat,
            'sisa_di_kendaraan' => $sisa,
            'uang' => $loading->uang(),
            'setor_tunai' => $loading->setor_tunai,
            'setor_at' => $loading->setor_at?->toIso8601String(),
            'diterima_at' => $loading->diterima_at?->toIso8601String(),
            'selisih' => $loading->selisih ?? [],
        ];
    }
}
