<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PANELCO III SmartDocs AI') }}</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --p3-navy: #009040;
            --p3-navy-2: #006030;
            --p3-blue: #00a040;
            --p3-yellow: #ffffff;
            --p3-yellow-dark: #e6f3ea;
            --p3-bg: #f2f7f3;
            --p3-card-border: #d5e8db;
        }
        body { background: var(--p3-bg); font-family: "Segoe UI", system-ui, -apple-system, sans-serif; }
        .p3-sidebar { background: linear-gradient(180deg, var(--p3-navy) 0%, var(--p3-navy-2) 100%); min-height: 100vh; }
        .p3-sidebar .nav-link { color: #d3e9dc; border-radius: .75rem; padding: .5rem .5rem .5rem 1rem; font-weight: 500; display: flex; align-items: center; gap: 1rem; transition: background-color .25s ease, color .25s ease; }
        .p3-sidebar .nav-link:hover { background: rgba(255,255,255,.12); color: #fff; }
        .p3-sidebar .nav-link:hover .nav-ico { background: rgba(255,255,255,.28); }
        .p3-sidebar .nav-link.active { background: rgba(255,255,255,.18); color: #fff; font-weight: 600; }
        .p3-sidebar .nav-link.active .nav-ico { background: #fff; color: var(--p3-navy); }
        .p3-sidebar nav { position: relative; }
        .p3-sidebar .nav-indicator { position: absolute; left: 2px; top: 0; width: 4px; height: 24px; border-radius: 4px; background: #fff; box-shadow: 0 0 10px rgba(255,255,255,.8); opacity: 0; transition: top 1.6s cubic-bezier(.65,0,.35,1), height 1.6s ease, opacity .3s ease; pointer-events: none; }
        .p3-sidebar .nav-link.arrived .nav-ico { animation: p3-ding .55s ease; }
        @keyframes p3-ding { 0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,.7); } 40% { transform: scale(1.18); box-shadow: 0 0 0 8px rgba(255,255,255,0); } 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0); } }
        .p3-sidebar .nav-ico { width: 32px; height: 32px; flex: 0 0 32px; border-radius: 10px; background: rgba(255,255,255,.16); display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; color: #fff; transition: background .15s ease; }
        .p3-brand-mark { width: 42px; height: 42px; border-radius: 12px; background: #fff; display: flex; align-items: center; justify-content: center; color: var(--p3-navy); font-size: 1.4rem; box-shadow: 0 4px 12px rgba(0,0,0,.25); }
        .p3-topbar { background: #fff; border-bottom: 1px solid var(--p3-card-border); }
        .card-p3 { border: 1px solid var(--p3-card-border); border-radius: 1rem; box-shadow: 0 1px 3px rgba(10,31,92,.06); }
        .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .btn-p3 { background: var(--p3-navy); border-color: var(--p3-navy); color: #fff; border-radius: .65rem; font-weight: 600; }
        .btn-p3:hover { background: var(--p3-navy-2); border-color: var(--p3-navy-2); color: var(--p3-yellow); }
        .btn-p3-yellow { background: var(--p3-yellow); border-color: var(--p3-yellow); color: var(--p3-navy); border-radius: .65rem; font-weight: 700; }
        .btn-p3-yellow:hover { background: var(--p3-yellow-dark); border-color: var(--p3-yellow-dark); color: var(--p3-navy); }
        .p3-footer { color: #8a94b3; font-size: .8rem; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        main { animation: p3-fade .3s ease; }
        @keyframes p3-fade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .form-control:focus, .form-select:focus, .btn:focus-visible, a:focus-visible { box-shadow: 0 0 0 .25rem rgba(0,144,64,.25); outline: none; }
        .lift { transition: transform .18s ease, box-shadow .18s ease; }
        .lift:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,98,48,.12); }
        .avatar { width: 32px; height: 32px; flex: 0 0 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
        .avatar-user { background: #009040; color: #fff; }
        .avatar-ai { background: #fff; color: #009040; border: 2px solid #009040; }
        .msg-in { animation: p3-msg .25s ease; }
        @keyframes p3-msg { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .chat-scroll { max-height: calc(100vh - 320px); min-height: 240px; overflow-y: auto; }
        .src-card { transition: background-color .15s ease, transform .15s ease; }
        .src-card:hover { background: #e6f3ea; transform: translateX(3px); }
        @media (max-width: 767.98px) {
            .p3-sidebar { position: fixed; z-index: 1045; top: 0; left: 0; bottom: 0; width: 280px; min-height: auto; transform: translateX(-105%); transition: transform .3s ease; display: flex !important; }
            .p3-sidebar.open { transform: none; box-shadow: 0 0 40px rgba(0,0,0,.35); }
            .sidebar-backdrop { position: fixed; inset: 0; z-index: 1040; background: rgba(0,0,0,.45); opacity: 0; pointer-events: none; transition: opacity .3s ease; }
            .sidebar-backdrop.show { opacity: 1; pointer-events: auto; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        @auth
        <aside class="col-md-3 col-lg-2 d-none d-md-flex p3-sidebar p-3 flex-column" id="p3Sidebar">
            <div class="d-flex align-items-center gap-2 mb-1 px-1">
                <img src="{{ asset('images/logo.png') }}" alt="PANELCO III" style="width:58px;height:58px;object-fit:contain">
                <div class="text-white lh-sm">
                    <div class="fw-bold fs-5">SmartDocs AI</div>
                </div>
            </div>
            <nav class="nav flex-column gap-1 flex-grow-1" id="p3Nav">
                <span class="nav-indicator" id="navIndicator"></span>
                <a class="nav-link {{ request()->routeIs('dashboard','home') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-ico"><i class="bi bi-grid-1x2-fill"></i></span>Dashboard</a>
                <a class="nav-link {{ request()->routeIs('documents.upload') ? 'active' : '' }}" href="{{ route('documents.upload') }}"><span class="nav-ico"><i class="bi bi-cloud-arrow-up-fill"></i></span>Upload Documents</a>
                <a class="nav-link {{ request()->routeIs('documents.library') ? 'active' : '' }}" href="{{ route('documents.library') }}"><span class="nav-ico"><i class="bi bi-folder-fill"></i></span>Document Library</a>
                <a class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}" href="{{ route('chat.index') }}"><span class="nav-ico"><i class="bi bi-lightning-charge-fill"></i></span>AI Chat</a>
                <a class="nav-link {{ request()->routeIs('queue.*') ? 'active' : '' }}" href="{{ route('queue.index') }}"><span class="nav-ico"><i class="bi bi-layers-fill"></i></span>Processing Queue</a>
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><span class="nav-ico"><i class="bi bi-gear-fill"></i></span>Settings</a>
                @can('admin')
                <div class="mt-2 px-2"><small style="color:#9ed2ae">ADMINISTRATION</small></div>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-ico"><i class="bi bi-people-fill"></i></span>User Management</a>
                <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><span class="nav-ico"><i class="bi bi-tags-fill"></i></span>Categories</a>
                <a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}"><span class="nav-ico"><i class="bi bi-diagram-3-fill"></i></span>Departments</a>
                <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}"><span class="nav-ico"><i class="bi bi-journal-text"></i></span>Audit Logs</a>
                @endcan
            </nav>
            <div class="mt-3 p-2 rounded" style="background:rgba(255,255,255,.08)">
                <div class="text-white fw-semibold small">{{ Auth::user()->name }}</div>
                <div class="small text-capitalize" style="color:#9ed2ae">{{ str_replace('_',' ',Auth::user()->role) }}</div>
                <a class="small text-white" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
        </aside>
        <div class="col-md-9 col-lg-10 p-0">
            <div class="p3-topbar px-3 px-md-4 py-2 d-flex justify-content-between align-items-center sticky-top">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm d-md-none" id="sidebarToggle" aria-label="Open menu" style="border:1px solid #d5e8db"><i class="bi bi-list"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-capitalize" style="background:#e6f3ea;color:var(--p3-navy)">{{ str_replace('_',' ',Auth::user()->role) }}</span>
                    <i class="bi bi-shield-lock text-success" title="Private storage · authorized access only"></i>
                </div>
            </div>
            <main class="p-3 p-md-4">
                @if (session('status'))<div class="alert alert-success alert-dismissible fade show" role="alert" id="flashOk">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                @if ($errors->any())<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                @yield('content')
            </main>
        </div>
        @else
        <main class="col-12 p-0">
            @yield('content')
        </main>
        @endauth
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var nav = document.getElementById('p3Nav');
    var bar = document.getElementById('navIndicator');
    if (!nav || !bar) return;
    var links = Array.prototype.slice.call(nav.querySelectorAll('.nav-link'));
    function speed(fast) {
        bar.style.transition = fast
            ? 'top .18s ease-out, height .18s ease, opacity .2s ease'
            : 'top 1.6s cubic-bezier(.65,0,.35,1), height 1.6s ease, opacity .3s ease';
    }
    function moveTo(el, fast) {
        if (!el) { bar.style.opacity = '0'; return; }
        speed(!!fast);
        bar.style.top = (el.offsetTop + 10) + 'px';
        bar.style.height = Math.max(el.offsetHeight - 20, 16) + 'px';
        bar.style.opacity = '1';
    }
    function activeLink() { return nav.querySelector('.nav-link.active'); }
    // Elevator ride: after a short delay the car travels slowly from the
    // top floor down to your selected item, then dings on arrival.
    speed(false);
    setTimeout(function () {
        var el = activeLink();
        moveTo(el);
        setTimeout(function () {
            if (!el) return;
            el.classList.remove('arrived');
            void el.offsetWidth;
            el.classList.add('arrived');
            setTimeout(function () { el.classList.remove('arrived'); }, 600);
        }, 1700);
    }, 450);
    // Hover = express ride; leaving returns the car to your floor.
    links.forEach(function (a) {
        a.addEventListener('mouseenter', function () { moveTo(a, true); });
        a.addEventListener('click', function () { moveTo(a, true); });
    });
    nav.addEventListener('mouseleave', function () { moveTo(activeLink(), true); });
})();
(function () {
    var ok = document.getElementById('flashOk');
    if (ok) setTimeout(function () {
        try { bootstrap.Alert.getOrCreateInstance(ok).close(); } catch (e) { ok.remove(); }
    }, 5000);
})();
(function () {
    var sidebar = document.getElementById('p3Sidebar');
    var toggle = document.getElementById('sidebarToggle');
    if (!sidebar || !toggle) return;
    var backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
    function close() { sidebar.classList.remove('open'); backdrop.classList.remove('show'); }
    toggle.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('show', sidebar.classList.contains('open'));
    });
    backdrop.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
</body>
</html>
