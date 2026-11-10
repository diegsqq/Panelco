@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
<div class="col-lg-11">

    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <div class="d-flex align-items-center gap-3">
            <span class="stat-icon" style="background:#e6f3ea;color:#00A868;width:52px;height:52px;font-size:1.4rem"><i class="bi bi-file-earmark-text"></i></span>
            <div>
                <h4 class="fw-bold mb-0">Edit document content</h4>
                <small class="text-muted text-truncate d-inline-block" style="max-width:26rem" title="{{ $document->original_filename }}">{{ $document->title ?? $document->original_filename }}</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span id="saveStatus" class="badge" data-state="saved" role="status" aria-live="polite"><i class="bi bi-check-circle me-1"></i><span id="saveStatusText">All changes saved</span></span>
            <button type="button" class="btn btn-sm" id="saveNowBtn" style="background:#ffffff;color:#00A868;border:1px solid #00A868;font-weight:700"><i class="bi bi-cloud-check me-1"></i>Save now</button>
            <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-outline-secondary text-nowrap"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    {{-- Formatting toolbar. Buttons keep the caret inside the editor by
         blocking mousedown, so a click never steals the selection. --}}
    <div class="card card-p3 p-2 mb-3" id="editorToolbar">
        <div class="d-flex flex-wrap gap-1 align-items-center">
            <button type="button" class="editor-btn" data-cmd="undo" title="Undo (Ctrl+Z)" aria-label="Undo"><i class="bi bi-arrow-counterclockwise"></i></button>
            <button type="button" class="editor-btn" data-cmd="redo" title="Redo (Ctrl+Y)" aria-label="Redo"><i class="bi bi-arrow-clockwise"></i></button>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <select id="blockFormat" class="form-select form-select-sm w-auto" title="Text style" aria-label="Text style">
                <option value="p">Paragraph</option>
                <option value="h1">Heading 1</option>
                <option value="h2">Heading 2</option>
                <option value="h3">Heading 3</option>
                <option value="h4">Heading 4</option>
                <option value="blockquote">Quote</option>
            </select>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <button type="button" class="editor-btn" data-cmd="bold" title="Bold (Ctrl+B)" aria-label="Bold"><i class="bi bi-type-bold"></i></button>
            <button type="button" class="editor-btn" data-cmd="italic" title="Italic (Ctrl+I)" aria-label="Italic"><i class="bi bi-type-italic"></i></button>
            <button type="button" class="editor-btn" data-cmd="underline" title="Underline (Ctrl+U)" aria-label="Underline"><i class="bi bi-type-underline"></i></button>
            <button type="button" class="editor-btn" data-cmd="strikeThrough" title="Strikethrough" aria-label="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <button type="button" class="editor-btn" data-cmd="insertUnorderedList" title="Bulleted list" aria-label="Bulleted list"><i class="bi bi-list-ul"></i></button>
            <button type="button" class="editor-btn" data-cmd="insertOrderedList" title="Numbered list" aria-label="Numbered list"><i class="bi bi-list-ol"></i></button>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <button type="button" class="editor-btn" data-cmd="justifyLeft" title="Align left" aria-label="Align left"><i class="bi bi-text-left"></i></button>
            <button type="button" class="editor-btn" data-cmd="justifyCenter" title="Center" aria-label="Center"><i class="bi bi-text-center"></i></button>
            <button type="button" class="editor-btn" data-cmd="justifyRight" title="Align right" aria-label="Align right"><i class="bi bi-text-right"></i></button>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <button type="button" class="editor-btn" id="linkBtn" title="Insert link" aria-label="Insert link"><i class="bi bi-link-45deg"></i></button>
            <button type="button" class="editor-btn" id="tableBtn" title="Insert table" aria-label="Insert table"><i class="bi bi-table"></i></button>
            <button type="button" class="editor-btn" id="imageBtn" title="Insert image" aria-label="Insert image"><i class="bi bi-image"></i></button>
            <button type="button" class="editor-btn" data-cmd="insertHorizontalRule" title="Horizontal line" aria-label="Horizontal line"><i class="bi bi-dash-lg"></i></button>
            <button type="button" class="editor-btn" data-cmd="removeFormat" title="Clear formatting" aria-label="Clear formatting"><i class="bi bi-eraser"></i></button>
        </div>
        <div class="d-flex flex-wrap gap-1 align-items-center mt-2">
            <select id="fontFamily" class="form-select form-select-sm w-auto" title="Font family" aria-label="Font family">
                <option value="">Font</option>
                <option value="Calibri">Calibri</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Arial">Arial</option>
                <option value="Georgia">Georgia</option>
                <option value="Courier New">Courier New</option>
                <option value="Verdana">Verdana</option>
                <option value="DejaVu Serif">DejaVu Serif</option>
            </select>
            <select id="fontSize" class="form-select form-select-sm w-auto" title="Font size" aria-label="Font size">
                <option value="">Size</option>
                <option value="9">9</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
                <option value="14">14</option>
                <option value="16">16</option>
                <option value="18">18</option>
                <option value="20">20</option>
                <option value="24">24</option>
                <option value="28">28</option>
                <option value="36">36</option>
            </select>
            <label class="d-inline-flex align-items-center gap-1 small mb-0" title="Text color">Color <input type="color" id="textColor" value="#000000" class="form-control form-control-color form-control-sm" style="width:2rem;height:2rem;padding:.1rem"></label>
            <label class="d-inline-flex align-items-center gap-1 small mb-0" title="Highlight">Highlight <input type="color" id="highlightColor" value="#ffff00" class="form-control form-control-color form-control-sm" style="width:2rem;height:2rem;padding:.1rem"></label>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <button type="button" class="editor-btn" data-cmd="subscript" title="Subscript" aria-label="Subscript"><i class="bi bi-subscript"></i></button>
            <button type="button" class="editor-btn" data-cmd="superscript" title="Superscript" aria-label="Superscript"><i class="bi bi-superscript"></i></button>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <button type="button" class="editor-btn" id="insertPageNumberBtn" title="Insert page number token" aria-label="Insert page number"><i class="bi bi-123"></i></button>
            <button type="button" class="editor-btn" id="insertDateBtn" title="Insert current date" aria-label="Insert date"><i class="bi bi-calendar"></i></button>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <select id="templateSelect" class="form-select form-select-sm w-auto" title="Insert template" aria-label="Insert template">
                <option value="">Template…</option>
                @foreach($templates as $t)
                    <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                @endforeach
            </select>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <button type="button" class="editor-btn" id="findReplaceBtn" title="Find and replace" aria-label="Find and replace"><i class="bi bi-search"></i></button>
        </div>
        <div class="d-flex flex-wrap gap-1 align-items-center mt-2">
            <span class="small text-muted me-1">Page setup:</span>
            <select id="pagePaper" class="form-select form-select-sm w-auto" title="Paper size">
                @foreach(\App\Support\PageLayout::PAPERS as $key => $spec)
                    <option value="{{ $key }}" @selected(($document->page_paper ?? 'a4') === $key)>{{ $spec['label'] }}</option>
                @endforeach
            </select>
            <select id="pageOrientation" class="form-select form-select-sm w-auto" title="Orientation">
                @foreach(\App\Support\PageLayout::ORIENTATIONS as $key => $label)
                    <option value="{{ $key }}" @selected(($document->page_orientation ?? 'portrait') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select id="pageMargins" class="form-select form-select-sm w-auto" title="Margins">
                @foreach(\App\Support\PageLayout::MARGINS as $key => $spec)
                    <option value="{{ $key }}" @selected(($document->page_margins ?? 'normal') === $key)>{{ $spec['label'] }}</option>
                @endforeach
            </select>
            <span class="toolbar-sep" aria-hidden="true"></span>
            <a id="exportDocxBtn" href="{{ route('documents.content-docx', $document) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-word me-1"></i>DOCX</a>
            <a id="exportPdfBtn" href="{{ route('documents.content-pdf', $document) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        </div>
    </div>

    {{-- The document itself: page header, editable body, page footer. --}}
    <div class="card card-p3 doc-paper p-0 overflow-hidden">
        <div class="p-3 doc-band doc-band-header">
            <div class="small text-muted mb-1"><i class="bi bi-type me-1"></i>Page header</div>
            <div id="docHeader" class="doc-region doc-header" contenteditable="true" role="textbox" aria-label="Page header" data-placeholder="Type a page header…">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_header) !!}</div>
        </div>
        <div class="p-4 p-md-5 doc-band-body">
            <div class="small text-muted mb-2"><i class="bi bi-pencil-square me-1"></i>Document body</div>
            <div id="docBody" class="doc-region doc-body" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Document body" data-placeholder="Start writing your document — use the toolbar for bold, italic, underline, tables and images.">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content) !!}</div>
        </div>
        <div class="p-3 doc-band doc-band-footer">
            <div class="small text-muted mb-1"><i class="bi bi-type me-1"></i>Page footer</div>
            <div id="docFooter" class="doc-region doc-footer" contenteditable="true" role="textbox" aria-label="Page footer" data-placeholder="Type a page footer…">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_footer) !!}</div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-3 small text-muted mt-2 justify-content-between">
        <div><span id="wordCount">0</span> words · <span id="charCount">0</span> characters</div>
        <div>
            @if($document->content_updated_at)
                Last saved {{ $document->content_updated_at->format('M d, Y H:i:s') }}{{ $document->contentEditor?->name ? ' by ' . $document->contentEditor->name : '' }}
            @else
                Not edited yet — your first change saves automatically.
            @endif
            · Autosaves while you type (Ctrl+S to save now)
        </div>
    </div>

    <input type="file" id="imageInput" accept="image/png,image/jpeg,image/gif,image/webp" class="d-none" aria-hidden="true" tabindex="-1">

    {{-- Find & replace dialog --}}
    <div class="modal fade" id="findReplaceModal" tabindex="-1" aria-labelledby="findReplaceLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="findReplaceLabel">Find and replace</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <input type="text" id="findInput" class="form-control form-control-sm" placeholder="Find…">
                    </div>
                    <div class="mb-2">
                        <input type="text" id="replaceInput" class="form-control form-control-sm" placeholder="Replace with…">
                    </div>
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
</div>
</div>

