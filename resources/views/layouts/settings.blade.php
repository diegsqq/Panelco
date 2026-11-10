@extends('layouts.app')

@section('content')
<style>
    /* ── Chrome-style settings shell ── */
    body:has(#settingsShell) { background: #f5f5f5; }
    [data-theme="dark"]:has(#settingsShell) { background: var(--p3-bg); }

    .cfg-shell   { display: flex; gap: 0; min-height: calc(100vh - 56px); }

    /* Left nav */
    .cfg-nav     { width: 240px; flex: 0 0 240px; padding: 1.5rem .75rem 1.5rem 1rem; }
    .cfg-nav-title { font-size: 1.3rem; font-weight: 800; color: #111; padding: 0 .5rem; margin-bottom: 1.1rem; }
    [data-theme="dark"] .cfg-nav-title { color: #fff; }

    .cfg-search  { position: relative; margin-bottom: 1rem; }
    .cfg-search input { width: 100%; border: 1px solid #e0e0e0; border-radius: 2rem; padding: .42rem 1rem .42rem 2.4rem; font-size: .82rem; background: #fff; color: #111; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .cfg-search input:focus { border-color: #00A868; box-shadow: 0 0 0 3px rgba(0,168,104,.12); }
    .cfg-search .cfg-search-ico { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: #aaa; font-size: .85rem; pointer-events: none; }
    [data-theme="dark"] .cfg-search input { background: #1d2b24; border-color: #2c3d34; color: #e5eee8; }

    .cfg-nav-section { font-size: .68rem; font-weight: 700; color: #aaa; text-transform: uppercase; letter-spacing: .6px; padding: .6rem .5rem .25rem; }
    [data-theme="dark"] .cfg-nav-section { color: #8fa59a; }

    .cfg-nav-item { display: flex; align-items: center; gap: .7rem; padding: .52rem .75rem; border-radius: .65rem; font-size: .84rem; font-weight: 500; color: #333; text-decoration: none; cursor: pointer; transition: background-color .12s ease, color .12s ease; white-space: nowrap; }
    .cfg-nav-item:hover:not(.active) { background: #ebebeb; color: #111; }
    .cfg-nav-item.active { background: #e6f3ea; color: #00743F; font-weight: 600; }
    .cfg-nav-item i { font-size: 1rem; width: 18px; text-align: center; flex: 0 0 18px; }
    [data-theme="dark"] .cfg-nav-item { color: #c3d0c8; }
    [data-theme="dark"] .cfg-nav-item:hover:not(.active) { background: #1d2b24; color: #e5eee8; }
    [data-theme="dark"] .cfg-nav-item.active { background: #12291d; color: #7fe0ac; }

    .cfg-nav-divider { border: 0; border-top: 1px solid #ebebeb; margin: .5rem .5rem; }
    [data-theme="dark"] .cfg-nav-divider { border-color: #26362e; }

    /* Right content */
    .cfg-content { flex: 1 1 auto; min-width: 0; padding: 1.5rem 2rem 2rem; max-width: 820px; }

    .cfg-page-title { font-size: 1.55rem; font-weight: 800; color: #111; margin-bottom: .2rem; }
    .cfg-page-sub   { font-size: .84rem; color: #888; margin-bottom: 1.5rem; }
    [data-theme="dark"] .cfg-page-title { color: #fff; }
    [data-theme="dark"] .cfg-page-sub   { color: #8fa59a; }

    /* Section rows (Chrome-style list items) */
    .cfg-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .95rem 1.1rem; background: #fff; border-bottom: 1px solid #f0f0f0; transition: background-color .1s ease; }
    .cfg-row:first-child { border-radius: .75rem .75rem 0 0; }
    .cfg-row:last-child  { border-radius: 0 0 .75rem .75rem; border-bottom: 0; }
    .cfg-row:only-child  { border-radius: .75rem; }
    .cfg-row:hover { background: #fafafa; }
    .cfg-group { border-radius: .75rem; overflow: hidden; border: 1px solid #ebebeb; margin-bottom: 1.25rem; }
    [data-theme="dark"] .cfg-row  { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .cfg-row:hover { background: #1a2820; }
    [data-theme="dark"] .cfg-group { border-color: #26362e; }

    .cfg-row-ico { width: 34px; height: 34px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #555; flex: 0 0 34px; }
    .cfg-row-ico.green { background: #e6f3ea; color: #00A868; }
    [data-theme="dark"] .cfg-row-ico { background: #1d2b24; color: #8fa59a; }
    [data-theme="dark"] .cfg-row-ico.green { color: #7fe0ac; }

    .cfg-row-label { font-size: .88rem; font-weight: 600; color: #111; }
    .cfg-row-sub   { font-size: .76rem; color: #888; margin-top: .08rem; }
    [data-theme="dark"] .cfg-row-label { color: #e5eee8; }
    [data-theme="dark"] .cfg-row-sub   { color: #8fa59a; }

    .cfg-row-chevron { color: #bbb; font-size: .85rem; }
    [data-theme="dark"] .cfg-row-chevron { color: #4a5e54; }

    .cfg-section-label { font-size: .78rem; font-weight: 700; color: #555; margin: 1.35rem 0 .4rem .1rem; text-transform: uppercase; letter-spacing: .45px; }
    [data-theme="dark"] .cfg-section-label { color: #8fa59a; }

    @media (max-width: 767.98px) {
        .cfg-shell   { flex-direction: column; }
        .cfg-nav     { width: 100%; flex: 0 0 auto; padding: 1rem; border-bottom: 1px solid #ebebeb; }
        .cfg-content { padding: 1rem; }
    }
</style>

<div class="cfg-shell" id="settingsShell">

    {{-- ── Left nav ── --}}
    <nav class="cfg-nav" aria-label="Settings navigation">
        <div class="cfg-nav-title">Settings</div>

        <div class="cfg-search">
            <i class="bi bi-search cfg-search-ico"></i>
            <input type="text" id="cfgSearch" placeholder="Search settings" aria-label="Search settings">
        </div>

        <div class="cfg-nav-section">Account</div>
        <a class="cfg-nav-item {{ request()->routeIs('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}">
            <i class="bi bi-person-circle"></i> My Account
        </a>
        <a class="cfg-nav-item {{ request()->routeIs('settings.security') ? 'active' : '' }}" href="{{ route('settings.security') }}">
            <i class="bi bi-shield-lock"></i> Privacy and security
        </a>
        <a class="cfg-nav-item {{ request()->routeIs('settings.preferences') ? 'active' : '' }}" href="{{ route('settings.preferences') }}">
            <i class="bi bi-palette"></i> Appearance
        </a>

        @can('admin')
        <hr class="cfg-nav-divider">
        <div class="cfg-nav-section">Administration</div>
        <a class="cfg-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
            <i class="bi bi-people"></i> Users
        </a>
        <a class="cfg-nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a class="cfg-nav-item {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}">
            <i class="bi bi-diagram-3"></i> Departments
        </a>
        <a class="cfg-nav-item {{ request()->routeIs('admin.system-health.*') ? 'active' : '' }}" href="{{ route('admin.system-health.index') }}">
            <i class="bi bi-heart-pulse"></i> System health
        </a>
        <a class="cfg-nav-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
            <i class="bi bi-journal-text"></i> Audit logs
        </a>
        @endcan
    </nav>

    {{-- ── Right content ── --}}
    <div class="cfg-content">
        @yield('settings-content')
    </div>

</div>

<script>
(function () {
    var input = document.getElementById('cfgSearch');
    if (!input) return;
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        document.querySelectorAll('.cfg-nav-item').forEach(function (el) {
            el.style.display = (!q || el.textContent.trim().toLowerCase().includes(q)) ? '' : 'none';
        });
    });
})();
</script>
@endsection



