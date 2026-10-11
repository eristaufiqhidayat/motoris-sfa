<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
<title>@yield('title', 'Inventori') · Oasis ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap">
<style>
:root{--bg:#f3f5f8;--surface:#fff;--surface-2:#f7f9fc;--line:#e3e8ef;--fg:#111a2b;--muted:#5d6878;--faint:#8a94a3;--ink:#293694;--ink-fg:#fff;--accent:#293694;--accent-soft:#e9ebf7;--teal:#18a6b1;--teal-soft:#e2f5f6;--ok:#11845b;--ok-soft:#e3f4ec;--warn:#a86300;--warn-soft:#fdf0dc;--bad:#c2352b;--bad-soft:#fbe6e4;--f-ui:"Plus Jakarta Sans",system-ui,sans-serif;--f-mono:"JetBrains Mono",ui-monospace,monospace}
@media (prefers-color-scheme:dark){:root{--bg:#0c121c;--surface:#141c28;--surface-2:#18212f;--line:#263142;--fg:#e8edf4;--muted:#9aa6b6;--faint:#6f7b8c;--ink:#8f9be6;--ink-fg:#0c121c;--accent:#8f9be6;--accent-soft:#1b2142;--teal:#46c6cf;--teal-soft:#10302f;--ok:#4cc794;--ok-soft:#12291f;--warn:#e9a54a;--warn-soft:#2d2212;--bad:#f07a70;--bad-soft:#331816;color-scheme:dark}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--fg);font:14px/1.5 var(--f-ui)}
a{color:var(--accent);text-decoration:none}button,input,select{font:inherit;color:inherit}
.shell{display:grid;grid-template-columns:240px minmax(0,1fr);min-height:100vh}
@media(max-width:900px){.shell{grid-template-columns:1fr}}
aside{background:var(--surface);border-right:1px solid var(--line);padding:16px 12px}
.brand{display:flex;flex-direction:column;gap:6px;padding:0 6px 12px;border-bottom:3px solid var(--teal);margin-bottom:6px}.brand .logo{background:#fff;border-radius:10px;padding:8px 10px}.brand .logo img{display:block;width:100%;max-width:170px;height:auto}
.brand b{display:block;line-height:1.1}.brand small{color:var(--muted);font-size:12px}
.grp{font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--faint);padding:12px 8px 4px}
nav a{display:flex;justify-content:space-between;padding:7px 10px;border-radius:8px;color:var(--fg)}nav a:hover{background:var(--surface-2)}
nav a.on{background:var(--ink);color:var(--ink-fg)}nav .k{font-family:var(--f-mono);font-size:10px;padding:1px 6px;border-radius:4px;background:var(--bad);color:#fff}
.topbar{border-top:3px solid var(--accent);display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 24px;background:var(--surface);border-bottom:1px solid var(--line);font-size:13px;color:var(--muted)}
main{padding:20px 24px 56px;max-width:1240px}@media(max-width:900px){main{padding:16px}}
h1{font-size:30px;letter-spacing:-.02em;margin:4px 0}.lede{color:var(--muted);margin:0 0 18px;max-width:72ch}
.eyebrow{font-size:13px;color:var(--teal);font-weight:600}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:16px}
.kpi,.card{background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:14px 16px}
.card{margin-bottom:16px;min-width:0}.card h2{font-size:15px;margin:0 0 10px}
.kpi .l{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)}.kpi .v{font-size:26px;font-weight:800;margin-top:4px}.kpi .s{font-size:12px;color:var(--muted)}
.grid2{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:16px}@media(max-width:1000px){.grid2{grid-template-columns:1fr}}
.scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:left;font-size:10.5px;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);padding:8px 10px;border-bottom:1px solid var(--line);white-space:nowrap}
td{padding:8px 10px;border-bottom:1px solid var(--line);white-space:nowrap;vertical-align:middle}tr:last-child td{border-bottom:0}td small{color:var(--muted)}
.r{text-align:right}.num{font-variant-numeric:tabular-nums}.mono{font-family:var(--f-mono);font-size:12px}
.pill{display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px}
.p-ok{background:var(--ok-soft);color:var(--ok)}.p-warn{background:var(--warn-soft);color:var(--warn)}.p-bad{background:var(--bad-soft);color:var(--bad)}.p-info{background:var(--accent-soft);color:var(--accent)}.p-mute{background:var(--surface-2);color:var(--muted);border:1px solid var(--line)}
.btn{border:1px solid var(--line);background:var(--surface);padding:6px 12px;border-radius:8px;font-weight:600;font-size:12.5px;cursor:pointer}.btn.pri{background:var(--ink);color:var(--ink-fg);border-color:var(--ink)}
input[type=number],input[type=text],input[type=email],input[type=password],select{border:1px solid var(--line);background:var(--surface-2);border-radius:8px;padding:6px 9px}
input[type=number]{width:90px;text-align:right}
.flash{padding:10px 14px;border-radius:10px;margin-bottom:14px;font-weight:600}.flash.ok{background:var(--ok-soft);color:var(--ok)}.flash.err{background:var(--bad-soft);color:var(--bad)}
.alert{border-left:4px solid var(--accent);background:var(--surface-2);border-radius:8px;padding:8px 12px;margin-bottom:8px}.alert.bad{border-color:var(--bad)}.alert.warn{border-color:var(--warn)}
.alert b{display:block;font-size:13px}.alert span{font-size:12.5px;color:var(--muted)}
.bar{height:6px;background:var(--surface-2);border:1px solid var(--line);border-radius:9px;overflow:hidden;min-width:70px}.bar i{display:block;height:100%;background:var(--accent)}
.row-form{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.tl{list-style:none;margin:10px 0 0;padding:0 0 0 18px;border-left:2px solid var(--line)}.tl li{margin-bottom:10px}.tl small{display:block;color:var(--muted)}
</style>
</head>
<body>
<div class="shell">
  <aside>
    <div class="brand"><div class="logo"><img src="{{ asset('img/oasis-logo.png') }}" alt="Oasis"></div><div><b>ERP Depo</b><small>{{ auth()->user()->depot?->nama }}</small></div></div>
    @php($pending = \App\Models\SalesOrder::where('status','menunggu_approval')->count())
    @php($setor = \App\Models\Loading::where('status','setor')->count())
    @php($transit = \App\Models\GoodsReceipt::where('status','transit')->count())
    <nav>
      <div class="grp">Inventory / Warehouse</div>
      <a href="{{ route('stok') }}" class="{{ request()->routeIs('stok') ? 'on' : '' }}">Stok depo</a>
      <a href="{{ route('galon') }}" class="{{ request()->routeIs('galon') ? 'on' : '' }}">Galon tracker</a>
      <a href="{{ route('penerimaan') }}" class="{{ request()->routeIs('penerimaan*') ? 'on' : '' }}">Penerimaan & transfer @if($transit)<span class="k">{{ $transit }}</span>@endif</a>
      <a href="{{ route('opname') }}" class="{{ request()->routeIs('opname*') ? 'on' : '' }}">Stock opname</a>
      <div class="grp">Sales & Distribusi</div>
      <a href="{{ route('muat') }}" class="{{ request()->routeIs('muat*') ? 'on' : '' }}">Muat & setoran @if($setor)<span class="k">{{ $setor }}</span>@endif</a>
      <a href="{{ route('order') }}" class="{{ request()->routeIs('order*') ? 'on' : '' }}">Sales order @if($pending)<span class="k">{{ $pending }}</span>@endif</a>
    </nav>
  </aside>
  <div style="min-width:0">
    <div class="topbar"><span>{{ now()->translatedFormat('l, j F Y') }}</span>
      <form method="post" action="{{ route('logout') }}">@csrf {{ auth()->user()->name }} · <button class="btn">Keluar</button></form></div>
    <main>
      @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
      @if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
      @yield('content')
    </main>
  </div>
</div>
</body>
</html>
