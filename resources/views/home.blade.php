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
    @keyframes wd-swipe { 0%, 100% { transform: translateX(0); opacity: .8; } 50% { transform: translateX(6px); opacity: 1; } }
    [data-theme="dark"] .wd-swipe-hint { background: #1d2b24; border-color: #2f6b4f; color: #7fe0ac; box-shadow: none; }

    /* ── Folder cards (2-row horizontal swipe strip) ── */
    .wd-folders { display: grid; grid-template-rows: repeat(2, auto); grid-auto-flow: column; grid-auto-columns: minmax(180px, 1fr); gap: .8rem; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; padding: .2rem .2rem .6rem; margin: -.2rem -.2rem 0; scrollbar-width: none; }
    .wd-folders::-webkit-scrollbar, .wd-recents::-webkit-scrollbar { display: none; }
    .wd-folder { scroll-snap-align: start; display: flex; align-items: center; gap: .7rem; background: #fff; border: 1px solid #ebebeb; border-radius: .85rem; padding: .8rem .9rem; text-decoration: none; transition: all .15s ease; }
    .wd-folder:hover { border-color: #b7e0c8; background: #fbfefc; box-shadow: 0 4px 14px rgba(10,31,92,.06); }
    .wd-folder-ico { width: 38px; height: 38px; flex: 0 0 38px; border-radius: .6rem; background: #e6f3ea; color: #00A868; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .wd-folder-name { font-weight: 700; font-size: .85rem; color: #1a1a2e; white-space: normal; overflow-wrap: anywhere; line-height: 1.35; }
    .wd-folder-meta { font-size: .7rem; color: #9a9ab0; }
    .wd-folder-go { margin-left: auto; color: #c8c8d8; font-size: .85rem; flex: 0 0 auto; }

    /* ── Recent file cards (2-row horizontal swipe strip) ── */
    .wd-recents { display: grid; grid-template-rows: repeat(1, auto); grid-auto-flow: column; grid-auto-columns: minmax(160px, 260px); gap: .8rem; justify-content: start; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; padding: .2rem .2rem .6rem; margin: -.2rem -.2rem 0; scrollbar-width: none; }
    .wd-recent { scroll-snap-align: start; display: flex; align-items: center; gap: .75rem; background: #fff; border: 1px solid #ebebeb; border-radius: .85rem; padding: .85rem .95rem; text-decoration: none; transition: all .15s ease; min-width: 0; }
    .wd-recent:hover { border-color: #b7e0c8; background: #fbfefc; box-shadow: 0 4px 14px rgba(10,31,92,.06); }
    .wd-recent-ico { width: 40px; height: 40px; flex: 0 0 40px; border-radius: .6rem; background: #f0f9f3; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; }
    .wd-recent-name { font-weight: 700; font-size: .84rem; color: #1a1a2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; line-height: 1.35; }
    .wd-recent-meta { font-size: .7rem; color: #9a9ab0; margin-top: .1rem; white-space: normal; overflow-wrap: anywhere; }

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
    [data-theme="dark"] .wd-folder-ico { background: #1d2b24; color: #7fe0ac; }
    [data-theme="dark"] .wd-folder-name, [data-theme="dark"] .wd-recent-name { color: #e5eee8; }
    [data-theme="dark"] .wd-folder-meta, [data-theme="dark"] .wd-recent-meta { color: #8fa59a; }
    [data-theme="dark"] .wd-folder-go { color: #4a5e54; }
    [data-theme="dark"] .wd-recent-ico, [data-theme="dark"] .wd-name-ico { background: #1d2b24; }
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
</style>

<div class="p3-dash" id="p3Dashboard">

    {{-- ── Welcome ── --}}
    <div class="wd-welcome">
        <div class="wd-welcome-text">
            @if(auth()->user()->created_at->diffInDays(now()) <= 3)
                <h1 class="wd-welcome-title">Welcome to SmartDocs, {{ strtok(auth()->user()->name, ' ') }}!</h1>
                <p class="wd-welcome-sub">Get started by uploading your first document.</p>
            @else
                <h1 class="wd-welcome-title">Welcome back, {{ strtok(auth()->user()->name, ' ') }}!</h1>
                <p class="wd-welcome-sub">Glad to have you back! Let's get started.</p>
            @endif
        </div>
        <div class="wd-welcome-right">
            <div class="wd-stats" aria-label="Document statistics">
                <div class="wd-stat"><div class="wd-stat-val" style="color:#00A868">{{ number_format($stats['total']) }}</div><div class="wd-stat-lbl">Total</div></div>
                <div class="wd-stat"><div class="wd-stat-val" style="color:#00743F">{{ number_format($stats['completed']) }}</div><div class="wd-stat-lbl">Done</div></div>
                <div class="wd-stat"><div class="wd-stat-val" style="color:#fd7e14">{{ number_format($stats['pending']) }}</div><div class="wd-stat-lbl">Pending</div></div>
                <div class="wd-stat"><div class="wd-stat-val" style="color:#dc3545">{{ number_format($stats['failed']) }}</div><div class="wd-stat-lbl">Failed</div></div>
            </div>
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
        @endif
        <span class="wd-swipe-hint" id="foldersSwipeHint" aria-hidden="true"><i class="bi bi-chevron-double-right"></i></span>
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
                                <th scope="col">File name <i class="bi bi-arrow-up" style="font-size:.55rem"></i></th>
                                <th scope="col" style="width:180px">Categories</th>
                                <th scope="col" style="width:230px">Shared by</th>
                                <th scope="col" style="width:160px">Last modified</th>
                                <th scope="col" style="width:125px">Size</th>
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
<script>
(function () {
    var strip = document.querySelector('.wd-folders');
    var hint = document.getElementById('foldersSwipeHint');
    if (!strip || !hint) return;
    function update() {
        var overflow = strip.scrollWidth - strip.clientWidth > 1;
        var atEnd = strip.scrollLeft + strip.clientWidth >= strip.scrollWidth - 4;
        hint.classList.toggle('on', overflow && !atEnd);
    }
    strip.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
})();
</script>
<form id="wdDelForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
@endsection
