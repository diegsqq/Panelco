@extends('layouts.app')
@section('content')
<style>
    body:has(#docViewer) main { padding: 0 !important; }
    /* ── Shell ── */
    #docViewer { display: flex; flex-direction: column; height: 100vh; overflow: hidden; background: #f5f5f5; }
    [data-theme="dark"] #docViewer { background: #0e1512; }

    /* ── Breadcrumb bar ── */
    .dv-breadcrumb { flex: 0 0 auto; display: flex; align-items: center; gap: .4rem; padding: .5rem 1.25rem; font-size: .78rem; color: #888; border-bottom: 1px solid #e8e8e8; background: #fff; }
    .dv-breadcrumb a { color: #555; text-decoration: none; }
    .dv-breadcrumb a:hover { color: #00A868; }
    .dv-breadcrumb .sep { color: #ccc; }
    [data-theme="dark"] .dv-breadcrumb { background: #16211c; border-color: #26362e; color: #8fa59a; }
    [data-theme="dark"] .dv-breadcrumb a { color: #8fa59a; }

    /* ── Title bar ── */
    .dv-titlebar { flex: 0 0 auto; display: flex; align-items: center; gap: .75rem; padding: .6rem 1.25rem; border-bottom: 1px solid #e8e8e8; background: #fff; flex-wrap: wrap; }
    .dv-title { font-weight: 700; font-size: 1rem; color: #111; flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dv-badge { font-size: .7rem; font-weight: 700; padding: .2rem .55rem; border-radius: 2rem; border: none; }
    .dv-badge-published { background: #d4edda; color: #155724; }
    .dv-badge-pending    { background: #fff3cd; color: #856404; }
    .dv-badge-rejected   { background: #f8d7da; color: #721c24; }
    .dv-meta-small { font-size: .75rem; color: #888; white-space: nowrap; }
    .dv-titlebar-actions { display: flex; align-items: center; gap: .4rem; margin-left: auto; }
    .dv-btn { font-size: .78rem; font-weight: 600; border-radius: .55rem; padding: .35rem .75rem; border: 1px solid #ddd; background: #fff; color: #333; cursor: pointer; display: inline-flex; align-items: center; gap: .3rem; transition: all .12s ease; text-decoration: none; white-space: nowrap; }
    .dv-btn:hover { background: #f5f5f5; color: #111; border-color: #bbb; }
    .dv-btn-primary { background: #00A868; border-color: #00A868; color: #fff; }
    .dv-btn-primary:hover { background: #00743F; border-color: #00743F; color: #fff; }
    .dv-btn-green { background: #00A868; border-color: #00A868; color: #fff; }
    .dv-btn-green:hover { background: #00743F; border-color: #00743F; color: #fff; }
    [data-theme="dark"] .dv-titlebar { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .dv-title { color: #fff; }
    [data-theme="dark"] .dv-btn { background: #1d2b24; border-color: #2c3d34; color: #c3d0c8; }
    [data-theme="dark"] .dv-btn:hover { background: #243324; color: #fff; }

    /* ── Toolbar ── */
    .dv-toolbar { flex: 0 0 auto; display: flex; align-items: center; gap: .2rem; padding: .35rem 1.25rem; border-bottom: 1px solid #e8e8e8; background: #fff; overflow-x: auto; }
    .dv-toolbar-sep { width: 1px; height: 18px; background: #e0e0e0; margin: 0 .3rem; flex: 0 0 1px; }
    .dv-tool { font-size: .75rem; padding: .28rem .5rem; border-radius: .4rem; border: none; background: transparent; color: #444; cursor: pointer; display: inline-flex; align-items: center; gap: .25rem; transition: background .1s ease; white-space: nowrap; }
    .dv-tool:hover { background: #f0f0f0; }
    .dv-tool.active { background: #e6f3ea; color: #00743F; }
    .dv-tool i { font-size: .85rem; }
    .dv-autosave { margin-left: auto; font-size: .72rem; color: #aaa; display: flex; align-items: center; gap: .4rem; white-space: nowrap; flex: 0 0 auto; }
    .dv-autosave .dot { width: 7px; height: 7px; border-radius: 50%; background: #00A868; }
    [data-theme="dark"] .dv-toolbar { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .dv-tool { color: #c3d0c8; }
    [data-theme="dark"] .dv-tool:hover { background: #1d2b24; }
    [data-theme="dark"] .dv-toolbar-sep { background: #26362e; }
    [data-theme="dark"] .dv-autosave { color: #4a5e54; }

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
    .dv-page-wrap { flex: 1 1 auto; overflow-y: auto; padding: 2rem 1.5rem; display: flex; flex-direction: column; align-items: center; gap: 1.5rem; }
    .dv-page { width: 100%; max-width: 740px; background: #fff; border: 1px solid #e0e0e0; border-radius: .5rem; box-shadow: 0 2px 12px rgba(0,0,0,.08); padding: 3rem 3.5rem; min-height: 400px; }
    [data-theme="dark"] .dv-page { background: #16211c; border-color: #26362e; box-shadow: none; }

    .dv-page-org  { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #00A868; margin-bottom: .15rem; }
    .dv-page-category { font-size: .72rem; color: #888; margin-bottom: .6rem; }
    .dv-page h1   { font-size: 1.75rem; font-weight: 800; color: #111; line-height: 1.2; margin-bottom: .35rem; }
    .dv-page-meta { font-size: .75rem; color: #aaa; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .dv-page-meta span { display: flex; align-items: center; gap: .25rem; }
    .dv-page-intro { font-size: .88rem; color: #444; line-height: 1.7; margin-bottom: 1.5rem; }
    .dv-page h2 { font-size: 1.1rem; font-weight: 700; color: #111; margin-top: 1.75rem; margin-bottom: .5rem; }
    .dv-page h3 { font-size: .95rem; font-weight: 700; color: #333; margin-top: 1.25rem; margin-bottom: .4rem; }
    .dv-page p  { font-size: .88rem; color: #333; line-height: 1.75; margin-bottom: .85rem; }
    .dv-page ul, .dv-page ol { font-size: .88rem; color: #333; line-height: 1.75; padding-left: 1.5rem; margin-bottom: .85rem; }
    .dv-page table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: .83rem; }
    .dv-page table th { background: #f5f5f5; font-weight: 600; padding: .5rem .75rem; border: 1px solid #e0e0e0; text-align: left; }
    .dv-page table td { padding: .5rem .75rem; border: 1px solid #e0e0e0; }
    .dv-page table tr:nth-child(even) td { background: #fafafa; }
    .dv-page-callout { background: #f0f9f3; border-left: 3px solid #00A868; border-radius: 0 .5rem .5rem 0; padding: .7rem 1rem; margin: 1rem 0; font-size: .85rem; color: #2d5a3d; }
    .dv-page-footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e8e8e8; font-size: .72rem; color: #aaa; display: flex; justify-content: space-between; }
    .dv-page-pagination { font-size: .78rem; color: #aaa; text-align: center; display: flex; align-items: center; gap: .75rem; justify-content: center; }
    .dv-page-pagination button { background: #fff; border: 1px solid #e0e0e0; border-radius: .4rem; padding: .25rem .6rem; font-size: .78rem; cursor: pointer; color: #555; }
    .dv-page-pagination button:hover { background: #e6f3ea; border-color: #00A868; color: #00743F; }
    [data-theme="dark"] .dv-page h1, [data-theme="dark"] .dv-page h2, [data-theme="dark"] .dv-page h3 { color: #fff; }
    [data-theme="dark"] .dv-page p, [data-theme="dark"] .dv-page ul, [data-theme="dark"] .dv-page ol { color: #c3d0c8; }
    [data-theme="dark"] .dv-page table th { background: #1d2b24; border-color: #2c3d34; color: #c3d0c8; }
    [data-theme="dark"] .dv-page table td { border-color: #2c3d34; }
    [data-theme="dark"] .dv-page table tr:nth-child(even) td { background: #131d18; }
    [data-theme="dark"] .dv-page-intro, [data-theme="dark"] .dv-page-meta { color: #8fa59a; }
    [data-theme="dark"] .dv-page-callout { background: #12291d; color: #7fe0ac; border-color: #00A868; }
    [data-theme="dark"] .dv-page-footer { border-color: #26362e; color: #4a5e54; }

    /* ── Right info panel ── */
    .dv-info { width: 240px; flex: 0 0 240px; background: #fff; border-left: 1px solid #e8e8e8; overflow-y: auto; padding: 1rem .9rem; display: flex; flex-direction: column; gap: .85rem; }
    [data-theme="dark"] .dv-info { background: #16211c; border-color: #26362e; }
    .dv-info-section { border: 1px solid #ebebeb; border-radius: .75rem; overflow: hidden; }
    [data-theme="dark"] .dv-info-section { border-color: #26362e; }
    .dv-info-section-head { display: flex; align-items: center; justify-content: space-between; padding: .6rem .75rem; background: #fafafa; border-bottom: 1px solid #ebebeb; }
    [data-theme="dark"] .dv-info-section-head { background: #131d18; border-color: #26362e; }
    .dv-info-section-title { font-size: .78rem; font-weight: 700; color: #111; }
    [data-theme="dark"] .dv-info-section-title { color: #fff; }
    .dv-info-section-body { padding: .65rem .75rem; display: flex; flex-direction: column; gap: .5rem; }
    .dv-info-row { display: flex; justify-content: space-between; align-items: flex-start; gap: .4rem; font-size: .77rem; }
    .dv-info-key { color: #888; flex: 0 0 auto; }
    .dv-info-val { color: #111; font-weight: 500; text-align: right; word-break: break-word; }
    [data-theme="dark"] .dv-info-key { color: #8fa59a; }
    [data-theme="dark"] .dv-info-val { color: #e5eee8; }
    .dv-version-row { display: flex; align-items: center; gap: .5rem; padding: .4rem 0; border-bottom: 1px solid #f5f5f5; font-size: .77rem; }
    .dv-version-row:last-child { border-bottom: 0; }
    [data-theme="dark"] .dv-version-row { border-color: #26362e; }
    .dv-version-dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 8px; }
    .dv-version-current .dv-version-dot { background: #00A868; }
    .dv-version-row:not(.dv-version-current) .dv-version-dot { background: #ddd; }
    .dv-avatar-row { display: flex; align-items: center; gap: .4rem; font-size: .77rem; }
    .dv-avatar { width: 22px; height: 22px; border-radius: 50%; background: #00A868; color: #fff; font-size: .6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex: 0 0 22px; }
    /* AI ask panel inside info */
    .dv-ask-box { padding: .65rem .75rem; }
    .dv-ask-title { font-size: .77rem; font-weight: 700; color: #00743F; display: flex; align-items: center; gap: .3rem; margin-bottom: .4rem; }
    [data-theme="dark"] .dv-ask-title { color: #7fe0ac; }
    .dv-ask-input-row { display: flex; gap: .3rem; }
    .dv-ask-input { flex: 1 1 auto; border: 1px solid #d5e8db; border-radius: .5rem; padding: .3rem .5rem; font-size: .77rem; background: #fff; color: #111; outline: none; }
    .dv-ask-input:focus { border-color: #00A868; }
    [data-theme="dark"] .dv-ask-input { background: #1d2b24; border-color: #2c3d34; color: #e5eee8; }
    .dv-ask-send { width: 26px; height: 26px; border-radius: 50%; background: #00A868; border: none; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; flex: 0 0 26px; font-size: .75rem; }
    .dv-ask-send:hover { background: #00743F; }
    .dv-ask-chip { font-size: .7rem; border: 1px solid #d5e8db; background: #f0f9f3; color: #00743F; border-radius: 2rem; padding: .15rem .5rem; cursor: pointer; transition: all .1s; }
    .dv-ask-chip:hover { background: #e6f3ea; }

    @media (max-width: 991.98px) {
        .dv-outline { display: none; }
        .dv-info { display: none; }
        .dv-page { padding: 1.5rem; }
    }
    @media print {
        .dv-breadcrumb, .dv-titlebar, .dv-toolbar, .dv-outline, .dv-info { display: none !important; }
        .dv-body { overflow: visible; }
        .dv-page-wrap { overflow: visible; padding: 0; }
        .dv-page { box-shadow: none; border: none; border-radius: 0; padding: 0; }
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
        <span>{{ \Illuminate\Support\Str::limit($document->title ?? $document->original_filename, 40) }}</span>
    </div>

    {{-- ── Title bar ── --}}
    <div class="dv-titlebar">
        <div class="dv-title">{{ $document->title ?? $document->original_filename }}</div>
        <span class="dv-badge {{ $document->approval_status === 'approved' ? 'dv-badge-published' : ($document->approval_status === 'rejected' ? 'dv-badge-rejected' : 'dv-badge-pending') }}">
            {{ $document->approval_status === 'approved' ? 'Published' : ucfirst($document->approval_status ?? 'Pending') }}
        </span>
        <span class="dv-meta-small">Version {{ $document->version_number ?? '1' }}</span>
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

    {{-- ── Toolbar ── --}}
    <div class="dv-toolbar">
        <span class="dv-tool" style="color:#888;font-size:.75rem">File</span>
        <span class="dv-tool" style="color:#888;font-size:.75rem">Edit</span>
        <span class="dv-tool" style="color:#888;font-size:.75rem">View</span>
        <span class="dv-tool" style="color:#888;font-size:.75rem">Insert</span>
        <span class="dv-tool" style="color:#888;font-size:.75rem">Format</span>
        <div class="dv-toolbar-sep"></div>
        <button class="dv-tool" title="Bold"><i class="bi bi-type-bold"></i></button>
        <button class="dv-tool" title="Italic"><i class="bi bi-type-italic"></i></button>
        <button class="dv-tool" title="Underline"><i class="bi bi-type-underline"></i></button>
        <button class="dv-tool" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
        <button class="dv-tool" title="Font color"><i class="bi bi-fonts"></i></button>
        <div class="dv-toolbar-sep"></div>
        <button class="dv-tool" title="Align left"><i class="bi bi-text-left"></i></button>
        <button class="dv-tool" title="Align center"><i class="bi bi-text-center"></i></button>
        <button class="dv-tool" title="Align right"><i class="bi bi-text-right"></i></button>
        <div class="dv-toolbar-sep"></div>
        <button class="dv-tool"><i class="bi bi-table"></i> Table</button>
        <button class="dv-tool"><i class="bi bi-image"></i> Image</button>
        <button class="dv-tool"><i class="bi bi-layout-text-window-reverse"></i> Header &amp; footer</button>
        <div class="dv-autosave">
            <span class="dot"></span>
            Auto-save <strong style="color:#333">On</strong>
            <span style="margin-left:.5rem;color:#aaa">|</span>
            <span style="color:#00A868;font-weight:600">Editing</span>
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
                <a href="#doc-summary" class="dv-outline-item active">Document Summary</a>
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
            <div class="alert alert-success alert-dismissible fade show w-100" style="max-width:740px;border-radius:.75rem" role="alert">
                {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show w-100" style="max-width:740px;border-radius:.75rem" role="alert">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="dv-page">

                {{-- Page header ── --}}
                <div class="dv-page-org">{{ strtoupper($document->department?->name ?? config('app.name', 'PANELCO III')) }}</div>
                <div class="dv-page-category">
                    {{ $document->category?->name ?? $document->suggestedCategory?->name ?? 'General' }}
                    @if($document->department) / {{ $document->department->name }} @endif
                    @if($document->category) / Topic 1 @endif
                </div>
                <h1>{{ $document->title ?? $document->original_filename }}</h1>
                <div class="dv-page-meta">
                    @if($document->document_date)<span><i class="bi bi-calendar3"></i> Effective {{ $document->document_date->format('F d, Y') }}</span>@endif
                    @if($document->reference_number)<span>· {{ $document->reference_number }}</span>@endif
                    <span>· Version {{ $document->version_number ?? '1' }}</span>
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

                @if(!$document->summary && !count($details) && !count($correctiveActions))
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
                        <span class="dv-info-key">Published version</span>
                        <span class="dv-info-val" style="color:#00A868">v{{ $document->version_number ?? '1' }}</span>
                    </div>
                    <div class="dv-info-row">
                        <span class="dv-info-key">Status</span>
                        <span class="dv-info-val">
                            <span class="dv-badge {{ $document->approval_status === 'approved' ? 'dv-badge-published' : 'dv-badge-pending' }}">
                                {{ ucfirst($document->approval_status ?? 'pending') }}
                            </span>
                        </span>
                    </div>
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

            {{-- Recent versions ── --}}
            <div class="dv-info-section">
                <div class="dv-info-section-head">
                    <span class="dv-info-section-title">Recent versions</span>
                    <i class="bi bi-clock-history" style="font-size:.8rem;color:#aaa"></i>
                </div>
                <div class="dv-info-section-body">
                    <div class="dv-version-row dv-version-current">
                        <div class="dv-version-dot"></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.77rem;font-weight:600">v{{ $document->version_number ?? '1' }} · Current</div>
                            <div style="font-size:.7rem;color:#aaa">{{ $document->updated_at->format('M d, g:i A') }} · {{ $document->currentVersionUploader?->name ?? $document->uploader?->name ?? '—' }}</div>
                        </div>
                    </div>
                    @foreach($document->versions->take(3) as $v)
                    <div class="dv-version-row">
                        <div class="dv-version-dot"></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.77rem;font-weight:500">v{{ $v->version_number }}</div>
                            <div style="font-size:.7rem;color:#aaa">{{ $v->created_at->format('M d, g:i A') }} · {{ $v->uploader?->name ?? '—' }}</div>
                        </div>
                        @can('download', $document)
                        <a href="{{ route('documents.versions.download', [$document, $v]) }}" style="font-size:.75rem;color:#00A868;text-decoration:none" title="Download v{{ $v->version_number }}"><i class="bi bi-download"></i></a>
                        @endcan
                    </div>
                    @endforeach
                    @if($document->versions->count() > 3)
                    <a href="#" data-bs-toggle="modal" data-bs-target="#versionHistoryModal" style="font-size:.75rem;color:#00A868;text-decoration:none">View all {{ $document->versions->count() + 1 }} versions →</a>
                    @endif
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

            {{-- Approval (managers) ── --}}
            @can('approve', $document)
            @if($document->processing_status === 'completed')
            <div class="dv-info-section">
                <div class="dv-info-section-head">
                    <span class="dv-info-section-title">Document approval</span>
                </div>
                <div class="dv-info-section-body">
                    <form method="POST" action="{{ route('documents.approve-document', $document) }}">
                        @csrf
                        <textarea name="review_note" class="form-control form-control-sm mb-2" rows="2" placeholder="Approval note (optional)"></textarea>
                        <button class="dv-btn dv-btn-green w-100 mb-1"><i class="bi bi-check-lg"></i> Approve</button>
                    </form>
                    <form method="POST" action="{{ route('documents.reject-document', $document) }}">
                        @csrf
                        <textarea name="review_note" class="form-control form-control-sm mb-2" rows="2" placeholder="Reason for rejection" required></textarea>
                        <button class="dv-btn w-100" style="color:#dc3545;border-color:#f5c6cb"><i class="bi bi-x-lg"></i> Reject</button>
                    </form>
                </div>
            </div>
            @endif
            @endcan

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
