<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') · كلية التقنية الإلكترونية - طرابلس</title>
    {{-- Inline styles only: an error page must render even if Vite assets are missing. --}}
    <style>
        :root { --brand: #1e3a8a; --brand-soft: #e0e7ff; --ink: #0f172a; --muted: #64748b; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: var(--ink);
            font-family: "Tajawal", "Cairo", "Segoe UI", Tahoma, sans-serif;
            padding: 1.5rem;
        }
        .card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 10px 40px rgba(15, 23, 42, .08);
            max-width: 32rem;
            width: 100%;
            padding: 3rem 2.5rem;
            text-align: center;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4.5rem;
            height: 4.5rem;
            margin-bottom: 1.5rem;
            border-radius: 9999px;
            background: var(--brand-soft);
            color: var(--brand);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: .05em;
        }
        .code { font-size: 3.5rem; font-weight: 800; color: var(--brand); line-height: 1; margin: 0; }
        .title { font-size: 1.375rem; font-weight: 700; margin: .75rem 0 .5rem; }
        .message { color: var(--muted); font-size: 1rem; line-height: 1.8; margin: 0 0 2rem; }
        .actions { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .75rem 1.75rem;
            border-radius: .75rem;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            transition: background-color .15s ease, color .15s ease;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: #1e40af; }
        .btn-ghost { background: transparent; color: var(--brand); border: 1px solid var(--brand-soft); }
        .btn-ghost:hover { background: var(--brand-soft); }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">CET</div>
        <p class="code">@yield('code')</p>
        <h1 class="title">@yield('title')</h1>
        <p class="message">@yield('message')</p>
        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">العودة إلى الرئيسية</a>
            @hasSection('secondary')
                @yield('secondary')
            @endif
        </div>
    </div>
</body>
</html>
