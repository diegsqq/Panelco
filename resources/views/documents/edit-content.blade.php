@extends('layouts.app')

@section('body-class', 'p3-no-scroll')
@section('full-screen', true)
@section('full-screen-col', 'col-12')

@section('content')
{{-- ============================================================
     Three-panel document editor
     Left:   Document outline (TOC)
     Centre: Formatting toolbar + paged canvas
     Right:  Document info, version history, Lightning AI
     ============================================================ --}}
<div class="p3-editor-shell" id="editorShell">

    {{-- ── Top bar ──────────────────────────────────────────────────── --}}
    <div class="p3-editor-topbar d-flex align-items-center gap-3 px-3 py-2">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="flex-grow-1 min-w-0">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="{{ route('documents.library') }}" class="text-decoration-none" style="color:var(--p3-navy)">Document Library</a>
                </li>
                @if($document->department)
                    <li class="breadcrumb-item text-muted">{{ $document->department->name }}</li>
                @endif
                @if($document->category)
                    <li class="breadcrumb-item text-muted">{{ $document->category->name }}</li>
                @endif
            </ol>
            <div class="fw-bold text-truncate" style="font-size:1.1rem;max-width:40vw;line-height:1.2" title="{{ $document->title ?? $document->original_filename }}">
                {{ $document->title ?? $document->original_filename }}
            </div>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span id="saveStatus" data-state="saved" role="status" aria-live="polite" class="d-flex align-items-center gap-1 small">
                    <i class="bi bi-check-circle" id="saveIcon"></i>
                    <span id="saveStatusText">All changes saved</span>
                    @if($document->content_updated_at)
                        · <span class="text-muted">{{ $document->content_updated_at->format('g:i A') }}</span>
                    @endif
                    @if($document->version_number)
                        · <span style="color:var(--p3-navy);font-weight:600">Version {{ $document->version_number }}</span>
                    @endif
                </span>
            </div>
        </nav>

        {{-- Actions --}}
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <a href="{{ route('documents.content-pdf', $document) }}" id="exportPdfBtn" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print
            </a>
            <button type="button" class="btn btn-sm btn-p3" id="saveNowBtn">
                <i class="bi bi-cloud-check me-1"></i>Save
            </button>
        </div>
    </div>

    {{-- ── Formatting toolbar ───────────────────────────────────────── --}}
    <div class="p3-editor-toolbar px-3 py-1 d-flex flex-wrap align-items-center gap-1" id="editorToolbar" role="toolbar" aria-label="Formatting toolbar">
        {{-- Text style --}}
        <select id="blockFormat" class="form-select form-select-sm w-auto" title="Text style" aria-label="Text style">
            <option value="p">Normal text</option>
            <option value="h1">Heading 1</option>
            <option value="h2">Heading 2</option>
            <option value="h3">Heading 3</option>
            <option value="h4">Heading 4</option>
            <option value="blockquote">Quote</option>
        </select>

        {{-- Font family --}}
        <select id="fontFamily" class="form-select form-select-sm w-auto" title="Font family" aria-label="Font family" style="max-width:6rem">
            <option value="">Inter</option>
            <option value="Calibri">Calibri</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Arial">Arial</option>
            <option value="Georgia">Georgia</option>
            <option value="Courier New">Courier New</option>
        </select>

        {{-- Font size --}}
        <select id="fontSize" class="form-select form-select-sm w-auto" title="Font size" aria-label="Font size" style="max-width:4rem">
            <option value="">12</option>
            <option value="9">9</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12" selected>12</option>
            <option value="14">14</option>
            <option value="16">16</option>
            <option value="18">18</option>
            <option value="24">24</option>
        </select>

        <span class="p3-toolbar-sep" aria-hidden="true"></span>

        {{-- Bold / Italic / Underline --}}
        <button type="button" class="p3-editor-btn" data-cmd="bold" title="Bold (Ctrl+B)" aria-label="Bold"><strong>B</strong></button>
        <button type="button" class="p3-editor-btn" data-cmd="italic" title="Italic (Ctrl+I)" aria-label="Italic"><em>I</em></button>
        <button type="button" class="p3-editor-btn" data-cmd="underline" title="Underline (Ctrl+U)" aria-label="Underline"><u>U</u></button>

        <span class="p3-toolbar-sep" aria-hidden="true"></span>

        {{-- Text color --}}
        <label class="d-inline-flex align-items-center gap-1 small mb-0 p3-editor-btn px-2" title="Text color" style="cursor:pointer">
            <span>Text color</span>
            <input type="color" id="textColor" value="#000000" class="form-control form-control-color form-control-sm" style="width:1.4rem;height:1.4rem;padding:.05rem;border-radius:.3rem" aria-label="Text color">
        </label>

        <span class="p3-toolbar-sep" aria-hidden="true"></span>

        {{-- Table / Image --}}
        <button type="button" class="p3-editor-btn px-2" id="tableBtn" title="Insert table" aria-label="Insert table">
            <i class="bi bi-table me-1"></i>Table
        </button>
        <button type="button" class="p3-editor-btn px-2" id="imageBtn" title="Insert image" aria-label="Insert image">
            <i class="bi bi-image me-1"></i>Image
        </button>

        <span class="p3-toolbar-sep" aria-hidden="true"></span>

        {{-- Header & footer toggle --}}
        <button type="button" class="p3-editor-btn px-2" id="headerFooterToggle" title="Toggle header & footer" aria-label="Toggle header and footer">
            <i class="bi bi-layout-text-window-reverse me-1"></i>Header &amp; footer
        </button>

        <span class="p3-toolbar-sep" aria-hidden="true"></span>

        {{-- Editing mode label --}}
        <span class="ms-auto small d-flex align-items-center gap-1" style="color:var(--p3-navy);font-weight:600">
            <i class="bi bi-pencil-square"></i> Editing
            <i class="bi bi-chevron-down small ms-1" style="font-size:.7rem"></i>
        </span>
    </div>

    {{-- ── Three-panel body ─────────────────────────────────────────── --}}
    <div class="p3-editor-body">

        {{-- Left panel: Document outline ─────────────────────────── --}}
        <aside class="p3-editor-left" id="editorOutline" aria-label="Document outline">
            <div class="p3-panel-header">Document outline</div>
            <div class="p3-outline-tree small" id="outlineTree">
                <div class="text-muted fst-italic" id="outlinePlaceholder" style="font-size:.8rem;padding:.5rem .25rem">
                    No headings yet — add Heading 1–3 to build an outline.
                </div>
            </div>
        </aside>

        {{-- Centre panel: Paged canvas ───────────────────────────── --}}
        <main class="p3-editor-centre" id="main-content" tabindex="-1">

            {{-- Page canvas --}}
            <div class="p3-pages-wrap" id="pagesWrap">
                <div class="p3-page doc-paper" id="docPage">

                    {{-- Page header band --}}
                    <div class="p3-page-band p3-page-band-header" id="headerBand">
                        <div class="p3-band-label"><i class="bi bi-layout-text-window me-1"></i>Header</div>
                        <div id="docHeader"
                             class="doc-region doc-header"
                             contenteditable="true"
                             role="textbox"
                             aria-label="Page header"
                             data-placeholder="Add a page header…">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_header) !!}</div>
                    </div>

                    {{-- Org / doc meta row (read-only cosmetic) --}}
                    <div class="p3-page-meta">
                        <span class="text-muted small text-uppercase fw-bold" style="letter-spacing:.05em">{{ config('app.name', 'PANELCO III') }}</span>
                        <span class="text-muted small text-uppercase" style="letter-spacing:.05em">{{ strtoupper($document->title ?? $document->original_filename) }}</span>
                    </div>

                    {{-- Document body --}}
                    <div class="p3-page-body">
                        @if($document->reference_number || $document->version_number)
                        <div class="p3-doc-meta-line small text-muted mb-3">
                            @if($document->reference_number)<span>{{ $document->reference_number }}</span>@endif
                            @if($document->version_number)<span>· Version {{ $document->version_number }}</span>@endif
                            <span>· Internal use only</span>
                        </div>
                        @endif

                        <div id="docBody"
                             class="doc-region doc-body"
                             contenteditable="true"
                             role="textbox"
                             aria-multiline="true"
                             aria-label="Document body"
                             data-placeholder="Start writing your document…">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content) !!}</div>
                    </div>

                    {{-- Page footer band --}}
                    <div class="p3-page-band p3-page-band-footer" id="footerBand">
                        <div class="p3-band-label"><i class="bi bi-layout-text-window-reverse me-1"></i>Footer</div>
                        <div id="docFooter"
                             class="doc-region doc-footer"
                             contenteditable="true"
                             role="textbox"
                             aria-label="Page footer"
                             data-placeholder="Add a page footer…">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_footer) !!}</div>

                        {{-- Page number cosmetic --}}
                        <div class="p3-page-num text-muted small">{{ config('app.name', 'PANELCO III') }} · Internal use only <span class="float-end">1 / 1</span></div>
                    </div>

                </div>{{-- /.p3-page --}}
            </div>{{-- /.p3-pages-wrap --}}

            {{-- Zoom / page nav footer --}}
            <div class="p3-canvas-footer d-flex align-items-center justify-content-center gap-3 py-2 small text-muted">
                <button type="button" class="p3-editor-btn px-2" id="zoomOutBtn" aria-label="Zoom out"><i class="bi bi-dash-lg"></i></button>
                <span id="zoomLabel">Zoom 100%</span>
                <button type="button" class="p3-editor-btn px-2" id="zoomInBtn" aria-label="Zoom in"><i class="bi bi-plus-lg"></i></button>
                <span class="ms-3"><span id="wordCount">0</span> words · <span id="charCount">0</span> characters</span>
            </div>

        </main>

        {{-- Right panel: Document info ───────────────────────────── --}}
        <aside class="p3-editor-right" aria-label="Document information">

            {{-- Document information --}}
            <div class="p3-panel-header">Document information</div>

            <div class="p3-info-section">
                @php
                    $statusColor = match($document->approval_status ?? 'pending') {
                        'approved'  => '#00A868',
                        'rejected'  => '#dc3545',
                        default     => '#f0ad4e',
                    };
                    $statusLabel = match($document->approval_status ?? 'pending') {
                        'approved'  => 'Completed',
                        'rejected'  => 'Rejected',
                        default     => 'Pending Review',
                    };
                @endphp
                <div class="fw-bold mb-1" style="color:{{ $statusColor }}">{{ $statusLabel }}
                    @if($document->version_number)· Version {{ $document->version_number }}@endif
                </div>
                <div class="small text-muted">
                    Saved automatically in the centralized Document Library.
                </div>
            </div>

            <div class="p3-info-section">
                <div class="p3-info-label">Employee access</div>
                <div class="small text-muted mb-2">
                    @if($document->approval_status === 'approved')
                        All employees can view. Authorized {{ $document->department?->name ?? 'HR' }} employees can edit.
                    @else
                        Access restricted until document is approved.
                    @endif
                </div>
                <a href="{{ route('documents.show', $document) }}#access" class="p3-info-link small">
                    Manage access <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="p3-info-section">
                <div class="p3-info-label">Recent versions</div>

                @forelse($document->versions()->take(3)->get() as $ver)
                <div class="p3-version-row {{ $loop->first ? 'is-current' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-semibold small">v{{ $ver->version_number }}
                            @if($loop->first)<span class="p3-current-badge ms-1">Current</span>@endif
                        </span>
                        <span class="small text-muted">{{ $ver->created_at->format('M d, g:i A') }}</span>
                    </div>
                </div>
                @empty
                {{-- If no DocumentVersion records yet, show the doc's own version --}}
                <div class="p3-version-row is-current">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-semibold small">
                            v{{ $document->version_number ?? '1.0' }}
                            <span class="p3-current-badge ms-1">Current</span>
                        </span>
                        <span class="small text-muted">
                            {{ ($document->content_updated_at ?? $document->updated_at)->format('M d, g:i A') }}
                        </span>
                    </div>
                </div>
                @endforelse

                <a href="{{ route('documents.show', $document) }}#versions" class="p3-info-link small mt-2 d-inline-block">
                    View version history <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            {{-- Lightning AI widget --}}
            <div class="p3-ai-widget">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-stars" style="color:var(--p3-navy);font-size:1rem"></i>
                    <span class="fw-bold small" style="color:var(--p3-navy)">Lightning AI</span>
                </div>
                <div class="small text-muted mb-2">Summarize this document or improve clarity while you edit.</div>
                <a href="{{ route('chat.index') }}?doc={{ $document->id }}" class="p3-info-link small fw-semibold">
                    Ask Lightning AI <i class="bi bi-arrow-right"></i>
                </a>
            </div>

        </aside>

    </div>{{-- /.p3-editor-body --}}

</div>{{-- /.p3-editor-shell --}}

{{-- Hidden inputs --}}
<input type="file" id="imageInput" accept="image/png,image/jpeg,image/gif,image/webp" class="d-none" aria-hidden="true" tabindex="-1">

{{-- Find & Replace modal --}}
<div class="modal fade" id="findReplaceModal" tabindex="-1" aria-labelledby="findReplaceLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="findReplaceLabel">Find and replace</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><input type="text" id="findInput" class="form-control form-control-sm" placeholder="Find…"></div>
                <div class="mb-2"><input type="text" id="replaceInput" class="form-control form-control-sm" placeholder="Replace with…"></div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="matchCase">
                    <label class="form-check-label small" for="matchCase">Match case</label>
                </div>
                <div class="d-flex gap-1 mt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="findNextBtn">Find next</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="replaceBtn">Replace</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="replaceAllBtn">Replace all</button>
                </div>
                <div id="findStatus" class="small text-muted mt-2"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Shell layout ─────────────────────────────────────────────────── */
.p3-editor-shell {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #f5f5f5;
    overflow: hidden;
}
.p3-editor-topbar {
    background: #fff;
    border-bottom: 1px solid var(--p3-card-border);
    flex-shrink: 0;
    min-height: 64px;
}
.p3-editor-toolbar {
    background: #fff;
    border-bottom: 1px solid var(--p3-card-border);
    flex-shrink: 0;
    min-height: 44px;
    overflow-x: auto;
    white-space: nowrap;
}
.p3-toolbar-sep {
    display: inline-block;
    width: 1px;
    height: 1.4rem;
    background: var(--p3-card-border);
    margin: 0 .2rem;
    vertical-align: middle;
}
.p3-editor-body {
    display: flex;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
}

/* ── Panels ───────────────────────────────────────────────────────── */
.p3-editor-left {
    width: 200px;
    flex-shrink: 0;
    background: #fff;
    border-right: 1px solid var(--p3-card-border);
    overflow-y: auto;
    padding: .75rem;
}
.p3-editor-centre {
    flex: 1 1 auto;
    min-width: 0;
    overflow-y: auto;
    background: #e8e8e8;
    display: flex;
    flex-direction: column;
    padding: 1.5rem 1rem .5rem;
    gap: .5rem;
    align-items: center;
}
.p3-editor-right {
    width: 220px;
    flex-shrink: 0;
    background: #fff;
    border-left: 1px solid var(--p3-card-border);
    overflow-y: auto;
    padding: .75rem;
}

/* ── Panel chrome ─────────────────────────────────────────────────── */
.p3-panel-header {
    font-weight: 700;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--p3-navy);
    margin-bottom: .6rem;
}
.p3-info-section {
    padding: .65rem 0;
    border-bottom: 1px solid var(--p3-card-border);
    margin-bottom: .4rem;
}
.p3-info-label {
    font-weight: 700;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--p3-navy);
    margin-bottom: .35rem;
}
.p3-info-link {
    color: var(--p3-navy);
    text-decoration: none;
}
.p3-info-link:hover { text-decoration: underline; }

