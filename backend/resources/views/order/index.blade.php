@extends('layouts.app')
@section('title', 'Sales order')
@section('content')
<div class="eyebrow">Sales & Distribusi</div>
<h1>Sales order</h1>
<p class="lede">Order dari aplikasi Oasis Sales. <b>Canvas</b> = dijual langsung dari motor (sudah diserahkan). <b>Taking order</b> = dikirim kemudian; order yang melewati limit kredit perlu persetujuan.</p>
<div class="row-form" style="margin-bottom:12px">
  @foreach([null=>'Semua','menunggu_approval'=>'Menunggu approval','disetujui'=>'Disetujui','selesai'=>'Selesai','ditolak'=>'Ditolak'] as $k=>$l)
    <a class="btn {{ $status===$k || (!$status && !$k) ? 'pri' : '' }}" href="{{ route('order', $k ? ['status'=>$k] : []) }}">{{ $l }}</a>
  @endforeach
</div>
<div class="card"><div class="scroll"><table>
  <thead><tr><th>No.</th><th>Waktu</th><th>Tipe</th><th>Outlet</th><th>Sales</th><th>Isi</th><th class="r">Total</th><th>Bayar</th><th>Status</th><th></th></tr></thead><tbody>
  @forelse($orders as $o)
    <tr><td class="mono">{{ $o->no }}</td><td class="mono">{{ $o->created_at->format('d/m H:i') }}</td><td><span class="pill p-mute">{{ $o->tipe==='canvas' ? 'Canvas' : 'Taking order' }}</span></td>
    <td>{{ $o->outlet->nama }}@if($o->alasan)<br><small>{{ $o->alasan }}</small>@endif</td><td>{{ $o->user->name }}</td>
    <td><small>@foreach($o->items as $i){{ $i->qty }} {{ $i->product->nama }}@if(!$loop->last), @endif @endforeach @if($o->galon_kosong_ditarik) · tarik {{ $o->galon_kosong_ditarik }} kosong @endif</small></td>
    <td class="r num">Rp{{ number_format($o->total,0,',','.') }}</td><td>{{ $o->metode_bayar }}</td>
    <td><span class="pill {{ ['menunggu_approval'=>'p-warn','disetujui'=>'p-info','selesai'=>'p-ok','ditolak'=>'p-bad'][$o->status] ?? 'p-mute' }}">{{ str_replace('_',' ',$o->status) }}</span></td>
    <td>@if($o->status==='menunggu_approval')
      <form method="post" action="{{ route('order.approve',$o) }}" style="display:inline">@csrf<button class="btn pri">Setujui</button></form>
      <form method="post" action="{{ route('order.reject',$o) }}" style="display:inline">@csrf<input type="hidden" name="alasan" value="Lunasi piutang dulu"><button class="btn">Tolak</button></form>
    @endif</td></tr>
  @empty <tr><td colspan="10"><small>Belum ada order.</small></td></tr> @endforelse
</tbody></table></div>{{ $orders->links() }}</div>
@endsection
