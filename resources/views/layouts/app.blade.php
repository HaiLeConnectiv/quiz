<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Thi trực tuyến')</title>
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f5f7fb;color:#172033}
        a{color:#2563eb;text-decoration:none}.container{max-width:1100px;margin:32px auto;padding:0 20px}
        .nav{background:#111827;color:#fff;padding:14px 20px;display:flex;justify-content:space-between;align-items:center}
        .card{background:#fff;border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:0 2px 12px #0000000d}
        input,textarea,select{width:100%;padding:11px;border:1px solid #d1d5db;border-radius:8px;margin-top:6px}
        textarea{min-height:120px}.btn{border:0;border-radius:8px;padding:11px 16px;cursor:pointer;background:#2563eb;color:#fff}
        .btn.secondary{background:#64748b}.btn.danger{background:#dc2626}.btn.success{background:#16a34a}
        .row{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}.mt{margin-top:16px}.error{color:#dc2626}.success{color:#15803d}
        table{width:100%;border-collapse:collapse}th,td{padding:11px;border-bottom:1px solid #e5e7eb;text-align:left}
        .timer{font-size:54px;font-weight:700;text-align:center;margin:25px}.center{text-align:center}
        @media(max-width:700px){.row{grid-template-columns:1fr}.timer{font-size:42px}}
    </style>
    @yield('head')
</head>
<body>
@auth
<div class="nav">
    <strong>THI TRỰC TUYẾN</strong>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn secondary">Đăng xuất</button>
    </form>
</div>
@endauth
<div class="container">@yield('content')</div>
@yield('scripts')
</body>
</html>