/* ── Outline tree ─────────────────────────────────────────────────── */
.p3-outline-tree { font-size: .8rem; }
.p3-outline-item {
    display: block;
    padding: .2rem .3rem;
    color: #444;
    text-decoration: none;
    border-radius: .3rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
    cursor: pointer;
}
.p3-outline-item:hover { background: #f0f9f3; color: var(--p3-navy); }
.p3-outline-h1 { font-weight: 700; padding-left: .3rem; }
.p3-outline-h2 { padding-left: 1rem; }
.p3-outline-h3 { padding-left: 1.6rem; color: #666; }

/* ── Page canvas ──────────────────────────────────────────────────── */
.p3-pages-wrap {
    width: 100%;
    max-width: 760px;
    flex-shrink: 0;
}
.p3-page {
    background: #fff;
    border: 1px solid #d8d8d8;
    border-radius: .5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.12);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.p3-page-band {
    background: #fbfcfb;
    padding: .6rem 1.5rem;
    position: relative;
}
.p3-page-band-header { border-bottom: 1px dashed var(--p3-card-border); }
.p3-page-band-footer { border-top: 1px dashed var(--p3-card-border); }
.p3-band-label {
    font-size: .7rem;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: .3rem;
}
.p3-page-meta {
    display: flex;
    justify-content: space-between;
    padding: .35rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    background: #fafafa;
}
.p3-page-body {
    padding: 1.5rem 1.5rem 1rem;
    flex: 1 1 auto;
}
.p3-doc-meta-line { color: #888; }
.p3-page-num { margin-top: .6rem; font-size: .72rem; }

/* ── Editable regions ─────────────────────────────────────────────── */
.doc-region { outline: none; line-height: 1.7; font-size: .9rem; min-height: 1.5rem; position: relative; }
.doc-region.is-empty::before { content: attr(data-placeholder); color: #bbb; pointer-events: none; }
.doc-body { min-height: 400px; font-size: .95rem; }
.doc-header, .doc-footer { min-height: 1.5rem; font-size: .85rem; }

/* Doc body content styles */
.doc-body h1 { font-size: 1.5rem; font-weight: 700; margin-top: 1.2rem; color: #1a1a1a; }
.doc-body h2 { font-size: 1.15rem; font-weight: 700; margin-top: 1rem; color: #1a1a1a; }
.doc-body h3 { font-size: 1rem; font-weight: 600; margin-top: .8rem; }
.doc-body h4 { font-size: .95rem; font-weight: 600; }
.doc-body table, .doc-header table, .doc-footer table { border-collapse: collapse; width: 100%; margin: .75rem 0; }
.doc-body td, .doc-body th { border: 1px solid #d5e8db; padding: .4rem .65rem; min-width: 3rem; font-size: .85rem; }
.doc-body th { background: #f1f8f4; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #444; }
.doc-body img { max-width: 100%; height: auto; border-radius: .35rem; }
.doc-body blockquote { border-left: 3px solid var(--p3-navy); margin: .75rem 0; padding: .25rem 0 .25rem .9rem; color: #4c5a53; }
.doc-body ul, .doc-body ol { padding-left: 1.4rem; }
.doc-body hr { border: 0; border-top: 1px solid var(--p3-card-border); margin: 1rem 0; }
.doc-body a { color: var(--p3-navy-2); }

/* ── Toolbar buttons ──────────────────────────────────────────────── */
.p3-editor-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2rem; height: 2rem; padding: 0;
    background: transparent; color: inherit;
    border: 1px solid transparent; border-radius: .4rem;
    font-size: .88rem; cursor: pointer; vertical-align: middle;
    white-space: nowrap;
}
.p3-editor-btn:hover { background: #f0f9f3; border-color: var(--p3-card-border); }
.p3-editor-btn.is-active { background: var(--p3-navy); border-color: var(--p3-navy); color: #fff; }

/* ── Version rows ─────────────────────────────────────────────────── */
.p3-version-row {
    padding: .4rem .5rem;
    border-radius: .4rem;
    margin-bottom: .3rem;
}
.p3-version-row.is-current { background: #f0f9f3; }
.p3-current-badge {
    display: inline-block;
    background: var(--p3-navy);
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    padding: .05rem .4rem;
    border-radius: 2rem;
    vertical-align: middle;
    text-transform: uppercase;
    letter-spacing: .04em;
}

/* ── Lightning AI widget ──────────────────────────────────────────── */
.p3-ai-widget {
    background: #f0f9f3;
    border: 1px solid #c7e8d6;
    border-radius: .65rem;
    padding: .75rem;
    margin-top: .75rem;
}

/* ── Save status ──────────────────────────────────────────────────── */
#saveStatus[data-state="saved"]   { color: var(--p3-navy); }
#saveStatus[data-state="unsaved"] { color: #856404; }
#saveStatus[data-state="saving"]  { color: #055160; }
#saveStatus[data-state="error"]   { color: #842029; }
.spin { animation: sd-spin 1s linear infinite; display: inline-block; }
@keyframes sd-spin { to { transform: rotate(360deg); } }

/* ── Canvas footer ────────────────────────────────────────────────── */
.p3-canvas-footer {
    flex-shrink: 0;
    color: #888;
    font-size: .8rem;
}

/* ── Header/footer bands hidden by default ────────────────────────── */
.p3-page-band { transition: opacity .2s ease; }
.p3-page-band.p3-band-hidden { display: none; }

/* ── Dark mode ────────────────────────────────────────────────────── */
[data-theme="dark"] .p3-editor-topbar,
[data-theme="dark"] .p3-editor-toolbar,
[data-theme="dark"] .p3-editor-left,
[data-theme="dark"] .p3-editor-right { background: #16211c; border-color: #26362e; }
[data-theme="dark"] .p3-editor-shell { background: #0e1512; }
[data-theme="dark"] .p3-editor-centre { background: #0a0f0c; }
[data-theme="dark"] .p3-page { background: #141c18; border-color: #26362e; }
[data-theme="dark"] .p3-page-meta { background: #111a15; border-color: #26362e; }
[data-theme="dark"] .p3-page-band { background: #182119; }
[data-theme="dark"] .p3-band-label { color: #6b8c7a; }
[data-theme="dark"] .doc-body h1, [data-theme="dark"] .doc-body h2,
[data-theme="dark"] .doc-body h3 { color: #fff; }
[data-theme="dark"] .doc-body th { background: #1f2b24; color: #c3d0c8; }
[data-theme="dark"] .doc-body td { border-color: #2c3d34; }
[data-theme="dark"] .doc-region.is-empty::before { color: #4a5e54; }
[data-theme="dark"] .p3-editor-btn:hover { background: #1f2b24; }
[data-theme="dark"] .p3-outline-item { color: #c3d0c8; }
[data-theme="dark"] .p3-outline-item:hover { background: #1f2b24; color: #7fe0ac; }
[data-theme="dark"] .p3-info-section { border-color: #26362e; }
[data-theme="dark"] .p3-version-row.is-current { background: #12291d; }
[data-theme="dark"] .p3-ai-widget { background: #12291d; border-color: #1d4432; }
[data-theme="dark"] .p3-toolbar-sep { background: #26362e; }

/* ── Print ────────────────────────────────────────────────────────── */
@media print {
    .p3-editor-topbar, .p3-editor-toolbar, .p3-editor-left,
    .p3-editor-right, .p3-canvas-footer, .p3-band-label { display: none !important; }
    .p3-editor-shell { height: auto; overflow: visible; }
    .p3-editor-body { overflow: visible; }
    .p3-editor-centre { background: #fff; padding: 0; overflow: visible; }
    .p3-page { box-shadow: none; border: none; }
}
</style>

<script>
(function () {
    var saveUrl  = @json(route('documents.update-content', $document));
    var imageUrl = @json(route('documents.content-images', $document));
    var pdfUrl   = @json(route('documents.content-pdf', $document));
    var csrf     = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var headerEl = document.getElementById('docHeader');
    var bodyEl   = document.getElementById('docBody');
    var footerEl = document.getElementById('docFooter');
    var editors  = [headerEl, bodyEl, footerEl];
    var active   = bodyEl;

    var statusEl   = document.getElementById('saveStatus');
    var statusIcon = document.getElementById('saveIcon');
    var statusText = document.getElementById('saveStatusText');
    var wordEl     = document.getElementById('wordCount');
    var charEl     = document.getElementById('charCount');
    var toolbar    = document.getElementById('editorToolbar');

    var dirty = false, saving = false, saveTimer = null;
    var savedRange = null;

    /* ── Placeholder & counts ──────────────────────────────────── */

    function isEmpty(el) {
        return el.textContent.trim() === '' && !el.querySelector('img,table,hr');
    }
    function refreshPlaceholder(el) {
        el.classList.toggle('is-empty', isEmpty(el));
    }
    function refreshCounts() {
        var text = bodyEl.innerText || '';
        var words = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
        wordEl.textContent = words;
        charEl.textContent = text.replace(/\s/g, '').length;
    }

    editors.forEach(function (el) {
        if (el.innerHTML.trim() === '' || el.innerHTML.trim() === '<br>') el.innerHTML = '';
        el.addEventListener('focus', function () { active = el; });
        el.addEventListener('input', markDirty);
        refreshPlaceholder(el);
    });
    try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
    refreshCounts();

    /* ── Dirty / save ─────────────────────────────────────────── */

    function setStatus(state, text) {
        statusEl.setAttribute('data-state', state);
        var icons = { saved: 'bi-check-circle', unsaved: 'bi-pencil', saving: 'bi-arrow-repeat', error: 'bi-exclamation-triangle' };
        statusIcon.className = 'bi ' + (icons[state] || 'bi-check-circle');
        statusText.textContent = text;
        statusIcon.classList.toggle('spin', state === 'saving');
    }

    function markDirty() {
        dirty = true;
        setStatus('unsaved', 'Unsaved changes');
        scheduleSave();
        editors.forEach(refreshPlaceholder);
        refreshCounts();
        refreshOutline();
    }

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () { save({}); }, 2500);
    }

    function save(opts) {
        if (saving && !opts.force) return Promise.resolve();
        saving = true;
        setStatus('saving', 'Saving…');
        var payload = {
            content: bodyEl.innerHTML,
            content_header: headerEl.innerHTML,
            content_footer: footerEl.innerHTML,
        };
        return fetch(saveUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        }).then(function (res) {
            if (!res.ok) throw new Error('Save failed (' + res.status + ')');
            return res.json();
        }).then(function () {
            dirty = false;
            saving = false;
            setStatus('saved', 'All changes saved');
        }).catch(function () {
            saving = false;
            setStatus('error', 'Save failed — retrying…');
            setTimeout(function () { save({}); }, 5000);
        });
    }

    document.getElementById('saveNowBtn').addEventListener('click', function () {
        save({ force: true });
    });

    /* ── Toolbar: selection tracking ──────────────────────────── */

    document.addEventListener('selectionchange', function () {
        var sel = window.getSelection();
        if (!sel || !sel.rangeCount) return;
        var node = sel.anchorNode;
        var el = node && (node.nodeType === 1 ? node : node.parentElement);
        var host = el && el.closest ? el.closest('.doc-region') : null;
        if (host) { active = host; savedRange = sel.getRangeAt(0).cloneRange(); }
        refreshToolbarState();
    });

    toolbar.addEventListener('mousedown', function (e) {
        if (e.target.closest('button, label')) e.preventDefault();
    });

    toolbar.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-cmd]');
        if (!btn) return;
        exec(btn.getAttribute('data-cmd'));
    });

    function exec(cmd, value) {
        active.focus();
        if (savedRange) {
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedRange);
        }
        try { document.execCommand(cmd, false, value || null); } catch (e) {}
        markDirty();
        refreshToolbarState();
    }

    function refreshToolbarState() {
        ['bold', 'italic', 'underline'].forEach(function (cmd) {
            var btn = toolbar.querySelector('[data-cmd="' + cmd + '"]');
            if (!btn) return;
            var on = false;
            try { on = document.queryCommandState(cmd); } catch (e) {}
            btn.classList.toggle('is-active', on);
        });
    }

    document.getElementById('blockFormat').addEventListener('change', function () {
        exec('formatBlock', '<' + this.value + '>');
        this.value = 'p';
    });

    /* ── Font controls ────────────────────────────────────────── */

    document.getElementById('fontFamily').addEventListener('change', function () {
        if (this.value) { try { document.execCommand('fontName', false, this.value); } catch (e) {} markDirty(); }
        this.value = '';
    });

    document.getElementById('fontSize').addEventListener('change', function () {
        if (!this.value) return;
        active.focus();
        if (savedRange) { var s = window.getSelection(); s.removeAllRanges(); s.addRange(savedRange); }
        var span = document.createElement('span');
        span.style.fontSize = this.value + 'pt';
        try {
            var r = window.getSelection().getRangeAt(0);
            r.surroundContents(span);
        } catch (e) {}
        markDirty();
        this.value = '';
    });

    document.getElementById('textColor').addEventListener('input', function () {
        try { document.execCommand('foreColor', false, this.value); } catch (e) {}
        markDirty();
    });

    /* ── Insert table ─────────────────────────────────────────── */

    document.getElementById('tableBtn').addEventListener('click', function () {
        var size = prompt('Table size — rows × columns (e.g. 3x4):', '3x4');
        if (!size) return;
        var m = /^(\d{1,2})\s*[x×]\s*(\d{1,2})$/.exec(size.trim());
        if (!m) { alert('Please enter a size like 3x4.'); return; }
        var rows = Math.min(parseInt(m[1], 10), 20);
        var cols = Math.min(parseInt(m[2], 10), 10);
        var html = '<table><thead><tr>';
        for (var c = 0; c < cols; c++) html += '<th>Column ' + (c + 1) + '</th>';
        html += '</tr></thead><tbody>';
        for (var r = 1; r < rows; r++) {
            html += '<tr>';
            for (var i = 0; i < cols; i++) html += '<td>&nbsp;</td>';
            html += '</tr>';
        }
        html += '</tbody></table><p><br></p>';
        exec('insertHTML', html);
    });

    /* ── Insert image ─────────────────────────────────────────── */

    var imageInput = document.getElementById('imageInput');
    document.getElementById('imageBtn').addEventListener('click', function () {
        imageInput.value = ''; imageInput.click();
    });
    imageInput.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        if (!/^image\/(png|jpeg|gif|webp)$/.test(file.type)) { alert('PNG, JPEG, GIF or WebP only.'); return; }
        if (file.size > 5 * 1024 * 1024) { alert('Images must be 5 MB or smaller.'); return; }
        var fd = new FormData();
        fd.append('image', file);
        fetch(imageUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: fd,
            credentials: 'same-origin',
        }).then(function (res) { return res.json().then(function (d) { if (!res.ok) throw new Error(d.message); return d; }); })
          .then(function (data) {
              var alt = (file.name || 'image').replace(/["<>]/g, '');
              exec('insertHTML', '<img src="' + data.url + '" alt="' + alt + '">');
          })
          .catch(function (err) { alert('Image upload failed: ' + err.message); });
    });

    /* ── PDF export ───────────────────────────────────────────── */

    document.getElementById('exportPdfBtn').addEventListener('click', function (e) {
        e.preventDefault();
        if (!dirty) { window.location.href = pdfUrl; return; }
        save({ force: true }).then(function () { window.location.href = pdfUrl; });
    });

    /* ── Zoom ─────────────────────────────────────────────────── */

    var zoom = 100;
    var page = document.getElementById('docPage');
    var zoomLabel = document.getElementById('zoomLabel');

    function applyZoom() {
        page.style.transform = 'scale(' + (zoom / 100) + ')';
        page.style.transformOrigin = 'top center';
        zoomLabel.textContent = 'Zoom ' + zoom + '%';
    }
    document.getElementById('zoomInBtn').addEventListener('click', function () { zoom = Math.min(zoom + 10, 150); applyZoom(); });
    document.getElementById('zoomOutBtn').addEventListener('click', function () { zoom = Math.max(zoom - 10, 60); applyZoom(); });

    /* ── Header/footer toggle ─────────────────────────────────── */

    var headerBand = document.getElementById('headerBand');
    var footerBand = document.getElementById('footerBand');
    var hfVisible = true;
    document.getElementById('headerFooterToggle').addEventListener('click', function () {
        hfVisible = !hfVisible;
        headerBand.classList.toggle('p3-band-hidden', !hfVisible);
        footerBand.classList.toggle('p3-band-hidden', !hfVisible);
        this.classList.toggle('is-active', !hfVisible);
    });

    /* ── Document outline ─────────────────────────────────────── */

    var outlineTree = document.getElementById('outlineTree');
    var outlinePlaceholder = document.getElementById('outlinePlaceholder');

    function refreshOutline() {
        var headings = bodyEl.querySelectorAll('h1, h2, h3');
        if (headings.length === 0) {
            outlinePlaceholder.style.display = '';
            // remove old entries
            outlineTree.querySelectorAll('.p3-outline-item').forEach(function (n) { n.remove(); });
            return;
        }
        outlinePlaceholder.style.display = 'none';
        outlineTree.querySelectorAll('.p3-outline-item').forEach(function (n) { n.remove(); });
        headings.forEach(function (h) {
            var a = document.createElement('a');
            a.className = 'p3-outline-item p3-outline-' + h.tagName.toLowerCase();
            a.textContent = h.textContent.trim() || '(untitled)';
            a.href = '#';
            a.addEventListener('click', function (e) {
                e.preventDefault();
                h.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            outlineTree.appendChild(a);
        });
    }
    refreshOutline();

    /* ── Find & replace ───────────────────────────────────────── */

    function escapeRegex(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    document.getElementById('findNextBtn').addEventListener('click', function () {
        var term = document.getElementById('findInput').value;
        if (!term) return;
        var found = false;
        try { found = window.find(term, false, false, false, false, false, false); } catch (e) {}
        document.getElementById('findStatus').textContent = found ? 'Found' : 'Not found';
    });
    document.getElementById('replaceBtn').addEventListener('click', function () {
        var term = document.getElementById('findInput').value;
        var repl = document.getElementById('replaceInput').value;
        if (!term) return;
        var sel = window.getSelection();
        if (sel && sel.toString().toLowerCase() === term.toLowerCase()) {
            try { document.execCommand('insertText', false, repl); } catch (e) {}
            markDirty();
            document.getElementById('findStatus').textContent = 'Replaced';
        } else {
            try { window.find(term); } catch (e) {}
            document.getElementById('findStatus').textContent = 'Select match first';
        }
    });
    document.getElementById('replaceAllBtn').addEventListener('click', function () {
        var term = document.getElementById('findInput').value;
        var repl = document.getElementById('replaceInput').value;
        if (!term) return;
        var mc = document.getElementById('matchCase').checked;
        var re = new RegExp(escapeRegex(term), mc ? 'g' : 'gi');
        var changed = false;
        editors.forEach(function (root) {
            var before = root.innerHTML;
            root.innerHTML = root.innerHTML.replace(re, function () { changed = true; return repl; });
        });
        if (changed) { markDirty(); document.getElementById('findStatus').textContent = 'Replaced all'; }
        else { document.getElementById('findStatus').textContent = 'No matches'; }
    });

    /* ── Keyboard shortcuts ───────────────────────────────────── */

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            save({ force: true });
        }
    });

    /* ── Warn on unload ───────────────────────────────────────── */

    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

})();
</script>
@endsection
