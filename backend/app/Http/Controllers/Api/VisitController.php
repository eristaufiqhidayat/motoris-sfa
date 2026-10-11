<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    /** Radius maksimal check-in dari titik outlet. */
    private const RADIUS_METER = 150;

    public function checkin(Request $request)
    {
        $data = $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);
        $user = $request->user();
        $outlet = Outlet::findOrFail($data['outlet_id']);

        $open = Visit::where('user_id', $user->id)->whereDate('tanggal', today())->where('status', 'checkin')->first();
        if ($open && $open->outlet_id !== $outlet->id) {
            throw ValidationException::withMessages(['outlet_id' => "Selesaikan dulu kunjungan di {$open->outlet->nama}."]);
        }
        if ($open) {
            return $this->payload($open);
        }

        $jarak = null;
        if (isset($data['lat'], $data['lng']) && $outlet->lat && $outlet->lng) {
            $jarak = (int) round($this->haversine($data['lat'], $data['lng'], $outlet->lat, $outlet->lng));
            if ($jarak > self::RADIUS_METER) {
                throw ValidationException::withMessages(['lokasi' => "Posisi Anda {$jarak} m dari outlet. Check-in maksimal ".self::RADIUS_METER.' m.']);
            }
        }

        $visit = Visit::create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'tanggal' => today(),
            'checkin_at' => now(),
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'jarak_meter' => $jarak,
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
            'stok_outlet' => $sisa,
            'saran_order' => $saran,
        ];
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
