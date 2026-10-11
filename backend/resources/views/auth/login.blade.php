<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Masuk · Oasis ERP</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f5f8;font:14px/1.5 system-ui,sans-serif;color:#111a2b}form{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:24px;width:min(360px,92vw);display:grid;gap:10px}h1{margin:0 0 6px;font-size:22px}input{border:1px solid #e3e8ef;border-radius:8px;padding:9px 10px;font:inherit}button{background:#0f1b2d;color:#fff;border:0;border-radius:8px;padding:10px;font-weight:700;font:inherit;cursor:pointer}.err{color:#c2352b;font-size:13px}small{color:#5d6878}</style></head>
<body><form method="post" action="/login">@csrf
<h1>Oasis ERP</h1><small>Back-office depo: admin & gudang</small>
@error('email')<div class="err">{{ $message }}</div>@enderror
<input type="email" name="email" placeholder="Email" value="{{ old('email', 'admin@oasis.test') }}" required>
<input type="password" name="password" placeholder="Kata sandi" required>
<label><input type="checkbox" name="remember"> Ingat saya</label>
<button>Masuk</button></form></body></html>
