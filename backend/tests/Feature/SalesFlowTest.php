<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SalesFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function token(string $email = 'rudi@oasis.test'): string
    {
        return $this->postJson('/api/login', ['email' => $email, 'password' => 'password'])
            ->assertOk()->json('token');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** Check-in tepat di titik outlet dengan foto, kecuali $extra menimpa. */
    private function checkin(string $token, Outlet $outlet, array $extra = []): TestResponse
    {
        return $this->withToken($token)->post('/api/visits', array_merge([
            'outlet_id' => $outlet->id,
            'lat' => $outlet->lat,
            'lng' => $outlet->lng,
            'akurasi' => 12,
            'foto' => UploadedFile::fake()->image('toko.jpg', 640, 480),
        ], $extra), ['Accept' => 'application/json']);
    }

    public function test_admin_tidak_bisa_login_ke_aplikasi_sales(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@oasis.test', 'password' => 'password'])->assertStatus(422);
    }

    public function test_beranda_menampilkan_rute_dan_stok_motor(): void
    {
        $res = $this->withToken($this->token())->getJson('/api/today')->assertOk();

        $this->assertSame('R-12', $res->json('rute.kode'));
        $this->assertSame('jalan', $res->json('muat.status'));
        $this->assertCount(8, $res->json('outlets'));
        $galon = collect($res->json('stok_kendaraan'))->firstWhere('kode', 'GLN19');
        $this->assertSame(30, $galon['qty']);
    }

    public function test_jual_canvas_menggerakkan_stok_custody_dan_piutang(): void
    {
        $t = $this->token();
        $outlet = Outlet::where('nama', 'Depot Segar Abadi')->first();
        $galon = Product::galon();
        $motor = Location::where('kode', 'B4521KZE')->first();

        $visit = $this->checkin($t, $outlet)->assertCreated()->json('id');

        $so = $this->withToken($t)->postJson('/api/orders/canvas', [
            'visit_id' => $visit,
            'items' => [['product_id' => $galon->id, 'qty' => 5]],
            'galon_kosong' => 3,
            'metode_bayar' => 'tempo',
        ])->assertCreated();

        // 5 x 20.000 + deposit 2 galon x 50.000
        $this->assertSame(200000, $so->json('total'));
        $this->assertSame(25, $motor->qty($galon));
        $this->assertSame(3, $motor->qty($galon, 'kosong'));

        $outlet->refresh();
        $this->assertSame(12, $outlet->galon_dipinjam);
        $this->assertSame(440000, $outlet->piutang);

        $this->assertSame(12, \App\Models\Galon::where('outlet_id', $outlet->id)->count());
    }

    public function test_tempo_ditolak_untuk_warung_tanpa_limit(): void
    {
        $t = $this->token();
        $outlet = Outlet::where('nama', 'Warung Bu Siti')->first();
        $visit = $this->checkin($t, $outlet)->json('id');

        $this->withToken($t)->postJson('/api/orders/canvas', [
            'visit_id' => $visit,
            'items' => [['product_id' => Product::galon()->id, 'qty' => 2]],
            'galon_kosong' => 2,
            'metode_bayar' => 'tempo',
        ])->assertStatus(422)->assertJsonValidationErrors('metode_bayar');
    }

    public function test_checkin_ditolak_bila_jauh_dari_outlet(): void
    {
        $outlet = Outlet::where('nama', 'Toko Berkah Jaya')->first();
        $this->checkin($this->token(), $outlet, ['lat' => $outlet->lat + 0.01])
            ->assertStatus(422)->assertJsonValidationErrors('lokasi');
    }

    public function test_checkin_ditolak_bila_lokasi_palsu(): void
    {
        $outlet = Outlet::where('nama', 'Toko Berkah Jaya')->first();
        $this->checkin($this->token(), $outlet, ['mock' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('lokasi');
    }

    public function test_checkin_wajib_foto_dan_gps(): void
    {
        $t = $this->token();
        $outlet = Outlet::where('nama', 'Toko Berkah Jaya')->first();
        $this->checkin($t, $outlet, ['foto' => null])->assertStatus(422)->assertJsonValidationErrors('foto');
        $this->checkin($t, $outlet, ['lat' => null, 'lng' => null])->assertStatus(422)->assertJsonValidationErrors('lokasi');

        $id = $this->checkin($t, $outlet)->assertCreated()->assertJsonPath('flags', [])->json('id');
        Storage::disk('local')->assertExists(\App\Models\Visit::find($id)->foto_path);
    }

    public function test_checkin_janggal_ditandai_lalu_diperiksa_admin(): void
    {
        $t = $this->token();
        $a = Outlet::where('nama', 'Toko Berkah Jaya')->first();
        $b = Outlet::where('nama', 'Warung Bu Siti')->first();

        $va = $this->checkin($t, $a)->assertCreated()->json('id');
        $this->withToken($t)->postJson("/api/visits/{$va}/tutup", ['status' => 'tutup'])->assertOk();

        // Warung Bu Siti ±4 km dari Berkah Jaya, check-in sedetik kemudian dengan GPS kurang akurat.
        $vb = $this->checkin($t, $b, ['akurasi' => 250])->assertCreated();
        $this->assertEqualsCanonicalizing(['akurasi_buruk', 'terlalu_cepat'], $vb->json('flags'));

        $admin = User::where('email', 'admin@oasis.test')->first();
        $this->actingAs($admin)->get(route('kunjungan', ['janggal' => 1]))->assertOk()->assertSee('Pindah outlet terlalu cepat');
        $visit = \App\Models\Visit::find($vb->json('id'));
        $this->actingAs($admin)->get(route('kunjungan.foto', $visit))->assertOk();
        $this->actingAs($admin)->post(route('kunjungan.periksa', $visit))->assertRedirect();
        $this->assertNotNull($visit->fresh()->diperiksa_at);
    }

    public function test_taking_order_lewat_limit_menunggu_approval_lalu_disetujui_admin(): void
    {
        $t = $this->token();
        $outlet = Outlet::where('nama', 'Toko Grosir Abadi')->first(); // piutang 5,4 jt dari limit 6 jt
        $visit = $this->checkin($t, $outlet)->json('id');

        $sisa = $this->withToken($t)->postJson("/api/visits/{$visit}/stok-outlet", ['stok' => [Product::galon()->id => 12]])->assertOk();
        $this->assertSame(28, collect($sisa->json('saran_order'))->firstWhere('nama', 'Galon 19 L')['qty']);

        $so = $this->withToken($t)->postJson('/api/orders/taking', [
            'visit_id' => $visit,
            'items' => [['product_id' => Product::galon()->id, 'qty' => 40]],
        ])->assertCreated();
        $this->assertSame('menunggu_approval', $so->json('status'));

        $admin = User::where('email', 'admin@oasis.test')->first();
        $order = \App\Models\SalesOrder::where('no', $so->json('no'))->first();
        $this->actingAs($admin)->post(route('order.approve', $order))->assertRedirect();
        $this->assertSame('disetujui', $order->fresh()->status);
    }

    public function test_setoran_dikirim_lalu_diterima_gudang_dengan_selisih(): void
    {
        $t = $this->token();
        $galon = Product::galon();
        $gudang = Location::where('tipe', 'gudang')->first();
        $before = $gudang->qty($galon);

        $this->withToken($t)->postJson('/api/setoran')->assertOk()->assertJsonPath('status', 'setor');
        // Setelah setor, jual langsung ditolak.
        $outlet = Outlet::where('nama', 'Kios Pak Darto')->first();
        $visit = $this->checkin($t, $outlet)->json('id');
        $this->withToken($t)->postJson('/api/orders/canvas', ['visit_id' => $visit, 'items' => [['product_id' => $galon->id, 'qty' => 1]], 'galon_kosong' => 0, 'metode_bayar' => 'tunai'])
            ->assertStatus(422);

        $admin = User::where('email', 'admin@oasis.test')->first();
        $loading = \App\Models\Loading::first();
        $this->actingAs($admin)->post(route('muat.terima', $loading), ['fisik' => [$galon->id.':isi' => 29]])->assertRedirect();

        $loading->refresh();
        $this->assertSame('selesai', $loading->status);
        $this->assertCount(1, $loading->selisih);
        $this->assertSame($before + 29, $gudang->qty($galon));
        $this->assertSame(0, Location::where('kode', 'B4521KZE')->first()->qty($galon));
    }

    public function test_halaman_web_inventori_bisa_dibuka_admin(): void
    {
        $admin = User::where('email', 'admin@oasis.test')->first();
        foreach (['stok', 'galon', 'penerimaan', 'muat', 'order', 'opname'] as $r) {
            $this->actingAs($admin)->get(route($r))->assertOk();
        }
        $this->actingAs(User::where('email', 'rudi@oasis.test')->first())->get(route('stok'))->assertForbidden();
    }

    public function test_terima_pabrik_dan_opname(): void
    {
        $admin = User::where('email', 'admin@oasis.test')->first();
        $galon = Product::galon();
        $gudang = Location::where('tipe', 'gudang')->first();
        $before = $gudang->qty($galon);

        $gr = \App\Models\GoodsReceipt::first();
        $this->actingAs($admin)->post(route('penerimaan.terima', $gr), ['qty_tolak' => 2])->assertRedirect();
        $this->assertSame($before + 478, $gudang->qty($galon));
        $this->assertSame(2, Location::where('tipe', 'karantina')->first()->qty($galon));

        $this->actingAs($admin)->post(route('opname.store'))->assertRedirect();
        $opname = \App\Models\StockOpname::first();
        $item = $opname->items()->where('product_id', $galon->id)->where('kondisi', 'kosong')->first();
        $this->actingAs($admin)->post(route('opname.post', $opname), ['items' => [$item->id => ['qty_fisik' => $item->qty_sistem - 4, 'penyebab' => 'Pecah']]])->assertRedirect();
        $this->assertSame($item->qty_sistem - 4, $gudang->qty($galon, 'kosong'));
    }
}