<style>
    #saveStatus[data-state="saved"]   { background:#e6f3ea; color:#00743F; }
    #saveStatus[data-state="unsaved"] { background:#fff3cd; color:#856404; }
    #saveStatus[data-state="saving"]  { background:#cff4fc; color:#055160; }
    #saveStatus[data-state="error"]   { background:#f8d7da; color:#842029; }
    #saveStatus .spin { animation: sd-spin 1s linear infinite; display: inline-block; }
    @keyframes sd-spin { to { transform: rotate(360deg); } }

    #editorToolbar { position: sticky; top: .5rem; z-index: 20; }
    .toolbar-sep { width: 1px; height: 1.4rem; background: var(--p3-card-border); margin: 0 .25rem; }
    .editor-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2rem; height: 2rem; padding: 0;
        background: transparent; color: inherit;
        border: 1px solid transparent; border-radius: .4rem;
        font-size: .95rem; cursor: pointer;
    }
    .editor-btn:hover { background: #e6f3ea; border-color: var(--p3-card-border); }
    .editor-btn.is-active { background: #00A868; border-color: #00A868; color: #fff; }
    #blockFormat { border-color: var(--p3-card-border); }

    .doc-paper { background: #ffffff; }
    .doc-region { outline: none; line-height: 1.7; font-size: 1rem; min-height: 1.5rem; position: relative; }
    .doc-region.is-empty::before {
        content: attr(data-placeholder); color: #9aa5a0; pointer-events: none;
    }
    .doc-body { min-height: 420px; }
    .doc-header, .doc-footer { min-height: 1.5rem; font-size: .95rem; }
    .doc-band { background: #fbfcfb; }
    .doc-band-header { border-bottom: 1px dashed var(--p3-card-border); }
    .doc-band-footer { border-top: 1px dashed var(--p3-card-border); }

    /* Content rendered by the editor */
    .doc-body h1 { font-size: 1.75rem; } .doc-body h2 { font-size: 1.45rem; }
    .doc-body h3 { font-size: 1.2rem; }  .doc-body h4 { font-size: 1.05rem; }
    .doc-body table, .doc-header table, .doc-footer table {
        border-collapse: collapse; width: 100%; margin: .75rem 0;
    }
    .doc-body td, .doc-body th, .doc-header td, .doc-header th, .doc-footer td, .doc-footer th {
        border: 1px solid #cfd8d3; padding: .4rem .6rem; min-width: 3rem;
    }
    .doc-body th, .doc-header th, .doc-footer th { background: #f1f6f3; font-weight: 600; }
    .doc-body img, .doc-header img, .doc-footer img { max-width: 100%; height: auto; border-radius: .35rem; }
    .doc-body blockquote { border-left: 3px solid #00A868; margin: .75rem 0; padding: .25rem 0 .25rem .9rem; color: #4c5a53; }
    .doc-body ul, .doc-body ol { padding-left: 1.4rem; }
    .doc-body hr { border: 0; border-top: 1px solid var(--p3-card-border); margin: 1rem 0; }
    .doc-body a, .doc-header a, .doc-footer a { color: #00743F; }

    [data-theme="dark"] .doc-paper { background: #141c18; }
    [data-theme="dark"] .doc-band { background: #182119; }
    [data-theme="dark"] .doc-body th { background: #1f2b24; }
    [data-theme="dark"] .doc-body td, [data-theme="dark"] .doc-body th { border-color: #2c3d34; }
    [data-theme="dark"] .doc-body blockquote { color: #b8c4bd; }
    [data-theme="dark"] .editor-btn:hover { background: #1f2b24; }
    [data-theme="dark"] .doc-region.is-empty::before { color: #6b7a72; }

    @media print {
        #editorToolbar, #saveNowBtn, #saveStatus, .stat-icon, #imageInput { display: none !important; }
    }
</style>

<script>
(function () {
    var saveUrl   = @json(route('documents.update-content', $document));
    var imageUrl  = @json(route('documents.content-images', $document));
    var docxUrl   = @json(route('documents.content-docx', $document));
    var pdfUrl    = @json(route('documents.content-pdf', $document));
    var templates = @json($templates);
    var csrf      = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var header = document.getElementById('docHeader');
    var body   = document.getElementById('docBody');
    var footer = document.getElementById('docFooter');
    var editors = [header, body, footer];
    var active = body;

    var statusEl    = document.getElementById('saveStatus');
    var statusText  = document.getElementById('saveStatusText');
    var wordCount   = document.getElementById('wordCount');
    var charCount   = document.getElementById('charCount');
    var toolbar     = document.getElementById('editorToolbar');
    var templateMap = {};
    (templates || []).forEach(function (t) { templateMap[t.id] = t.html; });
    var pageStyleEl = document.createElement('style');
    document.head.appendChild(pageStyleEl);

    var dirty = false, saving = false, saveTimer = null, retryTimer = null;
    var savedRange = null;

    var LIMITS = { content: 400000, header: 20000, footer: 20000 };

    /* ---------- editor plumbing ---------- */

    function isEmpty(el) {
        return el.textContent.trim() === '' && !el.querySelector('img, table, hr');
    }

    function refreshPlaceholder(el) {
        el.classList.toggle('is-empty', isEmpty(el));
    }

    function refreshCounts() {
        var text = body.innerText || '';
        var words = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
        wordCount.textContent = words;
        charCount.textContent = text.replace(/\s/g, '').length;
    }

    function setStatus(state, text) {
        statusEl.setAttribute('data-state', state);
        var icon = 'bi-check-circle';
        if (state === 'unsaved') icon = 'bi-pencil';
        if (state === 'saving')  icon = 'bi-arrow-repeat';
        if (state === 'error')   icon = 'bi-exclamation-triangle';
        statusEl.innerHTML = '<i class="bi ' + icon + ' me-1"></i><span id="saveStatusText"></span>';
        statusText = document.getElementById('saveStatusText');
        statusText.textContent = text;
        if (state === 'saving') {
            statusEl.querySelector('i').classList.add('spin');
        }
    }

    function markDirty() {
        dirty = true;
        setStatus('unsaved', 'Unsaved changes');
        scheduleSave();
        editors.forEach(refreshPlaceholder);
        refreshCounts();
    }

    editors.forEach(function (el) {
        // Empty documents render a stray <br>; normalize so the
        // placeholder shows up correctly.
        if (el.innerHTML.trim() === '' || el.innerHTML.trim() === '<br>') {
            el.innerHTML = '';
        }
        el.addEventListener('focus', function () { active = el; });
        el.addEventListener('input', markDirty);
        refreshPlaceholder(el);
    });
    try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
    refreshCounts();

    /* ---------- toolbar ---------- */

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

    document.addEventListener('selectionchange', function () {
        var sel = window.getSelection();
        if (!sel || !sel.rangeCount) return;
        var node = sel.anchorNode;
        var el = node && (node.nodeType === 1 ? node : node.parentElement);
        var host = el && el.closest ? el.closest('.doc-region') : null;
        if (host) {
            active = host;
            savedRange = sel.getRangeAt(0).cloneRange();
        }
        refreshToolbarState();
    });

    // Keep the caret in the editor when a toolbar button is pressed.
    toolbar.addEventListener('mousedown', function (e) {
        if (e.target.closest('button')) e.preventDefault();
    });

    toolbar.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-cmd]');
        if (!btn) return;
        exec(btn.getAttribute('data-cmd'));
    });

    document.getElementById('blockFormat').addEventListener('change', function () {
        exec('formatBlock', '<' + this.value + '>');
        this.value = 'p';
    });

    function refreshToolbarState() {
        ['bold', 'italic', 'underline', 'strikeThrough',
         'insertUnorderedList', 'insertOrderedList',
         'justifyLeft', 'justifyCenter', 'justifyRight'].forEach(function (cmd) {
            var btn = toolbar.querySelector('[data-cmd="' + cmd + '"]');
            if (!btn) return;
            var on = false;
            try { on = document.queryCommandState(cmd); } catch (e) {}
            btn.classList.toggle('is-active', on);
        });
    }

    /* ---------- page setup preview ---------- */

    var PAGE_DIMS = { a4: [210, 297], letter: [215.9, 279.4], legal: [215.9, 355.6] };
    var MARGIN_MM = { narrow: 12.7, normal: 25.4, wide: 50.8 };

    function applyPageSetup() {
        var paper = document.getElementById('pagePaper').value;
        var orientation = document.getElementById('pageOrientation').value;
        var margins = document.getElementById('pageMargins').value;
        var d = PAGE_DIMS[paper] || PAGE_DIMS.a4;
        var landscape = orientation === 'landscape';
        var w = landscape ? d[1] : d[0];
        var h = landscape ? d[0] : d[1];
        var m = MARGIN_MM[margins] || 25.4;
        pageStyleEl.textContent =
            '.doc-paper{max-width:' + w + 'mm;min-height:' + h + 'mm;padding:' + m + 'mm !important;box-sizing:border-box;}';
    }
    ['pagePaper', 'pageOrientation', 'pageMargins'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', function () {
            applyPageSetup();
            markDirty();
        });
    });
    applyPageSetup();

    /* ---------- font controls ---------- */

    function wrapSelection(style) {
        active.focus();
        var sel = window.getSelection();
        if (!sel || !sel.rangeCount || sel.isCollapsed) {
            // Apply to whole active editor's future typing by execCommand.
            try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
            return;
        }
        var range = sel.getRangeAt(0);
        var contents = range.extractContents();
        var span = document.createElement('span');
        span.setAttribute('style', style);
        span.appendChild(contents);
        range.insertNode(span);
        sel.removeAllRanges();
        var r = document.createRange();
        r.selectNodeContents(span);
        r.collapse(false);
        sel.addRange(r);
        markDirty();
    }

    document.getElementById('fontFamily').addEventListener('change', function () {
        if (this.value) {
            try { document.execCommand('fontName', false, this.value); } catch (e) {}
            normalizeFontTags();
            markDirty();
        }
        this.value = '';
    });
    document.getElementById('fontSize').addEventListener('change', function () {
        if (this.value) {
            wrapSelection('font-size: ' + this.value + 'pt');
        }
        this.value = '';
    });
    document.getElementById('textColor').addEventListener('input', function () {
        try { document.execCommand('foreColor', false, this.value); } catch (e) {}
        normalizeFontTags();
        markDirty();
    });
    document.getElementById('highlightColor').addEventListener('input', function () {
        try { document.execCommand('backColor', false, this.value); } catch (e) {}
        normalizeFontTags();
        markDirty();
    });

    function normalizeFontTags() {
        [header, body, footer].forEach(function (root) {
            root.querySelectorAll('font').forEach(function (font) {
                var span = document.createElement('span');
                var styles = [];
                if (font.getAttribute('face')) styles.push('font-family: ' + font.getAttribute('face'));
                var sizeMap = { 1: '8pt', 2: '10pt', 3: '12pt', 4: '14pt', 5: '18pt', 6: '24pt', 7: '36pt' };
                if (font.getAttribute('size')) {
                    styles.push('font-size: ' + (sizeMap[font.getAttribute('size')] || '12pt'));
                }
                if (font.getAttribute('color')) styles.push('color: ' + font.getAttribute('color'));
                if (styles.length) span.setAttribute('style', styles.join('; '));
                while (font.firstChild) span.appendChild(font.firstChild);
                if (font.parentNode) font.parentNode.replaceChild(span, font);
            });
        });
    }

    /* ---------- insert page number / date ---------- */

    document.getElementById('insertPageNumberBtn').addEventListener('click', function () {
        active.focus();
        try { document.execCommand('insertText', false, '{page}'); } catch (e) {}
        markDirty();
    });
    document.getElementById('insertDateBtn').addEventListener('click', function () {
        active.focus();
        try { document.execCommand('insertText', false, new Date().toLocaleDateString()); } catch (e) {}
        markDirty();
    });

    /* ---------- templates ---------- */

    document.getElementById('templateSelect').addEventListener('change', function () {
        var id = this.value;
        if (!id || !templateMap[id]) { this.value = ''; return; }
        if (!confirm('Replace the document body with this template?')) { this.value = ''; return; }
        body.innerHTML = templateMap[id];
        editors.forEach(refreshPlaceholder);
        refreshCounts();
        markDirty();
        this.value = '';
    });

    /* ---------- find & replace ---------- */

    var findModal = null;
    try { findModal = new bootstrap.Modal(document.getElementById('findReplaceModal')); } catch (e) {}

    document.getElementById('findReplaceBtn').addEventListener('click', function () {
        if (findModal) findModal.show();
        else document.getElementById('findReplaceModal').classList.add('show');
    });

    function escapeRegex(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    document.getElementById('findNextBtn').addEventListener('click', function () {
        var term = document.getElementById('findInput').value;
        if (!term) return;
        var mc = document.getElementById('matchCase').checked;
        var found = false;
        try { found = window.find(term, false, false, false, false, false, false); } catch (e) {}
        // window.find is case-insensitive by default in most browsers; matchCase ignored by API.
        document.getElementById('findStatus').textContent = found ? 'Found' : 'Not found';
    });

    document.getElementById('replaceBtn').addEventListener('click', function () {
        var term = document.getElementById('findInput').value;
        var repl = document.getElementById('replaceInput').value;
        if (!term) return;
        var mc = document.getElementById('matchCase').checked;
        var sel = window.getSelection();
        if (sel && sel.toString() && (mc ? sel.toString() === term : sel.toString().toLowerCase() === term.toLowerCase())) {
            try { document.execCommand('insertText', false, repl); } catch (e) {}
            markDirty();
            document.getElementById('findStatus').textContent = 'Replaced';
        } else {
            // find next first
            try { window.find(term, false, false, false, false, false, false); } catch (e) {}
            document.getElementById('findStatus').textContent = 'Select a match first';
        }
    });

    document.getElementById('replaceAllBtn').addEventListener('click', function () {
        var term = document.getElementById('findInput').value;
        var repl = document.getElementById('replaceInput').value;
        if (!term) return;
        var mc = document.getElementById('matchCase').checked;
        var flags = mc ? 'g' : 'gi';
        var re = new RegExp(escapeRegex(term), flags);
        var changed = false;
        [header, body, footer].forEach(function (root) {
            var before = root.innerHTML;
            root.innerHTML = root.innerHTML.replace(re, function () { changed = true; return repl.replace(/\$/g, '$$$$'); });
            if (root.innerHTML !== before) { changed = true; }
        });
        if (changed) {
            markDirty();
            document.getElementById('findStatus').textContent = 'Replaced all';
        } else {
            document.getElementById('findStatus').textContent = 'No matches';
        }
    });

    /* ---------- export (save first if dirty) ---------- */

    function exportAfterSave(url) {
        if (!dirty) { window.location.href = url; return; }
        save({ force: true }).then(function () { window.location.href = url; });
    }
    document.getElementById('exportDocxBtn').addEventListener('click', function (e) {
        e.preventDefault(); exportAfterSave(docxUrl);
    });
    document.getElementById('exportPdfBtn').addEventListener('click', function (e) {
        e.preventDefault(); exportAfterSave(pdfUrl);
    });

    /* ---------- insert link ---------- */

    document.getElementById('linkBtn').addEventListener('click', function () {
        var url = prompt('Link address (https://…, mailto:… or /path):', 'https://');
        if (url === null) return;
        url = url.trim();
        if (url === '') return;
        if (!/^(https?:|mailto:|tel:|\/|#)/i.test(url)) {
            alert('Please enter a valid link starting with https://, mailto: or /');
            return;
        }
        exec('createLink', url);
    });

    /* ---------- insert table ---------- */

    document.getElementById('tableBtn').addEventListener('click', function () {
        var size = prompt('Table size — rows x columns (e.g. 3x4):', '3x4');
        if (size === null) return;
        var match = /^(\d{1,2})\s*[x×]\s*(\d{1,2})$/.exec(size.trim());
        if (!match) { alert('Please enter a size like 3x4 (max 20x10).'); return; }
        var rows = Math.min(parseInt(match[1], 10), 20);
        var cols = Math.min(parseInt(match[2], 10), 10);
        if (rows < 1 || cols < 1) return;

        var html = '<table><thead><tr>';
        for (var c = 0; c < cols; c++) html += '<th>&nbsp;</th>';
        html += '</tr></thead><tbody>';
        for (var r = 1; r < rows; r++) {
            html += '<tr>';
            for (var i = 0; i < cols; i++) html += '<td>&nbsp;</td>';
            html += '</tr>';
        }
        html += '</tbody></table><p><br></p>';
        exec('insertHTML', html);
    });

    /* ---------- insert image ---------- */

    var imageInput = document.getElementById('imageInput');
    document.getElementById('imageBtn').addEventListener('click', function () {
        imageInput.value = '';
        imageInput.click();
    });

    imageInput.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        if (!/^image\/(png|jpeg|gif|webp)$/.test(file.type)) {
            alert('Only PNG, JPEG, GIF or WebP images can be inserted.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('Images must be 5 MB or smaller.');
            return;
        }

        var fd = new FormData();
        fd.append('image', file);

        fetch(imageUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: fd,
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw new Error(data.message || 'Upload failed.');
                return data;
            });
        }).then(function (data) {
            var alt = (file.name || 'image').replace(/["<>]/g, '');
            exec('insertHTML', '<img src="' + data.url + '" alt="' + alt + '"><p><br></p>');
        }).catch(function (err) {
            alert('Image upload failed: ' + err.message);
        });
    });

    /* ---------- autosave ---------- */

    function collect() {
        return {
            content: body.innerHTML,
            header: header.innerHTML,
            footer: footer.innerHTML,
            page_paper: document.getElementById('pagePaper').value,
            page_orientation: document.getElementById('pageOrientation').value,
            page_margins: document.getElementById('pageMargins').value
        };
    }

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () { save(); }, 1200);
    }

    function save(options) {
        options = options || {};
        if (saving) {
            // A save is already in flight — queue this one instead of
            // dropping it (the in-flight save clears `dirty` only on success).
            scheduleSave();
            return Promise.resolve();
        }
        if (!dirty && !options.force) return Promise.resolve();

        normalizeFontTags();
        var payload = collect();
        if (payload.content.length > LIMITS.content ||
            payload.header.length > LIMITS.header ||
            payload.footer.length > LIMITS.footer) {
            setStatus('error', 'Document too large to save — remove some content.');
            return Promise.resolve();
        }

        var failed = false;
        saving = true;
        setStatus('saving', 'Saving…');

        return fetch(saveUrl, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
            keepalive: !!options.keepalive
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) throw new Error(data.message || 'Save failed (' + res.status + ').');
                dirty = false;
                setStatus('saved', 'Saved ' + data.saved_at);
            });
        }).catch(function (err) {
            failed = true;
            setStatus('error', err.message + ' Retrying…');
            clearTimeout(retryTimer);
            retryTimer = setTimeout(function () { save(); }, 8000);
        }).finally(function () {
            saving = false;
            // Anything typed while the request was in flight still needs a save.
            if (dirty && !failed) scheduleSave();
        });
    }

    document.getElementById('saveNowBtn').addEventListener('click', function () {
        clearTimeout(saveTimer);
        save({ force: true });
    });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            clearTimeout(saveTimer);
            save({ force: true });
        }
    });

    // Save as soon as the tab goes away, and warn on a hard unload while
    // a save is still in flight. The hidden-tab save uses a regular fetch
    // (keepalive is capped at ~64 KB in browsers, below our content limit);
    // keepalive is reserved for the unload attempt.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden' && dirty) {
            clearTimeout(saveTimer);
            save({ force: true });
        }
    });

    window.addEventListener('beforeunload', function (e) {
        if (dirty) {
            save({ force: true, keepalive: true });
            e.preventDefault();
            e.returnValue = '';
        }
    });

    setStatus('saved', @json($document->content_updated_at ? 'Saved ' . $document->content_updated_at->format('M d, Y H:i:s') : 'Not edited yet'));
})();
</script>
@endsection



