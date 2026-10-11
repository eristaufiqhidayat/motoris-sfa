<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Penerimaan dari pabrik, kirim kosong ke pabrik, dan stock opname. */
class WarehouseService
{
    public function __construct(private StockService $stock, private GalonService $galon) {}

    public function terimaPabrik(GoodsReceipt $gr, int $tolak, User $user): GoodsReceipt
    {
        return DB::transaction(function () use ($gr, $tolak, $user) {
            if ($gr->status !== 'transit') {
                throw ValidationException::withMessages(['status' => 'Surat jalan ini sudah diterima.']);
            }
            if ($tolak > $gr->qty_kirim) {
                throw ValidationException::withMessages(['qty_tolak' => 'Jumlah ditolak melebihi jumlah kirim.']);
            }

            $depot = Depot::findOrFail($gr->depot_id);
            $ok = $gr->qty_kirim - $tolak;
            $this->stock->move($gr->product, $ok, null, $depot->gudang(), 'terima_pabrik', $gr->no, user: $user, keterangan: $gr->sumber);
            $this->stock->move($gr->product, $tolak, null, $depot->karantina(), 'karantina', $gr->no, user: $user, keterangan: 'Ditolak saat terima');

            $gr->update(['qty_terima' => $ok, 'qty_tolak' => $tolak, 'status' => 'diterima', 'diterima_at' => now(), 'user_id' => $user->id]);

            return $gr;
        });
    }

    public function kirimKosongKePabrik(Depot $depot, int $qty, User $user): string
    {
        return DB::transaction(function () use ($depot, $qty, $user) {
            $no = \App\Services\Nomor::next('TRF', 'stock_movements', 'ref_no');
            $galon = Product::galon();
            $this->stock->move($galon, $qty, $depot->gudang(), null, 'kirim_pabrik', $no, 'kosong', $user, 'Ke pabrik untuk cuci & QC');

            $sent = \App\Models\Galon::where('location_id', $depot->gudang()->id)->where('kondisi', 'kosong')->orderBy('last_moved_at')->limit($qty)->get();
            foreach ($sent as $g) {
                $g->update(['location_id' => null, 'last_moved_at' => now()]);
                $g->events()->create(['event' => 'Dikirim ke pabrik', 'dari' => $depot->gudang()->nama, 'ke' => 'Pabrik', 'ref_no' => $no]);
            }

            return $no;
        });
    }

    public function buatOpname(Location $location, User $user): StockOpname
    {
        return DB::transaction(function () use ($location, $user) {
            $opname = StockOpname::create([
                'no' => Nomor::next('OPN', 'stock_opnames'),
                'location_id' => $location->id,
                'user_id' => $user->id,
            ]);

            foreach ($location->balances()->get() as $b) {
                $opname->items()->create([
                    'product_id' => $b->product_id,
                    'kondisi' => $b->kondisi,
                    'qty_sistem' => $b->qty,
                    'qty_fisik' => $b->qty,
                ]);
            }

            return $opname;
        });
    }

    /** @param  array<int, array{qty_fisik:int, penyebab?:string}>  $counts  item_id => hitungan */
    public function postOpname(StockOpname $opname, array $counts, User $user): StockOpname
    {
        return DB::transaction(function () use ($opname, $counts, $user) {
            if ($opname->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Opname sudah diposting.']);
            }

            foreach ($opname->items()->with('product')->get() as $item) {
                $count = $counts[$item->id] ?? null;
                if ($count !== null) {
                    $item->update(['qty_fisik' => (int) $count['qty_fisik'], 'penyebab' => $count['penyebab'] ?? null]);
                }
                // Selisih dihitung terhadap saldo saat posting, bukan saat draft dibuat.
                $now = $opname->location->qty($item->product, $item->kondisi);
                $this->stock->adjust($opname->location, $item->product, $item->kondisi, $item->qty_fisik - $now, $opname->no, $user, $item->penyebab);
            }

            $opname->update(['status' => 'posted', 'posted_at' => now()]);

            return $opname;
        });
    }
}
