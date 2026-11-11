@extends('layouts.app')
@section('content')
<style>
    /* ── Upload page shell ── */
    .up-page { width: 100%; min-height: 100%; }

    /* ── Header ── */
    .up-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
    .up-head-info { display: flex; align-items: center; gap: 1rem; flex: 1 1 auto; }
    .up-head-ico { width: 52px; height: 52px; flex: 0 0 52px; border-radius: 1rem; background: #e6f3ea; color: #00A868; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    [data-theme="dark"] .up-head-ico { background: #1d2b24; color: #7fe0ac; }
    .up-head-title { font-size: 1.75rem; font-weight: 800; color: #111; line-height: 1.2; margin: 0; }
    [data-theme="dark"] .up-head-title { color: #fff; }
    .up-head-sub { color: #5a9e7a; font-size: .875rem; margin: .22rem 0 0; line-height: 1.45; }
    [data-theme="dark"] .up-head-sub { color: #8fa59a; }

    /* ── Action row ── */
    .up-actions { display: flex; align-items: center; gap: .75rem; margin-top: .25rem; width: 100%; }
    .up-btn-upload { display: inline-flex; align-items: center; gap: .5rem; background: #00A868; color: #fff; border: none; border-radius: .75rem; padding: .65rem 1.25rem; font-size: .95rem; font-weight: 700; text-decoration: none; transition: all .15s ease; box-shadow: 0 2px 8px rgba(0,168,104,.18); }
    .up-btn-upload:hover { background: #00743F; color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,168,104,.22); }
    .up-btn-upload:active { transform: none; }
    .up-btn-upload:disabled { opacity: .65; cursor: not-allowed; transform: none; }

    /* ── File drop zone ── */
    .up-zone { border: 2px dashed #b9dfc6; border-radius: 1rem; background: #f7fbf8; transition: all .2s ease; cursor: pointer; min-height: 240px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 2.5rem 1.5rem; }
    [data-theme="dark"] .up-zone { background: #16211c; border-color: #26362e; }
    .up-zone:hover { border-color: #00A868; background: #f0f9f3; }
    [data-theme="dark"] .up-zone:hover { border-color: #2f6b4f; background: #1d2b24; }
    .up-zone:focus-visible { outline: 3px solid #00743F; outline-offset: 3px; }
    .up-zone.dragging { border-color: #00A868; background: #e6f3ea; box-shadow: 0 0 0 4px rgba(0,168,104,.12); }
    [data-theme="dark"] .up-zone.dragging { background: #12291d; box-shadow: 0 0 0 4px rgba(0,168,104,.18); }
    .up-zone.filled { border-style: solid; border-color: #cfe9d7; background: #fff; cursor: default; }
    [data-theme="dark"] .up-zone.filled { background: #1d2b24; border-color: #2f6b4f; }
    .up-zone .up-idle-icon { width: 64px; height: 64px; border-radius: 18px; background: #e6f3ea; color: #00A868; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; transition: transform .2s ease; }
    [data-theme="dark"] .up-zone .up-idle-icon { background: #1d2b24; color: #7fe0ac; }
    .up-zone:hover .up-idle-icon { transform: translateY(-3px); }
    .up-zone-title { font-weight: 700; font-size: 1rem; color: #111; margin: 0 0 .25rem; }
    [data-theme="dark"] .up-zone-title { color: #e5eee8; }
    .up-zone-sub { color: #5a9e7a; font-size: .875rem; margin: 0 0 .5rem; }
    [data-theme="dark"] .up-zone-sub { color: #8fa59a; }
    .up-zone-limits { font-size: .72rem; color: #9a9ab0; }
    [data-theme="dark"] .up-zone-limits { color: #7d9087; }

    /* ── File tile ── */
    .up-file-tile { display: flex; align-items: center; gap: 1rem; padding: .7rem .9rem; background: #f0f9f3; border-radius: .8rem; margin: 0 auto; max-width: 100%; }
    [data-theme="dark"] .up-file-tile { background: #1d2b24; }
    .up-file-icon { width: 48px; height: 48px; flex: 0 0 48px; border-radius: 14px; background: #fff; color: #00A868; display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    [data-theme="dark"] .up-file-icon { background: #243324; }
    .up-file-info { flex: 1 1 auto; min-width: 0; }
    .up-file-name { font-weight: 700; font-size: .95rem; color: #1a1a2e; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; line-height: 1.3; }
    [data-theme="dark"] .up-file-name { color: #e5eee8; }
    .up-file-meta { font-size: .72rem; color: #5a9e7a; margin-top: .1rem; }
    [data-theme="dark"] .up-file-meta { color: #8fa59a; }
    .up-remove { border: none; background: #f1f3f5; color: #6c757d; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 28px; transition: all .15s ease; }
    [data-theme="dark"] .up-remove { background: #243324; color: #8fa59a; }
    .up-remove:hover { background: #f8d7da; color: #721c24; }
    [data-theme="dark"] .up-remove:hover { background: #3a1616; color: #f1a8a8; }

    /* ── Analyzing line ── */
    .up-analyzing { font-size: .76rem; color: #00743F; margin-top: .6rem; display: flex; align-items: center; gap: .5rem; }

    /* ── Progress ── */
    .up-progress { height: 4px; background: #e6f3ea; border-radius: 2px; overflow: hidden; opacity: 0; transition: opacity .2s ease; margin-top: .75rem; }
    .up-progress.on { opacity: 1; }
    .up-progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #00A868, #1DBF7D); border-radius: 2px; transition: width .3s ease; }

    /* ── Meta cards ── */
    .up-card-group { width: 100%; margin-top: 1.25rem; }
    .up-card-title { font-weight: 700; font-size: 1rem; color: #1a1a2e; margin: 0 0 .85rem; }
    [data-theme="dark"] .up-card-title { color: #e5eee8; }
    .up-meta-card { border: 1px solid #dfe8e9; border-radius: .85rem; padding: 1rem 1.25rem; background: #fff; transition: border-color .2s ease, box-shadow .2s ease; }
    .up-meta-card.suggested { border-color: #00A868; box-shadow: 0 0 0 3px rgba(0,168,104,.08); background: #fbfefe; }
    [data-theme="dark"] .up-meta-card { background: #16211c; border-color: #26362e; }
    [data-theme="dark"] .up-meta-card.suggested { background: #12291d; border-color: #00743F; }
    .up-card-label { display: flex; align-items: center; gap: .5rem; font-weight: 600; font-size: .8rem; color: #1a1a2e; margin-bottom: .45rem; cursor: pointer; }
    [data-theme="dark"] .up-card-label { color: #e5eee8; }
    .up-card-label .badge-suggested { font-size: .62rem; letter-spacing: .3px; background: #ffffff; color: #00743F; border: 1px solid #00A868; border-radius: 1rem; padding: .12rem .45rem; font-weight: 700; text-transform: uppercase; }
    [data-theme="dark"] .up-card-label .badge-suggested { background: #12291d; color: #7fe0ac; border-color: #00A868; }
    .up-card-label span.text-muted { font-weight: 400; font-size: .7rem; color: #6c757d; }
    [data-theme="dark"] .up-card-label span.text-muted { color: #8fa59a; }

    .up-field-input { border: 0; outline: none; background: transparent; font-size: 1rem; color: inherit; width: 100%; }
    .up-field-input::placeholder { color: #9a9ab0; }
    [data-theme="dark"] .up-field-input::placeholder { color: #7d9087; }
    .up-select { border: 0; outline: none; background: transparent; font-size: .95rem; color: inherit; width: 100%; -webkit-appearance: none; -moz-appearance: none; appearance: none; cursor: pointer; }
    .up-select option { background: inherit; color: inherit; }

    .up-card-hint { font-size: .7rem; color: #5a9e7a; margin-top: .4rem; line-height: 1.4; }
    [data-theme="dark"] .up-card-hint { color: #8fa59a; }

    /* ── Form text / notes ── */
    .up-note { font-size: .72rem; color: #9a9ab0; margin-top: .5rem; animation: up-fade .3s ease; }
    [data-theme="dark"] .up-note { color: #7d9087; }
    .up-note.error { color: #dc3545; }
    .up-note.success { color: #00743F; }

    /* ── Details hint ── */
    .up-details-hint { font-size: .78rem; color: #5a9e7a; margin-bottom: .75rem; line-height: 1.5; }
    [data-theme="dark"] .up-details-hint { color: #8fa59a; }
    .up-details-hint .stars { color: #00A868; }

    @keyframes up-fade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
</style>

<div class="up-page">

    <form id="uploadForm" method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
    @csrf

    {{-- ── Header + upload button on the right ── --}}
    <div class="up-head">
        <div class="up-head-info">
            <span class="up-head-ico"><i class="bi bi-cloud-arrow-up-fill"></i></span>
            <div>
                <h1 class="up-head-title">Upload a document</h1>
                <p class="up-head-sub">Title, category, and date are suggested automatically — edit anything before uploading.</p>
            </div>
        </div>
        <button class="up-btn-upload" id="submitBtn" type="submit" form="uploadForm" disabled>
            <i class="bi bi-cloud-upload me-1"></i>Upload &amp; Queue
        </button>
    </div>

    {{-- ── Step 1: file picker --}}
    <div class="card card-p3 p-4 up-card-group">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h6 class="fw-bold mb-0" style="color:#00743F">Choose a file</h6>
        </div>

        <div id="dropZone" class="up-zone p-4 text-center" role="button" tabindex="0" aria-label="Choose files to upload" aria-describedby="uploadInstructions">
            <div id="zoneIdle">
                <div class="up-idle-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                <p class="up-zone-title">Drag &amp; drop your file here</p>
                <p class="up-zone-sub">or <span style="color:#00743F;text-decoration:underline;cursor:pointer" onclick="document.getElementById('file').click()">browse from your computer</span></p>
                <p class="up-zone-limits" id="uploadInstructions">PDF · DOCX · TXT · PNG · JPEG — up to {{ number_format(config('smartdocs.upload.max_kb', 51200) / 1024, 0) }} MB. You can select multiple files.</p>
            </div>
            <div id="zoneFile" class="d-none">
                <div class="up-file-tile">
                    <span class="up-file-icon" id="fileIcon"><i class="bi bi-file-earmark-fill"></i></span>
                    <div class="up-file-info">
                        <div class="up-file-name" id="fileName"></div>
                        <div class="up-file-meta" id="fileMeta"></div>
                        <div class="up-analyzing d-none" id="analyzingLine" role="status" aria-live="polite" aria-atomic="true">
                            <span class="spinner-border spinner-border-sm me-1" style="width:.8rem;height:.8rem"></span>
                            <span id="analyzingText">Reading document and suggesting details…</span>
                        </div>
                    </div>
                    <button type="button" class="up-remove" id="removeFile" title="Remove file" aria-label="Remove file"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>

        <div class="up-progress mt-3" id="upProgress"><div class="up-progress-bar"></div></div>

        <input id="file" name="file[]" type="file" class="d-none @error('file') is-invalid @enderror" accept=".pdf,.docx,.txt,.png,.jpg,.jpeg" multiple required>
        <input type="hidden" name="file_last_modified" id="fileLastModified" value="">
        @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text mt-3" id="suggestNote" role="status" aria-live="polite" aria-atomic="true"></div>
    </div>

    {{-- ── Step 2: details --}}
    <div class="card card-p3 p-4 up-card-group mt-3">
        <div class="up-head" style="margin-bottom:.9rem">
            <div class="up-head-info">
                <span class="up-head-ico" style="width:34px;height:34px;font-size:1.1rem"><i class="bi bi-file-earmark-text"></i></span>
                <div>
                    <h2 class="up-head-title" style="font-size:1.15rem;margin:0">Document details</h2>
                    <p class="up-head-sub" style="margin:0">— all optional, all editable</p>
                </div>
            </div>
        </div>
        <p class="up-details-hint" id="detailsHint">Pick a file first and SmartDocs will fill these in for you.</p>

        <div class="row g-3">
            {{-- Title --}}
            <div class="col-md-3">
                <div class="mb-3 up-meta-card h-100" id="cardTitle">
                    <label class="up-card-label" for="title">
                        Title <span class="badge-suggested d-none" id="badgeTitle"><i class="bi bi-stars me-1"></i>suggested</span>
                    </label>
                    <input id="title" name="title" class="up-field-input border-0 shadow-none p-0" style="font-size:1rem" value="{{ old('title') }}" maxlength="255" placeholder="Leave blank to use the filename">
                </div>
            </div>

            {{-- Category --}}
            <div class="col-md-3">
                <div class="up-meta-card h-100" id="cardCategory">
                    <label class="up-card-label" for="category_id">
                        Category <span class="badge-suggested d-none" id="badgeCategory"><i class="bi bi-stars me-1"></i>suggested</span>
                    </label>
                    <select id="category_id" name="category_id" class="up-select border-0 shadow-none p-0" style="background:transparent">
                        <option value="">— None —</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}" {{ (string) old('category_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
            </div>

            {{-- Access level --}}
            <div class="col-md-3">
                <div class="up-meta-card h-100" id="cardAccess">
                    <label class="up-card-label" for="access_level">
                        Access level <span class="text-muted small fw-normal">Who can see this document</span>
                    </label>
                    <select id="access_level" name="access_level" class="up-select border-0 shadow-none p-0" style="background:transparent">
                        <option value="private" {{ old('access_level', 'private') === 'private' ? 'selected' : '' }}>Only you</option>
                        <option value="link" {{ old('access_level') === 'link' ? 'selected' : '' }}>Anyone with link</option>
                        <option value="all_employees" {{ old('access_level') === 'all_employees' ? 'selected' : '' }}>All employees</option>
                    </select>
                    <div class="up-card-hint mt-2">Private / Link / All employees</div>
                </div>
            </div>

            {{-- Date --}}
            <div class="col-md-3">
                <div class="up-meta-card h-100" id="cardDate">
                    <label class="up-card-label" for="document_date">
                        Document date <span class="badge-suggested d-none" id="badgeDate"><i class="bi bi-stars me-1"></i>suggested</span>
                    </label>
                    <input id="document_date" name="document_date" type="date" class="up-field-input border-0 shadow-none p-0" style="font-size:.9rem" value="{{ old('document_date') }}">
                </div>
            </div>
        </div>
    </div>

    </form>

</div>

<script>
(function () {
    var zone = document.getElementById('dropZone');
    var input = document.getElementById('file');
    var idle = document.getElementById('zoneIdle');
    var fileBox = document.getElementById('zoneFile');
    var fileName = document.getElementById('fileName');
    var fileMeta = document.getElementById('fileMeta');
    var fileIcon = document.getElementById('fileIcon');
    var removeBtn = document.getElementById('removeFile');
    var progress = document.getElementById('upProgress');
    var analyzing = document.getElementById('analyzingLine');
    var suggestNote = document.getElementById('suggestNote');
    var detailsHint = document.getElementById('detailsHint');
    var submitBtn = document.getElementById('submitBtn');

    var titleInput = document.getElementById('title');
    var categorySel = document.getElementById('category_id');
    var dateInput = document.getElementById('document_date');

    var ICONS = {
        pdf:  ['bi-file-earmark-pdf-fill',  '#dc3545', '#fdeaea'],
        docx: ['bi-file-earmark-word-fill', '#0d6efd', '#e7f0ff'],
        txt:  ['bi-file-earmark-text-fill', '#6c757d', '#f1f3f5'],
        png:  ['bi-file-earmark-image-fill', '#00A868', '#e6f3ea'],
        jpg:  ['bi-file-earmark-image-fill', '#00A868', '#e6f3ea'],
        jpeg: ['bi-file-earmark-image-fill', '#00A868', '#e6f3ea']
    };
    var MAX_BYTES = {{ (int) config('smartdocs.upload.max_kb', 51200) * 1024 }};
    var suggestUrl = @json(route('documents.suggest'));
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var req = null;
    var seq = 0;

    // Track manual edits so re-analysis never stomps user input.
    var touched = { title: false, category: false, date: false };
    [['input', titleInput, 'title'], ['change', categorySel, 'category'], ['change', dateInput, 'date']].forEach(function (t) {
        t[1].addEventListener(t[0], function () { touched[t[2]] = true; });
    });

    function ext(name) { var p = name.split('.'); return p.length > 1 ? p.pop().toLowerCase() : ''; }
    function humanSize(bytes) { return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : (bytes / 1024).toFixed(1) + ' KB'; }

    function setBadges(on) {
        ['badgeTitle', 'badgeCategory', 'badgeDate'].forEach(function (id) {
            document.getElementById(id).classList.toggle('d-none', !on);
        });
        ['cardTitle', 'cardCategory', 'cardDate'].forEach(function (id) {
            document.getElementById(id).classList.toggle('suggested', on);
        });
    }

    function clearBadges() {
        setBadges(false);
        touched = { title: false, category: false, date: false };
    }

    function showFile(file) {
        var e = ext(file.name);
        var ic = ICONS[e] || ['bi-file-earmark-fill', '#00A868', '#e6f3ea'];
        fileIcon.className = 'up-file-icon bi ' + ic[0];
        fileIcon.style.color = ic[1];
        fileIcon.style.background = ic[2];
        fileName.textContent = file.name;
        fileMeta.textContent = humanSize(file.size) + ' · ' + e.toUpperCase();
        idle.classList.add('d-none');
        fileBox.classList.remove('d-none');
        zone.classList.add('filled');
        clearBadges();
        suggestNote.textContent = '';
        submitBtn.disabled = false;
    }

    function resetZone() {
        if (req) { req.abort(); req = null; }
        input.value = '';
        document.getElementById('fileLastModified').value = '';
        idle.classList.remove('d-none');
        fileBox.classList.add('d-none');
        zone.classList.remove('filled');
        analyzing.classList.add('d-none');
        progress.classList.remove('on');
        suggestNote.textContent = '';
        detailsHint.textContent = 'Pick a file first and SmartDocs will fill these in for you.';
        setBadges(false);
        submitBtn.disabled = true;
    }

    function applySuggestions(data) {
        var filled = [];
        if (data.title && !touched.title && !titleInput.value.trim()) { titleInput.value = data.title; filled.push('title'); }
        if (data.category_id && !touched.category && !categorySel.value) {
            categorySel.value = String(data.category_id);
            categorySel.classList.add('p3-chosen');
            filled.push('category');
        }
        if (data.document_date && !touched.date && !dateInput.value) { dateInput.value = data.document_date; filled.push('date'); }

        if (filled.length) {
            setBadges(true);
            detailsHint.innerHTML = '<span class="up-hint d-inline-block"><i class="bi bi-stars me-1" style="color:#00A868"></i>Suggested from the document — click any field to edit.</span>';
        } else {
            detailsHint.textContent = 'No suggestions (or you already edited a field). You can still fill these in manually.';
        }
        suggestNote.textContent = data.text_found
            ? 'Details read from the file contents.'
            : 'Contents could not be read — fill in details manually if you like.';
    }

    function analyze(file) {
        if (file.size > MAX_BYTES) {
            suggestNote.innerHTML = '<span class="up-note error">File is larger than ' + humanSize(MAX_BYTES) + ' and will be rejected.</span>';
            return;
        }
        if (req) req.abort();
        var mySeq = ++seq;
        var fd = new FormData();
        fd.append('file', file);
        if (file.lastModified) fd.append('file_last_modified', String(file.lastModified));

        analyzing.classList.remove('d-none');
        progress.classList.add('on');
        detailsHint.textContent = 'Reading document and suggesting details…';

        req = fetch(suggestUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                if (mySeq !== seq) return;
                if (res.ok) { applySuggestions(res.body); }
                else {
                    var msg = (res.body && res.body.message) || 'Could not analyze this file.';
                    if (res.body && res.body.errors && res.body.errors.file) msg = res.body.errors.file[0];
                    suggestNote.innerHTML = '<span class="up-note error">' + msg + '</span>';
                    detailsHint.textContent = 'Fill in the details manually (optional).';
                }
            })
            .catch(function () {
                if (mySeq !== seq) return;
                suggestNote.innerHTML = '<span class="up-note">Could not reach the server for suggestions — you can still upload.</span>';
                detailsHint.textContent = 'Fill in the details manually (optional).';
            })
            .then(function () {
                if (mySeq !== seq) return;
                analyzing.classList.add('d-none');
                progress.classList.remove('on');
                req = null;
            });
    }

    function accept(file) {
        if (!file) return;
        document.getElementById('fileLastModified').value = file.lastModified ? String(file.lastModified) : '';
        showFile(file);
        analyze(file);
    }

    zone.addEventListener('click', function (e) { if (e.target !== removeBtn && !removeBtn.contains(e.target)) input.click(); });
    zone.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
    input.addEventListener('change', function () {
        if (!input.files.length) return;
        accept(input.files[0]);
        if (input.files.length > 1) {
            fileMeta.textContent += ' · +' + (input.files.length - 1) + ' more file(s)';
        }
    });
    removeBtn.addEventListener('click', function (e) { e.stopPropagation(); resetZone(); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('dragging'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('dragging'); });
    });
    zone.addEventListener('drop', function (e) {
        if (!e.dataTransfer.files.length) return;
        var dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    });

    var form = document.getElementById('uploadForm');
    if (form) form.addEventListener('submit', function (e) {
        if (!input.files.length) { e.preventDefault(); return; }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading…';
    });
})();
</script>
@endsection
