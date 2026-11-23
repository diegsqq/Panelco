@extends('layouts.app')
@section('content')
@php
    $pagePaper = $document->page_paper ?: \App\Support\PageLayout::DEFAULTS['paper'];
    $pageOrientation = $document->page_orientation ?: \App\Support\PageLayout::DEFAULTS['orientation'];
    $pageSize = \App\Support\PageLayout::dimensions($pagePaper, $pageOrientation);
    $pageWidthPx = (int) round($pageSize['width_mm'] * 96 / 25.4);
    $pageHeightPx = (int) round($pageSize['height_mm'] * 96 / 25.4);
@endphp
<style>
    body:has(#docViewer) main { padding: 0 !important; }
    @media (min-width: 768px) {
        body:has(#docViewer) #p3Sidebar { display: none !important; }
        body:has(#docViewer) > .container-fluid > .row > div.p-0.d-flex.flex-column { flex: 0 0 100%; max-width: 100%; width: 100%; }
    }
    /* ── Shell ── */
    #docViewer { display: flex; flex-direction: column; height: 100vh; overflow: hidden; background: #f5f5f5; }
    [data-theme="dark"] #docViewer { background: #0e1512; }

    /* ── Breadcrumb bar ── */
    .dv-breadcrumb { flex: 0 0 auto; display: flex; align-items: center; flex-wrap: nowrap; gap: .4rem; padding: .5rem 1.25rem; font-size: .78rem; color: #888; border-bottom: 1px solid #e8e8e8; background: #fff; overflow-x: auto; scrollbar-width: none; }
    .dv-breadcrumb::-webkit-scrollbar { display: none; }
    .dv-breadcrumb a, .dv-breadcrumb .sep, .dv-breadcrumb .dv-meta-small, .dv-breadcrumb .dv-titlebar-actions { flex: 0 0 auto; }
    .dv-breadcrumb a { color: #555; text-decoration: none; }
    .dv-breadcrumb a:hover { color: #00A868; }
    .dv-breadcrumb .sep { color: #ccc; }
    .dv-breadcrumb .bc-title { flex: 0 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dv-breadcrumb .dv-btn { font-size: .72rem; padding: .25rem .55rem; }
    [data-theme="dark"] .dv-breadcrumb { background: #16211c; border-color: #26362e; color: #8fa59a; }
    [data-theme="dark"] .dv-breadcrumb a { color: #8fa59a; }

    /* ── Breadcrumb actions ── */
    .dv-meta-small { font-size: .75rem; color: #888; white-space: nowrap; }
    .dv-titlebar-actions { display: flex; align-items: center; justify-content: flex-end; gap: .4rem; }
    #docViewer > .dv-breadcrumb > .dv-meta-small { margin-left: auto; }
    #docViewer > .dv-breadcrumb > div { justify-content: flex-end; }
    .dv-btn { font-size: .78rem; font-weight: 600; border-radius: .55rem; padding: .35rem .75rem; border: 1px solid #ddd; background: #fff; color: #333; cursor: pointer; display: inline-flex; align-items: center; gap: .3rem; transition: all .12s ease; text-decoration: none; white-space: nowrap; }
    .dv-btn:hover { background: #f5f5f5; color: #111; border-color: #bbb; }
    .dv-btn-primary { background: #00A868; border-color: #00A868; color: #fff; }
    .dv-btn-primary:hover { background: #00743F; border-color: #00743F; color: #fff; }
    .dv-btn-green { background: #00A868; border-color: #00A868; color: #fff; }
    .dv-btn-green:hover { background: #00743F; border-color: #00743F; color: #fff; }
    [data-theme="dark"] .dv-btn { background: #1d2b24; border-color: #2c3d34; color: #c3d0c8; }
    [data-theme="dark"] .dv-btn:hover { background: #243324; color: #fff; }

    /* ── Body: outline + page + info ── */
    .dv-body { flex: 1 1 auto; display: flex; min-height: 0; overflow: hidden; }

    /* ── Left outline panel ── */
    .dv-outline { width: 220px; flex: 0 0 220px; background: #fff; border-right: 1px solid #e8e8e8; overflow-y: auto; padding: 1rem .75rem; }
    .dv-outline-title { font-size: .72rem; font-weight: 700; color: #aaa; text-transform: uppercase; letter-spacing: .5px; margin-bottom: .6rem; }
    .dv-outline-item { display: block; font-size: .8rem; color: #555; padding: .3rem .5rem; border-radius: .4rem; text-decoration: none; transition: all .1s ease; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dv-outline-item:hover { background: #f0f0f0; color: #111; }
    .dv-outline-item.active { background: #e6f3ea; color: #00743F; font-weight: 600; }
    .dv-outline-item.h2 { padding-left: 1rem; font-size: .77rem; }
    .dv-outline-item.h3 { padding-left: 1.6rem; font-size: .74rem; color: #888; }
    [data-theme="dark"] .dv-outline { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .dv-outline-item { color: #8fa59a; }
    [data-theme="dark"] .dv-outline-item:hover { background: #1d2b24; color: #fff; }
    [data-theme="dark"] .dv-outline-item.active { background: #12291d; color: #7fe0ac; }

    /* ── Center document page ── */
    .dv-page-wrap { flex: 1 1 auto; min-width: 0; overflow-x: hidden; overflow-y: auto; padding: 2.25rem 1.5rem 3rem; display: flex; flex-direction: column; align-items: center; gap: 1.5rem; }
    .dv-page { position: relative; overflow: hidden; width: 100%; max-width: var(--dv-page-w, 794px); box-sizing: border-box; background: linear-gradient(180deg, #fff 0%, #fcfdfc 100%); border: 1px solid #e4ebe6; border-top: 4px solid #00A868; border-radius: 20px; box-shadow: 0 18px 45px rgba(16,40,27,.09), 0 2px 8px rgba(16,40,27,.05); padding: 3rem 3rem 2.75rem; min-height: var(--dv-page-h, 1123px); aspect-ratio: var(--dv-page-w, 794) / var(--dv-page-h, 1123); color: #2c3a33; }
    .dv-page::before, .dv-page::after { content: ""; position: absolute; top: 7.4rem; width: 24px; height: 24px; border-radius: 50%; background: #f5f5f5; z-index: 2; }
    .dv-page::before { left: -12px; }
    .dv-page::after { right: -12px; }
    .dv-page-perf { position: absolute; top: 7.5rem; left: 1.25rem; right: 1.25rem; border-top: 1px dashed #cfe3d5; z-index: 1; }
    [data-theme="dark"] .dv-page { background: linear-gradient(180deg, #16211c 0%, #131d18 100%); border-color: #26362e; border-top-color: #00A868; box-shadow: none; color: #c3d0c8; }
    [data-theme="dark"] .dv-page::before, [data-theme="dark"] .dv-page::after { background: #0e1512; }
    [data-theme="dark"] .dv-page-perf { border-color: #2c5c3d; }

    .dv-page-org { display: inline-block; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; color: #00743F; background: #e6f3ea; border-radius: 2rem; padding: .2rem .65rem; margin-bottom: .9rem; }
    .dv-page-category { font-size: .78rem; color: #8a978f; margin-bottom: .5rem; }
    .dv-page h1 { font-size: 2rem; font-weight: 800; letter-spacing: -.02em; color: #12251a; line-height: 1.18; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #edf1ee; }
    .dv-page-meta { font-size: .76rem; color: #7d8a82; margin-bottom: 2rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .dv-page-meta span { display: inline-flex; align-items: center; gap: .35rem; background: #f4f7f5; border: 1px solid #e6ece8; border-radius: 2rem; padding: .25rem .65rem; }
    .dv-page-meta i { color: #00A868; font-size: .8rem; }
    .dv-page-intro { font-size: .95rem; color: #4a5a52; line-height: 1.8; margin-bottom: 1.75rem; }
    /* Actual file text — reads like a printed document page */
    .dv-page-text { font-family: Georgia, "Times New Roman", Times, serif; font-size: 1rem; line-height: 1.75; color: #202b24; text-align: justify; hyphens: auto; margin-bottom: 2rem; padding: 1.5rem 1.75rem; background: #fff; border: 1px solid #eef2ef; border-radius: 10px; }
    .dv-page-text mark { background: #fff2b8; color: inherit; padding: 0 .1em; border-radius: 2px; }
    [data-theme="dark"] .dv-page-text { background: #131d18; border-color: #26362e; color: #d6e2d9; }
    [data-theme="dark"] .dv-page-text mark { background: #6b5b16; color: #fff7cf; }
    .dv-page h2 { font-size: 1.2rem; font-weight: 700; color: #12251a; margin-top: 2rem; margin-bottom: .75rem; padding-left: .8rem; border-left: 3px solid #00A868; }
    .dv-page h3 { font-size: 1rem; font-weight: 700; color: #2c3a33; margin-top: 1.5rem; margin-bottom: .5rem; }
    .dv-page p { font-size: .92rem; color: #38463e; line-height: 1.8; margin-bottom: 1rem; }
    .dv-page ul, .dv-page ol { font-size: .92rem; color: #38463e; line-height: 1.8; padding-left: 1.6rem; margin-bottom: 1rem; }
    .dv-page li { margin-bottom: .35rem; }
    .dv-page table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e2e9e4; border-radius: .6rem; overflow: hidden; margin-bottom: 1.25rem; font-size: .86rem; }
    .dv-page table th { background: #f2f8f4; font-weight: 700; color: #1f6b3a; padding: .65rem .85rem; border-bottom: 1px solid #dfe8e2; text-align: left; }
    .dv-page table td { padding: .65rem .85rem; border-bottom: 1px solid #edf1ee; }
    .dv-page table tr:last-child td { border-bottom: 0; }
    .dv-page table tbody tr:hover td { background: #fafdfb; }
    .dv-page-callout { background: #f0f9f3; border: 1px solid #d9ecdf; border-left: 3px solid #00A868; border-radius: .6rem; padding: .85rem 1rem; margin: 1.25rem 0; font-size: .87rem; color: #2d5a3d; }
    .dv-page-footer { margin-top: 2.5rem; padding-top: 1.1rem; border-top: 1px solid #edf1ee; font-size: .72rem; color: #9aa79f; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .dv-page-pagination { font-size: .78rem; color: #9aa79f; display: flex; align-items: center; gap: .75rem; justify-content: center; }
    .dv-page-pagination button { background: #fff; border: 1px solid #dfe7e1; border-radius: .55rem; padding: .35rem .7rem; font-size: .82rem; cursor: pointer; color: #5f6e65; display: inline-flex; align-items: center; transition: all .12s ease; }
    .dv-page-pagination button:hover { background: #e6f3ea; border-color: #00A868; color: #00743F; }
    [data-theme="dark"] .dv-page h1, [data-theme="dark"] .dv-page h2, [data-theme="dark"] .dv-page h3 { color: #eaf2ec; }
    [data-theme="dark"] .dv-page h1 { border-color: #26362e; }
    [data-theme="dark"] .dv-page p, [data-theme="dark"] .dv-page ul, [data-theme="dark"] .dv-page ol { color: #b9c8be; }
    [data-theme="dark"] .dv-page-org { background: #12291d; color: #7fe0ac; }
    [data-theme="dark"] .dv-page-category { color: #7f9488; }
    [data-theme="dark"] .dv-page-meta span { background: #1a2620; border-color: #26362e; color: #8fa59a; }
    [data-theme="dark"] .dv-page-meta i { color: #7fe0ac; }
    [data-theme="dark"] .dv-page table { border-color: #26362e; }
    [data-theme="dark"] .dv-page table th { background: #1d2b24; border-color: #26362e; color: #c3d0c8; }
    [data-theme="dark"] .dv-page table td { border-color: #26362e; }
    [data-theme="dark"] .dv-page table tbody tr:hover td { background: #1a2820; }
    [data-theme="dark"] .dv-page-intro, [data-theme="dark"] .dv-page-meta { color: #8fa59a; }
    [data-theme="dark"] .dv-page-callout { background: #12291d; color: #7fe0ac; border-color: #2c5c3d; border-left-color: #00A868; }
    [data-theme="dark"] .dv-page-footer { border-color: #26362e; color: #4a5e54; }
    [data-theme="dark"] .dv-page-pagination button { background: #1d2b24; border-color: #2c3d34; color: #c3d0c8; }
    [data-theme="dark"] .dv-page-pagination button:hover { background: #12291d; border-color: #00A868; color: #7fe0ac; }

    /* ── Right info panel ── */
    .dv-info { width: 300px; flex: 0 0 300px; min-height: 0; background: #fff; border-left: 1px solid #e8e8e8; overflow-y: auto; overscroll-behavior: contain; scrollbar-gutter: stable; padding: 1rem .9rem 2rem; display: flex; flex-direction: column; gap: .85rem; }
    .dv-info::-webkit-scrollbar { width: 10px; }
    .dv-info::-webkit-scrollbar-thumb { background: #b7c6bc; border-radius: 6px; }
    .dv-info::-webkit-scrollbar-track { background: transparent; }
    [data-theme="dark"] .dv-info::-webkit-scrollbar-thumb { background: #3a5044; }
    [data-theme="dark"] .dv-info { background: #16211c; border-color: #26362e; }
    .dv-info-section { border: 1px solid #ebebeb; border-radius: .75rem; overflow: hidden; flex: 0 0 auto; }
    [data-theme="dark"] .dv-info-section { border-color: #26362e; }
    .dv-info-section-head { display: flex; align-items: center; justify-content: space-between; padding: .7rem .85rem; background: #fafafa; border-bottom: 1px solid #ebebeb; }
    [data-theme="dark"] .dv-info-section-head { background: #131d18; border-color: #26362e; }
    .dv-info-section-title { font-size: .85rem; font-weight: 700; color: #111; }
    [data-theme="dark"] .dv-info-section-title { color: #fff; }
    .dv-info-section-body { padding: .75rem .85rem; display: flex; flex-direction: column; gap: .6rem; }
    .dv-info-row { display: flex; justify-content: space-between; align-items: flex-start; gap: .4rem; font-size: .84rem; }
    .dv-info-key { color: #888; flex: 0 0 auto; }
    .dv-info-val { color: #111; font-weight: 500; text-align: right; word-break: break-word; }
    [data-theme="dark"] .dv-info-key { color: #8fa59a; }
    [data-theme="dark"] .dv-info-val { color: #e5eee8; }
    .dv-version-row { display: flex; align-items: center; gap: .5rem; padding: .4rem 0; border-bottom: 1px solid #f5f5f5; font-size: .82rem; }
    .dv-version-row:last-child { border-bottom: 0; }
    [data-theme="dark"] .dv-version-row { border-color: #26362e; }
    .dv-version-dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 8px; }
    .dv-version-current .dv-version-dot { background: #00A868; }
    .dv-version-row:not(.dv-version-current) .dv-version-dot { background: #ddd; }
    .dv-avatar-row { display: flex; align-items: center; gap: .4rem; font-size: .82rem; }
    .dv-avatar { width: 22px; height: 22px; border-radius: 50%; background: #00A868; color: #fff; font-size: .6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex: 0 0 22px; }
    /* AI ask panel inside info */
    .dv-ask-box { padding: .65rem .75rem; }
    .dv-ask-title { font-size: .82rem; font-weight: 700; color: #00743F; display: flex; align-items: center; gap: .3rem; margin-bottom: .4rem; }
    [data-theme="dark"] .dv-ask-title { color: #7fe0ac; }
    .dv-ask-input-row { display: flex; gap: .3rem; }
    .dv-ask-input { flex: 1 1 auto; border: 1px solid #d5e8db; border-radius: .5rem; padding: .35rem .55rem; font-size: .82rem; background: #fff; color: #111; outline: none; }
    .dv-ask-input:focus { border-color: #00A868; }
    [data-theme="dark"] .dv-ask-input { background: #1d2b24; border-color: #2c3d34; color: #e5eee8; }
    .dv-ask-send { width: 26px; height: 26px; border-radius: 50%; background: #00A868; border: none; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; flex: 0 0 26px; font-size: .75rem; }
    .dv-ask-send:hover { background: #00743F; }
    .dv-ask-chip { font-size: .7rem; border: 1px solid #d5e8db; background: #f0f9f3; color: #00743F; border-radius: 2rem; padding: .15rem .5rem; cursor: pointer; transition: all .1s; }
    .dv-ask-chip:hover { background: #e6f3ea; }

    @media (max-width: 1199.98px) {
        .dv-outline { display: none; }
    }
    @media (max-width: 991.98px) {
        .dv-page { padding: 2rem 1.5rem; }
    }
    @media (min-width: 768px) and (max-width: 991.98px) {
        .dv-info { width: 260px; flex: 0 0 260px; }
    }
    @media (max-width: 767.98px) {
        .dv-info { display: none; }
    }
    @media screen and (min-width: 768px) {
        #docViewer .dv-page { max-width: min(var(--dv-page-w, 794px), calc(100% - 3rem)); padding: 3.5rem clamp(1.5rem, 3vw, 3.25rem) 3rem; }
        #docViewer .dv-page h1 { font-size: clamp(1.75rem, 1.25rem + 1.2vw, 2.5rem); }
        #docViewer .dv-page h2 { font-size: clamp(1.1rem, .95rem + .45vw, 1.4rem); }
        #docViewer .dv-page h3 { font-size: clamp(.95rem, .9rem + .25vw, 1.15rem); }
        #docViewer .dv-page p,
        #docViewer .dv-page ul,
        #docViewer .dv-page ol,
        #docViewer .dv-page .doc-render { font-size: clamp(.9rem, .84rem + .35vw, 1.08rem); line-height: 1.8; }
        #docViewer .dv-page .doc-render { font-size: clamp(.9rem, .84rem + .35vw, 1.08rem) !important; }
    }
    @media print {
        .dv-breadcrumb, .dv-outline, .dv-info { display: none !important; }
        .dv-body { overflow: visible; }
        .dv-page-wrap { overflow: visible; padding: 0; }
        .dv-page { background: #fff !important; box-shadow: none; border: none; border-radius: 0; padding: 0; overflow: visible; }
        .dv-page::before, .dv-page::after, .dv-page-perf { display: none !important; }
    }
</style>

<div id="docViewer">

    {{-- ── Breadcrumb ── --}}
    <div class="dv-breadcrumb">
        <a href="{{ route('documents.library') }}">Documents</a>
        @if($document->department)
        <span class="sep">/</span>
        <a href="{{ route('documents.library', ['department_id' => $document->department->id]) }}">{{ $document->department->name }}</a>
        @endif
        @if($document->category)
        <span class="sep">/</span>
        <a href="{{ route('documents.library', ['category_id' => $document->category->id]) }}">{{ $document->category->name }}</a>
        @endif
        <span class="sep">/</span>
        <span class="bc-title">{{ \Illuminate\Support\Str::limit($document->display_title, 40) }}</span>
        @if($document->uploader)
        <span class="dv-meta-small">· Owned by {{ $document->uploader->name }}</span>
        @endif
        <div class="dv-titlebar-actions">
            @if($document->versions->isNotEmpty())
            <button class="dv-btn" data-bs-toggle="modal" data-bs-target="#versionHistoryModal"><i class="bi bi-clock-history"></i> Version history</button>
            @endif
            @can('update', $document)
            <button class="dv-btn" data-bs-toggle="modal" data-bs-target="#shareModal"><i class="bi bi-share"></i> Share</button>
            @endcan
            @can('download', $document)
            <a href="{{ route('documents.print', $document) }}" class="dv-btn dv-btn-primary"><i class="bi bi-printer"></i> Print</a>
            @endcan
            @can('update', $document)
            <a href="{{ route('documents.edit-content', $document) }}" class="dv-btn dv-btn-green"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="dv-body">

        {{-- Left: Document outline ── --}}
        <aside class="dv-outline" id="dvOutline">
            <div class="dv-outline-title">Document outline</div>
            <div id="dvOutlineItems">
                {{-- populated by JS from headings --}}
                @if($document->content)
                @php
                    preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is',
                        app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content),
                        $hMatches);
                @endphp
                @foreach($hMatches[1] as $i => $level)
                @php $label = trim(strip_tags($hMatches[2][$i])); @endphp
                @if($label)
                <a href="#heading-{{ $i }}" class="dv-outline-item {{ $level == 2 ? 'h2' : ($level >= 3 ? 'h3' : '') }}">{{ $label }}</a>
                @endif
                @endforeach
                @else
                @if(trim((string) $document->extracted_text) !== '')
                <a href="#doc-content" class="dv-outline-item active">Document text</a>
                @endif
                <a href="#doc-summary" class="dv-outline-item">Document Summary</a>
                <a href="#doc-details" class="dv-outline-item h2">Important Details</a>
                <a href="#doc-actions" class="dv-outline-item h2">Corrective Actions</a>
                @if(count($tables)) <a href="#doc-tables" class="dv-outline-item h2">Tables</a> @endif
                @endif
            </div>
        </aside>

        {{-- Center: document page ── --}}
        <div class="dv-page-wrap" id="dvPageWrap">

            {{-- Flash messages --}}
            @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show w-100" style="max-width:880px;border-radius:.75rem" role="alert">
                {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show w-100" style="max-width:880px;border-radius:.75rem" role="alert">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="dv-page" style="--dv-page-w: {{ $pageWidthPx }}px; --dv-page-h: {{ $pageHeightPx }}px;">
                <div class="dv-page-perf" aria-hidden="true"></div>

                {{-- Page header ── --}}
                <div class="dv-page-org">{{ strtoupper($document->department?->name ?? config('app.name', 'PANELCO III')) }}</div>
                <div class="dv-page-category">
                    {{ $document->category?->name ?? $document->suggestedCategory?->name ?? 'General' }}
                    @if($document->department) / {{ $document->department->name }} @endif
                    @if($document->category) / Topic 1 @endif
                </div>
                <h1>{{ $document->display_title }}</h1>
                <div class="dv-page-meta">
                    @if($document->document_date)<span><i class="bi bi-calendar3"></i> Effective {{ $document->document_date->format('F d, Y') }}</span>@endif
                    @if($document->reference_number)<span><i class="bi bi-hash"></i> {{ $document->reference_number }}</span>@endif
                    <span><i class="bi bi-clock-history"></i> Version {{ $document->version_number ?? '1' }}</span>
                </div>

                @if($document->content || $document->content_header || $document->content_footer)
                {{-- Rich content ── --}}
                @if($document->content_header)
                <div class="doc-render pb-3 mb-3" style="border-bottom:1px dashed #e0e0e0">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_header) !!}</div>
                @endif
                <div class="doc-render" id="dvDocContent">
                    @php
                        // Inject IDs onto headings for outline links
                        $rawContent = app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content);
                        $counter = 0;
                        $rawContent = preg_replace_callback('/<h([1-6])([^>]*)>/i', function($m) use (&$counter) {
                            return '<h' . $m[1] . $m[2] . ' id="heading-' . ($counter++) . '">';
                        }, $rawContent);
                    @endphp
                    {!! $rawContent !!}
                </div>
                @if($document->content_footer)
                <div class="doc-render pt-3 mt-3" style="border-top:1px dashed #e0e0e0">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_footer) !!}</div>
                @endif

                @else
                {{-- Extracted content fallback ── --}}
                @if(trim((string) $highlightedHtml) !== '')
                <div class="dv-page-text" id="doc-content">{!! $highlightedHtml !!}</div>
                @endif
                @if($document->summary)
                <p class="dv-page-intro" id="doc-summary">{{ $document->summary }}</p>
                @endif

                @if(count($details))
                <h2 id="doc-details">Important Details</h2>
                <ul>@foreach($details as $d)<li>{{ $d }}</li>@endforeach</ul>
                @endif

                @if(count($correctiveActions))
                <h2 id="doc-actions">Corrective Actions</h2>
                <ul>@foreach($correctiveActions as $a)<li>{{ $a }}</li>@endforeach</ul>
                @endif

                @if(count($tables))
                <h2 id="doc-tables">Tables</h2>
                @foreach($tables as $table)
                @if($table['caption'])<p><strong>{{ $table['caption'] }}</strong></p>@endif
                <table>
                    <thead><tr>@foreach($table['headers'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                    <tbody>@foreach($table['rows'] as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
                </table>
                @endforeach
                @endif

                @if(!$document->summary && !count($details) && !count($correctiveActions) && trim((string) $highlightedHtml) === '')
                <p style="color:#aaa;text-align:center;padding:3rem 0">
                    @if($document->processing_status !== 'completed')
                    <i class="bi bi-hourglass-split d-block fs-2 mb-2"></i>Document is still being processed.
                    @else
                    <i class="bi bi-file-earmark d-block fs-2 mb-2"></i>No extracted content available.
                    @endif
                </p>
                @endif
                @endif

                {{-- Page footer ── --}}
                <div class="dv-page-footer">
                    <span>{{ $document->uploader?->organization ?? config('app.name') }} · Internal use only</span>
                    <span>Page 1 / 1</span>
                </div>
            </div>

            {{-- Pagination ── --}}
            <div class="dv-page-pagination">
                <button><i class="bi bi-chevron-left"></i></button>
                <span>Page 1 of 1 · {{ number_format(str_word_count(strip_tags($document->content ?? $document->extracted_text ?? ''))) }} words</span>
                <button><i class="bi bi-chevron-right"></i></button>
            </div>

        </div>{{-- /dv-page-wrap --}}

        {{-- Right: Info panel ── --}}
        <aside class="dv-info">

            {{-- Document info ── --}}
            <div class="dv-info-section">
                <div class="dv-info-section-head">
                    <span class="dv-info-section-title">Document info</span>
                    <i class="bi bi-info-circle" style="font-size:.8rem;color:#aaa"></i>
                </div>
                <div class="dv-info-section-body">
                    <div class="dv-info-row">
                        <span class="dv-info-key">Department</span>
                        <span class="dv-info-val">{{ $document->department?->name ?? '—' }}</span>
                    </div>
                    <div class="dv-info-row">
                        <span class="dv-info-key">Category</span>
                        <span class="dv-info-val">{{ $document->category?->name ?? $document->suggestedCategory?->name ?? '—' }}</span>
                    </div>
                    @if($document->document_date)
                    <div class="dv-info-row">
                        <span class="dv-info-key">Date</span>
                        <span class="dv-info-val">{{ $document->document_date->format('M d, Y') }}</span>
                    </div>
                    @endif
                    <div class="dv-info-row">
                        <span class="dv-info-key">Size</span>
                        <span class="dv-info-val">{{ $document->file_size >= 1048576 ? number_format($document->file_size/1048576,1).' MB' : number_format($document->file_size/1024,0).' KB' }}</span>
                    </div>
                </div>
            </div>

            {{-- Employee access ── --}}
            <div class="dv-info-section">
                <div class="dv-info-section-head">
                    <span class="dv-info-section-title">Employee access</span>
                </div>
                <div class="dv-info-section-body">
                    <p style="font-size:.75rem;color:#888;margin:0">All employees can view this document.</p>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        @foreach(($document->authorizedUsers ?? collect())->take(4) as $u)
                        <div class="dv-avatar" title="{{ $u->name }}">{{ $u->initials() }}</div>
                        @endforeach
                        @if(($document->authorizedUsers ?? collect())->count() > 4)
                        <span style="font-size:.72rem;color:#888;align-self:center">+{{ ($document->authorizedUsers ?? collect())->count() - 4 }} others</span>
                        @endif
                    </div>
                    @can('update', $document)
                    <a href="#" data-bs-toggle="modal" data-bs-target="#shareModal" style="font-size:.75rem;color:#00A868;text-decoration:none">Manage access →</a>
                    @endcan
                </div>
            </div>

            {{-- Ask Lightning AI ── --}}
            <div class="dv-info-section">
                <div class="dv-ask-box">
                    <div class="dv-ask-title">
                        <i class="bi bi-lightning-charge-fill"></i> A little help with words
                    </div>
                    <p style="font-size:.73rem;color:#888;margin-bottom:.5rem">Summarize a section or improve clarity without leaving your document.</p>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <button class="dv-ask-chip ask-chip" data-question="Summarize this document">Summarize</button>
                        <button class="dv-ask-chip ask-chip" data-question="What are the important details?">Details</button>
                        <button class="dv-ask-chip ask-chip" data-question="What corrective actions were recorded?">Actions</button>
                    </div>
                    <div class="dv-ask-input-row">
                        <input type="text" class="dv-ask-input" id="askInput" placeholder="Ask Lightning AI…" maxlength="2000">
                        <button class="dv-ask-send" id="askSendBtn"><i class="bi bi-send-fill"></i></button>
                    </div>
                    <div id="askResult" class="d-none mt-2" style="font-size:.77rem"></div>
                </div>
            </div>

            {{-- Actions ── --}}
            <div class="dv-info-section">
                <div class="dv-info-section-head">
                    <span class="dv-info-section-title">Actions</span>
                </div>
                <div class="dv-info-section-body" style="gap:.35rem">
                    @can('download', $document)
                    <button class="dv-btn w-100" data-bs-toggle="modal" data-bs-target="#viewerModal"><i class="bi bi-eye"></i> View file</button>
                    <a href="{{ route('documents.download', $document) }}" class="dv-btn w-100"><i class="bi bi-download"></i> Download</a>
                    @endcan
                    @can('update', $document)
                    <a href="{{ route('documents.edit', $document) }}" class="dv-btn w-100"><i class="bi bi-pencil"></i> Edit details</a>
                    @endcan
                    @can('delete', $document)
                    <button class="dv-btn w-100" style="color:#dc3545;border-color:#f5c6cb" data-bs-toggle="modal" data-bs-target="#deleteDocModal"><i class="bi bi-trash"></i> Delete</button>
                    @endcan
                </div>
            </div>

        </aside>{{-- /dv-info --}}

    </div>{{-- /dv-body --}}
</div>{{-- /docViewer --}}

{{-- ── Modals ── --}}

{{-- Version history modal --}}
<div class="modal fade" id="versionHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
        <div class="modal-content p3-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2" style="color:#00A868"></i>Version history</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="dv-version-row dv-version-current mb-2">
                    <div class="dv-version-dot"></div>
                    <div class="flex-fill">
                        <div class="fw-semibold small">v{{ $document->version_number ?? '1' }} · Current</div>
                        <div class="text-muted" style="font-size:.75rem">{{ $document->updated_at->format('M d, Y H:i') }} · {{ $document->currentVersionUploader?->name ?? '—' }}</div>
                    </div>
                    <span class="badge text-bg-success">Current</span>
                </div>
                @foreach($document->versions as $v)
                <div class="dv-version-row mb-2">
                    <div class="dv-version-dot"></div>
                    <div class="flex-fill">
                        <div class="fw-semibold small">v{{ $v->version_number }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ $v->created_at->format('M d, Y H:i') }} · {{ $v->uploader?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.72rem">{{ $v->original_filename }} · {{ number_format($v->file_size/1024,0) }} KB</div>
                    </div>
                    <div class="d-flex gap-1">
                        @can('download', $document)
                        <a href="{{ route('documents.versions.download', [$document, $v]) }}" class="btn btn-sm btn-outline-success" title="Download"><i class="bi bi-download"></i></a>
                        @endcan
                        @can('update', $document)
                        <form method="POST" action="{{ route('documents.versions.restore', [$document, $v]) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                        </form>
                        @endcan
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Share modal --}}
@can('update', $document)
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content p3-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-share me-2" style="color:#00A868"></i>Share document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('documents.share', $document) }}">
                    @csrf
                    <label class="form-label small fw-semibold">Select employees (hold Ctrl/Cmd for multiple)</label>
                    <select name="user_ids[]" class="form-select form-select-sm mb-3" multiple size="7">
                        @foreach(\App\Models\User::orderBy('name')->get() as $u)
                        <option value="{{ $u->id }}" @selected($document->authorizedUsers->contains($u->id))>{{ $u->name }} ({{ $u->role }})</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-success w-100">Update sharing</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan

{{-- View file modal --}}
<div class="modal fade" id="viewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content p3-modal d-flex flex-column" style="height:88vh">
            <div class="d-flex justify-content-between align-items-center px-3 py-2" style="border-bottom:1px solid var(--p3-card-border)">
                <span class="fw-bold text-truncate" style="max-width:70%"><i class="bi bi-eye-fill me-1" style="color:#00A868"></i>{{ $document->original_filename }}</span>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success" id="viewerPrintBtn"><i class="bi bi-printer me-1"></i>Print</button>
                    <a href="{{ route('documents.pdf', $document) }}" class="badge text-decoration-none" style="background:#e6f3ea;color:#1F7A2B">Convert to PDF</a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div id="viewerBody" class="flex-grow-1" style="overflow:auto;background:#f1f5f3;min-height:0;text-align:center">
                <span class="text-muted small"><i class="bi bi-hourglass"></i> Loading…</span>
            </div>
        </div>
    </div>
</div>

{{-- Delete modal --}}
<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;width:calc(100% - 2rem)">
        <div class="modal-content p3-modal">
            <div class="text-center pt-4 px-4">
                <div class="p3-meow"><img src="{{ asset('images/meow.gif') }}" alt="meow" style="width:90px;height:90px;object-fit:contain"></div>
                <h5 class="fw-bold mt-3 mb-0">Delete this document?</h5>
                <p class="text-muted mb-0 mt-2" style="font-size:.85rem">This permanently removes the file and its indexed passages. This action cannot be undone.</p>
            </div>
            <div class="p3-modal-actions">
                <button type="button" class="btn btn-outline-secondary px-4 flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-danger px-4 flex-fill" id="confirmDeleteDocBtn"><i class="bi bi-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>
<form id="deleteDocForm" method="POST" action="{{ route('documents.destroy', $document) }}" style="display:none">@csrf @method('DELETE')</form>

<script>
(function () {
    /* ── Delete ── */
    document.getElementById('confirmDeleteDocBtn')?.addEventListener('click', function() {
        document.getElementById('deleteDocForm').submit();
    });

    /* ── Viewer modal ── */
    @can('download', $document)
    var viewerOpened = false;
    var viewerModal = document.getElementById('viewerModal');
    if (viewerModal) {
        viewerModal.addEventListener('show.bs.modal', function () {
            if (viewerOpened) return;
            viewerOpened = true;
            var body = document.getElementById('viewerBody');
            fetch(@json(route('documents.view', $document)), { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
                .then(function(r) { return r.blob(); })
                .then(function(blob) {
                    var url = URL.createObjectURL(blob);
                    var mime = @json($document->mime_type ?? '');
                    if (mime.startsWith('image/')) {
                        body.innerHTML = '<img src="' + url + '" style="max-width:100%;max-height:80vh;margin:1rem auto;display:block;border-radius:.5rem">';
                    } else {
                        body.innerHTML = '<object data="' + url + '" type="' + mime + '" style="width:100%;height:100%;min-height:600px"><p class="p-3">Cannot preview. <a href="' + url + '" download>Download instead</a>.</p></object>';
                    }
                    document.getElementById('viewerPrintBtn').onclick = function() { window.open(url); };
                    viewerModal.addEventListener('hide.bs.modal', function() { URL.revokeObjectURL(url); }, {once:true});
                });
        });
    }
    @endcan

    /* ── Lightning AI ask ── */
    var askUrl = @json(route('documents.ask', $document));
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var result = document.getElementById('askResult');
    var input = document.getElementById('askInput');
    var sendBtn = document.getElementById('askSendBtn');
    var chips = document.querySelectorAll('.ask-chip');
    var busy = false;

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    function ask(question) {
        if (busy || !question) return;
        busy = true;
        chips.forEach(function(c) { c.disabled = true; });
        if (sendBtn) sendBtn.disabled = true;
        result.classList.remove('d-none');
        result.innerHTML = '<div style="color:#888;display:flex;align-items:center;gap:.4rem"><span class="spinner-border spinner-border-sm" style="width:.7rem;height:.7rem"></span>Lightning AI is reading…</div>';
        fetch(askUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: question })
        })
        .then(function(r) { return r.json().then(function(j) { return {ok:r.ok,body:j}; }); })
        .then(function(res) {
            var sources = (res.body.sources||[]).map(function(s) {
                return '<li>' + esc(s.title) + (s.page?' p.'+esc(s.page):'') + ': <em>' + esc(s.excerpt) + '</em></li>';
            }).join('');
            result.innerHTML = '<div style="background:#f0f9f3;border:1px solid #d5e8db;border-radius:.5rem;padding:.5rem .65rem">'
                + '<div style="color:#00743F;font-weight:600;font-size:.72rem;margin-bottom:.25rem"><i class="bi bi-lightning-charge-fill"></i> Lightning AI</div>'
                + '<div style="color:#2f3b35">' + (res.ok && res.body.answer ? res.body.answer : '<span style="color:red">Error. Try again.</span>') + '</div>'
                + (sources ? '<ul style="margin:.35rem 0 0;padding-left:1rem;font-size:.7rem;color:#888">' + sources + '</ul>' : '')
                + '</div>';
        })
        .catch(function() {
            result.innerHTML = '<div style="color:#dc3545;font-size:.77rem">Could not reach server. Try again.</div>';
        })
        .then(function() {
            busy = false;
            chips.forEach(function(c) { c.disabled = false; });
            if (sendBtn) sendBtn.disabled = false;
        });
    }

    chips.forEach(function(c) {
        c.addEventListener('click', function() { ask(c.getAttribute('data-question')); });
    });
    if (sendBtn) sendBtn.addEventListener('click', function() {
        var q = (input && input.value||'').trim();
        if (q) { ask(q); if(input) input.value=''; }
    });
    if (input) input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); if(input.value.trim()) { ask(input.value.trim()); input.value=''; } }
    });
})();
</script>
@endsection
