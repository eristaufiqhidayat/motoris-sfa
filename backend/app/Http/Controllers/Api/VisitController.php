<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Visit;
use App\Services\CheckinGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    public function checkin(Request $request, CheckinGuard $guard)
    {
        $request->validate(['outlet_id' => ['required', 'exists:outlets,id']]);
        $user = $request->user();
        $outlet = Outlet::findOrFail($request->integer('outlet_id'));

        // Kunjungan yang masih terbuka dilanjutkan tanpa check-in ulang.
        $open = Visit::where('user_id', $user->id)->whereDate('tanggal', today())->where('status', 'checkin')->first();
        if ($open && $open->outlet_id !== $outlet->id) {
            throw ValidationException::withMessages(['outlet_id' => "Selesaikan dulu kunjungan di {$open->outlet->nama}."]);
        }
        if ($open) {
            return $this->payload($open);
        }

        $data = $request->validate([
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'akurasi' => ['nullable', 'numeric', 'min:0'],
            'mock' => ['nullable', 'boolean'],
            'foto' => ['required', 'image', 'max:6144'],
        ], ['foto.required' => 'Foto toko wajib diambil saat check-in.']);

        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;
        $akurasi = isset($data['akurasi']) ? (float) $data['akurasi'] : null;
        $hasil = $guard->periksa($user->id, $outlet, $lat, $lng, $akurasi, (bool) ($data['mock'] ?? false));
        if ($hasil['tolak']) {
            throw ValidationException::withMessages(['lokasi' => $hasil['tolak']]);
        }

        $visit = Visit::create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'tanggal' => today(),
            'checkin_at' => now(),
            'lat' => $lat,
            'lng' => $lng,
            'jarak_meter' => $hasil['jarak'],
            'gps_akurasi' => $akurasi,
            'foto_path' => $request->file('foto')->store('checkin/'.today()->format('Y-m-d'), 'local'),
            'flags' => $hasil['flags'] ?: null,
            'status' => 'checkin',
        ]);

        return response($this->payload($visit), 201);
    }

    /** Sales mencatat sisa stok di toko; sistem mengembalikan saran order. */
    public function stokOutlet(Request $request, Visit $visit)
    {
        abort_unless($visit->user_id === $request->user()->id, 403);
        $data = $request->validate(['stok' => ['present', 'array'], 'stok.*' => ['integer', 'min:0']]);

        $visit->update(['stok_outlet' => $data['stok']]);

        return $this->payload($visit->fresh());
    }

    public function tutup(Request $request, Visit $visit)
    {
        abort_unless($visit->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'status' => ['required', 'in:tutup,tanpa_order'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);
        if ($visit->status !== 'checkin') {
            throw ValidationException::withMessages(['visit' => 'Kunjungan ini sudah ditutup.']);
        }
        $visit->update($data);

        return $this->payload($visit);
    }

    private function payload(Visit $visit): array
    {
        $rata = $visit->outlet->rata_jual_mingguan ?? [];
        $sisa = $visit->stok_outlet ?? [];
        $products = Product::all()->keyBy('kode');

        // Saran order = rata-rata jual seminggu - sisa stok di toko.
        $saran = [];
        foreach ($rata as $kode => $qty) {
            if (! isset($products[$kode])) {
                continue;
            }
            $p = $products[$kode];
            $saran[] = ['product_id' => $p->id, 'nama' => $p->nama, 'qty' => max(0, (int) $qty - (int) ($sisa[$p->id] ?? 0))];
        }

        return [
            'id' => $visit->id,
            'outlet_id' => $visit->outlet_id,
            'status' => $visit->status,
            'checkin_at' => $visit->checkin_at->toIso8601String(),
            'jarak_meter' => $visit->jarak_meter,
            'flags' => $visit->flags ?? [],
            'stok_outlet' => $sisa,
            'saran_order' => $saran,
        ];
    }
}
