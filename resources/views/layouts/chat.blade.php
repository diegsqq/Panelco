<!doctype html>
@php $p3Dark = auth()->user()?->theme === 'dark'; @endphp
<html lang="{{ app()->getLocale() }}" data-theme="{{ $p3Dark ? 'dark' : 'light' }}" data-bs-theme="{{ $p3Dark ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lightning AI · {{ config('app.name', 'PANELCO III SmartDocs AI') }}</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: "Segoe UI", system-ui, -apple-system, sans-serif; }
        /* Light default */
        html { background: #f7f7f7; color: #111; }
        body { background: inherit; color: inherit; }
        /* Dark */
        html[data-theme="dark"] { background: #212121; color: #ececec; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    @if (session('status'))
    <div style="position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:9999;background:#00A868;color:#fff;border-radius:.65rem;padding:.55rem 1.25rem;font-size:.85rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.35)">
        {{ session('status') }}
    </div>
    @endif

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



