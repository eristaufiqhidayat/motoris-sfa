@extends('layouts.app')
@section('title', 'Penerimaan & transfer')
@section('content')
<div class="eyebrow">Inventory / Warehouse</div>
<h1>Penerimaan & transfer</h1>
<p class="lede">Barang masuk dari pabrik dan galon kosong yang dikirim balik untuk dicuci.</p>
<div class="card"><h2>Surat jalan dari pabrik</h2><div class="scroll"><table>
  <thead><tr><th>Surat jalan</th><th>Sumber</th><th>Batch</th><th>Barang</th><th class="r">Kirim</th><th class="r">Terima</th><th class="r">Tolak</th><th>Status</th><th></th></tr></thead><tbody>
  @foreach($receipts as $gr)
    <tr><td class="mono">{{ $gr->no }}</td><td>{{ $gr->sumber }}</td><td class="mono">{{ $gr->batch }}</td><td>{{ $gr->product->nama }}</td>
    <td class="r num">{{ $gr->qty_kirim }}</td><td class="r num">{{ $gr->status==='diterima' ? $gr->qty_terima : '–' }}</td><td class="r num">{{ $gr->status==='diterima' ? $gr->qty_tolak : '–' }}</td>
    <td><span class="pill {{ $gr->status==='diterima' ? 'p-ok' : 'p-info' }}">{{ $gr->status==='diterima' ? 'Diterima' : 'Dalam perjalanan' }}</span></td>
    <td>@if($gr->status==='transit')<form class="row-form" method="post" action="{{ route('penerimaan.terima',$gr) }}">@csrf <label>Tolak <input type="number" name="qty_tolak" value="0" min="0"></label><button class="btn pri">Terima</button></form>@endif</td></tr>
  @endforeach
</tbody></table></div></div>
<div class="card"><h2>Kirim galon kosong ke pabrik</h2>
  <p class="lede">Galon kosong di gudang: <b>{{ number_format($kosong,0,',','.') }}</b></p>
  <form class="row-form" method="post" action="{{ route('penerimaan.kirim-kosong') }}">@csrf <input type="number" name="qty" value="800" min="1" max="{{ $kosong }}"> <button class="btn pri">Kirim ke pabrik</button></form>
</div>
@endsection
