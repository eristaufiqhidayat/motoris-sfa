<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Galon;
use Illuminate\Http\Request;

class GalonController extends Controller
{
    public function index(Request $request)
    {
        $q = strtoupper(trim((string) $request->query('serial', '')));
        $galon = $q ? Galon::with(['events', 'location', 'outlet'])->where('serial', $q)->first() : null;

        $posisi = [
            'Di gudang (isi)' => Galon::whereHas('location', fn ($l) => $l->where('tipe', 'gudang'))->where('kondisi', 'isi')->count(),
            'Di gudang (kosong)' => Galon::whereHas('location', fn ($l) => $l->where('tipe', 'gudang'))->where('kondisi', 'kosong')->count(),
            'Di kendaraan' => Galon::whereHas('location', fn ($l) => $l->where('tipe', 'kendaraan'))->count(),
            'Di outlet & pelanggan' => Galon::whereNotNull('outlet_id')->count(),
            'Di pabrik / perjalanan' => Galon::whereNull('outlet_id')->whereNull('location_id')->count(),
        ];

        $siklus = [
            '0–20' => Galon::where('siklus', '<=', 20)->count(),
            '21–40' => Galon::whereBetween('siklus', [21, 40])->count(),
            '41–55' => Galon::whereBetween('siklus', [41, 55])->count(),
            '56–60' => Galon::whereBetween('siklus', [56, 60])->count(),
            '> 60' => Galon::where('siklus', '>', 60)->count(),
        ];

        $tidakKembali = Galon::with('outlet')->whereNotNull('outlet_id')
            ->where('last_moved_at', '<', now()->subDays(60))->limit(20)->get();
        $terbaru = \App\Models\GalonEvent::latest('id')->limit(15)->get()->load('galon:id,serial');

        return view('galon.index', compact('q', 'galon', 'posisi', 'siklus', 'tidakKembali', 'terbaru'));
    }
}
