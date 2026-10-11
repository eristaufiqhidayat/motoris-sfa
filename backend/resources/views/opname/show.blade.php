@extends('layouts.app')
@section('title', $opname->no)
@section('content')
<div class="eyebrow"><a href="{{ route('opname') }}">Stock opname</a></div>
<h1>{{ $opname->no }}</h1>
<p class="lede">{{ $opname->location->nama }} · {{ $opname->status==='posted' ? 'Diposting '.$opname->posted_at->format('d/m/Y H:i') : 'Draft' }}</p>
<form method="post" action="{{ route('opname.post',$opname) }}">@csrf
<div class="card"><div class="scroll"><table>
  <thead><tr><th>Barang</th><th>Kondisi</th><th class="r">Sistem</th><th class="r">Fisik</th><th class="r">Selisih</th><th>Penyebab</th></tr></thead><tbody>
  @foreach($opname->items as $i)
    <tr><td>{{ $i->product->nama }}</td><td>{{ $i->kondisi }}</td><td class="r num">{{ $i->qty_sistem }}</td>
    <td class="r">@if($opname->status==='draft')<input type="number" min="0" name="items[{{ $i->id }}][qty_fisik]" value="{{ $i->qty_fisik }}">@else {{ $i->qty_fisik }} @endif</td>
    <td class="r num" style="color:{{ $i->selisih()<0 ? 'var(--bad)' : 'inherit' }}">{{ $i->selisih() }}</td>
    <td>@if($opname->status==='draft')<input type="text" name="items[{{ $i->id }}][penyebab]" value="{{ $i->penyebab }}" placeholder="Wajib bila ada selisih">@else <small>{{ $i->penyebab }}</small> @endif</td></tr>
  @endforeach
</tbody></table></div></div>
@if($opname->status==='draft')<button class="btn pri">Posting opname</button>@endif
</form>
@endsection
