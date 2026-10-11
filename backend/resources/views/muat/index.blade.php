@extends('layouts.app')
@section('title', 'Muat & setoran')
@section('content')
<div class="eyebrow">Sales & Distribusi</div>
<h1>Muat & setoran</h1>
<p class="lede">Pagi: muat barang dari gudang ke kendaraan sales. Sore: sales mengirim setoran dari aplikasi, lalu gudang menghitung fisik dan menerimanya.</p>
<div class="card"><h2>Hari ini</h2><div class="scroll"><table>
  <thead><tr><th>No.</th><th>Sales</th><th>Rute</th><th>Muat</th><th class="r">Tunai</th><th class="r">QRIS</th><th class="r">Tempo</th><th>Status</th></tr></thead><tbody>
  @forelse($today as $l)
    @php($u = $l->uang())
    <tr><td class="mono">{{ $l->no }}</td><td>{{ $l->user->kode }} {{ $l->user->name }}</td><td>{{ $l->route->kode }} {{ $l->route->nama }}</td>
    <td><small>@foreach($l->muat as $pid => $q){{ $products->firstWhere('id',$pid)?->nama }} {{ $q }}@if(!$loop->last), @endif @endforeach</small></td>
    <td class="r num">Rp{{ number_format($u['tunai'],0,',','.') }}</td><td class="r num">Rp{{ number_format($u['qris'],0,',','.') }}</td><td class="r num">Rp{{ number_format($u['tempo'],0,',','.') }}</td>
    <td><span class="pill {{ ['jalan'=>'p-info','setor'=>'p-warn','selesai'=>'p-ok'][$l->status] }}">{{ ['jalan'=>'Di jalan','setor'=>'Setoran masuk','selesai'=>'Selesai'][$l->status] }}</span></td></tr>
    @if($l->status==='setor')
      <tr><td colspan="8" style="background:var(--surface-2)">
        <form method="post" action="{{ route('muat.terima',$l) }}">@csrf
          <b>Hitung fisik sisa di {{ $l->vehicle->nama }}</b> · tunai disetor Rp{{ number_format($l->setor_tunai,0,',','.') }}
          <div class="row-form" style="margin:8px 0">
          @foreach($l->vehicle->balances->where('qty','>',0) as $b)
            <label>{{ $b->product->nama }} ({{ $b->kondisi }}), sistem {{ $b->qty }}: <input type="number" name="fisik[{{ $b->product_id }}:{{ $b->kondisi }}]" value="{{ $b->qty }}" min="0"></label>
          @endforeach
          </div><button class="btn pri">Terima setoran</button></form></td></tr>
    @endif
    @if($l->status==='selesai' && $l->selisih)
      <tr><td colspan="8"><small style="color:var(--bad)">Selisih: @foreach($l->selisih as $s){{ $s['produk'] }} {{ $s['kondisi'] }} sistem {{ $s['sistem'] }}, fisik {{ $s['fisik'] }}. @endforeach</small></td></tr>
    @endif
  @empty <tr><td colspan="8"><small>Belum ada muat hari ini.</small></td></tr> @endforelse
</tbody></table></div></div>
<div class="card"><h2>Muat pagi</h2>
  <form method="post" action="{{ route('muat.store') }}">@csrf
    <div class="row-form" style="margin-bottom:10px"><select name="sales_route_id">@foreach($routes as $r)<option value="{{ $r->id }}" @disabled(in_array($r->user_id,$sibuk))>{{ $r->kode }} · {{ $r->user?->name }} · {{ $r->vehicle?->nama }}{{ in_array($r->user_id,$sibuk) ? ' (masih jalan)' : '' }}</option>@endforeach</select></div>
    <div class="row-form" style="margin-bottom:10px">@foreach($products as $p)<label>{{ $p->nama }} <input type="number" name="items[{{ $p->id }}]" value="0" min="0"></label>@endforeach</div>
    <button class="btn pri">Catat muat</button>
  </form>
</div>
@endsection
