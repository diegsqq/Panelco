<!doctype html>
@php $p3Dark = auth()->user()?->theme === 'dark'; @endphp
<html lang="{{ app()->getLocale() }}" data-theme="{{ $p3Dark ? 'dark' : 'light' }}" data-bs-theme="{{ $p3Dark ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8">
    <script>
    /* Theme swap delay: keep previous theme for a beat, then fade to the
       new one so the CSS transition is visible instead of jumping. */
    (function () {
        var h = document.documentElement;
        var final = h.getAttribute('data-theme');
        var last = null;
        try { last = localStorage.getItem('p3-theme'); } catch (e) {}
        if (last && last !== final) {
            h.setAttribute('data-theme', last);
            h.setAttribute('data-bs-theme', last);
            setTimeout(function () {
                h.setAttribute('data-theme', final);
                h.setAttribute('data-bs-theme', final);
                try { localStorage.setItem('p3-theme', final); } catch (e) {}
            }, 550);
        } else {
            try { localStorage.setItem('p3-theme', final); } catch (e) {}
        }
    })();
    </script>
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
        /* Dark theme: remap shared vars so every var()-based rule adapts.
           color-scheme makes native scrollbars, form controls, and
           date pickers render dark automatically. */
        [data-theme="dark"] { color-scheme: dark; --p3-bg: #0e1512; --p3-card-border: #26362e; scrollbar-color: #2c3d34 transparent; }
        [data-theme="dark"] ::-webkit-scrollbar { width: 10px; height: 10px; }
        [data-theme="dark"] ::-webkit-scrollbar-thumb { background: #2c3d34; border-radius: 6px; }
        [data-theme="dark"] ::-webkit-scrollbar-track { background: transparent; }
        /* Smooth fade when switching themes */
        body, .card-p3, .p3-topbar, .form-control, .form-select, .input-group-text, .modal-content, .p3-notif-row, .p3-theme-tile, .p3-dept-pill { transition: background-color .5s ease, border-color .5s ease, color .5s ease; }
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
        .p3-avatar-sm { width: 30px; height: 30px; border-radius: 50%; overflow: hidden; background: #e6f3ea; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #d5e8db; flex: 0 0 auto; }
        .p3-avatar-sm .fw-bold { color: #00743F; font-size: .72rem; }
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
        .p3-docx img { max-width: 100%; height: auto; border-radius: .5rem; margin: .5rem 0; }
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
        /* Dark mode — per-user, Settings > Preferences > Appearance */
        [data-theme="dark"] body { background: #101714; color: #ffffff; }
        [data-theme="dark"] .p3-topbar { background: #16211c; border-color: #26362e; color: #ffffff; }
        [data-theme="dark"] .card-p3 { background: #16211c; border-color: #26362e; box-shadow: none; }
        [data-theme="dark"] .text-muted, [data-theme="dark"] small.text-muted { color: #c2d2ca !important; }
        [data-theme="dark"] .form-label, [data-theme="dark"] label.form-label { color: #ffffff; }
        [data-theme="dark"] .form-control, [data-theme="dark"] .form-select { background: #0f1512; border-color: #2c3d34; color: #ffffff; }
        [data-theme="dark"] .form-control::placeholder { color: #7d9087; }
        [data-theme="dark"] .form-control:disabled, [data-theme="dark"] .form-select:disabled { background: #121a16; color: #9db2a7; border-color: #26362e; }
        [data-theme="dark"] .input-group-text { background: #1d2b24; border-color: #2c3d34; }
        [data-theme="dark"] .border-bottom, [data-theme="dark"] .border-top { border-color: #26362e !important; }
        [data-theme="dark"] hr { border-color: #26362e; }
        [data-theme="dark"] .src-card:hover { background: #1c2a23; }
        [data-theme="dark"] .stat-icon { background: #1d2b24 !important; }
        [data-theme="dark"] .p3-dept-pill { background: #16211c; border-color: #2c3d34; color: #7fe0ac; }
        [data-theme="dark"] .p3-dept-pill:hover { background: #1d2b24; }
        [data-theme="dark"] .btn-outline-secondary { color: #b8c8bf; border-color: #3a4c43; }
        [data-theme="dark"] .btn-outline-success { color: #7fe0ac; border-color: #2f6b4f; }
        [data-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        [data-theme="dark"] .alert-success { background: #12291d; border-color: #1d4432 !important; color: #8fe0b3; }
        [data-theme="dark"] .alert-danger { background: #2d1414; border-color: #5a2424; color: #f1a8a8; }
        [data-theme="dark"] .alert-warning { background: #2b2410; border-color: #4d421c; color: #ecd78a; }
        [data-theme="dark"] .modal-content { background: #16211c; color: #ffffff; }
        [data-theme="dark"] .modal-content .text-muted { color: #c2d2ca !important; }
        [data-theme="dark"] code { background: #0f1512; color: #8fe0ac; }
        [data-theme="dark"] .form-check-input { background-color: #0f1512; border-color: #3a4c43; }
        [data-theme="dark"] .form-check-input:checked { background-color: #00A868; border-color: #00A868; }
        [data-theme="dark"] .p3-summary-text, [data-theme="dark"] .p3-summary-list { color: #ffffff; }
        [data-theme="dark"] .p3-cell-interp { color: #dfe8e3; }
        [data-theme="dark"] .table-p3 tbody tr:nth-child(odd) { background: #131d18; }
        [data-theme="dark"] .table-p3 tbody tr { border-color: #26362e; }
        [data-theme="dark"] a[style*="color:#00743F"], [data-theme="dark"] div[style*="color:#00743F"], [data-theme="dark"] span[style*="color:#00743F"] { color: #4fd39a !important; }
        [data-theme="dark"] .chat-scroll, [data-theme="dark"] .p3-docx { background: #0f1512; color: #ffffff; }
        [data-theme="dark"] .p3-sidebar { box-shadow: none; }
        /* Settings — avatar, tiles, QR, notifications */
        .p3-avatar-lg { width: 92px; height: 92px; border-radius: 50%; overflow: visible; border: 3px solid #d5e8db; background: #e6f3ea; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; position: relative; }
        .p3-avatar-lg .p3-avatar-inner { width: 100%; height: 100%; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .p3-avatar-lg img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .p3-avatar-cam { position: absolute; right: -3px; bottom: -3px; width: 30px; height: 30px; border-radius: 50%; background: #00A868; color: #fff; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,.22); transition: transform .15s ease, background-color .15s ease; z-index: 2; }
        .p3-avatar-cam:hover { transform: scale(1.1); background: #00743F; }
        .p3-theme-tile { flex: 1 1 150px; border: 2px solid var(--p3-card-border); border-radius: .85rem; background: #fff; padding: .9rem 1rem; display: flex; align-items: center; gap: .65rem; font-weight: 600; font-size: .9rem; color: #4b5a52; cursor: pointer; transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease; }
        .p3-theme-tile:hover { border-color: #7ed99f; background: #f0f9f3; }
        .p3-theme-tile.on { border-color: #00A868; background: #e6f3ea; color: #00743F; box-shadow: 0 2px 8px rgba(0,168,104,.18); }
        .p3-theme-tile i { font-size: 1.35rem; }
        .p3-qr-box { display: inline-block; background: #fff; border: 1px dashed #b7e0c8; border-radius: 1rem; padding: .85rem; line-height: 0; }
        .p3-code-input { max-width: 10rem; letter-spacing: 4px; text-align: center; font-weight: 700; font-size: 1.05rem; }
        .form-switch .form-check-input:checked { background-color: #00A868; border-color: #00A868; }
        .p3-notif-row { display: flex; align-items: flex-start; gap: 1rem; padding: .85rem 1rem; border: 1px solid var(--p3-card-border); border-radius: .8rem; background: #fbfefc; transition: background-color .15s ease; }
        .p3-notif-row:hover { background: #f0f9f3; }
        .p3-notif-row + .p3-notif-row { margin-top: .6rem; }
        .p3-notif-row i { font-size: 1.15rem; color: #00743F; margin-top: .1rem; }
        [data-theme="dark"] .p3-avatar-lg { background: #1d2b24; border-color: #2c3d34; }
        [data-theme="dark"] .p3-avatar-cam { border-color: #16211c; }
        [data-theme="dark"] .p3-theme-tile { background: #16211c; border-color: #2c3d34; color: #b8c8bf; }
        [data-theme="dark"] .p3-theme-tile:hover { background: #1d2b24; border-color: #2f6b4f; }
        [data-theme="dark"] .p3-theme-tile.on { border-color: #00A868; background: #12291d; color: #7fe0ac; }
        [data-theme="dark"] .p3-qr-box { background: #fff; border-color: #2f6b4f; }
        [data-theme="dark"] .p3-notif-row { background: #131d18; border-color: #26362e; }
        [data-theme="dark"] .p3-notif-row:hover { background: #1d2b24; }
        /* ---- Dark mode, part 2: components the first pass missed ---- */
        [data-theme="dark"] .status-pending { background: #3a3010; color: #ffe08a; }
        [data-theme="dark"] .status-processing { background: #10304d; color: #8fc6ff; }
        [data-theme="dark"] .status-completed { background: #12291d; color: #8fe0b3; }
        [data-theme="dark"] .status-failed { background: #3a1616; color: #f1a8a8; }
        [data-theme="dark"] .p3-cat-pill { background: #12291d; color: #7fe0ac; border-color: #1d4432; }
        [data-theme="dark"] .p3-formula { background: #0f1512; border-color: #2c3d34; color: #a9c4b6; }
        [data-theme="dark"] .p3-cell-interp { color: #9db2a7; }
        [data-theme="dark"] .p3-fig { background: #12291d; color: #7fe0ac; }
        [data-theme="dark"] .form-select.p3-chosen { color: #7fe0ac; background-color: #12291d; }
        [data-theme="dark"] mark { background: #4a3d12; color: #ffe9a8; }
        [data-theme="dark"] ::selection { background: rgba(0,168,104,.35); }
        [data-theme="dark"] .table, [data-theme="dark"] .table-p3 { --bs-table-bg: transparent; --bs-table-color: #d7e5dc; --bs-table-border-color: #26362e; }
        [data-theme="dark"] .table-p3 { --bs-table-striped-bg: #131d18; --bs-table-hover-bg: #1d2b24; }
        [data-theme="dark"] .table-p3 tbody tr:hover { background: #1d2b24; }
        [data-theme="dark"] .p3-docx h1, [data-theme="dark"] .p3-docx h2, [data-theme="dark"] .p3-docx h3, [data-theme="dark"] .p3-docx h4 { color: #ffffff; }
        [data-theme="dark"] .p3-docx td, [data-theme="dark"] .p3-docx th { border-color: #2c3d34; }
        [data-theme="dark"] .p3-docx tr:nth-child(odd) td { background: #131d18; }
        [data-theme="dark"] .p3-modal-actions { border-color: #26362e; }
        [data-theme="dark"] .p3-summary::before { background: linear-gradient(180deg, #2c3d34, #1d2b24); }
        [data-theme="dark"] .p3-summary.is-verified::before { background: linear-gradient(180deg, #00A868, #1d4432); }
        [data-theme="dark"] .p3-summary.is-warn::before { background: linear-gradient(180deg, #f0ad4e, #4d421c); }
        [data-theme="dark"] .p3-avatar-sm { background: #1d2b24; border-color: #2c3d34; }
        [data-theme="dark"] .p3-avatar-sm .fw-bold { color: #7fe0ac; }
        [data-theme="dark"] #sidebarToggle { border-color: #2c3d34 !important; }
        [data-theme="dark"] .dropdown-menu { background: #16211c; border-color: #26362e; }
        [data-theme="dark"] .dropdown-item { color: #ffffff; }
        [data-theme="dark"] .dropdown-item:hover, [data-theme="dark"] .dropdown-item:focus { background: #1d2b24; color: #fff; }
        [data-theme="dark"] .dropdown-divider { border-color: #26362e; }
        [data-theme="dark"] .pagination { --bs-pagination-bg: #16211c; --bs-pagination-border-color: #26362e; --bs-pagination-color: #7fe0ac; --bs-pagination-hover-bg: #1d2b24; --bs-pagination-hover-border-color: #2f6b4f; --bs-pagination-active-bg: #00A868; --bs-pagination-active-border-color: #00A868; --bs-pagination-disabled-bg: #121a16; --bs-pagination-disabled-border-color: #26362e; --bs-pagination-disabled-color: #6f857a; }
        [data-theme="dark"] .breadcrumb-item, [data-theme="dark"] .breadcrumb-item.active { color: #8fa59a; }
        [data-theme="dark"] .text-success { color: #4fd39a !important; }
        [data-theme="dark"] .btn-p3-yellow { background: #e6f3ea; border-color: #e6f3ea; color: #0b5c34; }
        [data-theme="dark"] .avatar-ai { background: #16211c; }
        [data-theme="dark"] .chat-bubble-in, [data-theme="dark"] .msg-in { background: #16211c; }
        [data-theme="dark"] .src-card { border-color: #2c3d34 !important; }
        /* Inline-styled text that would be dark-on-dark in dark mode.
           Only overridden where the background is also themed dark. */
        [data-theme="dark"] .stat-icon { color: #7fe0ac !important; }
        [data-theme="dark"] [style*="color:#00743F"] { color: #4fd39a !important; }
        /* ...except text sitting on a white background that stays white */
        [data-theme="dark"] [style*="background:#fff"][style*="color:#00743F"] { color: #0b5c34 !important; }
        [data-theme="dark"] [style*="color:#7d8f86"] { color: #9db2a7 !important; }
        [data-theme="dark"] [style*="color:#33503f"] { color: #a9c4b6 !important; }
        [data-theme="dark"] [style*="color:#2b3a32"] { color: #d7e5dc !important; }
        @media print {
            /* Real page margin so page 2+ never starts flush (content was
               being cut at the top). Stamps-free output comes from the app
               PDF flow (Ctrl+P / Print button) — File > Print always shows
               browser date/title/URL, no website can stop that. */
            @page { margin: 16mm 14mm; size: auto; }
            .p3-sidebar, .p3-topbar, .sidebar-backdrop, #logoutModal, .modal, .btn, .btn-close, .p3-footer { display: none !important; }
            body { background: #fff; }
            main { animation: none; padding: 0 !important; }
            .card-p3 { box-shadow: none; }
        }
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
                <a class="nav-link {{ request()->routeIs('dashboard','home') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-ico"><i class="bi bi-grid-1x2-fill"></i></span>{{ __('nav.dashboard') }}</a>
                <a class="nav-link {{ request()->routeIs('documents.upload') ? 'active' : '' }}" href="{{ route('documents.upload') }}"><span class="nav-ico"><i class="bi bi-cloud-arrow-up-fill"></i></span>{{ __('nav.upload') }}</a>
                <a class="nav-link {{ request()->routeIs('documents.library') ? 'active' : '' }}" href="{{ route('documents.library') }}"><span class="nav-ico"><i class="bi bi-folder-fill"></i></span>{{ __('nav.library') }}</a>
                <a class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}" href="{{ route('chat.index') }}"><span class="nav-ico"><i class="bi bi-lightning-charge-fill"></i></span>{{ __('nav.lightning') }}</a>
                @php $settingsOpen = request()->routeIs('settings.*') || request()->routeIs('admin.*'); @endphp
                <a class="nav-link {{ $settingsOpen ? 'active' : '' }}" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}"><span class="nav-ico"><i class="bi bi-gear-fill"></i></span>{{ __('nav.settings') }}<i class="bi bi-chevron-down ms-auto small"></i></a>
                <div class="collapse {{ $settingsOpen ? 'show' : '' }} ps-3" id="settingsMenu">
                    <a class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}"><span class="nav-ico"><i class="bi bi-sliders"></i></span>{{ __('nav.general') }}</a>
                    <a class="nav-link {{ request()->routeIs('settings.security') ? 'active' : '' }}" href="{{ route('settings.security') }}"><span class="nav-ico"><i class="bi bi-shield-lock"></i></span>{{ __('nav.security') }}</a>
                    <a class="nav-link {{ request()->routeIs('settings.preferences') ? 'active' : '' }}" href="{{ route('settings.preferences') }}"><span class="nav-ico"><i class="bi bi-palette-fill"></i></span>{{ __('nav.preferences') }}</a>
                    @can('admin')
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-ico"><i class="bi bi-people-fill"></i></span>{{ __('nav.users') }}</a>
                    <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><span class="nav-ico"><i class="bi bi-tags-fill"></i></span>{{ __('nav.categories') }}</a>
                    <a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}"><span class="nav-ico"><i class="bi bi-diagram-3-fill"></i></span>{{ __('nav.departments') }}</a>
                    @endcan
                </div>
            </nav>
            <div class="mt-3">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                <button type="button" class="btn w-100 p3-logout justify-content-center" data-bs-toggle="modal" data-bs-target="#logoutModal" title="Logout"><i class="bi bi-power me-2"></i>{{ __('nav.logout') }}</button>
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
                    <span class="p3-avatar-sm" title="{{ auth()->user()->name }}">
                    @if(auth()->user()->avatar_path)
                        <img src="{{ route('profile.avatar') }}?v={{ auth()->user()->updated_at?->timestamp }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover">
                    @else
                        <span class="fw-bold">{{ auth()->user()->initials() }}</span>
                    @endif
                    </span>
                    <i class="bi bi-shield-lock text-success" title="Private storage · authorized access only"></i>
                    <span class="fw-semibold small" style="color:inherit">{{ auth()->user()->name }}</span>
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
