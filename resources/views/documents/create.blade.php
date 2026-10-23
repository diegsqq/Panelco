@extends('layouts.app')
@section('content')
<style>
    .up-zone { border: 2px dashed #b9dfc6; border-radius: 1rem; background: #f7fbf8; transition: all .2s ease; cursor: pointer; }
    .up-zone:hover { border-color: #00A868; background: #f0f9f3; }
    .up-zone.dragging { border-color: #00A868; background: #e6f3ea; box-shadow: 0 0 0 4px rgba(0,168,104,.12); }
    .up-zone.filled { border-style: solid; border-color: #cfe9d7; background: #fff; cursor: default; }
    .up-zone .up-idle-icon { width: 64px; height: 64px; border-radius: 18px; background: #e6f3ea; color: #00A868; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto; transition: transform .2s ease; }
    .up-zone:hover .up-idle-icon { transform: translateY(-3px); }
    .up-file-tile { display: flex; align-items: center; gap: .9rem; }
    .up-file-icon { width: 52px; height: 52px; flex: 0 0 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .up-meta-card { border: 1px solid var(--p3-card-border, #d5e8db); border-radius: .9rem; padding: .85rem 1rem; background: #fff; transition: border-color .2s ease, box-shadow .2s ease; }
    .up-meta-card.suggested { border-color: #9ed9b3; box-shadow: 0 0 0 3px rgba(0,168,104,.08); }
    .up-auto-badge { font-size: .65rem; letter-spacing: .3px; background: #e6f3ea; color: #00743F; border-radius: 1rem; padding: .15rem .5rem; font-weight: 700; text-transform: uppercase; vertical-align: middle; }
    .up-progress { height: 3px; background: #e6f3ea; border-radius: 2px; overflow: hidden; opacity: 0; transition: opacity .2s ease; }
    .up-progress.on { opacity: 1; }
    .up-progress-bar { height: 100%; width: 40%; background: linear-gradient(90deg, #00A868, #1DBF7D); border-radius: 2px; animation: up-slide 1s ease-in-out infinite; }
    @keyframes up-slide { 0% { transform: translateX(-100%); } 100% { transform: translateX(350%); } }
    .up-analyzing { font-size: .78rem; color: #00743F; }
    .up-remove { border: none; background: #f1f3f5; color: #6c757d; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; transition: all .15s ease; }
    .up-remove:hover { background: #f8d7da; color: #721c24; }
    .up-field-input:focus { border-color: #00A868; }
    .up-hint { animation: up-fade .3s ease; }
    @keyframes up-fade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
</style>

<div class="row">
<div class="col-12">

    <div class="d-flex align-items-center gap-3 mb-3">
        <span class="stat-icon" style="background:#e6f3ea;color:#00A868;width:52px;height:52px;font-size:1.4rem"><i class="bi bi-cloud-arrow-up-fill"></i></span>
        <div>
            <h4 class="fw-bold mb-0">Upload a document</h4>
            <small class="text-muted">Title, category, and date are suggested automatically — edit anything before uploading.</small>
        </div>
    </div>

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" id="uploadForm">
        @csrf

        {{-- Step 1: file picker --}}
        <div class="card card-p3 p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="avatar avatar-user" style="width:26px;height:26px;font-size:.75rem">1</span>
                <h6 class="fw-bold mb-0">Choose a file</h6>
            </div>

            <div id="dropZone" class="up-zone p-4 text-center" role="button" tabindex="0" aria-label="Upload file">
                <div id="zoneIdle">
                    <div class="up-idle-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                    <div class="fw-semibold mt-3">Drag &amp; drop your file here</div>
                    <small class="text-muted">or <span style="color:#00743F;text-decoration:underline">browse from your computer</span></small>
                    <div class="mt-2"><small class="text-muted">PDF · DOCX · TXT · PNG · JPEG — up to {{ number_format(config('smartdocs.upload.max_kb', 51200) / 1024, 0) }} MB</small></div>
                </div>
                <div id="zoneFile" class="d-none text-start px-1">
                    <div class="up-file-tile">
                        <span class="up-file-icon" id="fileIcon"><i class="bi bi-file-earmark-fill"></i></span>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-semibold text-truncate" id="fileName" style="color:#00743F"></div>
                            <small class="text-muted" id="fileMeta"></small>
                            <div class="up-analyzing mt-1 d-none" id="analyzingLine">
                                <span class="spinner-border spinner-border-sm me-1" style="width:.8rem;height:.8rem"></span>
                                <span id="analyzingText">Reading document and suggesting details…</span>
                            </div>
                        </div>
                        <button type="button" class="up-remove" id="removeFile" title="Remove file" aria-label="Remove file"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>
            <div class="up-progress mt-2" id="upProgress"><div class="up-progress-bar"></div></div>

            <input id="file" name="file" type="file" class="d-none @error('file') is-invalid @enderror" accept=".pdf,.docx,.txt,.png,.jpg,.jpeg" required>
            <input type="hidden" name="file_last_modified" id="fileLastModified" value="">
            @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="form-text mt-2" id="suggestNote"></div>
        </div>

        {{-- Step 2: details --}}
        <div class="card card-p3 p-4 mt-3">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="avatar avatar-user" style="width:26px;height:26px;font-size:.75rem">2</span>
                <h6 class="fw-bold mb-0">Document details</h6>
                <span class="text-muted" style="font-size:.72rem">— all optional, all editable</span>
            </div>
            <p class="small text-muted mb-3" id="detailsHint">Pick a file first and SmartDocs will fill these in for you.</p>

            <div class="mb-3 up-meta-card" id="cardTitle">
                <label class="form-label fw-semibold mb-1" for="title">
                    Title <span class="up-auto-badge d-none" id="badgeTitle"><i class="bi bi-stars me-1"></i>suggested</span>
                </label>
                <input id="title" name="title" class="form-control up-field-input border-0 shadow-none p-0" style="font-size:1rem;background:transparent" value="{{ old('title') }}" maxlength="255" placeholder="Leave blank to use the filename">
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="up-meta-card h-100" id="cardCategory">
                        <label class="form-label fw-semibold mb-1" for="category_id">
                            Category <span class="up-auto-badge d-none" id="badgeCategory"><i class="bi bi-stars me-1"></i>suggested</span>
                        </label>
                        <select id="category_id" name="category_id" class="form-select up-field-input border-0 shadow-none p-0" style="background:transparent">
                            <option value="">— None —</option>
                            @foreach ($categories as $c)<option value="{{ $c->id }}" {{ (string) old('category_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="up-meta-card h-100" id="cardDate">
                        <label class="form-label fw-semibold mb-1" for="document_date">
                            Document date <span class="up-auto-badge d-none" id="badgeDate"><i class="bi bi-stars me-1"></i>suggested</span>
                        </label>
                        <input id="document_date" name="document_date" type="date" class="form-control up-field-input border-0 shadow-none p-0" style="background:transparent" value="{{ old('document_date') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card card-p3 p-3 mt-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="alert small mb-0 flex-grow-1 py-2" style="background:#e6f3ea;color:#00743F;border:none">
                    <i class="bi bi-shield-lock me-1"></i>Private storage · classification needs approval.
                </div>
                <a href="{{ route('documents.library') }}" class="btn btn-link text-decoration-none">Cancel</a>
                <button class="btn btn-p3 px-4" id="submitBtn"><i class="bi bi-cloud-upload me-1"></i>Upload &amp; Queue</button>
            </div>
        </div>
    </form>

</div>
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
    var form = document.getElementById('uploadForm');
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
            suggestNote.innerHTML = '<span class="text-danger">File is larger than ' + humanSize(MAX_BYTES) + ' and will be rejected.</span>';
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
                    suggestNote.innerHTML = '<span class="text-danger">' + msg + '</span>';
                    detailsHint.textContent = 'Fill in the details manually (optional).';
                }
            })
            .catch(function () {
                if (mySeq !== seq) return;
                suggestNote.innerHTML = '<span class="text-muted">Could not reach the server for suggestions — you can still upload.</span>';
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
        input.files = (function () {
            var dt = new DataTransfer();
            dt.items.add(file);
            return dt.files;
        })();
        document.getElementById('fileLastModified').value = file.lastModified ? String(file.lastModified) : '';
        showFile(file);
        analyze(file);
    }

    zone.addEventListener('click', function (e) { if (e.target !== removeBtn && !removeBtn.contains(e.target)) input.click(); });
    zone.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
    input.addEventListener('change', function () { accept(input.files[0]); });
    removeBtn.addEventListener('click', function (e) { e.stopPropagation(); resetZone(); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('dragging'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('dragging'); });
    });
    zone.addEventListener('drop', function (e) { if (e.dataTransfer.files.length) accept(e.dataTransfer.files[0]); });

    form.addEventListener('submit', function () {
        if (!input.files.length) return;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading…';
    });
})();
</script>
@endsection
