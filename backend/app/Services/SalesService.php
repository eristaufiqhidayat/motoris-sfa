<?php

namespace App\Services;

use App\Models\Loading;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Penjualan dari aplikasi sales lapangan.
 *  - canvas: motoris menjual langsung dari stok di motor, barang diserahkan saat itu.
 *  - taking_order: sales mencatat order, barang dikirim kemudian dari gudang.
 */
class SalesService
{
    public function __construct(private StockService $stock, private GalonService $galon) {}

    /**
     * @param  array<int,int>  $items  product_id => qty
     * @param  list<string>  $serialKeluar  hasil scan QR (opsional)
     * @param  list<string>  $serialMasuk
     */
    public function canvas(User $user, Visit $visit, array $items, int $kosongDitarik, string $metodeBayar, array $serialKeluar = [], array $serialMasuk = []): SalesOrder
    {
        return DB::transaction(function () use ($user, $visit, $items, $kosongDitarik, $metodeBayar, $serialKeluar, $serialMasuk) {
            $this->guardVisit($user, $visit);
            $loading = $this->activeLoading($user);
            $outlet = Outlet::lockForUpdate()->findOrFail($visit->outlet_id);
            $vehicle = $loading->vehicle;
            $galon = Product::galon();

            if ($kosongDitarik > $outlet->galon_dipinjam) {
                throw ValidationException::withMessages(['galon_kosong' => "Outlet hanya meminjam {$outlet->galon_dipinjam} galon."]);
            }

            [$lines, $subtotal] = $this->lines($items);
            $galonQty = $items[$galon->id] ?? 0;
            $deposit = max(0, $galonQty - $kosongDitarik) * $galon->deposit;
            $total = $subtotal + $deposit;

            if ($metodeBayar === 'tempo') {
                $this->guardKredit($outlet, $total);
            }

            $so = SalesOrder::create([
                'no' => Nomor::next('DO', 'sales_orders'),
                'tipe' => SalesOrder::CANVAS,
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'visit_id' => $visit->id,
                'loading_id' => $loading->id,
                'status' => 'selesai',
                'metode_bayar' => $metodeBayar,
                'galon_kosong_ditarik' => $kosongDitarik,
                'subtotal' => $subtotal,
                'deposit' => $deposit,
                'total' => $total,
            ]);
            $so->items()->createMany($lines);

            foreach ($lines as $line) {
                $this->stock->move(Product::find($line['product_id']), $line['qty'], $vehicle, null, 'jual', $so->no, user: $user, keterangan: $outlet->nama);
            }
            $this->stock->move($galon, $kosongDitarik, null, $vehicle, 'tarik_kosong', $so->no, 'kosong', $user, $outlet->nama);

            $this->galon->keOutlet($vehicle, $outlet, $galonQty, $so->no, $serialKeluar);
            $this->galon->tarikKosong($outlet, $vehicle, $kosongDitarik, $so->no, $serialMasuk);

            $outlet->galon_dipinjam += $galonQty - $kosongDitarik;
            if ($metodeBayar === 'tempo') {
                $outlet->piutang += $total;
            }
            $outlet->save();

            $visit->update(['status' => 'order']);

            return $so->load('items.product');
        });
    }

    /** @param  array<int,int>  $items */
    public function takingOrder(User $user, Visit $visit, array $items, ?string $catatan = null): SalesOrder
    {
        return DB::transaction(function () use ($user, $visit, $items, $catatan) {
            $this->guardVisit($user, $visit);
            $outlet = $visit->outlet;

            [$lines, $subtotal] = $this->lines($items);
            $tempo = $outlet->bolehTempo();
            $lewatLimit = $tempo && $outlet->piutang + $subtotal > $outlet->limit_kredit;

            $so = SalesOrder::create([
                'no' => Nomor::next('SO', 'sales_orders'),
                'tipe' => SalesOrder::TAKING_ORDER,
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'visit_id' => $visit->id,
                'status' => $lewatLimit ? 'menunggu_approval' : 'disetujui',
                'metode_bayar' => $tempo ? 'tempo' : 'tunai',
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'alasan' => $lewatLimit ? 'Melewati limit kredit' : $catatan,
            ]);
            $so->items()->createMany($lines);
            $visit->update(['status' => 'order']);

            return $so->load('items.product');
        });
    }

    public function approve(SalesOrder $so, User $admin): void
    {
        $this->guardPending($so);
        $so->update(['status' => 'disetujui', 'approved_by' => $admin->id, 'alasan' => 'Disetujui '.$admin->name]);
    }

    public function reject(SalesOrder $so, User $admin, string $alasan): void
    {
        $this->guardPending($so);
        $so->update(['status' => 'ditolak', 'approved_by' => $admin->id, 'alasan' => $alasan]);
    }

    public function activeLoading(User $user): Loading
    {
        $loading = Loading::where('user_id', $user->id)
            ->whereDate('tanggal', today())
            ->where('status', 'jalan')
            ->latest('id')->first();

        if (! $loading) {
            throw ValidationException::withMessages(['loading' => 'Belum ada muat hari ini, atau setoran sudah dikirim. Jual langsung tidak bisa dilakukan.']);
        }

        return $loading;
    }

    /** @return array{0: list<array>, 1: int} */
    private function lines(array $items): array
    {
        $items = array_filter($items, fn ($q) => (int) $q > 0);
        if (! $items) {
            throw ValidationException::withMessages(['items' => 'Order masih kosong.']);
        }

        $products = Product::whereIn('id', array_keys($items))->get()->keyBy('id');
        $lines = [];
        $subtotal = 0;
        foreach ($items as $productId => $qty) {
            $product = $products[$productId] ?? throw ValidationException::withMessages(['items' => "Produk {$productId} tidak dikenal."]);
            $lines[] = [
                'product_id' => $product->id,
                'qty' => (int) $qty,
                'harga' => $product->harga,
                'subtotal' => $product->harga * (int) $qty,
            ];
            $subtotal += $product->harga * (int) $qty;
        }

        return [$lines, $subtotal];
    }

    private function guardVisit(User $user, Visit $visit): void
    {
        if ($visit->user_id !== $user->id) {
            abort(403, 'Kunjungan ini milik sales lain.');
        }
        if ($visit->status !== 'checkin') {
            throw ValidationException::withMessages(['visit' => 'Kunjungan ini sudah ditutup.']);
        }
    }

    private function guardKredit(Outlet $outlet, int $total): void
    {
        if (! $outlet->bolehTempo()) {
            throw ValidationException::withMessages(['metode_bayar' => 'Outlet ini tidak punya limit kredit. Gunakan tunai atau QRIS.']);
        }
        if ($outlet->piutang + $total > $outlet->limit_kredit) {
            throw ValidationException::withMessages(['metode_bayar' => 'Melewati limit kredit. Sisa limit Rp'.number_format($outlet->sisaLimit(), 0, ',', '.').'.']);
        }
    }

    private function guardPending(SalesOrder $so): void
    {
        if ($so->status !== 'menunggu_approval') {
            throw ValidationException::withMessages(['status' => 'Order ini tidak menunggu persetujuan.']);
        }
    }
}
