@extends('layouts.app')
@section('title', 'Stok depo')
@section('content')
<div class="eyebrow">Inventory / Warehouse</div>
<h1>Stok depo</h1>
<p class="lede">Stok di gudang dan di kendaraan sales. Muat, penjualan, dan setoran dari aplikasi Oasis Sales tercatat otomatis di kartu stok.</p>
@php($g = $rows->firstWhere('product.kode','GLN19'))
<div class="kpis">
  <div class="kpi"><div class="l">Galon isi di gudang</div><div class="v num">{{ number_format($g['gudang'],0,',','.') }}</div><div class="s">{{ $g['cukup_hari'] ? 'cukup '.number_format($g['cukup_hari'], 1, ',', '.').' hari' : 'belum ada data jual 7 hari' }}</div></div>
  <div class="kpi"><div class="l">Galon isi di kendaraan</div><div class="v num">{{ number_format($g['jalan'],0,',','.') }}</div><div class="s">dibawa sales hari ini</div></div>
  <div class="kpi"><div class="l">Galon kosong</div><div class="v num">{{ number_format($g['kosong'],0,',','.') }}</div><div class="s">siap kirim ke pabrik</div></div>
  <div class="kpi"><div class="l">Perlu tindakan</div><div class="v num">{{ count($alerts) }}</div><div class="s">lihat daftar di kanan</div></div>
</div>
<div class="grid2"><div>
  <div class="card"><h2>Stok per barang</h2><div class="scroll"><table>
    <thead><tr><th>Barang</th><th class="r">Gudang</th><th class="r">Di kendaraan</th><th class="r">Kosong</th><th class="r">Cukup</th><th>Level</th><th>Status</th></tr></thead><tbody>
    @foreach($rows as $r)
      <tr><td>{{ $r['product']->nama }} <small>{{ $r['product']->satuan }}</small></td>
      <td class="r num">{{ number_format($r['gudang'],0,',','.') }}</td><td class="r num">{{ $r['jalan'] }}</td>
      <td class="r num">{{ $r['kosong'] ?? '–' }}</td><td class="r num">{{ $r['cukup_hari'] ? number_format($r['cukup_hari'], 1, ',', '.').' hr' : '–' }}</td>
      <td><div class="bar"><i style="width:{{ min(100, $r['product']->max_stok ? $r['gudang']/$r['product']->max_stok*100 : 0) }}%"></i></div></td>
      <td><span class="pill {{ ['aman'=>'p-ok','rendah'=>'p-warn','kritis'=>'p-bad'][$r['status']] }}">{{ ucfirst($r['status']) }}</span></td></tr>
    @endforeach
  </tbody></table></div></div>
  <div class="card"><h2>Kartu stok terbaru</h2><div class="scroll"><table>
    <thead><tr><th>Waktu</th><th>Jenis</th><th>Barang</th><th>Dari</th><th>Ke</th><th class="r">Qty</th><th>Ref</th></tr></thead><tbody>
    @foreach($movements as $m)
      <tr><td class="mono">{{ $m->created_at->format('d/m H:i') }}</td><td>{{ str_replace('_',' ',$m->jenis) }}</td><td>{{ $m->product->nama }} @if($m->kondisi!=='isi')<span class="pill p-mute">{{ $m->kondisi }}</span>@endif</td>
      <td><small>{{ $m->from?->nama ?? 'luar depo' }}</small></td><td><small>{{ $m->to?->nama ?? 'luar depo' }}</small></td><td class="r num">{{ $m->qty }}</td><td class="mono">{{ $m->ref_no }}</td></tr>
    @endforeach
  </tbody></table></div></div>
</div><div>
  <div class="card"><h2>Perlu tindakan</h2>
    @forelse($alerts as [$c,$b,$s,$link])
      <div class="alert {{ $c }}"><b>@if($link)<a href="{{ $link }}">{{ $b }}</a>@else{{ $b }}@endif</b><span>{{ $s }}</span></div>
    @empty <p class="lede">Tidak ada.</p> @endforelse
  </div>
</div></div>
@endsection
