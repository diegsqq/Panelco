@extends('layouts.app')
@section('content')
<style>
    body:has(#printPage) main { padding: 0 !important; }

    /* ── Shell ── */
    #printPage { display: flex; flex-direction: column; height: 100vh; overflow: hidden; background: #f7f7f7; }
    [data-theme="dark"] #printPage { background: #0e1512; }

    /* ── Top bar ── */
    .pp-topbar { flex: 0 0 auto; background: #fff; border-bottom: 1px solid #e8e8e8; padding: .55rem 1.25rem; display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    [data-theme="dark"] .pp-topbar { background: #16211c; border-color: #26362e; }
    .pp-back { display: flex; align-items: center; gap: .35rem; font-size: .8rem; color: #555; text-decoration: none; }
    .pp-back:hover { color: #00A868; }
    .pp-breadcrumb { font-size: .75rem; color: #aaa; display: flex; align-items: center; gap: .3rem; }
    .pp-breadcrumb a { color: #888; text-decoration: none; }
    .pp-title-row { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .pp-title { font-weight: 700; font-size: .95rem; color: #111; }
    [data-theme="dark"] .pp-title { color: #fff; }
    .pp-badge { font-size: .68rem; font-weight: 700; padding: .18rem .5rem; border-radius: 2rem; background: #d4edda; color: #155724; }
    .pp-meta { font-size: .73rem; color: #aaa; }
    .pp-topbar-right { margin-left: auto; display: flex; align-items: center; gap: .5rem; }
    .pp-btn { font-size: .78rem; font-weight: 600; border-radius: .55rem; padding: .35rem .75rem; border: 1px solid #e0e0e0; background: #fff; color: #333; cursor: pointer; display: inline-flex; align-items: center; gap: .3rem; text-decoration: none; transition: all .12s ease; }
    .pp-btn:hover { background: #f0f0f0; color: #111; }
    .pp-btn-primary { background: #00A868; border-color: #00A868; color: #fff; }
    .pp-btn-primary:hover { background: #00743F; border-color: #00743F; color: #fff; }
    .pp-autosave { font-size: .72rem; color: #aaa; display: flex; align-items: center; gap: .3rem; }
    [data-theme="dark"] .pp-btn { background: #1d2b24; border-color: #2c3d34; color: #c3d0c8; }

    /* ── Headline ── */
    .pp-headline { flex: 0 0 auto; padding: .9rem 1.25rem .5rem; }
    .pp-headline h2 { font-size: 1.3rem; font-weight: 800; color: #111; margin-bottom: .15rem; }
    .pp-headline p { font-size: .82rem; color: #888; margin: 0; }
    [data-theme="dark"] .pp-headline h2 { color: #fff; }
    [data-theme="dark"] .pp-headline p { color: #8fa59a; }

    /* ── Body ── */
    .pp-body { flex: 1 1 auto; display: flex; min-height: 0; overflow: hidden; }

    /* ── Left panel ── */
    .pp-left { width: 280px; flex: 0 0 280px; background: #fff; border-right: 1px solid #e8e8e8; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 1.1rem; }
    [data-theme="dark"] .pp-left { background: #16211c; border-color: #26362e; }

    .pp-section-title { font-size: .72rem; font-weight: 700; color: #111; margin-bottom: .5rem; display: flex; align-items: center; gap: .3rem; }
    [data-theme="dark"] .pp-section-title { color: #e5eee8; }

    /* Scope options */
    .pp-scope-opt { display: flex; align-items: flex-start; gap: .6rem; padding: .55rem .7rem; border: 1px solid #e8e8e8; border-radius: .65rem; cursor: pointer; transition: border-color .12s ease, background .12s ease; margin-bottom: .4rem; }
    .pp-scope-opt:last-child { margin-bottom: 0; }
    .pp-scope-opt.selected { border-color: #00A868; background: #e6f3ea; }
    [data-theme="dark"] .pp-scope-opt { border-color: #26362e; }
    [data-theme="dark"] .pp-scope-opt.selected { border-color: #00A868; background: #12291d; }
    .pp-scope-radio { width: 15px; height: 15px; border-radius: 50%; border: 2px solid #ccc; display: flex; align-items: center; justify-content: center; flex: 0 0 15px; margin-top: 2px; transition: border-color .12s; }
    .pp-scope-opt.selected .pp-scope-radio { border-color: #00A868; }
    .pp-scope-opt.selected .pp-scope-radio::after { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #00A868; display: block; }
    .pp-scope-label { font-size: .82rem; font-weight: 600; color: #111; }
    .pp-scope-sub { font-size: .72rem; color: #888; margin-top: .08rem; }
    .pp-scope-pages { font-size: .72rem; color: #888; margin-left: auto; white-space: nowrap; }
    [data-theme="dark"] .pp-scope-label { color: #e5eee8; }

    /* Topic list */
    .pp-topic-item { display: flex; align-items: center; gap: .6rem; padding: .5rem .7rem; border: 1px solid #e8e8e8; border-radius: .65rem; cursor: pointer; transition: border-color .12s ease, background .12s ease; margin-bottom: .35rem; }
    .pp-topic-item.selected { border-color: #00A868; background: #e6f3ea; }
    [data-theme="dark"] .pp-topic-item { border-color: #26362e; }
    [data-theme="dark"] .pp-topic-item.selected { border-color: #00A868; background: #12291d; }
    .pp-topic-ico { width: 28px; height: 28px; border-radius: .4rem; background: #f0f0f0; display: flex; align-items: center; justify-content: center; flex: 0 0 28px; font-size: .75rem; color: #555; }
    .pp-topic-item.selected .pp-topic-ico { background: #00A868; color: #fff; }
    .pp-topic-name { font-size: .8rem; font-weight: 600; color: #111; }
    .pp-topic-sub { font-size: .7rem; color: #888; }
    [data-theme="dark"] .pp-topic-name { color: #e5eee8; }

    /* Page range */
    .pp-range-input { width: 100%; border: 1px solid #e0e0e0; border-radius: .5rem; padding: .4rem .6rem; font-size: .8rem; color: #111; outline: none; transition: border-color .12s; }
    .pp-range-input:focus { border-color: #00A868; }
    [data-theme="dark"] .pp-range-input { background: #1d2b24; border-color: #2c3d34; color: #e5eee8; }
    .pp-range-hint { font-size: .7rem; color: #aaa; margin-top: .3rem; }

    /* Print settings */
    .pp-setting-row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .pp-setting-label { font-size: .8rem; color: #555; }
    [data-theme="dark"] .pp-setting-label { color: #8fa59a; }
    .pp-select { border: 1px solid #e0e0e0; border-radius: .45rem; padding: .3rem .5rem; font-size: .78rem; color: #333; background: #fff; min-width: 110px; outline: none; }
    [data-theme="dark"] .pp-select { background: #1d2b24; border-color: #2c3d34; color: #e5eee8; }
    .pp-select-sm { min-width: 65px; }

    /* Summary bar */
    .pp-summary { display: flex; align-items: center; gap: .5rem; padding: .65rem .9rem; background: #f7f7f7; border-top: 1px solid #e8e8e8; }
    [data-theme="dark"] .pp-summary { background: #131d18; border-color: #26362e; }
    .pp-summary-ico { width: 22px; height: 22px; border-radius: 50%; background: #e6f3ea; display: flex; align-items: center; justify-content: center; color: #00A868; font-size: .7rem; flex: 0 0 22px; }
    .pp-summary-text { font-size: .78rem; font-weight: 600; color: #111; flex: 1 1 auto; }
    .pp-summary-sub { font-size: .7rem; color: #888; }
    [data-theme="dark"] .pp-summary-text { color: #e5eee8; }
    .pp-cancel-btn { font-size: .8rem; color: #555; border: none; background: none; cursor: pointer; padding: .3rem .5rem; }
    .pp-print-btn { background: #00A868; color: #fff; border: none; border-radius: .65rem; padding: .5rem 1.1rem; font-size: .82rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: .4rem; }
    .pp-print-btn:hover { background: #00743F; }

    /* ── Right: Preview ── */
    .pp-right { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }
    .pp-preview-bar { flex: 0 0 auto; display: flex; align-items: center; gap: .6rem; padding: .55rem 1.1rem; background: #fff; border-bottom: 1px solid #e8e8e8; }
    [data-theme="dark"] .pp-preview-bar { background: #16211c; border-color: #26362e; }
    .pp-preview-title { font-size: .82rem; font-weight: 600; color: #111; display: flex; align-items: center; gap: .4rem; }
    [data-theme="dark"] .pp-preview-title { color: #fff; }
    .pp-topic-tag { font-size: .7rem; background: #e6f3ea; color: #00743F; border-radius: 2rem; padding: .15rem .55rem; font-weight: 600; }
    .pp-preview-actions { margin-left: auto; display: flex; align-items: center; gap: .5rem; }
    .pp-preview-wrap { flex: 1 1 auto; overflow-y: auto; background: #f0f0f0; padding: 1.5rem; display: flex; gap: 1rem; align-items: flex-start; justify-content: center; }
    [data-theme="dark"] .pp-preview-wrap { background: #0e1512; }

    /* Thumbnail strip */
    .pp-thumbs { display: flex; flex-direction: column; gap: .5rem; flex: 0 0 80px; }
    .pp-thumb { width: 72px; height: 92px; background: #fff; border: 2px solid #e0e0e0; border-radius: .4rem; cursor: pointer; overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: .6rem; color: #aaa; transition: border-color .12s; position: relative; }
    .pp-thumb.selected { border-color: #00A868; }
    .pp-thumb .pp-thumb-label { position: absolute; bottom: .2rem; left: 0; right: 0; text-align: center; font-size: .6rem; color: #888; }
    .pp-thumb.excluded { opacity: .4; }
    .pp-thumb-sel-badge { position: absolute; top: .2rem; right: .2rem; background: #00A868; color: #fff; font-size: .55rem; font-weight: 700; border-radius: .25rem; padding: .05rem .25rem; }

    /* Page preview */
    .pp-page { background: #fff; border: 1px solid #e0e0e0; border-radius: .4rem; box-shadow: 0 2px 10px rgba(0,0,0,.08); padding: 2.5rem 2.75rem; max-width: 480px; width: 100%; min-height: 600px; flex: 1 1 auto; }
    [data-theme="dark"] .pp-page { background: #16211c; border-color: #26362e; }
    .pp-page-org { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #00A868; }
    .pp-page-topic { font-size: .65rem; color: #aaa; margin-bottom: .6rem; text-transform: uppercase; letter-spacing: .4px; }
    .pp-page h1 { font-size: 1.4rem; font-weight: 800; color: #111; line-height: 1.2; margin-bottom: .25rem; }
    .pp-page-meta { font-size: .68rem; color: #aaa; margin-bottom: 1.2rem; }
    .pp-page-intro { font-size: .82rem; color: #444; line-height: 1.7; margin-bottom: 1rem; }
    .pp-page h2 { font-size: .95rem; font-weight: 700; color: #111; margin-top: 1.2rem; margin-bottom: .4rem; }
    .pp-page p { font-size: .82rem; color: #333; line-height: 1.7; margin-bottom: .6rem; }
    .pp-page table { width: 100%; border-collapse: collapse; margin-bottom: .75rem; font-size: .75rem; }
    .pp-page table th { background: #f5f5f5; font-weight: 600; padding: .35rem .6rem; border: 1px solid #e8e8e8; text-align: left; color: #555; font-size: .68rem; text-transform: uppercase; letter-spacing: .3px; }
    .pp-page table td { padding: .35rem .6rem; border: 1px solid #e8e8e8; color: #333; }
    .pp-page-callout { background: #f0f9f3; border-left: 3px solid #00A868; padding: .6rem .9rem; border-radius: 0 .4rem .4rem 0; font-size: .78rem; color: #333; margin: .75rem 0; line-height: 1.6; }
    .pp-page-footer { margin-top: 2rem; padding-top: .75rem; border-top: 1px solid #e8e8e8; display: flex; justify-content: space-between; font-size: .65rem; color: #bbb; }
    .pp-page-pagination { font-size: .75rem; color: #888; display: flex; align-items: center; justify-content: center; gap: .6rem; padding: .75rem; background: #fff; border-top: 1px solid #e8e8e8; }
    [data-theme="dark"] .pp-page h1, [data-theme="dark"] .pp-page h2 { color: #fff; }
    [data-theme="dark"] .pp-page p, [data-theme="dark"] .pp-page-intro { color: #c3d0c8; }

    /* Bottom note */
    .pp-footnote { flex: 0 0 auto; padding: .5rem 1.25rem; background: #fff; border-top: 1px solid #e8e8e8; font-size: .72rem; color: #aaa; text-align: center; }
    [data-theme="dark"] .pp-footnote { background: #16211c; border-color: #26362e; }
</style>

@php
    $sanitizer = app(\App\Services\DocumentContentSanitizer::class);
    $rawContent = $document->content ? $sanitizer->sanitize($document->content) : '';

    // Extract sections from headings
    $sections = [];
    if ($rawContent) {
        preg_match_all('/<h([1-3])[^>]*>(.*?)<\/h\1>/is', $rawContent, $hm);
        foreach ($hm[2] as $i => $t) {
            $label = trim(strip_tags($t));
            if ($label) $sections[] = ['label' => $label, 'level' => (int)$hm[1][$i]];
        }
    }
    if (empty($sections) && $document->summary) {
        $sections = [
            ['label' => 'Document Summary', 'level' => 2],
            ['label' => 'Important Details', 'level' => 2],
        ];
    }
    $pageCount = max(1, (int) ceil(str_word_count(strip_tags($rawContent ?: ($document->extracted_text ?? ''))) / 350));
@endphp

<div id="printPage">

    {{-- ── Top bar ── --}}
    <div class="pp-topbar">
        <a href="{{ route('documents.show', $document) }}" class="pp-back"><i class="bi bi-arrow-left"></i></a>
        <div class="pp-breadcrumb">
            <a href="{{ route('documents.library') }}">Documents</a>
            @if($document->department)<span>/</span><span>{{ $document->department->name }}</span>@endif
            @if($document->category)<span>/</span><span>{{ $document->category->name }}</span>@endif
        </div>
        <div class="d-flex flex-column" style="gap:.05rem">
            <div class="pp-title-row">
                <span class="pp-title">{{ $document->title ?? $document->original_filename }}</span>
                <span class="pp-badge">{{ $document->approval_status === 'approved' ? 'Published' : ucfirst($document->approval_status ?? 'Pending') }}</span>
            </div>
            <div class="pp-meta">{{ $document->reference_number ?? 'HR-DOC-001' }} · Version {{ $document->version_number ?? '1' }} · Owned by {{ $document->department?->name ?? 'Organization' }}</div>
        </div>
        <div class="pp-topbar-right">
            <span class="pp-autosave"><i class="bi bi-arrow-repeat" style="color:#00A868"></i> All changes saved · {{ now()->format('g:i A') }}</span>
            <span class="pp-meta ms-2"><i class="bi bi-shield-check" style="color:#00A868"></i> Authorized employees only</span>
            <a href="{{ route('documents.edit-content', $document) }}" class="pp-btn ms-2"><i class="bi bi-pencil"></i> Back to editor</a>
            <span class="pp-meta ms-2">You can edit</span>
        </div>
    </div>

    {{-- ── Headline ── --}}
    <div class="pp-headline">
        <h2>Print only what matters.</h2>
        <p>Select your content and review it before printing.</p>
    </div>

    {{-- ── Body ── --}}
    <div class="pp-body">

        {{-- Left: options ── --}}
        <div class="pp-left">

            {{-- Scope ── --}}
            <div>
                <div class="pp-section-title">What would you like to print?<br><small style="font-weight:400;color:#aaa">Choose a whole document, a topic or page numbers.</small></div>

                <div class="pp-scope-opt selected" id="scopeAll" onclick="setScope('all')">
                    <div class="pp-scope-radio"></div>
                    <div class="flex-fill">
                        <div class="pp-scope-label">Entire document</div>
                        <div class="pp-scope-sub">All topics and all pages</div>
                    </div>
                    <span class="pp-scope-pages">{{ $pageCount }} pages</span>
                </div>

                @if(count($sections))
                <div style="font-size:.7rem;font-weight:700;color:#aaa;margin:.6rem 0 .3rem;text-transform:uppercase;letter-spacing:.4px">▤ Select by topic</div>
                @foreach($sections as $si => $sec)
                @php $pg = max(1, (int) ceil(($si + 1) * $pageCount / max(count($sections),1))); @endphp
                <div class="pp-topic-item {{ $si === 0 ? 'selected' : '' }}" id="topicItem{{ $si }}" onclick="setTopic({{ $si }})">
                    <div class="pp-topic-ico"><i class="bi bi-file-text"></i></div>
                    <div class="flex-fill">
                        <div class="pp-topic-name">{{ \Illuminate\Support\Str::limit($sec['label'], 32) }}</div>
                        <div class="pp-topic-sub">{{ $document->category?->name ?? 'Policy' }}</div>
                    </div>
                    <span class="pp-scope-pages">Pages {{ $pg }}-{{ min($pg+1,$pageCount) }}</span>
                </div>
                @endforeach
                @endif

                <div style="font-size:.7rem;font-weight:700;color:#aaa;margin:.6rem 0 .3rem;text-transform:uppercase;letter-spacing:.4px">▤ Select by page number</div>
                <div class="pp-scope-opt" id="scopePages" onclick="setScope('pages')">
                    <div class="pp-scope-radio"></div>
                    <div class="flex-fill">
                        <div class="pp-scope-label">Specific page ranges</div>
                    </div>
                </div>
                <div id="pageRangeField" style="display:none;margin-top:.5rem">
                    <input type="text" class="pp-range-input" id="pageRange" placeholder="e.g. 1-2, 4" aria-label="Page range">
                    <div class="pp-range-hint">Use original document page numbers.</div>
                </div>
            </div>

            {{-- Print settings ── --}}
            <div>
                <div class="pp-section-title"><i class="bi bi-gear" style="color:#aaa"></i> Print settings</div>
                <div class="d-flex flex-column gap-2">
                    <div>
                        <div class="pp-setting-label mb-1">Destination</div>
                        <select class="pp-select w-100" id="printDest">
                            <option>Office printer - HP LaserJet</option>
                            <option>Microsoft Print to PDF</option>
                            <option>Save as PDF</option>
                        </select>
                    </div>
                    <div class="pp-setting-row">
                        <div>
                            <div class="pp-setting-label">Paper size</div>
                            <select class="pp-select pp-select-sm mt-1" id="printPaper">
                                <option value="a4" @selected(($document->page_paper ?? 'a4') === 'a4')>A4</option>
                                <option value="letter" @selected(($document->page_paper ?? 'a4') === 'letter')>Letter</option>
                                <option value="legal" @selected(($document->page_paper ?? 'a4') === 'legal')>Legal</option>
                            </select>
                        </div>
                        <div>
                            <div class="pp-setting-label">Copies</div>
                            <input type="number" class="pp-select pp-select-sm mt-1" id="printCopies" value="1" min="1" max="99">
                        </div>
                    </div>
                    <div class="pp-setting-row">
                        <span class="pp-setting-label">Orientation</span>
                        <span class="pp-setting-label fw-semibold" style="color:#111" id="orientLabel">{{ ucfirst($document->page_orientation ?? 'Portrait') }}</span>
                    </div>
                </div>
            </div>

            {{-- Summary + actions ── --}}
            <div style="margin-top:auto">
                <div style="display:flex;align-items:center;gap:.5rem;padding:.6rem .7rem;background:#f7f7f7;border:1px solid #e8e8e8;border-radius:.65rem">
                    <div class="pp-summary-ico"><i class="bi bi-check2"></i></div>
                    <div>
                        <div class="pp-summary-text" id="summaryText">{{ $pageCount }} pages selected</div>
                        <div class="pp-summary-sub" id="summaryDetail">All topics · {{ $pageCount }} sheets</div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <a href="{{ route('documents.show', $document) }}" class="pp-cancel-btn">Cancel</a>
                    <button class="pp-print-btn flex-fill" id="printNowBtn">
                        <i class="bi bi-printer"></i> <span id="printBtnLabel">Print {{ $pageCount }} pages</span>
                    </button>
                </div>
            </div>

        </div>{{-- /pp-left --}}

        {{-- Right: preview ── --}}
        <div class="pp-right">
            <div class="pp-preview-bar">
                <div class="pp-preview-title">
                    <i class="bi bi-eye" style="color:#00A868"></i> Print preview
                    <span class="pp-topic-tag" id="previewTopicTag">{{ count($sections) > 0 ? $sections[0]['label'] : 'Full document' }}</span>
                </div>
                <div class="pp-preview-actions">
                    <span style="font-size:.75rem;color:#aaa">— Fit to page +</span>
                </div>
            </div>

            <div class="pp-preview-wrap" id="previewWrap">

                {{-- Thumbnails ── --}}
                <div class="pp-thumbs" id="thumbStrip">
                    @for($p = 1; $p <= min($pageCount, 4); $p++)
                    <div class="pp-thumb {{ $p === 1 ? 'selected' : '' }}" id="thumb{{ $p }}" onclick="selectPage({{ $p }})">
                        <span style="font-size:.6rem;color:#aaa">p.{{ $p }}</span>
                        @if($p === 1)<span class="pp-thumb-sel-badge">SELECTED</span>@endif
                        <div class="pp-thumb-label">Page {{ $p }}</div>
                    </div>
                    @endfor
                </div>

                {{-- Page preview ── --}}
                <div class="d-flex flex-column gap-0 flex-fill" style="max-width:490px">
                    <div class="pp-page">
                        <div class="pp-page-org">{{ strtoupper($document->department?->name ?? config('app.name', 'PANELCO III')) }}</div>
                        <div class="pp-page-topic">{{ strtoupper(($document->department?->name ?? 'GENERAL') . ' / ' . (count($sections) > 0 ? $sections[0]['label'] : 'DOCUMENT')) }}</div>
                        <h1>{{ $document->title ?? $document->original_filename }}</h1>
                        <div class="pp-page-meta">
                            Effective {{ $document->document_date?->format('F d, Y') ?? now()->format('F d, Y') }} &nbsp;·&nbsp; {{ $document->reference_number ?? 'HR-DOC-001' }} &nbsp;·&nbsp; Version {{ $document->version_number ?? '1' }}
                        </div>

                        @if($document->summary)
                        <p class="pp-page-intro">{{ \Illuminate\Support\Str::limit($document->summary, 320) }}</p>
                        @endif

                        @if($rawContent)
                            {!! $rawContent !!}
                        @else
                            @foreach(array_slice(array_filter(explode("\n", $document->extracted_text ?? '')), 0, 20) as $line)
                            @if(trim($line)) <p>{{ $line }}</p> @endif
                            @endforeach
                        @endif

                        <div class="pp-page-footer">
                            <span>{{ $document->uploader?->organization ?? config('app.name') }} · Internal use only</span>
                            <span>1 / {{ $pageCount }}</span>
                        </div>
                    </div>

                    {{-- Pagination ── --}}
                    <div class="pp-page-pagination">
                        <button class="pp-btn" onclick="prevPage()"><i class="bi bi-chevron-left"></i></button>
                        <span id="pageIndicator">1 of {{ count($sections) }} selected pages</span>
                        <button class="pp-btn" onclick="nextPage()">Original page <i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>

            </div>{{-- /pp-preview-wrap --}}

            {{-- Footnote ── --}}
            <div class="pp-footnote" id="ppFootnote">
                @if(count($sections) > 0)
                Only <strong>{{ $sections[0]['label'] ?? 'selected content' }}</strong> will print.
                @else
                All document content will print.
                @endif
            </div>

        </div>{{-- /pp-right --}}

    </div>{{-- /pp-body --}}
</div>{{-- /printPage --}}

<script>
(function () {
    var currentScope = 'all';
    var currentTopic = 0;
    var currentPage  = 1;
    var totalPages   = {{ $pageCount }};
    var topics       = @json(array_values(array_map(fn($s) => $s['label'], $sections)));
    var docTitle     = @json($document->title ?? $document->original_filename);

    /* ── Scope switching ── */
    window.setScope = function(scope) {
        currentScope = scope;
        ['scopeAll','scopePages'].forEach(function(id) {
            document.getElementById(id)?.classList.remove('selected');
        });
        document.getElementById(scope === 'all' ? 'scopeAll' : 'scopePages')?.classList.add('selected');
        document.getElementById('pageRangeField').style.display = scope === 'pages' ? 'block' : 'none';
        updateSummary();
    };

    window.setTopic = function(idx) {
        currentTopic = idx;
        document.querySelectorAll('.pp-topic-item').forEach(function(el) { el.classList.remove('selected'); });
        document.getElementById('topicItem' + idx)?.classList.add('selected');
        document.getElementById('previewTopicTag').textContent = topics[idx] || 'Selected topic';
        updateSummary();
    };

    window.selectPage = function(p) {
        currentPage = p;
        document.querySelectorAll('.pp-thumb').forEach(function(el) { el.classList.remove('selected'); });
        document.getElementById('thumb' + p)?.classList.add('selected');
        document.getElementById('pageIndicator').textContent = p + ' of ' + totalPages + ' pages';
    };

    window.prevPage = function() { if (currentPage > 1) selectPage(currentPage - 1); };
    window.nextPage = function() { if (currentPage < totalPages) selectPage(currentPage + 1); };

    function updateSummary() {
        var text, detail;
        if (currentScope === 'pages') {
            var range = (document.getElementById('pageRange')?.value || '').trim();
            text = range ? 'Custom pages selected' : 'Enter page range';
            detail = range || 'e.g. 1-2, 4';
        } else {
            text = totalPages + ' pages selected';
            detail = (topics.length > 0 ? topics[currentTopic] || 'All topics' : 'All topics') + ' · ' + totalPages + ' sheets';
        }
        document.getElementById('summaryText').textContent = text;
        document.getElementById('summaryDetail').textContent = detail;
        document.getElementById('printBtnLabel').textContent = 'Print ' + (currentScope === 'pages' ? '' : totalPages + ' ') + 'pages';
    }

    /* ── Print button ── */
    document.getElementById('printNowBtn')?.addEventListener('click', function() {
        window.print();
    });

    /* ── Page range live update ── */
    document.getElementById('pageRange')?.addEventListener('input', updateSummary);
})();
</script>
@endsection
