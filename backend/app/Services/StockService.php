<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya pintu untuk mengubah saldo stok. Setiap perubahan saldo
 * selalu disertai baris kartu stok (stock_movements).
 * Panggil di dalam DB::transaction().
 */
class StockService
{
    public function move(
        Product $product,
        int $qty,
        ?Location $from,
        ?Location $to,
        string $jenis,
        ?string $refNo = null,
        string $kondisi = 'isi',
        ?User $user = null,
        ?string $keterangan = null,
        ?string $kondisiTujuan = null,
    ): ?StockMovement {
        if ($qty <= 0) {
            return null;
        }

        if ($from) {
            $this->change($from, $product, $kondisi, -$qty);
        }
        if ($to) {
            $this->change($to, $product, $kondisiTujuan ?? $kondisi, $qty);
        }

        return StockMovement::create([
            'product_id' => $product->id,
            'kondisi' => $kondisiTujuan ?? $kondisi,
            'from_location_id' => $from?->id,
            'to_location_id' => $to?->id,
            'qty' => $qty,
            'jenis' => $jenis,
            'ref_no' => $refNo,
            'keterangan' => $keterangan,
            'user_id' => $user?->id,
        ]);
    }

    /** Penyesuaian (mis. opname): delta positif = tambah, negatif = kurang. */
    public function adjust(Location $location, Product $product, string $kondisi, int $delta, string $refNo, ?User $user, ?string $keterangan): void
    {
        if ($delta > 0) {
            $this->move($product, $delta, null, $location, 'opname', $refNo, $kondisi, $user, $keterangan);
        } elseif ($delta < 0) {
            $this->move($product, -$delta, $location, null, 'opname', $refNo, $kondisi, $user, $keterangan);
        }
    }

    private function change(Location $location, Product $product, string $kondisi, int $delta): void
    {
        $balance = StockBalance::where([
            'location_id' => $location->id,
            'product_id' => $product->id,
            'kondisi' => $kondisi,
        ])->lockForUpdate()->first()
            ?? StockBalance::create([
                'location_id' => $location->id,
                'product_id' => $product->id,
                'kondisi' => $kondisi,
                'qty' => 0,
            ]);

        if ($balance->qty + $delta < 0) {
            throw ValidationException::withMessages([
                'stok' => "Stok {$product->nama} ({$kondisi}) di {$location->nama} tidak cukup: tersedia {$balance->qty}, dibutuhkan ".(-$delta).'.',
            ]);
        }

        $balance->increment('qty', $delta);
    }
}
