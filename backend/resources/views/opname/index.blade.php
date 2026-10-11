@extends('layouts.app')
@section('title', 'Stock opname')
@section('content')
<div class="eyebrow">Inventory / Warehouse</div>
<h1>Stock opname</h1>
<p class="lede">Buat lembar opname dari saldo sistem gudang, isi hitungan fisik, lalu posting. Selisih langsung menyesuaikan stok dan tercatat di kartu stok.</p>
<form method="post" action="{{ route('opname.store') }}" style="margin-bottom:14px">@csrf<button class="btn pri">Buat opname gudang</button></form>
<div class="card"><div class="scroll"><table>
  <thead><tr><th>No.</th><th>Lokasi</th><th>Dibuat</th><th class="r">Baris</th><th class="r">Selisih</th><th>Status</th></tr></thead><tbody>
  @forelse($opnames as $o)
    <tr><td class="mono"><a href="{{ route('opname.show',$o) }}">{{ $o->no }}</a></td><td>{{ $o->location->nama }}</td><td class="mono">{{ $o->created_at->format('d/m H:i') }}</td>
    <td class="r num">{{ $o->items->count() }}</td><td class="r num">{{ $o->items->sum(fn($i)=>$i->selisih()) }}</td>
    <td><span class="pill {{ $o->status==='posted' ? 'p-ok' : 'p-mute' }}">{{ $o->status==='posted' ? 'Diposting' : 'Draft' }}</span></td></tr>
  @empty <tr><td colspan="6"><small>Belum ada opname.</small></td></tr> @endforelse
</tbody></table></div></div>
@endsection
