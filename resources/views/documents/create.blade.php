@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card card-p3 p-4">
<div class="d-flex align-items-center gap-3 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:#00CC33"><i class="bi bi-cloud-arrow-up-fill"></i></span>
<div><h4 class="fw-bold mb-0">Upload</h4><small class="text-muted">Stored privately and queued for processing.</small></div>
</div>
<form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
@csrf
<div class="mb-3">
<label class="form-label fw-semibold" for="file">File</label>
<div id="dropZone" class="border rounded-3 p-4 text-center" style="border-style:dashed !important;border-color:#00CC33 !important;background:#f2f7f3;cursor:pointer">
<i class="bi bi-cloud-arrow-up fs-2" style="color:#00CC33"></i>
<div class="fw-semibold">Drag &amp; drop your file here, or click to browse</div>
<small class="text-muted">PDF · DOCX · TXT · PNG · JPEG — max 20 MB</small>
<div id="fileName" class="mt-2 fw-semibold" style="color:#007A24"></div>
</div>
<input id="file" name="file" type="file" class="d-none @error('file') is-invalid @enderror" accept=".pdf,.docx,.txt,.png,.jpg,.jpeg" required>
@error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
<div class="mb-3"><label class="form-label fw-semibold" for="title">Title</label><input id="title" name="title" class="form-control" value="{{ old('title') }}" maxlength="255" placeholder="Leave blank to use the filename"></div>
<div class="row g-3">
<div class="col-md-6"><label class="form-label fw-semibold" for="category_id">Suggested category</label><select id="category_id" name="category_id" class="form-select"><option value="">— None yet —</option>@foreach ($categories as $c)<option value="{{ $c->id }}" {{ (string) old('category_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label fw-semibold" for="document_date">Document date</label><input id="document_date" name="document_date" type="date" class="form-control" value="{{ old('document_date') }}"></div>
</div>
<div class="form-text mt-2">Everything except the file is optional.</div>
<div class="alert mt-3 mb-0 small" style="background:#e6f3ea;color:#007A24"><i class="bi bi-shield-lock me-1"></i>Private storage · classification needs approval.</div>
<button class="btn mt-3" style="background:#00CC33;border-color:#00CC33;color:#fff;font-weight:600"><i class="bi bi-cloud-upload me-1"></i>Upload &amp; Queue</button>
<a href="{{ route('documents.library') }}" class="btn btn-link mt-3">Cancel</a>
</form>
</div>
</div>
</div>
<script>
(function () {
    var zone = document.getElementById('dropZone');
    var input = document.getElementById('file');
    var name = document.getElementById('fileName');
    if (!zone || !input) return;
    function show(file) {
        if (!file) return;
        name.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    }
    zone.addEventListener('click', function () { input.click(); });
    input.addEventListener('change', function () { show(input.files[0]); });
    ['dragenter', 'dragover'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.style.background = '#e6f3ea'; });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.style.background = '#f2f7f3'; });
    });
    zone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; show(input.files[0]); }
    });
})();
</script>
@endsection
