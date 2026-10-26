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
            --p3-navy: #00A868;
            --p3-navy-2: #00743F;
            --p3-blue: #1DBF7D;
            --p3-yellow: #ffffff;
            --p3-yellow-dark: #e6f3ea;
            --p3-bg: #f2f7f3;
            --p3-card-border: #d5e8db;
        }
        body { background: var(--p3-bg); font-family: "Segoe UI", system-ui, -apple-system, sans-serif; }
        .p3-sidebar { background: linear-gradient(180deg, var(--p3-navy) 0%, var(--p3-navy-2) 100%); min-height: 100vh; max-height: 100vh; position: sticky; top: 0; overflow-y: auto; }
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
        .form-control:focus, .form-select:focus, .btn:focus-visible, a:focus-visible { box-shadow: 0 0 0 .25rem rgba(0,168,104,.25); outline: none; }
        .form-select.p3-chosen { color: var(--p3-navy); font-weight: 600; background-color: #f0f9f3; }
        .p3-dept-pill { border: 1px solid #d5e8db; color: var(--p3-navy); background: #fff; border-radius: 2rem; font-weight: 500; padding: .35rem .9rem; transition: all .15s ease; }
        .p3-dept-pill:hover { background: #e6f3ea; }
        .p3-dept-pill:has(input:checked) { background: var(--p3-navy); border-color: var(--p3-navy); color: #fff; box-shadow: 0 2px 6px rgba(0,168,104,.3); }
        .p3-logout { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.3); color: #fff; border-radius: .75rem; font-weight: 600; padding: .6rem 1rem; letter-spacing: .3px; transition: all .18s ease; }
        .p3-logout:hover, .p3-logout:focus { background: #fff; color: #c0392b; border-color: #fff; box-shadow: 0 6px 16px rgba(0,0,0,.3); transform: translateY(-1px); }
        .p3-logout:active { transform: none; }
        .p3-modal { position: relative; border: none; border-radius: 1.25rem; overflow: hidden; box-shadow: 0 20px 50px rgba(0,100,60,.25); }
        .p3-modal .btn-close { position: absolute; top: .75rem; right: .75rem; opacity: .5; }
        .p3-meow { display: block; margin: 0 auto; filter: drop-shadow(0 6px 12px rgba(0,100,60,.25)); }
        @keyframes p3-meow-bob { 0%,100% { transform: translateY(0) rotate(-2deg); } 50% { transform: translateY(-7px) rotate(2deg); } }
        .p3-modal-actions { display: flex; gap: .75rem; padding: 1rem 1.25rem 1.25rem; margin-top: 1.25rem; border-top: 1px solid var(--p3-card-border); }
        .p3-modal .btn { border-radius: .7rem; font-weight: 600; padding: .55rem 1rem; }
        .lift { transition: transform .18s ease, box-shadow .18s ease; }
        .lift:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,100,60,.12); }
        .avatar { width: 32px; height: 32px; flex: 0 0 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
        .avatar-user { background: #00A868; color: #fff; }
        .avatar-ai { background: #fff; color: #00A868; border: 2px solid #00A868; }
        .msg-in { animation: p3-msg .25s ease; }
        @keyframes p3-msg { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .chat-scroll { max-height: calc(100vh - 320px); min-height: 240px; overflow-y: auto; }
        .src-card { transition: background-color .15s ease, transform .15s ease; }
        .src-card:hover { background: #e6f3ea; transform: translateX(3px); }
        /* Extracted-table rendering */
        .p3-table-wrap { border: 1px solid var(--p3-card-border); border-radius: .85rem; overflow: hidden; }
        .table-p3 { --bs-table-striped-bg: #f6fbf7; margin-bottom: 0; }
        .table-p3 thead th { background: linear-gradient(180deg, var(--p3-navy) 0%, var(--p3-navy-2) 100%); color: #fff; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; border: 0; padding: .65rem .85rem; white-space: nowrap; }
        .table-p3 tbody td { font-size: .82rem; padding: .7rem .85rem; vertical-align: top; }
        .table-p3 tbody tr { border-top: 1px solid #e9f3ec; }
        .table-p3 tbody tr:nth-child(odd) { background: #fbfefc; }
        .table-p3 tbody tr:hover { background: #eef9f1; }
        .p3-cat-pill { display: inline-block; padding: .2rem .65rem; border-radius: 2rem; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; background: #e6f3ea; color: #1F7A2B; border: 1px solid #cde9d6; min-width: 7.5rem; text-align: center; box-sizing: border-box; }
        /* Leverage, profitability, investment, efficiency, and market use the
           same color as the default (liquidity) pill. */
        .p3-result { display: inline-block; padding: .18rem .6rem; border-radius: .5rem; background: var(--p3-navy); color: #fff; font-weight: 700; font-size: .8rem; white-space: nowrap; }
        .p3-formula { display: inline-block; background: #f1f5f3; border: 1px dashed #c9dcd0; color: #33503f; border-radius: .45rem; padding: .12rem .5rem; font-size: .76rem; white-space: nowrap; }
        .p3-cell-interp { color: #4b5a52; max-width: 26rem; }
        /* View-only DOCX rendering (mammoth.js output) */
        .p3-docx { background: #fff; width: 100%; min-height: 100%; padding: 2.5rem 3rem; color: #2b3a32; font-size: .88rem; line-height: 1.7; }
        .p3-docx > * { max-width: 52rem; margin-left: auto; margin-right: auto; }
        .p3-docx h1, .p3-docx h2, .p3-docx h3, .p3-docx h4 { font-weight: 700; color: #17362a; margin-top: 1.4rem; }
        .p3-docx h1 { font-size: 1.4rem; }
        .p3-docx h2 { font-size: 1.2rem; }
        .p3-docx h3 { font-size: 1.05rem; }
        .p3-docx p { margin-bottom: .7rem; }
        .p3-docx table { width: 100%; border-collapse: collapse; margin: 1rem auto; font-size: .8rem; }
        .p3-docx td, .p3-docx th { border: 1px solid #d5e8db; padding: .45rem .6rem; vertical-align: top; }
        .p3-docx tr:nth-child(odd) td { background: #fbfefc; }
        /* Summary card */
        .p3-summary { position: relative; overflow: hidden; }
        .p3-summary::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, #c9dcd0, #e9f3ec); }
        .p3-summary.is-verified::before { background: linear-gradient(180deg, #00A868, #7ed99f); }
        .p3-summary.is-warn::before { background: linear-gradient(180deg, #f0ad4e, #ffe3ae); }
        .p3-summary-text { color: #33443b; line-height: 1.85; text-align: justify; }
        .p3-summary-list { color: #33443b; line-height: 1.8; }
        .p3-summary-list li { margin-bottom: .5rem; }
        .p3-summary-list li::marker { color: #00A868; font-weight: 700; }
        .p3-fig { background: #e6f3ea; color: #00743F; font-weight: 700; padding: .05rem .38rem; border-radius: .4rem; font-size: .88em; white-space: nowrap; }
        mark { background: #fff3b0; padding: 0 .1em; border-radius: .2em; }
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
                    <div class="fw-bold fs-3">SmartDocs AI</div>
                </div>
            </div>
            <nav class="nav flex-column gap-1 flex-grow-1" id="p3Nav">
                <span class="nav-indicator" id="navIndicator"></span>
                <a class="nav-link {{ request()->routeIs('dashboard','home') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-ico"><i class="bi bi-grid-1x2-fill"></i></span>Dashboard</a>
                <a class="nav-link {{ request()->routeIs('documents.upload') ? 'active' : '' }}" href="{{ route('documents.upload') }}"><span class="nav-ico"><i class="bi bi-cloud-arrow-up-fill"></i></span>Upload Documents</a>
                <a class="nav-link {{ request()->routeIs('documents.library') ? 'active' : '' }}" href="{{ route('documents.library') }}"><span class="nav-ico"><i class="bi bi-folder-fill"></i></span>Document Library</a>
                <a class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}" href="{{ route('chat.index') }}"><span class="nav-ico"><i class="bi bi-lightning-charge-fill"></i></span>Lightning</a>
                @php $settingsOpen = request()->routeIs('settings.*') || request()->routeIs('admin.*'); @endphp
                <a class="nav-link {{ $settingsOpen ? 'active' : '' }}" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}"><span class="nav-ico"><i class="bi bi-gear-fill"></i></span>Settings<i class="bi bi-chevron-down ms-auto small"></i></a>
                <div class="collapse {{ $settingsOpen ? 'show' : '' }} ps-3" id="settingsMenu">
                    <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><span class="nav-ico"><i class="bi bi-sliders"></i></span>General</a>
                    @can('admin')
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-ico"><i class="bi bi-people-fill"></i></span>User Management</a>
                    <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><span class="nav-ico"><i class="bi bi-tags-fill"></i></span>Categories</a>
                    <a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}"><span class="nav-ico"><i class="bi bi-diagram-3-fill"></i></span>Departments</a>
                    @endcan
                </div>
            </nav>
            <div class="mt-3">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                <button type="button" class="btn w-100 p3-logout justify-content-center" data-bs-toggle="modal" data-bs-target="#logoutModal" title="Logout"><i class="bi bi-power me-2"></i>Logout</button>
            </div>
        </aside>
        <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered" style="max-width:380px;width:calc(100% - 2rem)">
                <div class="modal-content p3-modal">
                    <div class="text-center pt-4 px-4">
                        <div class="p3-meow"><img src="{{ asset('images/meow.gif') }}" alt="meow" style="width:110px;height:110px;object-fit:contain"></div>
                        <h5 class="fw-bold mt-3 mb-0">Leaving so soon?</h5>
                        <p class="text-muted mb-0 mt-2" style="font-size:.85rem">You can always log back in anytime. Your documents will be waiting.</p>
                    </div>
                    <div class="p3-modal-actions">
                        <button type="button" class="btn btn-outline-secondary px-4 flex-fill" data-bs-dismiss="modal">Stay</button>
                        <button type="submit" form="logout-form" class="btn btn-p3 px-4 flex-fill">Logout</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9 col-lg-10 p-0">
            <div class="p3-topbar px-3 px-md-4 py-2 d-flex justify-content-between align-items-center sticky-top">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm d-md-none" id="sidebarToggle" aria-label="Open menu" style="border:1px solid #d5e8db"><i class="bi bi-list"></i></button>
                    @if(request()->routeIs('documents.show'))
                    <a href="{{ route('documents.library') }}" class="small text-decoration-none fw-semibold" style="color:#00A868"><i class="bi bi-arrow-left me-1"></i>Back to library</a>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-success" title="Private storage · authorized access only"></i>
                    <span class="fw-semibold small text-dark">{{ auth()->user()->name }}</span>
                </div>
            </div>
            <main class="p-3">
                @if (session('status'))<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-3 shadow-sm" role="alert" id="flashOk" style="border:1px solid #c3e6cb;border-radius:.75rem"><span class="stat-icon" style="width:34px;height:34px;background:#d4edda;color:#155724"><i class="bi bi-check-circle-fill"></i></span><span class="fw-semibold text-success">{{ session('status') }}</span><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
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
    function mark(sel) {
        sel.classList.toggle('p3-chosen', !!sel.value);
    }
    document.querySelectorAll('.form-select').forEach(function (s) {
        mark(s);
        s.addEventListener('change', function () { mark(s); });
    });
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
