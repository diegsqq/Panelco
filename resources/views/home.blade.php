@extends('layouts.app')

@section('content')
<style>
    /* ── Dashboard shell ── */
    .p3-dash { display: flex; flex-direction: column; gap: 1.5rem; }
    html:has(#p3Dashboard), body:has(#p3Dashboard) { overflow: auto; }
    body:has(#p3Dashboard) .p3-topbar { display: none; }
    @media (max-width: 767.98px) {
        body:has(#p3Dashboard) .p3-topbar { display: flex !important; }
    }

    /* ── Top bar: search + actions ── */
    .wd-topbar { display: flex; align-items: center; gap: .85rem; flex-wrap: wrap; }
    .wd-search { display: flex; align-items: center; gap: .5rem; flex: 1 1 320px; max-width: 420px; background: #fff; border: 1px solid #e2e2e2; border-radius: .7rem; padding: .5rem .85rem; transition: border-color .15s ease, box-shadow .15s ease; }
    .wd-search:focus-within { border-color: #00A868; box-shadow: 0 0 0 .2rem rgba(0,168,104,.12); }
    .wd-search i { color: #9a9ab0; font-size: .9rem; }
    .wd-search input { border: 0; outline: none; background: transparent; flex: 1; font-size: .85rem; color: #1a1a2e; min-width: 0; }
    .wd-search input::placeholder { color: #9a9ab0; }
    .wd-top-actions { display: flex; align-items: center; gap: .6rem; margin-left: auto; }
    .wd-btn-primary { display: inline-flex; align-items: center; gap: .4rem; background: #111; color: #fff; border: none; border-radius: .7rem; padding: .55rem 1.05rem; font-size: .84rem; font-weight: 700; text-decoration: none; transition: background-color .15s ease; white-space: nowrap; }
    .wd-btn-primary:hover { background: #333; color: #fff; }
    .wd-btn-ghost { display: inline-flex; align-items: center; gap: .4rem; background: #fff; color: #00743F; border: 1px solid #d5e8db; border-radius: .7rem; padding: .55rem 1rem; font-size: .84rem; font-weight: 700; text-decoration: none; transition: all .15s ease; white-space: nowrap; }
    .wd-btn-ghost:hover { background: #f0f9f3; border-color: #00A868; color: #00743F; }
    .hp-cta { display: inline-flex; align-items: center; gap: .4rem; background: #00A868; color: #fff; border: none; border-radius: .7rem; padding: .55rem 1.05rem; font-size: .84rem; font-weight: 700; text-decoration: none; transition: background-color .15s ease; white-space: nowrap; }
    .hp-cta:hover { background: #00743F; color: #fff; }

    /* ── Welcome ── */
    .wd-welcome { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .wd-welcome-text { min-width: 0; }
    .wd-welcome-right { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .wd-welcome-title { font-size: 1.6rem; font-weight: 800; color: #111; line-height: 1.2; margin: 0; }
    .wd-welcome-sub { color: #5a9e7a; font-size: .875rem; margin: .25rem 0 0; }
    .wd-stats { display: flex; gap: .5rem; flex-wrap: wrap; justify-content: center; }
    .wd-stat { background: #fff; border: 1px solid #ebebeb; border-radius: .75rem; padding: .45rem .85rem; min-width: 74px; text-align: center; }
    .wd-stat-val { font-weight: 800; font-size: 1.05rem; line-height: 1.1; }
    .wd-stat-lbl { font-size: .66rem; color: #9a9ab0; margin-top: .1rem; text-transform: uppercase; letter-spacing: .4px; }

    /* ── Sections ── */
    .wd-section-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap; }
    .wd-section-head .wd-tools { border: 0; padding: 0; }
    .wd-section-title { font-size: 1.02rem; font-weight: 800; color: #1a1a2e; margin: 0; }
    .wd-section { position: relative; }
    /* Swipe hint icon on folders strip */
    .wd-swipe-hint { position: absolute; right: .4rem; bottom: .9rem; width: 30px; height: 30px; border-radius: 50%; background: #fff; border: 1px solid #d5e8db; box-shadow: 0 2px 8px rgba(10,31,92,.14); color: #00A868; display: none; align-items: center; justify-content: center; font-size: .95rem; pointer-events: none; z-index: 4; animation: wd-swipe 1.2s ease-in-out infinite; }
    .wd-swipe-hint.on { display: flex; }
    .wd-slider-btn { width: 36px; height: 36px; border-radius: 50%; border: 1px solid #d5e8db; background: #fff; color: #00743F; display: inline-flex; align-items: center; justify-content: center; font-size: .9rem; cursor: pointer; transition: all .15s ease; box-shadow: 0 2px 8px rgba(10,31,92,.12); }
    .wd-slider-btn:hover { background: #f0f9f3; border-color: #00A868; color: #00743F; }
    .wd-slider-btn:disabled { opacity: .4; cursor: not-allowed; }
    @keyframes wd-swipe { 0%, 100% { transform: translateX(0); opacity: .8; } 50% { transform: translateX(6px); opacity: 1; } }
    [data-theme="dark"] .wd-swipe-hint { background: #1d2b24; border-color: #2f6b4f; color: #7fe0ac; box-shadow: none; }

    /* ── Folder cards (2-row horizontal swipe strip) ── */
    .wd-folders { display: grid; grid-template-rows: repeat(2, auto); grid-auto-flow: column; grid-auto-columns: minmax(280px, 1fr); gap: .8rem; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; padding: .2rem .2rem .6rem; margin: -.2rem -.2rem 0; scrollbar-width: none; }
    .wd-folder { scroll-snap-align: start; display: flex; align-items: center; gap: .7rem; background: #fff; border: 1px solid #ebebeb; border-radius: .85rem; padding: .8rem .9rem; text-decoration: none; transition: all .15s ease; min-width: 0; }
    .wd-folder:hover { border-color: #b7e0c8; background: #fbfefc; box-shadow: 0 4px 14px rgba(10,31,92,.06); }
    .wd-folder-ico { width: 38px; height: 38px; flex: 0 0 38px; border-radius: .6rem; background: #e6f3ea; color: #00A868; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .wd-folder-name { font-weight: 700; font-size: .85rem; color: #1a1a2e; white-space: normal; overflow: visible; text-overflow: clip; max-width: 100%; line-height: 1.35; word-break: break-word; }
    .wd-folder-meta { font-size: .7rem; color: #9a9ab0; margin-top: .1rem; white-space: normal; overflow: visible; text-overflow: clip; max-width: 100%; line-height: 2.35; word-break: break-word; }
    .wd-folder-go { margin-left: auto; color: #c8c8d8; font-size: .85rem; flex: 0 0 auto; }

    /* ── Recent file cards (2-row horizontal swipe strip) ── */
    .wd-recents { display: grid; grid-template-rows: repeat(1, auto); grid-auto-flow: column; grid-auto-columns: minmax(180px, 1fr); gap: .8rem; justify-content: start; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; padding: .2rem .2rem .6rem; margin: -.2rem -.2rem 0; scrollbar-width: none; }
    .wd-recent { scroll-snap-align: start; display: flex; align-items: center; gap: .75rem; background: #fff; border: 1px solid #ebebeb; border-radius: .85rem; padding: .85rem .95rem; text-decoration: none; transition: all .15s ease; min-width: 0; }
    .wd-recent:hover { border-color: #b7e0c8; background: #fbfefc; box-shadow: 0 4px 14px rgba(10,31,92,.06); }
    .wd-recent-ico { width: 40px; height: 40px; flex: 0 0 40px; border-radius: .6rem; background: #f0f9f3; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .wd-recent-name { font-weight: 700; font-size: .85rem; color: #1a1a2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; line-height: 1.35; }
    .wd-recent-meta { font-size: .7rem; color: #9a9ab0; margin-top: .1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; line-height: 1.35; }

    /* ── All files ── */
    .wd-table-wrap { overflow: visible; }
    .wd-tools { display: flex; align-items: center; gap: .6rem; padding: .5rem .7rem; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; }
    .wd-filter { display: inline-flex; align-items: center; gap: .4rem; background: #fff; border: 1px solid #e2e2e2; border-radius: .65rem; padding: .35rem .65rem; font-size: .74rem; font-weight: 600; color: #444; text-decoration: none; transition: all .15s ease; }
    .wd-filter:hover { border-color: #00A868; color: #00743F; background: #f0f9f3; }
    .wd-mini-search { display: flex; align-items: center; gap: .45rem; flex: 1 1 200px; max-width: 300px; background: #f7f8fa; border: 1px solid #ececec; border-radius: .65rem; padding: .3rem .6rem; }
    .wd-mini-search:focus-within { border-color: #00A868; background: #fff; }
    .wd-mini-search i { color: #9a9ab0; font-size: .8rem; }
    .wd-mini-search input { border: 0; outline: none; background: transparent; flex: 1; font-size: .78rem; color: #1a1a2e; min-width: 0; }
    .wd-mini-search input::placeholder { color: #9a9ab0; }
    .wd-table { width: 100%; margin: 0; font-size: .78rem; table-layout: fixed; }
    .wd-table > :not(caption) > * > * { padding: .45rem .7rem; vertical-align: middle; }
    .wd-table thead th { background: #f8f9fa; color: #7b7fa8; font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #ececec !important; white-space: nowrap; }
    .wd-table tbody tr { transition: background-color .1s ease; }
    .wd-table tbody tr:hover { background: #fbfefc; }
    .wd-table tbody tr + tr td { border-top: 1px solid #f2f2f2; }
    .wd-name-cell { display: flex; align-items: center; gap: .65rem; min-width: 0; }
    .wd-name-ico { width: 30px; height: 30px; flex: 0 0 30px; border-radius: .5rem; background: #f0f9f3; display: flex; align-items: center; justify-content: center; font-size: .9rem; }
    .wd-name-text { display: flex; align-items: center; gap: .6rem; min-width: 0; overflow: hidden; }
    .wd-name-link { display: block; font-weight: 700; color: #1a1a2e; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; max-width: 260px; }
    .wd-name-link:hover { color: #00743F; text-decoration: underline; }
    .wd-name-meta { display: flex; align-items: center; gap: .4rem; margin-top: .2rem; font-size: .7rem; flex-wrap: wrap; }
    .wd-dot { color: #c8c8d8; }
    .wd-shared { display: flex; align-items: center; gap: .5rem; min-width: 0; }
    .wd-avatar { width: 28px; height: 28px; flex: 0 0 28px; border-radius: 50%; background: #e6f3ea; color: #00743F; font-size: .66rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    .wd-shared-name { font-weight: 600; color: #333; font-size: .88rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1 1 auto; min-width: 0; }
    .wd-modified { color: #5a9e7a; font-size: .95rem; white-space: nowrap; }
    .wd-cat { display: inline-flex; align-items: center; gap: .35rem; font-size: .78rem; font-weight: 600; color: #00743F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
    .wd-cat i { color: #00A868; font-size: .8rem; }
    .wd-size-cell { display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; }
    .wd-dots { display: inline-flex; align-items: center; justify-content: center; width: 1.2em; height: 1.2em; color: #9a9ab0; font-size: 1rem; line-height: 1; text-decoration: none; transition: color .15s ease; }
    .wd-dots:hover, .wd-dots:focus { color: #00A868; }
    .wd-empty { text-align: center; padding: 2.2rem 1rem; color: #9a9ab0; }
    .wd-empty i { font-size: 1.8rem; display: block; margin-bottom: .45rem; opacity: .5; }

    /* ── Dark mode ── */
    [data-theme="dark"] .wd-search { background: #131d18; border-color: #2c3d34; }
    [data-theme="dark"] .wd-search input { color: #e5eee8; }
    [data-theme="dark"] .wd-search input::placeholder { color: #7d9087; }
    [data-theme="dark"] .wd-btn-primary { background: #00A868; }
    [data-theme="dark"] .wd-btn-primary:hover { background: #00743F; }
    [data-theme="dark"] .wd-btn-ghost { background: #16211c; border-color: #2c3d34; color: #7fe0ac; }
    [data-theme="dark"] .wd-btn-ghost:hover { background: #1d2b24; border-color: #2f6b4f; color: #7fe0ac; }
    [data-theme="dark"] .wd-welcome-title { color: #fff; }
    [data-theme="dark"] .wd-welcome-sub { color: #8fa59a; }
    [data-theme="dark"] .wd-stat { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .wd-stat-lbl { color: #8fa59a; }
    [data-theme="dark"] .wd-section-title { color: #e5eee8; }
    [data-theme="dark"] .wd-folder, [data-theme="dark"] .wd-recent { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .wd-folder:hover, [data-theme="dark"] .wd-recent:hover { background: #1a2820; border-color: #2f6b4f; box-shadow: none; }
    [data-theme="dark"] .wd-folder-ico { background: #1d2b24; color: #fff; }
    [data-theme="dark"] .wd-folder-name, [data-theme="dark"] .wd-recent-name { color: #e5eee8; }
    [data-theme="dark"] .wd-folder-meta, [data-theme="dark"] .wd-recent-meta { color: #8fa59a; }
    [data-theme="dark"] .wd-folder-go { color: #fff; }
    [data-theme="dark"] .wd-recent-ico, [data-theme="dark"] .wd-name-ico { background: #1d2b24; color: #fff; }
    [data-theme="dark"] .wd-card { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .wd-tools { border-color: #26362e; }
    [data-theme="dark"] .wd-filter { background: #1d2b24; border-color: #2c3d34; color: #c3d0c8; }
    [data-theme="dark"] .wd-filter:hover { background: #12291d; border-color: #2f6b4f; color: #7fe0ac; }
    [data-theme="dark"] .wd-mini-search { background: #0f1512; border-color: #2c3d34; }
    [data-theme="dark"] .wd-mini-search:focus-within { background: #131d18; }
    [data-theme="dark"] .wd-mini-search input { color: #e5eee8; }
    [data-theme="dark"] .wd-mini-search input::placeholder { color: #7d9087; }
    [data-theme="dark"] .wd-table thead th { background: #131d18; color: #8fa59a; border-color: #2c3d34 !important; }
    [data-theme="dark"] .wd-table tbody tr:hover { background: #1a2820; }
    [data-theme="dark"] .wd-table tbody tr + tr td { border-color: #1d2b24; }
    [data-theme="dark"] .wd-table tbody td { color: #d7e5dc; }
    [data-theme="dark"] .wd-name-link { color: #e5eee8; }
    [data-theme="dark"] .wd-name-sub { color: #8fa59a; }
    [data-theme="dark"] .wd-name-meta { color: #8fa59a; }
    [data-theme="dark"] .wd-dot { color: #4a5e54; }
    [data-theme="dark"] .wd-avatar { background: #1d2b24; color: #7fe0ac; }
    [data-theme="dark"] .wd-shared-name { color: #c3d0c8; }
    [data-theme="dark"] .wd-modified { color: #8fa59a; }
    [data-theme="dark"] .wd-cat { color: #7fe0ac; }
    [data-theme="dark"] .wd-dots { color: #8fa59a; }
    [data-theme="dark"] .wd-dots:hover, [data-theme="dark"] .wd-dots:focus { color: #7fe0ac; }
    [data-theme="dark"] .wd-empty { color: #8fa59a; }

    /* ── Right-click create menu (Drive-style) ── */
    .wd-ctx { position: fixed; z-index: 1080; display: none; align-items: flex-start; gap: .35rem; margin: 0; padding: 0; }
    .wd-ctx.open { display: flex; }
    .wd-ctx-main { position: relative; left: 0; top: 0; }
    .wd-ctx-panel { background: #fff; color: #202124; border: 1px solid #e3e3e3; border-radius: .75rem; box-shadow: 0 12px 32px rgba(32,33,36,.22), 0 2px 6px rgba(32,33,36,.12); padding: .4rem 0; min-width: 268px; }
    .wd-ctx-sub { position: fixed; display: none; min-width: 236px; }
    .wd-ctx-sub.open { display: block; }
    .wd-ctx-item { width: 100%; display: flex; align-items: center; gap: .75rem; border: 0; background: transparent; color: inherit; text-align: left; padding: .5rem .85rem; font-size: .84rem; font-weight: 500; cursor: pointer; white-space: nowrap; }
    .wd-ctx-item:hover, .wd-ctx-item:focus-visible { background: #f1f3f4; outline: none; }
    .wd-ctx-ico { width: 20px; flex: 0 0 20px; font-size: 1.05rem; color: #5f6368; display: inline-flex; align-items: center; justify-content: center; }
    .wd-ctx-label { flex: 1; }
    .wd-ctx-key { color: #80868b; font-size: .7rem; font-weight: 400; }
    .wd-ctx-caret { color: #9aa0a6; font-size: .75rem; }
    .wd-ctx-sep { height: 1px; background: #e8eaed; margin: .35rem .5rem; }
    .wd-ctx-toast { position: fixed; left: 50%; bottom: 1.5rem; transform: translate(-50%, 1rem); z-index: 1090; background: #202124; color: #fff; border-radius: .6rem; padding: .65rem 1rem; font-size: .84rem; box-shadow: 0 8px 24px rgba(0,0,0,.28); opacity: 0; pointer-events: none; transition: opacity .18s ease, transform .18s ease; }
    .wd-ctx-toast.show { opacity: 1; transform: translate(-50%, 0); }
    [data-theme="dark"] .wd-ctx-panel { background: #1d2b24; border-color: #2f6b4f; color: #e5eee8; box-shadow: 0 12px 32px rgba(0,0,0,.45); }
    [data-theme="dark"] .wd-ctx-item:hover, [data-theme="dark"] .wd-ctx-item:focus-visible { background: #26382f; }
    [data-theme="dark"] .wd-ctx-ico, [data-theme="dark"] .wd-ctx-key, [data-theme="dark"] .wd-ctx-caret { color: #8fa59a; }
    [data-theme="dark"] .wd-ctx-sep { background: #2f6b4f; }
</style>

<div class="p3-dash" id="p3Dashboard">

    {{-- ── Welcome ── --}}
    <div class="wd-welcome">
        <div class="wd-welcome-text">
            @if(auth()->user()->created_at->diffInDays(now()) <= 3)
                <h1 class="wd-welcome-title">Welcome to P-ARCHIVE, {{ strtok(auth()->user()->name, ' ') }}!</h1>
                <p class="wd-welcome-sub">Get started by uploading your first document.</p>
            @else
                <h1 class="wd-welcome-title">Welcome back, {{ strtok(auth()->user()->name, ' ') }}!</h1>
                <p class="wd-welcome-sub">Glad to have you back! Let's get started.</p>
            @endif
        </div>
        <div class="wd-welcome-right">

            <div class="wd-welcome-actions">
                <a href="{{ route('chat.index') }}" class="wd-btn-ghost"><i class="bi bi-lightning-charge-fill"></i> Ask Lightning AI</a>
                <a href="{{ route('documents.upload') }}" class="wd-btn-primary"><i class="bi bi-cloud-arrow-up-fill"></i> Upload files</a>
            </div>
        </div>
    </div>

    {{-- ── Folders (categories) ── --}}
    <section class="wd-section" aria-labelledby="wdFoldersTitle">
        <div class="wd-section-head">
            <h2 class="wd-section-title" id="wdFoldersTitle">Folders</h2>
        </div>
        @if($categories->isEmpty())
            <div class="wd-card wd-empty"><i class="bi bi-folder2-open"></i>No folders yet — categories appear here as soon as they are added.</div>
        @else
            <div style="position:relative;">
                <button type="button" class="wd-slider-btn" data-target=".wd-folders" data-dir="-1" aria-label="Scroll folders left" style="position:absolute;left:-16px;top:50%;transform:translateY(-50%);z-index:5;"><i class="bi bi-chevron-left"></i></button>
                <div class="wd-folders">
                    @php
                        $folderCounts = [];
                        foreach ($categoryDistribution as $cd) {
                            $folderCounts[$cd->category_id] = (int) $cd->total;
                        }
                    @endphp
                    @foreach($categories as $folder)
                        <?php $catCount = $folderCounts[$folder->id] ?? 0; ?>
                        <a class="wd-folder" href="{{ route('documents.library', ['category_id' => $folder->id]) }}">
                            <span class="wd-folder-ico"><i class="bi bi-folder-fill"></i></span>
                            <span style="min-width:0">
                                <span class="wd-folder-name d-block">{{ $folder->name }}</span>
                                <span class="wd-folder-meta">{{ number_format($catCount) }} document{{ $catCount !== 1 ? 's' : '' }}</span>
                            </span>
                            <i class="bi bi-chevron-right wd-folder-go" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
                <button type="button" class="wd-slider-btn" data-target=".wd-folders" data-dir="1" aria-label="Scroll folders right" style="position:absolute;right:-16px;top:50%;transform:translateY(-50%);z-index:5;"><i class="bi bi-chevron-right"></i></button>
            </div>
        @endif
        </div>
    </section>

    {{-- ── Recent files ── --}}
    <section class="wd-section" aria-labelledby="wdRecentTitle">
        <div class="wd-section-head">
            <h2 class="wd-section-title" id="wdRecentTitle">Recent files</h2>
        </div>
        @if($recentFiles->isEmpty())
            <div class="wd-card wd-empty"><i class="bi bi-file-earmark-arrow-up"></i>No files yet. Upload your first document to get started.</div>
        @else
            <div class="wd-recents">
                @foreach($recentFiles as $file)
                    @php
                        $mime = $file->mime_type ?? '';
                        $fileIcon = str_contains($mime, 'pdf') ? ['bi-file-earmark-pdf-fill', '#dc3545']
                                  : (str_contains($mime, 'word') ? ['bi-file-earmark-word-fill', '#0d6efd']
                                  : (str_starts_with($mime, 'image/') ? ['bi-file-earmark-image-fill', '#00A868']
                                  : (str_contains($mime, 'text') ? ['bi-file-earmark-text-fill', '#6c757d']
                                  : ['bi-file-earmark-fill', '#6c757d'])));
                        $fileSize = $file->file_size >= 1048576 ? number_format($file->file_size / 1048576, 1) . ' MB' : number_format($file->file_size / 1024, 0) . ' KB';
                    @endphp
                    <a class="wd-recent" href="{{ route('documents.show', $file) }}">
                        <span class="wd-recent-ico"><i class="bi {{ $fileIcon[0] }}" style="color:{{ $fileIcon[1] }}"></i></span>
                        <span style="min-width:0">
                            <span class="wd-recent-name d-block">{{ $file->title ?? $file->original_filename }}</span>
                            <span class="wd-recent-meta">{{ $fileSize }} · {{ $file->updated_at?->format('M j, Y') ?? '—' }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ── Shared files ── --}}
    <section class="wd-section" aria-labelledby="wdSharedTitle">
        <div class="wd-section-head">
            <h2 class="wd-section-title" id="wdSharedTitle">Shared files</h2>
            <a href="{{ route('documents.library', ['shared' => 1]) }}" class="wd-filter"><i class="bi bi-arrow-right"></i> View all</a>
        </div>
        @if($sharedFiles->isEmpty())
            <div class="wd-card wd-empty"><i class="bi bi-people"></i>No shared files yet. Documents shared with you will appear here.</div>
        @else
            <div class="wd-recents">
                @foreach($sharedFiles as $file)
                    @php
                        $mime = $file->mime_type ?? '';
                        $fileIcon = str_contains($mime, 'pdf') ? ['bi-file-earmark-pdf-fill', '#dc3545']
                                  : (str_contains($mime, 'word') ? ['bi-file-earmark-word-fill', '#0d6efd']
                                  : (str_starts_with($mime, 'image/') ? ['bi-file-earmark-image-fill', '#00A868']
                                  : (str_contains($mime, 'text') ? ['bi-file-earmark-text-fill', '#6c757d']
                                  : ['bi-file-earmark-fill', '#6c757d'])));
                        $fileSize = $file->file_size >= 1048576 ? number_format($file->file_size / 1048576, 1) . ' MB' : number_format($file->file_size / 1024, 0) . ' KB';
                    @endphp
                    <a class="wd-recent" href="{{ route('documents.show', $file) }}">
                        <span class="wd-recent-ico"><i class="bi {{ $fileIcon[0] }}" style="color:{{ $fileIcon[1] }}"></i></span>
                        <span style="min-width:0">
                            <span class="wd-recent-name d-block">{{ $file->title ?? $file->original_filename }}</span>
                            <span class="wd-recent-meta">{{ $fileSize }} · {{ $file->uploader?->name ?? 'Unknown' }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ── All files ── --}}
    <section class="wd-section" aria-labelledby="wdAllTitle">
        <div class="wd-section-head">
            <h2 class="wd-section-title" id="wdAllTitle">All files</h2>
            <a href="{{ route('documents.library') }}" class="wd-filter"><i class="bi bi-arrow-right"></i> View all</a>
        </div>
        <div class="wd-card">
            @if($allFiles->isEmpty())
                <div class="wd-empty"><i class="bi bi-inbox"></i>No files to show yet.</div>
            @else
                <div class="wd-table-wrap">
                    <table class="table wd-table mb-0">
                        <caption class="visually-hidden">All files</caption>
                        <thead>
                            <tr>
                                @php
                                    $curSort = $sort ?? 'updated_at';
                                    $curDir = $dir ?? 'desc';
                                    $sortLink = function (string $col) use ($curSort, $curDir) {
                                        $next = ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
                                        return request()->fullUrlWithQuery(['sort' => $col, 'dir' => $next]);
                                    };
                                @endphp
                                <th scope="col" style="cursor:pointer">
                                    <a href="{{ $sortLink('title') }}" class="text-decoration-none d-flex align-items-center gap-1">
                                        File name @if($curSort === 'title')<i class="bi bi-arrow-{{ $curDir === 'asc' ? 'up' : 'down' }}-short" style="font-size:.55rem"></i>@else<i class="bi bi-arrow-down-up" style="font-size:.55rem;opacity:.45"></i>@endif
                                    </a>
                                </th>
                                <th scope="col" style="width:180px;cursor:pointer">
                                    <a href="{{ $sortLink('category') }}" class="text-decoration-none d-flex align-items-center gap-1">
                                        Categories @if($curSort === 'category')<i class="bi bi-arrow-{{ $curDir === 'asc' ? 'up' : 'down' }}-short" style="font-size:.55rem"></i>@else<i class="bi bi-arrow-down-up" style="font-size:.55rem;opacity:.45"></i>@endif
                                    </a>
                                </th>
                                <th scope="col" style="width:230px;cursor:pointer">
                                    <a href="{{ $sortLink('uploader') }}" class="text-decoration-none d-flex align-items-center gap-1">
                                        Shared by @if($curSort === 'uploader')<i class="bi bi-arrow-{{ $curDir === 'asc' ? 'up' : 'down' }}-short" style="font-size:.55rem"></i>@else<i class="bi bi-arrow-down-up" style="font-size:.55rem;opacity:.45"></i>@endif
                                    </a>
                                </th>
                                <th scope="col" style="width:160px;cursor:pointer">
                                    <a href="{{ $sortLink('updated_at') }}" class="text-decoration-none d-flex align-items-center gap-1">
                                        Last modified @if($curSort === 'updated_at')<i class="bi bi-arrow-{{ $curDir === 'asc' ? 'up' : 'down' }}-short" style="font-size:.55rem"></i>@else<i class="bi bi-arrow-down-up" style="font-size:.55rem;opacity:.45"></i>@endif
                                    </a>
                                </th>
                                <th scope="col" style="width:125px;cursor:pointer">
                                    <a href="{{ $sortLink('file_size') }}" class="text-decoration-none d-flex align-items-center gap-1">
                                        Size @if($curSort === 'file_size')<i class="bi bi-arrow-{{ $curDir === 'asc' ? 'up' : 'down' }}-short" style="font-size:.55rem"></i>@else<i class="bi bi-arrow-down-up" style="font-size:.55rem;opacity:.45"></i>@endif
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allFiles as $file)
                                @php
                                    $mime = $file->mime_type ?? '';
                                    $rowIcon = str_contains($mime, 'pdf') ? ['bi-file-earmark-pdf-fill', '#dc3545']
                                             : (str_contains($mime, 'word') ? ['bi-file-earmark-word-fill', '#0d6efd']
                                             : (str_starts_with($mime, 'image/') ? ['bi-file-earmark-image-fill', '#00A868']
                                             : (str_contains($mime, 'text') ? ['bi-file-earmark-text-fill', '#6c757d']
                                             : ['bi-file-earmark-fill', '#6c757d'])));
                                    $rowSize = $file->file_size >= 1048576 ? number_format($file->file_size / 1048576, 1) . ' MB' : number_format($file->file_size / 1024, 0) . ' KB';
                                    $rowInitials = $file->uploader?->initials() ?? '?';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="wd-name-cell">
                                            <span class="wd-name-ico"><i class="bi {{ $rowIcon[0] }}" style="color:{{ $rowIcon[1] }}"></i></span>
                                            <span class="wd-name-text">
                                                <a class="wd-name-link" href="{{ route('documents.show', $file) }}" title="{{ $file->title ?? $file->original_filename }}">{{ $file->title ?? $file->original_filename }}</a>
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="wd-cat"><i class="bi bi-folder-fill"></i> {{ $file->category?->name ?? 'Uncategorized' }}</span></td>
                                    <td>
                                        <div class="wd-shared">
                                            <span class="wd-avatar">{{ $rowInitials }}</span>
                                            <span class="wd-shared-name">{{ $file->uploader?->name ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    <td class="wd-modified text-nowrap">{{ $file->updated_at?->format('d F Y') ?? '—' }}</td>
                                    <td class="wd-modified text-nowrap">
                                        <span class="wd-size-cell">
                                            {{ $rowSize }}
                                            <span class="dropdown d-inline-block">
                                                <button class="btn btn-link p-0 wd-dots" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $file->title ?? $file->original_filename }}"><i class="bi bi-three-dots-vertical"></i></button>
                                                <ul class="dropdown-menu dropdown-menu-end" style="border-radius:.75rem;border-color:#d5e8db">
                                                    <li><a class="dropdown-item" href="{{ route('documents.show', $file) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                                    <li><a class="dropdown-item" href="{{ route('documents.show', $file) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                                <li><a class="dropdown-item" href="{{ route('documents.clone', $file) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                    <li><button type="button" class="dropdown-item text-danger" data-delete-url="{{ route('documents.destroy', $file) }}" onclick="if(confirm('Delete this document?')){var f=document.getElementById('wdDelForm');f.action=this.getAttribute('data-delete-url');f.submit();}"><i class="bi bi-trash me-2"></i>Delete</button></li>
                                                </ul>
                                            </span>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

</div>

{{-- ── Right-click create menu ── --}}
<div class="wd-ctx" id="wdContextMenu" aria-hidden="true">
    <div class="wd-ctx-panel wd-ctx-main" role="menu" aria-label="Create menu">
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="new-folder" title="Creates a new empty folder to organize your files.">
            <span class="wd-ctx-ico"><i class="bi bi-folder-plus"></i></span><span class="wd-ctx-label">New folder</span><span class="wd-ctx-key">Alt+C then F</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="file-upload" title="Lets you pick individual files from your computer to save in Google Drive.">
            <span class="wd-ctx-ico"><i class="bi bi-file-earmark-arrow-up"></i></span><span class="wd-ctx-label">File upload</span><span class="wd-ctx-key">Alt+C then U</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="folder-upload" title="Lets you upload an entire folder and all of its contents at once.">
            <span class="wd-ctx-ico"><i class="bi bi-folder-symlink"></i></span><span class="wd-ctx-label">Folder upload</span><span class="wd-ctx-key">Alt+C then I</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="new-project" title="Creates a workspace or project hub to group related files and tasks together.">
            <span class="wd-ctx-ico"><i class="bi bi-kanban"></i></span><span class="wd-ctx-label">New project</span>
        </button>
        <div class="wd-ctx-sep"></div>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="docs" title="Opens a word processor for writing documents, letters, or reports.">
            <span class="wd-ctx-ico"><i class="bi bi-file-earmark-text"></i></span><span class="wd-ctx-label">Google Docs</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="sheets" title="Opens a spreadsheet tool for data, tables, and calculations.">
            <span class="wd-ctx-ico"><i class="bi bi-table"></i></span><span class="wd-ctx-label">Google Sheets</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="slides" title="Opens a presentation builder for creating slideshows.">
            <span class="wd-ctx-ico"><i class="bi bi-easel"></i></span><span class="wd-ctx-label">Google Slides</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="vids" title="Opens an AI-powered video creation app for making work or project videos.">
            <span class="wd-ctx-ico"><i class="bi bi-camera-video"></i></span><span class="wd-ctx-label">Google Vids</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="forms" title="Opens a tool to build online surveys, quizzes, and registration forms.">
            <span class="wd-ctx-ico"><i class="bi bi-ui-checks"></i></span><span class="wd-ctx-label">Google Forms</span>
        </button>
        <div class="wd-ctx-sep"></div>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="more" aria-haspopup="menu" aria-expanded="false" aria-controls="wdContextMore" title="Expands a side menu showing extra Google apps and connected tools.">
            <span class="wd-ctx-ico"><i class="bi bi-three-dots"></i></span><span class="wd-ctx-label">More</span><span class="wd-ctx-caret"><i class="bi bi-chevron-right"></i></span>
        </button>
    </div>
    <div class="wd-ctx-panel wd-ctx-sub" id="wdContextMore" role="menu" aria-label="More apps" aria-hidden="true">
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="drawings" title="A tool for making simple diagrams, flowcharts, and graphics.">
            <span class="wd-ctx-ico"><i class="bi bi-palette"></i></span><span class="wd-ctx-label">Google Drawings</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="maps" title="Lets you create custom maps with pins, routes, and shapes.">
            <span class="wd-ctx-ico"><i class="bi bi-map"></i></span><span class="wd-ctx-label">Google My Maps</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="sites" title="A drag-and-drop builder for creating basic websites or internal pages.">
            <span class="wd-ctx-ico"><i class="bi bi-globe2"></i></span><span class="wd-ctx-label">Google Sites</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="markdown" title="Creates a plain-text file formatted with Markdown syntax (often used for notes or code documentation).">
            <span class="wd-ctx-ico"><i class="bi bi-file-earmark-code"></i></span><span class="wd-ctx-label">Markdown</span>
        </button>
        <button type="button" class="wd-ctx-item" role="menuitem" data-wd-action="apps-script" title="Opens a coding environment to write scripts that automate tasks across Google apps.">
            <span class="wd-ctx-ico"><i class="bi bi-code-slash"></i></span><span class="wd-ctx-label">Google Apps Script</span>
        </button>
    </div>
</div>
<div class="wd-ctx-toast" id="wdContextToast" role="status" aria-live="polite"></div>

{{-- ── New folder modal ── --}}
<div class="modal fade" id="wdNewFolderModal" tabindex="-1" aria-labelledby="wdNewFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;width:calc(100% - 2rem)">
        <div class="modal-content p3-modal">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="dashboard">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="wdNewFolderModalLabel"><i class="bi bi-folder-plus me-2" style="color:#00A868"></i>New folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="wdNewFolderName">Folder name</label>
                        <input id="wdNewFolderName" name="name" class="form-control" maxlength="255" placeholder="e.g. Maintenance" required autofocus>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold" for="wdNewFolderParent">Inside <span class="text-muted fw-normal">(optional)</span></label>
                        <select id="wdNewFolderParent" name="parent_id" class="form-select">
                            <option value="">— Top level —</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-lg me-1"></i>Create folder</button>
                </div>
            </form>
        </div>
    </div>
</div>



{{-- ── Password reset approved popup ── --}}
@php
    $approvedResetNotification = null;
    if (auth()->check()) {
        $approvedResetNotification = auth()->user()->unreadNotifications()->where('type', 'password_reset_approved')->first();
    }
@endphp
@if($approvedResetNotification)
<div class="modal fade" id="passwordResetPopup" tabindex="-1" aria-labelledby="passwordResetPopupLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:1rem;overflow:hidden;">
            <div class="modal-header" style="background:#087a4b;color:#fff;border:0;">
                <h5 class="modal-title" id="passwordResetPopupLabel"><i class="bi bi-check-circle-fill me-2"></i>Password Reset Approved</h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-key-fill" style="font-size:3rem;color:#087a4b;"></i>
                </div>
                <p class="fw-semibold mb-3">Your password has been reset to the default.</p>
                <div class="card border-0 bg-light p-3 mb-3" style="border-radius:.75rem;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Login ID</span>
                        <span class="fw-bold" style="font-size:1.1rem;color:#087a4b;">{{ auth()->user()->employee_id }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Default Password</span>
                        <span class="fw-bold" style="font-size:1.1rem;color:#087a4b;">P@55w0rd</span>
                    </div>
                </div>
                <p class="text-muted small mb-0">Please change your password after logging in for security.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal" id="passwordResetPopupClose">
                    <i class="bi bi-check-lg me-1"></i>Got it
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = new bootstrap.Modal(document.getElementById('passwordResetPopup'));
    modal.show();
    document.getElementById('passwordResetPopupClose').addEventListener('click', function () {
        fetch('/notifications/{{ $approvedResetNotification->id }}/read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
    });
})();
</script>
@endif

<script>
(function () {
    var strip = document.querySelector('.wd-folders');
    if (!strip) return;
    function update() {
        // no-op hint removed
    }
    strip.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
})();
</script>

<script>
(function () {
    document.querySelectorAll('.wd-slider-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.querySelector(btn.getAttribute('data-target'));
            if (!target) return;
            var dir = parseInt(btn.getAttribute('data-dir'), 10);
            var scrollAmount = target.clientWidth * 0.8;
            target.scrollBy({ left: dir * scrollAmount, behavior: 'smooth' });
        });
    });

    // Disable slider buttons at scroll boundaries
    document.querySelectorAll('.wd-folders, .wd-recents').forEach(function (strip) {
        var parent = strip.parentElement;
        if (!parent) return;
        var leftBtn = parent.querySelector('.wd-slider-btn[data-dir="-1"]');
        var rightBtn = parent.querySelector('.wd-slider-btn[data-dir="1"]');
        if (!leftBtn || !rightBtn) return;

        function updateButtons() {
            var atStart = strip.scrollLeft <= 1;
            var atEnd = strip.scrollLeft + strip.clientWidth >= strip.scrollWidth - 4;
            leftBtn.disabled = atStart;
            rightBtn.disabled = atEnd;
        }
        strip.addEventListener('scroll', updateButtons, { passive: true });
        window.addEventListener('resize', updateButtons);
        updateButtons();
    });
})();
</script>

<script>
(function () {
    var dash = document.getElementById('p3Dashboard');
    var menu = document.getElementById('wdContextMenu');
    var sub = document.getElementById('wdContextMore');
    var moreItem = menu && menu.querySelector('[data-wd-action="more"]');
    var toast = document.getElementById('wdContextToast');
    var folderModalEl = document.getElementById('wdNewFolderModal');
    var folderModal = folderModalEl && window.bootstrap ? new bootstrap.Modal(folderModalEl) : null;
    var uploadUrl = @json(route('documents.upload'));
    var folderUrl = @json(route('categories.create'));
    var hideSubTimer = null;
    var toastTimer = null;
    var chordArmed = false;
    var chordTimer = null;

    if (!dash || !menu || !sub || !moreItem) return;

    function flash(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
    }

    function closeSub(immediate) {
        clearTimeout(hideSubTimer);
        var apply = function () {
            sub.classList.remove('open');
            sub.setAttribute('aria-hidden', 'true');
            moreItem.setAttribute('aria-expanded', 'false');
        };
        if (immediate) apply();
        else hideSubTimer = setTimeout(apply, 140);
    }

    function positionSub() {
        if (!sub.classList.contains('open')) return;
        var label = moreItem.querySelector('.wd-ctx-label');
        var anchorRect = label ? label.getBoundingClientRect() : moreItem.getBoundingClientRect();
        var width = sub.offsetWidth;
        var height = sub.offsetHeight;
        var gap = 8;
        var left = anchorRect.right + gap;
        if (left + width > window.innerWidth - 8) left = Math.max(8, anchorRect.left - width - gap);
        var top = anchorRect.top + (anchorRect.height - height) / 2;
        sub.style.left = left + 'px';
        sub.style.top = Math.max(8, Math.min(top, window.innerHeight - height - 8)) + 'px';
    }

    function openSub() {
        clearTimeout(hideSubTimer);
        sub.classList.add('open');
        sub.setAttribute('aria-hidden', 'false');
        moreItem.setAttribute('aria-expanded', 'true');
        positionSub();
    }

    function closeMenu() {
        menu.classList.remove('open');
        menu.setAttribute('aria-hidden', 'true');
        closeSub(true);
    }

    function openMenu(x, y) {
        closeSub(true);
        menu.classList.add('open');
        menu.setAttribute('aria-hidden', 'false');
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
    }

    function isTyping(el) {
        return !!el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable);
    }

    function downloadMarkdown() {
        var blob = new Blob(['# Untitled\n\n'], { type: 'text/markdown;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'Untitled.md';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }

    function openExternal(url) {
        window.open(url, '_blank', 'noopener,noreferrer');
    }

    function runAction(action) {
        closeMenu();
        switch (action) {
            case 'new-folder':
                if (folderModal) folderModal.show();
                else window.location.href = folderUrl;
                break;
            case 'file-upload':
                window.location.href = uploadUrl;
                break;
            case 'folder-upload':
                window.location.href = uploadUrl + '?mode=folder';
                break;
            case 'new-project':
                flash('New project is not available in P-ARCHIVE yet.');
                break;
            case 'docs': openExternal('https://docs.google.com/document/u/0/create'); break;
            case 'sheets': openExternal('https://docs.google.com/spreadsheets/u/0/create'); break;
            case 'slides': openExternal('https://docs.google.com/presentation/u/0/create'); break;
            case 'vids': openExternal('https://docs.google.com/vids/u/0/create'); break;
            case 'forms': openExternal('https://docs.google.com/forms/u/0/create'); break;
            case 'drawings': openExternal('https://docs.google.com/drawings/u/0/create'); break;
            case 'maps': openExternal('https://mymaps.google.com/'); break;
            case 'sites': openExternal('https://sites.google.com/new'); break;
            case 'markdown': downloadMarkdown(); break;
            case 'apps-script': openExternal('https://script.google.com/home/start'); break;
        }
    }

    dash.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        openMenu(e.clientX, e.clientY);
    });

    document.addEventListener('contextmenu', function (e) {
        if (!menu.contains(e.target) && !dash.contains(e.target)) closeMenu();
    });

    document.addEventListener('click', function (e) {
        if (menu.classList.contains('open') && !menu.contains(e.target)) closeMenu();
    });

    menu.addEventListener('click', function (e) {
        var item = e.target.closest('[data-wd-action]');
        if (!item) return;
        e.preventDefault();
        e.stopPropagation();
        var action = item.getAttribute('data-wd-action');
        if (action === 'more') {
            if (sub.classList.contains('open')) closeSub(true); else openSub();
            return;
        }
        runAction(action);
    });

    moreItem.addEventListener('mouseenter', openSub);
    moreItem.addEventListener('focus', openSub);
    sub.addEventListener('mouseenter', openSub);
    menu.addEventListener('mouseleave', function () { closeSub(false); });

    window.addEventListener('scroll', closeMenu, true);
    window.addEventListener('resize', closeMenu);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenu();
            return;
        }
        if (chordArmed) {
            var chord = { KeyF: 'new-folder', KeyU: 'file-upload', KeyI: 'folder-upload' };
            var action = chord[e.code];
            chordArmed = false;
            clearTimeout(chordTimer);
            if (action && !isTyping(e.target)) {
                e.preventDefault();
                runAction(action);
                return;
            }
        }
        if (e.altKey && e.code === 'KeyC' && !isTyping(e.target)) {
            chordArmed = true;
            clearTimeout(chordTimer);
            chordTimer = setTimeout(function () { chordArmed = false; }, 3000);
        }
    });

    folderModalEl && folderModalEl.addEventListener('show.bs.modal', function () {
        var input = document.getElementById('wdNewFolderName');
        if (input) setTimeout(function () { input.focus(); }, 120);
    });
})();
</script>

<form id="wdDelForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
@endsection
