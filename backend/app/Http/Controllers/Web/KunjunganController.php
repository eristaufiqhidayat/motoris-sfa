<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\CheckinGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KunjunganController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->date('tanggal') ?? today();
        $janggal = $request->boolean('janggal');
        $visits = Visit::with(['outlet', 'user', 'order'])
            ->whereDate('tanggal', $tanggal)
            ->when($janggal, fn ($q) => $q->whereNotNull('flags'))
            ->orderByDesc('checkin_at')->get();
        $perluCek = Visit::whereNotNull('flags')->whereNull('diperiksa_at')->count();

        return view('kunjungan.index', [
            'visits' => $visits,
            'tanggal' => $tanggal,
            'janggal' => $janggal,
            'perluCek' => $perluCek,
            'labels' => CheckinGuard::FLAGS,
        ]);
    }

    public function foto(Visit $visit)
    {
        abort_unless($visit->foto_path && Storage::disk('local')->exists($visit->foto_path), 404);

        return Storage::disk('local')->response($visit->foto_path);
    }

    public function periksa(Request $request, Visit $visit)
    {
        $visit->update(['diperiksa_at' => now(), 'diperiksa_oleh' => $request->user()->id]);

        return back()->with('ok', "Kunjungan {$visit->outlet->nama} ditandai sudah diperiksa.");
    }
}
