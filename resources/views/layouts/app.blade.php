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
            --p3-bg: #f5f5f5;
            --p3-card-border: #e5e5e5;
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
        .p3-sidebar { background: #f8f8f8; border-right: 1px solid #e8e8e8; min-height: 100vh; height: 100vh; position: sticky; top: 0; overflow-y: auto; overflow-x: hidden; }
.p3-sidebar nav { overflow-y: auto; max-height: calc(100vh - 140px); }
        .p3-sidebar .text-white { color: #242424 !important; }
        .p3-sidebar .nav-link { color: #555; border-radius: .55rem; padding: .45rem .6rem; font-weight: 500; display: flex; align-items: center; gap: .6rem; transition: background-color .25s ease, color .25s ease; }
        .p3-sidebar .nav-link:hover { background: #ededed; color: #171717; }
        .p3-sidebar .nav-link.active { background: #fff; color: #171717; box-shadow: 0 1px 3px rgba(0,0,0,.08); font-weight: 600; }
        .p3-sidebar .nav-link.active .nav-ico { color: #00A868; }
        .p3-sidebar nav { position: relative; }
        .p3-sidebar .nav-ico { width: 22px; height: 22px; flex: 0 0 22px; border-radius: 10px; background: transparent; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; color: inherit; transition: background .15s ease; }
        .p3-brand-mark { width: 42px; height: 42px; border-radius: 12px; background: #fff; display: flex; align-items: center; justify-content: center; color: var(--p3-navy); font-size: 1.4rem; box-shadow: 0 4px 12px rgba(0,0,0,.25); }
        .p3-topbar { background: #f5f5f5; border: 0; }
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
        /* Full page scroll locked on Lightning AI + Document Library routes — only inner areas scroll. */
        body.p3-no-scroll { overflow: hidden; height: 100vh; }
        /* No visible scrollbars (vertical or horizontal) on full-screen pages —
           wheel/touch scrolling still works inside the list card. */
        .p3-chat-page, .p3-conv-wrap, .p3-conv-card, .p3-chat-msgs { overflow-x: hidden; -ms-overflow-style: none; scrollbar-width: none; }
        .p3-chat-page::-webkit-scrollbar, .p3-conv-wrap::-webkit-scrollbar, .p3-conv-card::-webkit-scrollbar, .p3-chat-msgs::-webkit-scrollbar { width: 0; height: 0; display: none; }
        /* Shared full-page fit layout (Lightning AI + Document Library) */
        .p3-chat-page { display: flex; flex-direction: column; min-height: 0; }
        .p3-conv-wrap { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
        .p3-conv-card { flex: 1 1 auto; min-height: 0; overflow-y: auto; display: flex; flex-direction: column; }
        .p3-conv-card > div { flex-shrink: 0; }
        .p3-conv-foot { position: sticky; bottom: 0; z-index: 5; margin-top: auto; padding: .45rem 0 .35rem; background: inherit; }
        main { animation: p3-fade .3s ease; }
        @keyframes p3-fade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .form-control:focus, .form-select:focus, .btn:focus-visible, a:focus-visible { box-shadow: 0 0 0 .25rem rgba(0,168,104,.25); outline: none; }
        .p3-skip-link { position: fixed; top: -4rem; left: 1rem; z-index: 2000; padding: .6rem 1rem; background: #fff; color: #00743F; border: 2px solid #00743F; border-radius: .5rem; }
        .p3-skip-link:focus { top: 1rem; }
        .form-select.p3-chosen { color: var(--p3-navy); font-weight: 600; background-color: #f0f9f3; }
        .p3-dept-pill { border: 1px solid #d5e8db; color: var(--p3-navy); background: #fff; border-radius: 2rem; font-weight: 500; padding: .35rem .9rem; transition: all .15s ease; }
        .p3-dept-pill:hover { background: #e6f3ea; }
        .p3-dept-pill:has(input:checked) { background: #ffffff; border-color: var(--p3-navy); color: var(--p3-navy); box-shadow: 0 2px 6px rgba(0,168,104,.18); }
        .p3-logout { background: transparent; border: 1px solid #ddd; color: #555; border-radius: .75rem; font-weight: 600; padding: .6rem 1rem; letter-spacing: .3px; transition: all .18s ease; }
        .p3-logout:hover, .p3-logout:focus { background: #ededed; color: #c0392b; border-color: #ddd; }
        .p3-logout:active { transform: none; }
        /* Topbar icon buttons */
        .p3-topbar-btn { background: #fff; border: 1px solid #e2e2e2; color: #555; border-radius: .65rem; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; font-size: .95rem; padding: 0; cursor: pointer; transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease; }
        .p3-topbar-btn:hover { background: #f0f9f3; border-color: #b7e0c8; color: #00A868; }
        .p3-topbar-btn.active { background: #e6f3ea; border-color: #00A868; color: #00743F; }
        .p3-topbar-avatar-btn { width: auto; padding: 0 .45rem; gap: .3rem; }
        [data-theme="dark"] .p3-topbar-btn { background: #1d2b24; border-color: #2c3d34; color: #c3d0c8; }
        [data-theme="dark"] .p3-topbar-btn:hover { background: #243324; border-color: #3a5a46; color: #7fe0ac; }
        [data-theme="dark"] .p3-topbar-btn.active { background: #12291d; border-color: #00A868; color: #7fe0ac; }
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
        [data-theme="dark"] body { background: var(--p3-bg); color: #ffffff; }
        [data-theme="dark"] .p3-topbar { background: var(--p3-bg); border-color: #26362e; color: #ffffff; }
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
        [data-theme="dark"] .p3-sidebar { background: #111a15; border-color: #26362e; box-shadow: none; }
        [data-theme="dark"] .p3-sidebar .text-white { color: #e5eee8 !important; }
        [data-theme="dark"] .p3-sidebar .nav-link { color: #c3d0c8; }
        [data-theme="dark"] .p3-sidebar .nav-link:hover, [data-theme="dark"] .p3-sidebar .nav-link.active { background: #1d2b24; color: #fff; }
        [data-theme="dark"] .p3-sidebar .nav-link.active .nav-ico { color: #7fe0ac; }
        [data-theme="dark"] .p3-logout { background: transparent; border-color: #3a4c43; color: #c3d0c8; }
        [data-theme="dark"] .p3-logout:hover, [data-theme="dark"] .p3-logout:focus { background: #1d2b24; border-color: #3a4c43; color: #f1a8a8; }
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
        [data-theme="dark"] .table-light, [data-theme="dark"] thead.table-light, [data-theme="dark"] tbody.table-light { --bs-table-bg: #131d18; --bs-table-color: #d7e5dc; --bs-table-border-color: #26362e; }
        [data-theme="dark"] .table-light > tr > th, [data-theme="dark"] .table-light > * > th, [data-theme="dark"] .table-light td, [data-theme="dark"] .table-light th { background-color: #131d18 !important; color: #d7e5dc !important; border-color: #26362e !important; }
        [data-theme="dark"] .alert-info { background: #10304d; border-color: #1d4a75; color: #a8d5ff; }
        [data-theme="dark"] .badge[style*="e6f3ea"] { background: #12291d !important; color: #7fe0ac !important; }
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
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>
<body class="{{ trim(\Illuminate\Support\Facades\View::yieldContent('body-class')) ?: '' }}">
<div class="container-fluid">
    <div class="row g-0" style="min-height:100vh;align-items:stretch;">
        @auth
        <a class="p3-skip-link" href="#main-content">Skip to main content</a>
        @unless(View::hasSection('full-screen'))
        <aside class="col-md-3 col-lg-2 d-none d-md-flex p3-sidebar p-3 flex-column" id="p3Sidebar" aria-label="Primary navigation">
            <div class="d-flex align-items-center gap-2 mb-1 px-1">
                <img src="{{ asset('images/logo.png') }}" alt="PANELCO III" style="width:58px;height:58px;object-fit:contain">
                <div class="text-white lh-sm">
                    <div class="fw-bold fs-3">SmartDocs AI</div>
                </div>
            </div>
            <nav class="nav flex-column gap-1 flex-grow-1" id="p3Nav">
                <a class="nav-link {{ request()->routeIs('dashboard','home') ? 'active' : '' }}" aria-current="{{ request()->routeIs('dashboard','home') ? 'page' : 'false' }}" href="{{ route('dashboard') }}"><span class="nav-ico"><i class="bi bi-grid-1x2-fill"></i></span>{{ __('nav.dashboard') }}</a>
                <a class="nav-link {{ request()->routeIs('documents.upload') ? 'active' : '' }}" aria-current="{{ request()->routeIs('documents.upload') ? 'page' : 'false' }}" href="{{ route('documents.upload') }}"><span class="nav-ico"><i class="bi bi-cloud-arrow-up-fill"></i></span>{{ __('nav.upload') }}</a>
                <a class="nav-link {{ request()->routeIs('documents.library') ? 'active' : '' }}" aria-current="{{ request()->routeIs('documents.library') ? 'page' : 'false' }}" href="{{ route('documents.library') }}"><span class="nav-ico"><i class="bi bi-folder-fill"></i></span>{{ __('nav.library') }}</a>
                @if(auth()->user()->isAdmin())
                <a class="nav-link {{ request()->routeIs('documents.approvals') ? 'active' : '' }}" aria-current="{{ request()->routeIs('documents.approvals') ? 'page' : 'false' }}" href="{{ route('documents.approvals') }}"><span class="nav-ico"><i class="bi bi-clipboard-check-fill"></i></span>Document approvals</a>
                @endif
                <a class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('chat.*') ? 'page' : 'false' }}" href="{{ route('chat.index') }}"><span class="nav-ico"><i class="bi bi-lightning-charge-fill"></i></span>{{ __('nav.lightning') }}</a>
                <a class="nav-link {{ request()->routeIs('help') ? 'active' : '' }}" aria-current="{{ request()->routeIs('help') ? 'page' : 'false' }}" href="{{ route('help') }}"><span class="nav-ico"><i class="bi bi-question-circle-fill" aria-hidden="true"></i></span>Help</a>
            </nav>
            <div class="mt-3">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
        </aside>
        @endunless
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
                        <button type="submit" form="logout-form" class="btn btn-outline-success px-4 flex-fill">Logout</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-0 d-flex flex-column @hasSection('full-screen') @yield('full-screen-col') @else col-md-9 col-lg-10 @endif" style="height:100vh;overflow-y:auto;overflow-x:hidden;">
            @hasSection('full-screen')
            @yield('content')
            @else
            <div class="p3-topbar px-3 px-md-4 py-2 d-flex justify-content-between align-items-center sticky-top">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm d-md-none" id="sidebarToggle" aria-label="Open menu" aria-controls="p3Sidebar" aria-expanded="false" style="border:1px solid #d5e8db"><i class="bi bi-list" aria-hidden="true"></i></button>
                    @if(request()->routeIs('documents.show'))
                    <a href="{{ route('documents.library') }}" class="small text-decoration-none fw-semibold" style="color:#00A868"><i class="bi bi-arrow-left me-1"></i>Back to library</a>
                    @endif
                    <span id="p3DateTime" class="small fw-semibold text-muted d-none d-sm-inline" style="white-space:nowrap"></span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    {{-- Theme: Light --}}
                    <form method="POST" action="{{ route('settings.theme') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="theme" value="light">
                        <button type="submit" class="p3-topbar-btn{{ auth()->user()->theme !== 'dark' ? ' active' : '' }}" title="Light mode" aria-label="Switch to light mode">
                            <i class="bi bi-sun-fill"></i>
                        </button>
                    </form>
                    {{-- Theme: Dark --}}
                    <form method="POST" action="{{ route('settings.theme') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="theme" value="dark">
                        <button type="submit" class="p3-topbar-btn{{ auth()->user()->theme === 'dark' ? ' active' : '' }}" title="Dark mode" aria-label="Switch to dark mode">
                            <i class="bi bi-moon-fill"></i>
                        </button>
                    </form>
                    {{-- Notifications bell --}}
                    <button type="button" class="p3-topbar-btn" title="Notifications" aria-label="Notifications">
                        <i class="bi bi-bell-fill"></i>
                    </button>
                    {{-- User avatar dropdown --}}
                    <div class="dropdown">
                        <button class="p3-topbar-btn p3-topbar-avatar-btn d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                            <span class="p3-avatar-sm" style="width:28px;height:28px;">
                                @if(auth()->user()->avatar_path)
                                    <img src="{{ route('profile.avatar') }}?v={{ auth()->user()->updated_at?->timestamp }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                @else
                                    <span class="fw-bold" style="font-size:.7rem;">{{ auth()->user()->initials() }}</span>
                                @endif
                            </span>
                            <i class="bi bi-chevron-down" style="font-size:.65rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:200px;border-radius:.85rem;padding:.4rem;">
                            <li class="px-3 py-2">
                                <div class="fw-semibold" style="font-size:.88rem;">{{ auth()->user()->name }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ auth()->user()->email }}</div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item rounded-2" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="button" class="dropdown-item rounded-2 text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                    <i class="bi bi-power me-2"></i>Logout
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <main class="p-3" id="main-content" tabindex="-1">
                @if (session('status'))<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-3 shadow-sm" role="alert" id="flashOk" style="border:1px solid #c3e6cb;border-radius:.75rem"><span class="stat-icon" style="width:34px;height:34px;background:#d4edda;color:#155724"><i class="bi bi-check-circle-fill"></i></span><span class="fw-semibold text-success">{{ session('status') }}</span><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                @if (session('warning'))<div class="alert alert-warning alert-dismissible fade show" role="alert">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                @if ($errors->any())<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                @yield('content')
            </main>
        @endif
        </div>
        @else
        <main class="col-12 p-0">
            @yield('content')
        </main>
        @endauth
    </div>
</div>

@auth
{{-- ══════════════════════════════════════════════
     SETTINGS MODAL — Chrome-style popup
     Available on every page via the topbar dropdown
     ══════════════════════════════════════════════ --}}
<div class="modal fade" id="settingsModal" tabindex="-1" aria-label="Settings" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:900px;height:90vh;margin:auto;">
        <div class="modal-content" style="height:90vh;border-radius:1.25rem;overflow:hidden;border:none;box-shadow:0 24px 64px rgba(0,0,0,.18);">

            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid #ebebeb;flex:0 0 auto;">
                <div class="fw-bold" style="font-size:1.1rem;color:#111">Settings</div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Body: left nav + right panel --}}
            <div class="d-flex" style="flex:1 1 auto;min-height:0;overflow:hidden;">

                {{-- Left nav --}}
                <div style="width:220px;flex:0 0 220px;border-right:1px solid #f0f0f0;padding:1rem .75rem;overflow-y:auto;background:#fafafa;">

                    {{-- Search --}}
                    <div class="position-relative mb-3">
                        <i class="bi bi-search position-absolute" style="left:.75rem;top:50%;transform:translateY(-50%);color:#bbb;font-size:.8rem;pointer-events:none"></i>
                        <input id="sModalSearch" type="text" class="form-control form-control-sm" placeholder="Search settings" style="padding-left:2.1rem;border-radius:2rem;border-color:#e0e0e0;font-size:.8rem;" aria-label="Search settings">
                    </div>

                    <div class="smn-section">Account</div>
                    <button class="smn-item active" data-panel="account"><i class="bi bi-person-circle"></i> My Account</button>
                    <button class="smn-item" data-panel="security"><i class="bi bi-shield-lock"></i> Privacy &amp; Security</button>
                    <button class="smn-item" data-panel="appearance"><i class="bi bi-palette"></i> Appearance</button>
                    <button class="smn-item" data-panel="notifications"><i class="bi bi-bell"></i> Notifications</button>
                    @can('admin')
                    <hr style="border-color:#ebebeb;margin:.5rem 0">
                    <div class="smn-section">Administration</div>
                    <a class="smn-item text-decoration-none" href="{{ route('admin.users.index') }}" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-people"></i> Users</a>
                    <a class="smn-item text-decoration-none" href="{{ route('admin.categories.index') }}" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-tags"></i> Categories</a>
                    <a class="smn-item text-decoration-none" href="{{ route('admin.departments.index') }}" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-diagram-3"></i> Departments</a>
                    <a class="smn-item text-decoration-none" href="{{ route('admin.system-health.index') }}" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-heart-pulse"></i> System health</a>
                    <a class="smn-item text-decoration-none" href="{{ route('admin.audit-logs.index') }}" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-journal-text"></i> Audit logs</a>
                    @endcan
                </div>

                {{-- Right panels --}}
                <div style="flex:1 1 auto;overflow-y:auto;padding:1.5rem 2rem;">

                    {{-- ── Account panel ── --}}
                    <div class="s-panel" id="sPanel-account">
                        <div class="s-panel-title">My Account</div>
                        <div class="s-panel-sub">Profile picture and personal details.</div>

                        {{-- Avatar + name hero --}}
                        <div class="s-group mb-3" style="padding:1.25rem">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p3-avatar-lg" style="width:64px;height:64px;">
                                    <div class="p3-avatar-inner">
                                        @if(auth()->user()->avatar_path)
                                            <img src="{{ route('profile.avatar') }}?v={{ auth()->user()->updated_at?->timestamp }}" alt="{{ auth()->user()->name }}">
                                        @else
                                            <span class="fw-bold" style="color:#00743F;font-size:1.4rem">{{ auth()->user()->initials() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:1rem">{{ auth()->user()->name }}</div>
                                    @if(auth()->user()->job_title)<div class="small" style="color:#00A868">{{ auth()->user()->job_title }}</div>@endif
                                    <div class="small text-muted">{{ auth()->user()->email }}</div>
                                </div>
                                <a href="{{ route('settings.index') }}" class="btn btn-sm ms-auto" style="border:1px solid #d5e8db;color:#00743F;border-radius:.65rem;font-size:.8rem" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()">
                                    <i class="bi bi-pencil me-1"></i>Edit profile
                                </a>
                            </div>
                        </div>

                        <div class="s-section-lbl">Account details</div>
                        <div class="s-group">
                            <div class="s-row">
                                <div class="s-row-ico green"><i class="bi bi-person"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Full name</div><div class="s-row-sub">{{ auth()->user()->name }}</div></div>
                                <a href="{{ route('settings.index') }}" class="s-row-action" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-chevron-right"></i></a>
                            </div>
                            <div class="s-row">
                                <div class="s-row-ico green"><i class="bi bi-envelope"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Email address</div><div class="s-row-sub">{{ auth()->user()->email }}</div></div>
                                <a href="{{ route('settings.index') }}" class="s-row-action" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-chevron-right"></i></a>
                            </div>
                            @if(auth()->user()->phone)
                            <div class="s-row">
                                <div class="s-row-ico green"><i class="bi bi-phone"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Phone</div><div class="s-row-sub">{{ auth()->user()->phone }}</div></div>
                                <a href="{{ route('settings.index') }}" class="s-row-action" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-chevron-right"></i></a>
                            </div>
                            @endif
                            <div class="s-row">
                                <div class="s-row-ico green"><i class="bi bi-building"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Department</div><div class="s-row-sub">{{ auth()->user()->department?->name ?? 'Not assigned' }}</div></div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Security panel ── --}}
                    <div class="s-panel d-none" id="sPanel-security">
                        <div class="s-panel-title">Privacy &amp; Security</div>
                        <div class="s-panel-sub">Manage your password and two-factor authentication.</div>

                        <div class="s-section-lbl">Sign-in</div>
                        <div class="s-group">
                            <div class="s-row">
                                <div class="s-row-ico green"><i class="bi bi-key"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Password</div><div class="s-row-sub">Change your account password</div></div>
                                <a href="{{ route('settings.security') }}" class="s-row-action" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-chevron-right"></i></a>
                            </div>
                            <div class="s-row">
                                <div class="s-row-ico {{ auth()->user()->two_factor_enabled ? 'green' : '' }}"><i class="bi bi-phone-shield"></i></div>
                                <div class="s-row-body">
                                    <div class="s-row-label">Two-Factor Authentication</div>
                                    <div class="s-row-sub">
                                        @if(auth()->user()->two_factor_enabled)
                                            <span class="badge text-bg-success" style="font-size:.68rem">ON</span> Authenticator app active
                                        @else
                                            <span class="badge border border-secondary text-secondary" style="font-size:.68rem">OFF</span> Add an extra layer of protection
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('settings.security') }}" class="s-row-action" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-chevron-right"></i></a>
                            </div>
                        </div>

                        <div class="s-section-lbl">Activity</div>
                        <div class="s-group">
                            <div class="s-row">
                                <div class="s-row-ico"><i class="bi bi-clock-history"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Sign-in history</div><div class="s-row-sub">View your recent sign-in events</div></div>
                                <a href="{{ route('settings.security') }}" class="s-row-action" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>

                    {{-- ── Appearance panel ── --}}
                    <div class="s-panel d-none" id="sPanel-appearance">
                        <div class="s-panel-title">Appearance</div>
                        <div class="s-panel-sub">Choose how SmartDocs looks for you.</div>

                        <div class="s-section-lbl">Theme</div>
                        <div class="s-group">
                            <div class="s-row">
                                <div class="s-row-ico green"><i class="bi bi-sun-fill"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Light</div><div class="s-row-sub">Bright white interface</div></div>
                                <form method="POST" action="{{ route('settings.theme') }}">
                                    @csrf<input type="hidden" name="theme" value="light">
                                    <button type="submit" class="btn btn-sm" style="border-radius:.55rem;border:1px solid {{ auth()->user()->theme !== 'dark' ? '#00A868' : '#e0e0e0' }};color:{{ auth()->user()->theme !== 'dark' ? '#00743F' : '#888' }};font-size:.78rem;font-weight:600;background:{{ auth()->user()->theme !== 'dark' ? '#e6f3ea' : '#fff' }}">
                                        {{ auth()->user()->theme !== 'dark' ? '✓ Active' : 'Set' }}
                                    </button>
                                </form>
                            </div>
                            <div class="s-row">
                                <div class="s-row-ico" style="background:#1d2b24"><i class="bi bi-moon-fill" style="color:#7fe0ac"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Dark</div><div class="s-row-sub">Easy on the eyes at night</div></div>
                                <form method="POST" action="{{ route('settings.theme') }}">
                                    @csrf<input type="hidden" name="theme" value="dark">
                                    <button type="submit" class="btn btn-sm" style="border-radius:.55rem;border:1px solid {{ auth()->user()->theme === 'dark' ? '#00A868' : '#e0e0e0' }};color:{{ auth()->user()->theme === 'dark' ? '#00743F' : '#888' }};font-size:.78rem;font-weight:600;background:{{ auth()->user()->theme === 'dark' ? '#e6f3ea' : '#fff' }}">
                                        {{ auth()->user()->theme === 'dark' ? '✓ Active' : 'Set' }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="s-section-lbl">Language</div>
                        <div class="s-group">
                            <div class="s-row">
                                <div class="s-row-ico green"><i class="bi bi-globe2"></i></div>
                                <div class="s-row-body"><div class="s-row-label">Interface language</div><div class="s-row-sub">{{ (auth()->user()->locale ?? 'en') === 'tl' ? 'Tagalog / Filipino' : 'English' }}</div></div>
                                <a href="{{ route('settings.preferences') }}" class="s-row-action" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('settingsModal')).hide()"><i class="bi bi-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>

                    {{-- ── Notifications panel ── --}}
                    <div class="s-panel d-none" id="sPanel-notifications">
                        <div class="s-panel-title">Notifications</div>
                        <div class="s-panel-sub">Choose which emails SmartDocs sends you.</div>

                        <div class="s-group">
                            <form method="POST" action="{{ route('settings.notifications') }}">
                                @csrf
                                <div class="s-row">
                                    <div class="s-row-ico green"><i class="bi bi-check2-circle"></i></div>
                                    <div class="s-row-body"><div class="s-row-label">Document processed</div><div class="s-row-sub">Email when text extraction finishes</div></div>
                                    <div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input s-notif-toggle" role="switch" name="notify_doc_done" value="1" {{ auth()->user()->notify_doc_done ? 'checked' : '' }}></div>
                                </div>
                                <div class="s-row">
                                    <div class="s-row-ico green"><i class="bi bi-x-octagon"></i></div>
                                    <div class="s-row-body"><div class="s-row-label">Processing failed</div><div class="s-row-sub">Get notified when processing fails</div></div>
                                    <div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input s-notif-toggle" role="switch" name="notify_doc_failed" value="1" {{ auth()->user()->notify_doc_failed ? 'checked' : '' }}></div>
                                </div>
                                <div class="s-row">
                                    <div class="s-row-ico green"><i class="bi bi-clipboard-check"></i></div>
                                    <div class="s-row-body"><div class="s-row-label">Approval decisions</div><div class="s-row-sub">Email when your document is approved or rejected</div></div>
                                    <div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input s-notif-toggle" role="switch" name="notify_doc_approval" value="1" {{ auth()->user()->notify_doc_approval ? 'checked' : '' }}></div>
                                </div>
                                <div class="s-row" style="border-bottom:0">
                                    <div class="s-row-ico green"><i class="bi bi-fingerprint"></i></div>
                                    <div class="s-row-body"><div class="s-row-label">Sign-in alerts</div><div class="s-row-sub">Security alert for every new sign-in</div></div>
                                    <div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input s-notif-toggle" role="switch" name="notify_login_alert" value="1" {{ auth()->user()->notify_login_alert ? 'checked' : '' }}></div>
                                </div>
                                <div class="px-3 py-2" style="border-top:1px solid #f0f0f0">
                                    <button type="submit" class="btn btn-sm" style="background:#00A868;color:#fff;border-radius:.6rem;font-size:.8rem;font-weight:600;border:none">
                                        <i class="bi bi-check-lg me-1"></i>Save notification preferences
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>{{-- /right panels --}}
            </div>{{-- /body --}}
        </div>{{-- /modal-content --}}
    </div>{{-- /modal-dialog --}}
</div>{{-- /settingsModal --}}

<style>
    /* ── Settings modal nav ── */
    .smn-section { font-size:.67rem;font-weight:700;color:#bbb;text-transform:uppercase;letter-spacing:.55px;padding:.5rem .5rem .2rem; }
    .smn-item { display:flex;align-items:center;gap:.6rem;width:100%;padding:.48rem .7rem;border:none;background:transparent;border-radius:.6rem;font-size:.83rem;font-weight:500;color:#333;cursor:pointer;text-align:left;transition:background .12s ease,color .12s ease;white-space:nowrap; }
    .smn-item:hover:not(.active) { background:#ebebeb;color:#111; }
    .smn-item.active { background:#e6f3ea;color:#00743F;font-weight:600; }
    .smn-item i { width:16px;text-align:center;font-size:.95rem;flex:0 0 16px; }
    [data-theme="dark"] .smn-item { color:#c3d0c8; }
    [data-theme="dark"] .smn-item:hover:not(.active) { background:#1d2b24;color:#e5eee8; }
    [data-theme="dark"] .smn-item.active { background:#12291d;color:#7fe0ac; }
    /* ── Settings modal panels ── */
    .s-panel-title { font-size:1.3rem;font-weight:800;color:#111;margin-bottom:.15rem; }
    .s-panel-sub   { font-size:.82rem;color:#888;margin-bottom:1.25rem; }
    [data-theme="dark"] .s-panel-title { color:#fff; }
    [data-theme="dark"] .s-panel-sub   { color:#8fa59a; }
    .s-section-lbl { font-size:.73rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.45px;margin:.1rem 0 .4rem; }
    .s-group { border:1px solid #ebebeb;border-radius:.85rem;overflow:hidden;margin-bottom:1.1rem;background:#fff; }
    [data-theme="dark"] .s-group { background:#16211c;border-color:#26362e; }
    .s-row { display:flex;align-items:center;gap:.85rem;padding:.75rem 1rem;border-bottom:1px solid #f5f5f5;transition:background .1s; }
    .s-row:last-child { border-bottom:0; }
    .s-row:hover { background:#fafafa; }
    [data-theme="dark"] .s-row { border-color:#1d2b24; }
    [data-theme="dark"] .s-row:hover { background:#1a2820; }
    .s-row-ico { width:32px;height:32px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:.9rem;color:#666;flex:0 0 32px; }
    .s-row-ico.green { background:#e6f3ea;color:#00A868; }
    [data-theme="dark"] .s-row-ico { background:#1d2b24;color:#8fa59a; }
    [data-theme="dark"] .s-row-ico.green { color:#7fe0ac; }
    .s-row-body { flex:1 1 auto;min-width:0; }
    .s-row-label { font-size:.85rem;font-weight:600;color:#111; }
    .s-row-sub   { font-size:.74rem;color:#888; }
    [data-theme="dark"] .s-row-label { color:#e5eee8; }
    [data-theme="dark"] .s-row-sub   { color:#8fa59a; }
    .s-row-action { color:#ccc;font-size:.85rem;text-decoration:none;flex:0 0 auto;transition:color .12s; }
    .s-row-action:hover { color:#00A868; }
    /* modal dark bg */
    [data-theme="dark"] #settingsModal .modal-content { background:#16211c; }
    [data-theme="dark"] #settingsModal .modal-content > div:first-child { border-color:#26362e!important; }
    [data-theme="dark"] #settingsModal > .modal-dialog > .modal-content > .d-flex > div:first-child { background:#111a15!important;border-color:#26362e!important; }
    [data-theme="dark"] .smn-section { color:#4a5e54; }
    [data-theme="dark"] #settingsModal .modal-content > .d-flex > div:first-child { background:#111a15; }
</style>
<script>
(function () {
    /* ── Settings modal: tab switching ── */
    var modal = document.getElementById('settingsModal');
    if (!modal) return;

    function switchPanel(name) {
        modal.querySelectorAll('.s-panel').forEach(function(p){ p.classList.add('d-none'); });
        modal.querySelectorAll('.smn-item[data-panel]').forEach(function(b){ b.classList.remove('active'); });
        var panel = modal.querySelector('#sPanel-' + name);
        var btn   = modal.querySelector('.smn-item[data-panel="' + name + '"]');
        if (panel) panel.classList.remove('d-none');
        if (btn)   btn.classList.add('active');
    }

    modal.querySelectorAll('.smn-item[data-panel]').forEach(function(btn) {
        btn.addEventListener('click', function(){ switchPanel(btn.dataset.panel); });
    });

    /* Open to correct panel when triggered from specific URL hash */
    modal.addEventListener('show.bs.modal', function(e) {
        var trigger = e.relatedTarget;
        var panel = trigger ? (trigger.dataset.panel || 'account') : 'account';
        switchPanel(panel);
    });

    /* ── Notification toggles: auto-submit on change ── */
    modal.querySelectorAll('.s-notif-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            toggle.closest('form').requestSubmit();
        });
    });

    /* ── Nav search ── */
    var search = document.getElementById('sModalSearch');
    if (search) {
        search.addEventListener('input', function() {
            var q = search.value.trim().toLowerCase();
            modal.querySelectorAll('.smn-item').forEach(function(el) {
                el.style.display = (!q || el.textContent.trim().toLowerCase().includes(q)) ? '' : 'none';
            });
        });
    }
})();
</script>
@endauth
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
    var mobileNav = window.matchMedia('(max-width: 767.98px)');
    var backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);

    function syncState() {
        var open = mobileNav.matches && sidebar.classList.contains('open');
        sidebar.inert = mobileNav.matches && !open;
        if (mobileNav.matches && !open) {
            sidebar.setAttribute('aria-hidden', 'true');
        } else {
            sidebar.removeAttribute('aria-hidden');
        }
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        backdrop.classList.toggle('show', open);
    }

    function close(returnFocus) {
        sidebar.classList.remove('open');
        syncState();
        if (returnFocus && mobileNav.matches) toggle.focus();
    }

    toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('open')) {
            close(false);
            return;
        }
        sidebar.classList.add('open');
        syncState();
        var firstLink = Array.prototype.slice.call(sidebar.querySelectorAll('.nav-link'))
            .find(function (link) { return link.getClientRects().length > 0; });
        if (firstLink) firstLink.focus();
    });
    backdrop.addEventListener('click', function () { close(true); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) close(true);
    });
    mobileNav.addEventListener('change', function () {
        sidebar.classList.remove('open');
        syncState();
    });
    syncState();
})();
</script>
<script>
(function () {
    var el = document.getElementById('p3DateTime');
    if (!el) return;
    function tick() {
        var d = new Date();
        var date = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: '2-digit', year: 'numeric' });
        var time = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        el.textContent = date + ' - ' + time;
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
</body>
</html>



