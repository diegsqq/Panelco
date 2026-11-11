@extends('layouts.app')

@section('content')
<style>
    body:has(#libPage) main { padding: .75rem 1rem .5rem !important; }
    /* Full-fit: Document Library page never scrolls — only the table card scrolls inside. */
    body:has(#libPage) main { flex: 1 1 auto; min-height: 0; overflow: hidden; display: flex; flex-direction: column; }

    /* ── Page shell ── */
    .lib-page { display: flex; flex-direction: column; gap: .75rem; flex: 1 1 auto; min-height: 0; }
    .lib-page > *:not(#libContentRow) { flex: 0 0 auto; }

    /* ── Header ── */
    .lib-hero-title { font-size: 1.55rem; font-weight: 800; color: #00743F; line-height: 1.2; }
    .lib-hero-sub   { color: #5a9e7a; font-size: .85rem; margin-top: .2rem; }
    [data-theme="dark"] .lib-hero-title { color: #7fe0ac; }
    [data-theme="dark"] .lib-hero-sub   { color: #8fa59a; }

    /* ── Stat bar ── */
    .lib-stat-bar { display: flex; align-items: center; gap: 0; background: #fff; border: 1px solid #ebebeb; border-radius: 1rem; overflow: hidden; flex-wrap: wrap; }
    [data-theme="dark"] .lib-stat-bar { background: #16211c; border-color: #26362e; }
    .lib-stat-item { display: flex; align-items: center; gap: .7rem; padding: 1rem 1.5rem; border-right: 1px solid #f0f0f0; flex: 1 1 0; min-width: 0; }
    .lib-stat-item:last-child { border-right: 0; }
    [data-theme="dark"] .lib-stat-item { border-color: #26362e; }
    .lib-stat-ico  { width: 38px; height: 38px; border-radius: .6rem; background: #f4f4f6; display: flex; align-items: center; justify-content: center; color: #7b7fa8; font-size: 1.05rem; flex: 0 0 38px; }
    .lib-stat-val  { font-weight: 800; font-size: 1.3rem; color: #1a1a2e; line-height: 1.1; }
    .lib-stat-lbl  { font-size: .72rem; color: #9a9ab0; margin-top: .1rem; }
    .lib-stat-right { flex: 1.4 1 0; border-right: 0; justify-content: flex-end; gap: .5rem; }
    .lib-stat-right .lib-stat-ico { background: #f4f4f6; color: #7b7fa8; }
    [data-theme="dark"] .lib-stat-ico  { background: #1d2b24; color: #8fa59a; }
    [data-theme="dark"] .lib-stat-val  { color: #fff; }
    [data-theme="dark"] .lib-stat-lbl  { color: #8fa59a; }

    /* ── Department cards ── */
    .lib-dept-section { background: #fff; border: 1px solid #ebebeb; border-radius: 1rem; padding: 1rem 1.25rem 1.1rem; }
    [data-theme="dark"] .lib-dept-section { background: #16211c; border-color: #26362e; }
    .lib-dept-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: .75rem; }
    @media (max-width: 991.98px) { .lib-dept-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 575.98px)  { .lib-dept-grid { grid-template-columns: 1fr 1fr; } }
    .lib-dept-card { background: #fff; border: 1px solid #ebebeb; border-radius: .85rem; padding: .4rem .6rem; display: flex; flex-direction: row; align-items: center; gap: .6rem; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; text-decoration: none; }
    .lib-dept-card:hover { border-color: #c5c5db; box-shadow: 0 4px 14px rgba(0,0,0,.07); }
    .lib-dept-card.active { border-color: #00A868; background: #f0f9f3; }
    [data-theme="dark"] .lib-dept-card { background: #1a2620; border-color: #26362e; }
    [data-theme="dark"] .lib-dept-card:hover { border-color: #3a5a46; box-shadow: none; }
    [data-theme="dark"] .lib-dept-card.active { background: #12291d; border-color: #00A868; }
    .lib-dept-ico  { width: 26px; height: 26px; border-radius: .45rem; background: #f4f4f6; display: flex; align-items: center; justify-content: center; color: #7b7fa8; font-size: .8rem; }
    [data-theme="dark"] .lib-dept-ico { background: #1d2b24; color: #8fa59a; }
    .lib-dept-card.active .lib-dept-ico { background: #e6f3ea; color: #00A868; }
    .lib-dept-name { font-weight: 700; font-size: .78rem; color: #1a1a2e; margin-top: .05rem; }
    .lib-dept-meta { font-size: .64rem; color: #9a9ab0; }
    [data-theme="dark"] .lib-dept-name { color: #e5eee8; }
    [data-theme="dark"] .lib-dept-meta { color: #8fa59a; }

    /* ── Main content area (table + optional detail panel) ── */
    .lib-content-row { display: flex; gap: .85rem; flex: 1 1 auto; min-height: 0; }
    .lib-table-col   { flex: 1 1 auto; min-width: 0; min-height: 0; display: flex; flex-direction: column; background: #fff; border: 1px solid #d5e8db; border-radius: 1rem; overflow: hidden; }
    [data-theme="dark"] .lib-table-col { background: #16211c; border-color: #26362e; }

    /* ── Tabs + toolbar row ── */
    .lib-tabs-row { display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: nowrap; padding: .5rem 1rem; border-bottom: 1px solid #e6f3ea; flex: 0 0 auto; }
    [data-theme="dark"] .lib-tabs-row { border-color: #26362e; }
    .lib-tabs { display: flex; gap: .25rem; padding: 0; border: 0; flex-wrap: wrap; flex: 0 0 auto; }
    .lib-toolbar { display: flex; flex-wrap: nowrap; align-items: center; gap: .6rem; padding: 0; border: 0; background: transparent; flex: 0 0 auto; }
    .lib-tab  { padding: .38rem .9rem; border-radius: .55rem; font-size: .82rem; font-weight: 600; color: #5a9e7a; background: transparent; border: none; cursor: pointer; transition: all .15s ease; display: flex; align-items: center; gap: .35rem; }
    .lib-tab.active { background: #e6f3ea; color: #00743F; }
    .lib-tab:hover:not(.active) { background: #f0f9f3; color: #00A868; }
    [data-theme="dark"] .lib-tab       { color: #8fa59a; }
    [data-theme="dark"] .lib-tab.active { background: #1d2b24; color: #7fe0ac; }
    .lib-tab-count { font-size: .7rem; background: #d5e8db; color: #00743F; border-radius: 2rem; padding: .05rem .45rem; font-weight: 700; }
    [data-theme="dark"] .lib-tab-count { background: #1d2b24; color: #7fe0ac; }

    /* ── Toolbar (search/filters) ── */
    /* (toolbar styling moved above) */
    [data-theme="dark"] .lib-toolbar { background: #131d18; border-color: #26362e; }
    /* Dark mode — search bar + recycle bin (inline white styles overridden) */
    [data-theme="dark"] #libTrashBtn { background: #1d2b24 !important; border-color: #2c3d34 !important; color: #c3d0c8 !important; }
    [data-theme="dark"] #libTrashBtn:hover { background: #243324 !important; color: #fff !important; }
    [data-theme="dark"] #libFilterForm .input-group { border-color: #2c3d34 !important; }
    [data-theme="dark"] #libFilterForm .input-group-text { background: #1d2b24 !important; color: #c3d0c8 !important; }
    [data-theme="dark"] #libFilterForm .form-control { background: #1d2b24 !important; color: #e5eee8 !important; }
    [data-theme="dark"] #libFilterForm .form-control::placeholder { color: #8fa59a !important; }
    [data-theme="dark"] #libFilterForm .form-control:focus { background: #1d2b24 !important; color: #fff !important; }

    /* ── Table ── */
    .lib-table-wrap { overflow: auto; flex: 1 1 auto; min-height: 0; }
    .lib-table { width: 100%; margin: 0; font-size: .8rem; }
    .lib-table > :not(caption) > * > * { padding: .55rem .75rem; border-color: #e6f3ea; vertical-align: middle; }
    .lib-table thead th { background: #fbfefc; color: #5a9e7a; font-weight: 600; font-size: .7rem; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; border-bottom: 1px solid #d5e8db !important; }
    .lib-table tbody tr { border-color: #e6f3ea; transition: background-color .1s ease; cursor: pointer; }
    .lib-table tbody tr:hover { background: #f0f9f3; }
    .lib-table tbody tr.selected { background: #e6f3ea; }
    [data-theme="dark"] .lib-table { --bs-table-bg: transparent; --bs-table-color: #d7e5dc; --bs-table-border-color: #26362e; }
    [data-theme="dark"] .lib-table thead th { background: #131d18; color: #8fa59a; border-color: #2c3d34 !important; }
    [data-theme="dark"] .lib-table tbody tr:hover { background: #1d2b24; }
    [data-theme="dark"] .lib-table tbody tr.selected { background: #12291d; }
    .lib-doc-name { max-width: 220px; color: #00743F; font-weight: 600; }
    [data-theme="dark"] .lib-doc-name { color: #7fe0ac; }
    .lib-file-icon { font-size: 1.05rem; }
    .lib-avatar { width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 22px; border-radius: 50%; background: #e6f3ea; color: #00743F; font-size: .58rem; font-weight: 700; }
    [data-theme="dark"] .lib-avatar { background: #1d2b24; color: #7fe0ac; }
    .lib-cat-badge { display: inline-block; padding: .18rem .55rem; border-radius: 2rem; font-size: .68rem; font-weight: 600; background: #e6f3ea; color: #00743F; border: 1px solid #d5e8db; white-space: nowrap; }
    [data-theme="dark"] .lib-cat-badge { background: #1d2b24; color: #7fe0ac; border-color: #2c3d34; }
    .lib-ver { font-size: .68rem; color: #5a9e7a; background: #e6f3ea; border-radius: .3rem; padding: .08rem .35rem; }
    [data-theme="dark"] .lib-ver { background: #1d2b24; color: #8fa59a; }

    /* ── Pagination ── */
    .lib-pagination { padding: .6rem 1rem; border-top: 1px solid #e6f3ea; display: flex; align-items: center; justify-content: space-between; gap: .5rem; flex-wrap: wrap; flex: 0 0 auto; }
    [data-theme="dark"] .lib-pagination { border-color: #26362e; }
    .lib-pagination-info { font-size: .78rem; color: #5a9e7a; }
    .lib-pg-btn { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: .45rem; border: 1px solid #d5e8db; background: #fff; color: #00743F; font-size: .75rem; text-decoration: none; transition: all .15s ease; }
    .lib-pg-btn:hover { background: #e6f3ea; border-color: #00A868; }
    .lib-pg-btn.disabled { opacity: .4; pointer-events: none; color: #aaa; }
    [data-theme="dark"] .lib-pg-btn { background: #16211c; border-color: #2c3d34; color: #7fe0ac; }

    /* ── Detail side panel ── */
    .lib-detail { width: 280px; flex: 0 0 280px; background: #fff; border: 1px solid #d5e8db; border-radius: 1rem; overflow: hidden; display: none; flex-direction: column; }
    .lib-detail.open { display: flex; }
    [data-theme="dark"] .lib-detail { background: #16211c; border-color: #26362e; }
    .lib-detail-head { display: flex; align-items: center; justify-content: space-between; padding: .5rem .75rem; border-bottom: 1px solid #e6f3ea; flex: 0 0 auto; }
    [data-theme="dark"] .lib-detail-head { border-color: #26362e; }
    .lib-detail-title { font-weight: 700; font-size: .82rem; color: #00743F; }
    [data-theme="dark"] .lib-detail-title { color: #7fe0ac; }
    .lib-detail-close { background: none; border: none; color: #5a9e7a; font-size: .9rem; cursor: pointer; padding: 0; line-height: 1; }
    .lib-detail-body  { flex: 1 1 auto; overflow: hidden; padding: .65rem .75rem; display: flex; flex-direction: column; gap: .4rem; min-height: 0; }
    .lib-detail-body .gap-2 { gap: .35rem !important; }
    .lib-detail-ico   { width: 34px; height: 34px; border-radius: .55rem; background: #e6f3ea; display: flex; align-items: center; justify-content: center; color: #00A868; font-size: 1rem; flex: 0 0 34px; }
    [data-theme="dark"] .lib-detail-ico { background: #1d2b24; }
    .lib-detail-doc-title { font-weight: 700; font-size: .85rem; color: #00743F; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; max-height: 3.75em; }
    [data-theme="dark"] .lib-detail-doc-title { color: #7fe0ac; }
    .lib-detail-doc-sub   { font-size: .7rem; color: #5a9e7a; margin-top: .1rem; }
    .lib-detail-open-btn  { display: block; text-align: center; background: #00A868; color: #fff; border-radius: .55rem; padding: .4rem .75rem; font-weight: 700; font-size: .76rem; text-decoration: none; transition: background-color .15s ease; }
    .lib-detail-open-btn:hover { background: #00743F; color: #fff; }
    .lib-detail-meta { display: flex; flex-direction: column; gap: .22rem; }
    .lib-detail-row  { display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem; font-size: .72rem; line-height: 1.3; }
    .lib-detail-key  { color: #5a9e7a; flex: 0 0 auto; }
    .lib-detail-val  { color: #00743F; font-weight: 600; text-align: right; }
    [data-theme="dark"] .lib-detail-val { color: #7fe0ac; }
    .lib-detail-divider { border: 0; border-top: 1px solid #e6f3ea; margin: .15rem 0; }
    [data-theme="dark"] .lib-detail-divider { border-color: #26362e; }
    .lib-detail-section-title { font-weight: 700; font-size: .72rem; color: #5a9e7a; text-transform: uppercase; letter-spacing: .4px; margin-bottom: .3rem !important; }
    .lib-detail-chip { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; color: #5a9e7a; background: #e6f3ea; border-radius: 2rem; padding: .18rem .55rem; }
    [data-theme="dark"] .lib-detail-chip { background: #1d2b24; color: #8fa59a; }

    @media (max-width: 767.98px) {
        body:has(#libPage) main { padding: .5rem !important; display: block; overflow: visible; height: auto; flex: none; }
        .lib-page, .lib-content-row { flex: none; }
        .lib-table-col, .lib-table-wrap { flex: none; min-height: 0; }
        .lib-table-wrap { overflow-x: auto; overflow-y: visible; }
        .lib-detail { width: 100%; flex: 0 0 auto; }
        .lib-content-row { flex-direction: column; }
    }
</style>

<div class="lib-page" id="libPage">

    {{-- ── Header ── --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <div class="lib-hero-title">Your documents, connected.</div>
            <div class="lib-hero-sub">Find, edit and share the latest knowledge across your organization.</div>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->isManager())
            <a href="{{ route('documents.trash') }}" id="libTrashBtn" class="btn btn-sm" style="border:1px solid #d5e8db;background:#fff;color:#5a9e7a;border-radius:.65rem;font-weight:600">
                <i class="bi bi-trash3 me-1"></i>Recycle bin
            </a>
            @endif
            <a href="{{ route('documents.upload') }}" class="btn btn-sm" style="background:#00A868;color:#fff;border:none;border-radius:.65rem;font-weight:700">
                <i class="bi bi-plus-lg me-1"></i>New document
            </a>
        </div>
    </div>

    {{-- ── Stat bar ── --}}
    @php
        $updatedThisWeek = $documents->getCollection()->filter(fn($d) => $d->updated_at?->gte(now()->startOfWeek()))->count();
        $deptCount = $departments->count();
    @endphp
    <div class="lib-stat-bar">
        <div class="lib-stat-item">
            <div class="lib-stat-ico"><i class="bi bi-files"></i></div>
            <div>
                <div class="lib-stat-val">{{ number_format($documents->total()) }}</div>
                <div class="lib-stat-lbl">Total documents</div>
            </div>
        </div>
        <div class="lib-stat-item">
            <div class="lib-stat-ico"><i class="bi bi-pencil-square"></i></div>
            <div>
                <div class="lib-stat-val">{{ $updatedThisWeek }}</div>
                <div class="lib-stat-lbl">Updated this week</div>
            </div>
        </div>
        <div class="lib-stat-item">
            <div class="lib-stat-ico"><i class="bi bi-diagram-3"></i></div>
            <div>
                <div class="lib-stat-val">{{ $deptCount }}</div>
                <div class="lib-stat-lbl">Departments</div>
            </div>
        </div>
        <div class="lib-stat-item lib-stat-right">
            <div class="lib-stat-ico"><i class="bi bi-chat-text"></i></div>
            <div>
                <div style="font-weight:700;font-size:.82rem;color:#1a1a2e">Everything is up to date</div>
                <div class="lib-stat-lbl">Edited versions are saved automatically</div>
            </div>
        </div>
    </div>

    {{-- ── Department cards ── --}}
    @if($departments->isNotEmpty())
    <div class="lib-dept-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold" style="color:#1a1a2e;font-size:.8rem">Browse by department</span>
            <a href="{{ route('documents.library') }}" class="small fw-semibold text-decoration-none" style="color:#7b7fa8">View all departments →</a>
        </div>
        <div class="lib-dept-grid">
            @foreach($departments->take(4) as $dept)
            @php
                $deptDocCount = (int) ($deptDocCounts->get($dept->id, 0));
            @endphp
            <a href="{{ route('documents.library', ['department_id' => $dept->id]) }}"
               class="lib-dept-card {{ request('department_id') == $dept->id ? 'active' : '' }}">
                <div class="lib-dept-ico"><i class="bi bi-building"></i></div>
                <div style="min-width:0;flex:1 1 auto">
                    <div class="lib-dept-name">{{ $dept->name }}</div>
                    <div class="lib-dept-meta">{{ $deptDocCount }} document{{ $deptDocCount !== 1 ? 's' : '' }}</div>
                </div>
                <button type="button" class="p-0 border-0 bg-transparent" style="color:#c8c8d8;font-size:.7rem;line-height:1;flex:0 0 auto" onclick="event.preventDefault()" aria-label="More options"><i class="bi bi-three-dots"></i></button>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Category cards ── --}}
    @if($categories->isNotEmpty())
    <div class="lib-dept-section mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold" style="color:#1a1a2e;font-size:.8rem">Folders</span>
            <a href="{{ route('documents.library') }}" class="small fw-semibold text-decoration-none" style="color:#7b7fa8">View all categories →</a>
        </div>
        <div class="lib-dept-grid">
            @foreach($categories->take(4) as $cat)
            @php
                $catDocCount = (int) ($catDocCounts->get($cat->id, 0));
            @endphp
            <a href="{{ route('documents.library', ['category_id' => $cat->id]) }}"
               class="lib-dept-card {{ request('category_id') == $cat->id ? 'active' : '' }}">
                <div class="lib-dept-ico"><i class="bi bi-folder-fill"></i></div>
                <div style="min-width:0;flex:1 1 auto">
                    <div class="lib-dept-name">{{ $cat->name }}</div>
                    <div class="lib-dept-meta">{{ $catDocCount }} document{{ $catDocCount !== 1 ? 's' : '' }}</div>
                </div>
                <button type="button" class="p-0 border-0 bg-transparent" style="color:#c8c8d8;font-size:.7rem;line-height:1;flex:0 0 auto" onclick="event.preventDefault()" aria-label="More options"><i class="bi bi-three-dots"></i></button>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Table + detail panel ── --}}
    <div class="lib-content-row" id="libContentRow">

        {{-- Left: table card ── --}}
        <div class="lib-table-col" id="libTableCol">

            {{-- Tabs + toolbar (single row) --}}
            <div class="lib-tabs-row">
                <div class="lib-tabs">
                    <button class="lib-tab {{ !request('favorite') && !request('recent') ? 'active' : '' }}"
                            onclick="location.href='{{ route('documents.library', array_merge(request()->except(['favorite','recent','page']), [])) }}'">
                        <i class="bi bi-files"></i> All documents
                        <span class="lib-tab-count">{{ $documents->total() }}</span>
                    </button>
                    <button class="lib-tab {{ request('recent') ? 'active' : '' }}"
                            onclick="location.href='{{ route('documents.library', array_merge(request()->except(['favorite','recent','page']), ['recent'=>1])) }}'">
                        <i class="bi bi-clock-history"></i> Recently edited
                    </button>
                    <button class="lib-tab {{ request('favorite') ? 'active' : '' }}"
                            onclick="location.href='{{ route('documents.library', array_merge(request()->except(['favorite','recent','page']), ['favorite'=>1])) }}'">
                        <i class="bi bi-star"></i> Starred
                    </button>
                </div>

                <div class="lib-toolbar">
                    <form method="GET" action="{{ route('documents.library') }}" class="d-flex flex-nowrap align-items-center gap-2 w-100" role="search" id="libFilterForm">
                        @foreach(request()->except(['q','page']) as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endforeach
                        <div class="input-group input-group-sm" style="width:min(16rem,100%);border-radius:.55rem;overflow:hidden;border:1px solid #d5e8db;">
                            <span class="input-group-text" style="background:#fff;border:0"><i class="bi bi-search" style="color:#00A868"></i></span>
                            <input type="search" name="q" value="{{ $search }}" class="form-control border-0" placeholder="Search by name, topic or keyword..." aria-label="Search documents" style="background:#fff">
                        </div>
                        <select name="department_id" class="form-select form-select-sm" aria-label="Filter by department" style="width:auto;border-color:#d5e8db;color:#00743F;font-weight:600" onchange="this.form.submit()">
                            <option value="">Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string)request('department_id') === (string)$department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <select name="category_id" class="form-select form-select-sm" aria-label="Filter by category" style="width:auto;border-color:#d5e8db;color:#00743F;font-weight:600" onchange="this.form.submit()">
                            <option value="">Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="d-flex gap-1 ms-auto">
                            @if(collect($validated)->filter(fn($v) => $v !== null && $v !== '' && $v !== 'newest')->isNotEmpty())
                                <a href="{{ route('documents.library') }}" class="btn btn-sm" style="border:1px solid #d5e8db;color:#5a9e7a;border-radius:.55rem">Clear</a>
                            @endif
                            <a href="{{ route('documents.export', request()->query()) }}" class="btn btn-sm" style="border:1px solid #d5e8db;color:#5a9e7a;border-radius:.55rem" title="Export CSV">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table --}}
            <div class="lib-table-wrap">
                <table class="table lib-table" id="libTable">
                    <caption class="visually-hidden">Document library results</caption>
                    <thead>
                        <tr>
                            <th scope="col" style="width:30px"><input type="checkbox" class="form-check-input" id="libCheckAll" aria-label="Select all"></th>
                            <th scope="col">Document name <i class="bi bi-arrow-up" style="font-size:.6rem"></i></th>
                            <th scope="col">Last modified</th>
                            <th scope="col">Access</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                        @php
                            $mime = $doc->mime_type ?? '';
                            $fileType = str_contains($mime, 'pdf')   ? ['PDF',   'bi-file-earmark-pdf-fill',  '#dc3545']
                                      : (str_contains($mime, 'word') ? ['Word',  'bi-file-earmark-word-fill', '#0d6efd']
                                      : (str_starts_with($mime, 'image/') ? ['Image','bi-file-earmark-image-fill','#00A868']
                                      : (str_contains($mime, 'text')  ? ['Text',  'bi-file-earmark-text-fill','#6c757d']
                                      : ['File', 'bi-file-earmark-fill', '#6c757d'])));
                            $uploaderInitials = $doc->uploader?->initials() ?? '?';
                            $uploaderColor = '#00A868';
                        @endphp
                        <tr class="lib-doc-row" data-id="{{ $doc->id }}"
                            data-title="{{ $doc->title ?? $doc->original_filename }}"
                            data-sub="{{ $doc->category?->name ?? $doc->suggestedCategory?->name ?? 'Uncategorized' }} / {{ $doc->department?->name ?? $doc->suggestedDepartment?->name ?? '—' }}"
                            data-modified="{{ $doc->updated_at?->format('M d, Y') ?? '—' }}"
                            data-status="{{ ucfirst($doc->processing_status ?? 'pending') }}"
                            data-version="v{{ $doc->version_number ?? '1' }}"
                            data-dept="{{ $doc->department?->name ?? $doc->suggestedDepartment?->name ?? '—' }}"
                            data-cat="{{ $doc->category?->name ?? $doc->suggestedCategory?->name ?? 'Uncategorized' }}"
                            data-uploader="{{ $doc->uploader?->name ?? 'Unknown' }}"
                            data-filetype="{{ $fileType[0] }}"
                            data-size="{{ $doc->file_size >= 1048576 ? number_format($doc->file_size/1048576,1).' MB' : number_format($doc->file_size/1024,0).' KB' }}"
                            data-url="{{ route('documents.show', $doc) }}"
                            data-icon="{{ $fileType[1] }}"
                            data-iconcolor="{{ $fileType[2] }}"
                            data-initials="{{ $uploaderInitials }}">
                            <td><input type="checkbox" class="form-check-input lib-row-check" aria-label="Select {{ $doc->title ?? $doc->original_filename }}"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="lib-file-icon {{ $fileType[1] }}" style="color:{{ $fileType[2] }};flex:0 0 auto" aria-hidden="true"></i>
                                    <div style="min-width:0">
                                        <a href="{{ route('documents.show', $doc) }}" class="lib-doc-name text-decoration-none d-block text-truncate" title="{{ $doc->title ?? $doc->original_filename }}">
                                            {{ $doc->title ?? $doc->original_filename }}
                                        </a>
                                        <div style="font-size:.68rem;color:#5a9e7a">
                                            {{ $doc->category?->name ?? $doc->suggestedCategory?->name ?? 'Uncategorized' }} / {{ $doc->department?->name ?? $doc->suggestedDepartment?->name ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div style="font-size:.75rem;color:#5a9e7a">{{ $doc->updated_at?->format('M d, Y') ?? '—' }}</div>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="lib-avatar" title="{{ $doc->uploader?->name ?? 'Unknown' }}" style="background:{{ $uploaderColor }}20;color:{{ $uploaderColor }}">{{ $uploaderInitials }}</span>
                                    <span class="lib-ver">v{{ $doc->version_number ?? '1' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1" style="font-size:.75rem;color:#5a9e7a">
                                    <i class="bi bi-people-fill" style="color:#00A868"></i>
                                    All employees
                                </div>
                            </td>
                            <td class="text-end">
                                @canany(['update','delete'], $doc)
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm p-1" style="background:none;border:none;color:#5a9e7a" title="Actions for {{ $doc->title ?? $doc->original_filename }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" style="border-radius:.75rem;border-color:#d5e8db">
                                        <li><a class="dropdown-item" href="{{ route('documents.show', $doc) }}"><i class="bi bi-eye me-2" style="color:#00A868"></i>View</a></li>
                                        @can('update', $doc)
                                        <li><a class="dropdown-item" href="{{ route('documents.edit', $doc) }}"><i class="bi bi-pencil me-2" style="color:#00A868"></i>Edit</a></li>
                                        @endcan
                                        @can('delete', $doc)
                                        <li><hr class="dropdown-divider"></li>
                                        <li><button type="button" class="dropdown-item text-danger" data-delete-url="{{ route('documents.destroy', $doc) }}" onclick="window.__libDeleteUrl=this.getAttribute('data-delete-url');bootstrap.Modal.getOrCreateInstance(document.getElementById('libDeleteModal')).show()"><i class="bi bi-trash me-2"></i>Delete</button></li>
                                        @endcan
                                    </ul>
                                </div>
                                @endcanany
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-folder2-open fs-2" style="color:#b7e0c8"></i>
                                <p class="fw-semibold mt-2 mb-2" style="color:#5a9e7a">No documents found.</p>
                                <a href="{{ route('documents.upload') }}" class="btn btn-sm" style="background:#00A868;color:#fff;border-radius:.65rem">Upload a document</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="lib-pagination">
                <span class="lib-pagination-info">
                    Showing {{ $documents->firstItem() }}&ndash;{{ $documents->lastItem() }} of {{ number_format($documents->total()) }} documents
                </span>
                <div class="d-flex align-items-center gap-1">
                    @if($documents->onFirstPage())
                        <span class="lib-pg-btn disabled"><i class="bi bi-chevron-left"></i></span>
                    @else
                        <a class="lib-pg-btn" href="{{ $documents->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
                    @endif
                    @foreach(range(max(1,$documents->currentPage()-1), min($documents->lastPage(),$documents->currentPage()+1)) as $pg)
                        @if($pg === $documents->currentPage())
                            <span class="lib-pg-btn" style="background:#00A868;color:#fff;border-color:#00A868;font-weight:700">{{ $pg }}</span>
                        @else
                            <a class="lib-pg-btn" href="{{ $documents->url($pg) }}">{{ $pg }}</a>
                        @endif
                    @endforeach
                    @if($documents->currentPage() < $documents->lastPage() - 1)
                        <span class="lib-pg-btn disabled" style="border:0;background:none;color:#5a9e7a">…</span>
                        <a class="lib-pg-btn" href="{{ $documents->url($documents->lastPage()) }}">{{ $documents->lastPage() }}</a>
                    @endif
                    @if($documents->hasMorePages())
                        <a class="lib-pg-btn" href="{{ $documents->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
                    @else
                        <span class="lib-pg-btn disabled"><i class="bi bi-chevron-right"></i></span>
                    @endif
                </div>
            </div>

        </div>{{-- /lib-table-col --}}

        {{-- Right: detail side panel ── --}}
        <div class="lib-detail" id="libDetail">
            <div class="lib-detail-head">
                <span class="lib-detail-title">Document details</span>
                <button class="lib-detail-close" id="libDetailClose" aria-label="Close detail panel"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="lib-detail-body" id="libDetailBody">
                <div class="d-flex align-items-center gap-2">
                    <div class="lib-detail-ico" id="libDetailIco"><i class="bi bi-file-earmark-text" id="libDetailIcoIcon"></i></div>
                </div>
                <div>
                    <div class="lib-detail-doc-title" id="libDetailDocTitle">—</div>
                    <div class="lib-detail-doc-sub" id="libDetailDocSub">—</div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="lib-cat-badge" id="libDetailStatus">—</span>
                </div>
                <a href="#" class="lib-detail-open-btn" id="libDetailOpenBtn">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Open in editor
                </a>
                <hr class="lib-detail-divider">
                <div class="lib-detail-meta">
                    <div class="lib-detail-row"><span class="lib-detail-key">Department</span><span class="lib-detail-val" id="libDetailDept">—</span></div>
                    <div class="lib-detail-row"><span class="lib-detail-key">Category</span><span class="lib-detail-val" id="libDetailCat">—</span></div>
                    <div class="lib-detail-row"><span class="lib-detail-key">Owner</span><span class="lib-detail-val" id="libDetailOwner">—</span></div>
                    <div class="lib-detail-row"><span class="lib-detail-key">Updated</span><span class="lib-detail-val" id="libDetailUpdated">—</span></div>
                    <div class="lib-detail-row"><span class="lib-detail-key">Size</span><span class="lib-detail-val" id="libDetailSize">—</span></div>
                    <div class="lib-detail-row"><span class="lib-detail-key">Version</span><span class="lib-detail-val" id="libDetailVersion">—</span></div>
                </div>
                <hr class="lib-detail-divider">
                <div>
                    <div class="lib-detail-section-title mb-2">Access</div>
                    <div class="lib-detail-chip"><i class="bi bi-people-fill" style="color:#00A868"></i> All employees can view</div>
                </div>
            </div>
        </div>

    </div>{{-- /lib-content-row --}}



</div>{{-- /lib-page --}}

{{-- Delete modal --}}
<div class="modal fade" id="libDeleteModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;width:calc(100% - 2rem)">
        <div class="modal-content p3-modal">
            <div class="text-center pt-4 px-4">
                <div class="p3-meow"><img src="{{ asset('images/meow.gif') }}" alt="meow" style="width:90px;height:90px;object-fit:contain"></div>
                <h5 class="fw-bold mt-3 mb-0">Delete this document?</h5>
                <p class="text-muted mb-0 mt-2" style="font-size:.85rem">This permanently removes the file and its indexed passages. This action cannot be undone.</p>
            </div>
            <div class="p3-modal-actions">
                <button type="button" class="btn btn-outline-secondary px-4 flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-danger px-4 flex-fill" id="libConfirmDeleteBtn"><i class="bi bi-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>
<form id="libDeleteForm" method="POST" style="display:none">@csrf @method('DELETE')</form>

<script>
(function () {
    /* ── Delete modal ── */
    var form = document.getElementById('libDeleteForm');
    var btn  = document.getElementById('libConfirmDeleteBtn');
    var pendingUrl = null;
    var modal = document.getElementById('libDeleteModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (e) {
            var t = e.relatedTarget;
            if (t && t.getAttribute('data-delete-url')) pendingUrl = t.getAttribute('data-delete-url');
            if (!pendingUrl && window.__libDeleteUrl) pendingUrl = window.__libDeleteUrl;
        });
    }
    document.addEventListener('click', function (e) {
        var t = e.target && e.target.closest ? e.target.closest('[data-delete-url]') : null;
        if (t) { window.__libDeleteUrl = t.getAttribute('data-delete-url'); pendingUrl = window.__libDeleteUrl; }
    }, true);
    if (btn) {
        btn.addEventListener('click', function () {
            var url = pendingUrl || window.__libDeleteUrl;
            if (url && form) { form.action = url; form.submit(); }
        });
    }

    /* ── Detail panel ── */
    var detail = document.getElementById('libDetail');
    var closeBtn = document.getElementById('libDetailClose');
    var tableCol = document.getElementById('libTableCol');
    var currentRow = null;

    function openDetail(row) {
        if (currentRow) currentRow.classList.remove('selected');
        currentRow = row;
        row.classList.add('selected');

        var d = row.dataset;
        document.getElementById('libDetailDocTitle').textContent  = d.title    || '—';
        document.getElementById('libDetailDocSub').textContent    = d.sub      || '—';
        document.getElementById('libDetailStatus').textContent    = d.status   || '—';
        document.getElementById('libDetailDept').textContent      = d.dept     || '—';
        document.getElementById('libDetailCat').textContent       = d.cat      || '—';
        document.getElementById('libDetailOwner').textContent     = d.uploader || '—';
        document.getElementById('libDetailUpdated').textContent   = d.modified || '—';
        document.getElementById('libDetailSize').textContent      = d.size     || '—';
        document.getElementById('libDetailVersion').textContent   = d.version  || '—';
        document.getElementById('libDetailOpenBtn').href          = d.url      || '#';

        var icoEl = document.getElementById('libDetailIcoIcon');
        if (icoEl) { icoEl.className = 'bi ' + (d.icon || 'bi-file-earmark-text'); icoEl.style.color = d.iconcolor || '#00A868'; }

        detail.classList.add('open');
    }

    function closeDetail() {
        detail.classList.remove('open');
        if (currentRow) { currentRow.classList.remove('selected'); currentRow = null; }
    }

    if (closeBtn) closeBtn.addEventListener('click', closeDetail);

    document.querySelectorAll('.lib-doc-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a') || e.target.closest('.dropdown') || e.target.closest('.form-check-input')) return;
            if (detail.classList.contains('open') && currentRow === row) { closeDetail(); return; }
            openDetail(row);
        });
    });

    /* ── Select all checkbox ── */
    var checkAll = document.getElementById('libCheckAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.lib-row-check').forEach(function (c) { c.checked = checkAll.checked; });
        });
    }
})();
</script>
@endsection



