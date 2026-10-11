@extends('layouts.app')
@section('title', 'Galon tracker')
@section('content')
<div class="eyebrow">Inventory / Warehouse</div>
<h1>Galon tracker</h1>
<p class="lede">Posisi dan riwayat setiap galon berseri. Custody berpindah otomatis saat muat, serah terima di outlet, dan setoran.</p>
<div class="kpis">@foreach($posisi as $l => $v)<div class="kpi"><div class="l">{{ $l }}</div><div class="v num">{{ number_format($v,0,',','.') }}</div></div>@endforeach</div>
<div class="grid2"><div>
  <div class="card"><h2>Cari nomor seri</h2>
    <form class="row-form" method="get"><input type="text" name="serial" value="{{ $q }}" placeholder="GLN-19L-418120" style="flex:1;font-family:var(--f-mono)"><button class="btn pri">Cari</button></form>
    @if($q && !$galon)<p class="lede" style="margin-top:10px">Nomor seri {{ $q }} tidak ditemukan.</p>@endif
    @if($galon)
      <h2 class="mono" style="margin-top:14px">{{ $galon->serial }}</h2>
      <p>Siklus <b>{{ $galon->siklus }} / {{ \App\Models\Galon::MAKS_SIKLUS }}</b> · {{ $galon->kondisi }} · <b>{{ $galon->posisi() }}</b></p>
      @if($galon->mendekatiBatas())<div class="alert warn"><b>Mendekati batas siklus</b><span>Pisahkan untuk pensiun saat kembali kosong.</span></div>@endif
      <ul class="tl">@forelse($galon->events as $e)<li><b>{{ $e->event }}</b><small>{{ $e->dari }} → {{ $e->ke }} · {{ $e->ref_no }} · {{ $e->created_at->format('d/m H:i') }}</small></li>@empty<li><small>Belum ada riwayat sejak dilabel.</small></li>@endforelse</ul>
    @endif
  </div>
  <div class="card"><h2>Perpindahan terbaru</h2><div class="scroll"><table>
    <thead><tr><th>Waktu</th><th>Seri</th><th>Kejadian</th><th>Dari</th><th>Ke</th><th>Ref</th></tr></thead><tbody>
    @foreach($terbaru as $e)<tr><td class="mono">{{ $e->created_at->format('H:i') }}</td><td class="mono"><a href="?serial={{ $e->galon->serial }}">{{ $e->galon->serial }}</a></td><td>{{ $e->event }}</td><td><small>{{ $e->dari }}</small></td><td><small>{{ $e->ke }}</small></td><td class="mono">{{ $e->ref_no }}</td></tr>@endforeach
  </tbody></table></div></div>
</div><div>
  <div class="card"><h2>Sebaran siklus pakai</h2><table><tbody>@foreach($siklus as $l => $v)<tr><td>{{ $l }}</td><td class="r num">{{ number_format($v,0,',','.') }}</td></tr>@endforeach</tbody></table></div>
  <div class="card"><h2>Tidak kembali &gt; 60 hari</h2>
    @forelse($tidakKembali as $t)<div><span class="mono">{{ $t->serial }}</span> <small>{{ $t->outlet->nama }} · {{ $t->last_moved_at->diffForHumans() }}</small></div>@empty<p class="lede">Tidak ada.</p>@endforelse
  </div>
</div></div>
@endsection
