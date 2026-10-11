<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}"><title>Masuk · Oasis ERP</title>
<style>:root{--navy:#293694;--teal:#18a6b1}body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(160deg,#eef0fa 0%,#fff 55%,#e2f5f6 100%);font:14px/1.5 system-ui,sans-serif;color:#111a2b}form{background:#fff;border:1px solid #e3e8ef;border-top:4px solid var(--teal);border-radius:14px;padding:28px 24px 24px;width:min(360px,92vw);display:grid;gap:10px;box-shadow:0 10px 30px rgba(41,54,148,.08)}.logo{display:block;width:200px;max-width:80%;margin:0 auto 4px}h1{margin:0;font-size:18px;color:var(--navy);text-align:center}small{color:#5d6878;text-align:center;margin-bottom:6px}input{border:1px solid #e3e8ef;border-radius:8px;padding:9px 10px;font:inherit}input:focus{outline:2px solid var(--teal);border-color:transparent}button{background:var(--navy);color:#fff;border:0;border-radius:8px;padding:10px;font:inherit;font-weight:700;cursor:pointer}button:hover{background:#1f2a78}.err{color:#c2352b;font-size:13px}</style></head>
<body><form method="post" action="/login">@csrf
<img class="logo" src="{{ asset('img/oasis-logo.png') }}" alt="Oasis, air minum dalam kemasan">
<h1>ERP Depo</h1><small>Back-office depo: admin & gudang</small>
@error('email')<div class="err">{{ $message }}</div>@enderror
<input type="email" name="email" placeholder="Email" value="{{ old('email', 'admin@oasis.test') }}" required>
<input type="password" name="password" placeholder="Kata sandi" required>
<label><input type="checkbox" name="remember"> Ingat saya</label>
<button>Masuk</button></form></body></html>
