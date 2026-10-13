@extends('layouts.app')
@section('title', 'Kunjungan')
@section('content')
<div class="eyebrow">Sales & Distribusi</div>
<h1>Kunjungan & check-in</h1>
<p class="lede">Setiap check-in wajib GPS (maks. {{ \App\Services\CheckinGuard::RADIUS_METER }} m dari outlet) dan foto toko. Lokasi palsu langsung ditolak. Check-in yang lolos tapi janggal ditandai di sini untuk dicek supervisor.</p>
<div class="row-form" style="margin-bottom:12px">
  <form method="get" class="row-form"><input type="date" name="tanggal" value="{{ $tanggal->format('Y-m-d') }}" style="border:1px solid var(--line);background:var(--surface-2);border-radius:8px;padding:5px 8px">@if($janggal)<input type="hidden" name="janggal" value="1">@endif<button class="btn">Lihat</button></form>
  <a class="btn {{ $janggal ? '' : 'pri' }}" href="{{ route('kunjungan', ['tanggal' => $tanggal->format('Y-m-d')]) }}">Semua</a>
  <a class="btn {{ $janggal ? 'pri' : '' }}" href="{{ route('kunjungan', ['tanggal' => $tanggal->format('Y-m-d'), 'janggal' => 1]) }}">Janggal saja</a>
  @if($perluCek)<span class="pill p-warn">{{ $perluCek }} belum diperiksa (semua tanggal)</span>@endif
</div>
<div class="card"><div class="scroll"><table>
  <thead><tr><th>Foto</th><th>Jam</th><th>Sales</th><th>Outlet</th><th class="r">Jarak</th><th class="r">Akurasi</th><th>Hasil</th><th>Catatan sistem</th><th></th></tr></thead><tbody>
  @forelse($visits as $v)
    <tr>
      <td>@if($v->foto_path)<a href="{{ route('kunjungan.foto', $v) }}" target="_blank"><img src="{{ route('kunjungan.foto', $v) }}" alt="Foto {{ $v->outlet->nama }}" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid var(--line);display:block"></a>@else<small>tanpa foto</small>@endif</td>
      <td class="mono">{{ $v->checkin_at->format('H:i') }}</td>
      <td>{{ $v->user->name }}<br><small>{{ $v->user->kode }}</small></td>
      <td>{{ $v->outlet->nama }}<br><small>{{ $v->outlet->alamat }}</small></td>
      <td class="r num">{{ $v->jarak_meter !== null ? $v->jarak_meter.' m' : '–' }}</td>
      <td class="r num">{{ $v->gps_akurasi !== null ? '±'.round($v->gps_akurasi).' m' : '–' }}</td>
      <td><span class="pill p-mute">{{ str_replace('_', ' ', $v->status) }}</span>@if($v->order)<br><small class="mono">{{ $v->order->no }}</small>@endif</td>
      <td>@forelse($v->flags ?? [] as $f)<span class="pill {{ $v->diperiksa_at ? 'p-mute' : 'p-warn' }}" style="margin:1px 2px 1px 0">{{ $labels[$f] ?? $f }}</span>@empty<span class="pill p-ok">Wajar</span>@endforelse
        @if($v->diperiksa_at)<br><small>Diperiksa {{ $v->diperiksa_at->format('d/m H:i') }}</small>@endif</td>
      <td>@if($v->flags && !$v->diperiksa_at)<form method="post" action="{{ route('kunjungan.periksa', $v) }}">@csrf<button class="btn pri">Sudah dicek</button></form>@endif</td>
    </tr>
  @empty <tr><td colspan="9"><small>Belum ada kunjungan di tanggal ini.</small></td></tr> @endforelse
</tbody></table></div></div>
@endsection
