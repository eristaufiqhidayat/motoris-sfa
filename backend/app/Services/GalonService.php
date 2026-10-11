<?php

namespace App\Services;

use App\Models\Galon;
use App\Models\Location;
use App\Models\Outlet;
use Illuminate\Support\Collection;

/**
 * Memindahkan custody galon berseri. Kalau scan nomor seri tidak dikirim,
 * sistem memilih galon yang paling lama berada di lokasi asal (FIFO).
 */
class GalonService
{
    /** @param  list<string>  $serials */
    public function keOutlet(Location $from, Outlet $outlet, int $qty, string $refNo, array $serials = []): Collection
    {
        $galons = $this->pick(Galon::where('location_id', $from->id)->where('kondisi', 'isi'), $qty, $serials);

        foreach ($galons as $galon) {
            $galon->update(['location_id' => null, 'outlet_id' => $outlet->id, 'last_moved_at' => now()]);
            $galon->events()->create(['event' => 'Diserahkan ke pelanggan', 'dari' => $from->nama, 'ke' => $outlet->nama, 'ref_no' => $refNo]);
        }

        return $galons;
    }

    /** @param  list<string>  $serials */
    public function tarikKosong(Outlet $outlet, Location $to, int $qty, string $refNo, array $serials = []): Collection
    {
        $galons = $this->pick(Galon::where('outlet_id', $outlet->id), $qty, $serials);

        foreach ($galons as $galon) {
            $galon->update(['outlet_id' => null, 'location_id' => $to->id, 'kondisi' => 'kosong', 'last_moved_at' => now()]);
            $galon->events()->create(['event' => 'Ditarik kosong', 'dari' => $outlet->nama, 'ke' => $to->nama, 'ref_no' => $refNo]);
        }

        return $galons;
    }

    public function pindah(Location $from, Location $to, int $qty, string $kondisi, string $event, string $refNo): Collection
    {
        $galons = $this->pick(Galon::where('location_id', $from->id)->where('kondisi', $kondisi), $qty, []);

        foreach ($galons as $galon) {
            $galon->update(['location_id' => $to->id, 'last_moved_at' => now()]);
            $galon->events()->create(['event' => $event, 'dari' => $from->nama, 'ke' => $to->nama, 'ref_no' => $refNo]);
        }

        return $galons;
    }

    private function pick($query, int $qty, array $serials): Collection
    {
        if ($serials) {
            return (clone $query)->whereIn('serial', $serials)->limit($qty)->get();
        }

        return $query->orderBy('last_moved_at')->orderBy('id')->limit($qty)->get();
    }
}
