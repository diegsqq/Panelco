@extends('layouts.app')
@section('content')
<div class="mb-3">
    <h4 class="mb-0 fw-bold"><i class="bi bi-tag-fill text-success me-2"></i>Edit Category</h4>
    <small class="text-muted">Update how documents are classified into “{{ $category->name }}”.</small>
</div>
<div class="card card-p3 p-4">
<form method="POST" action="{{ route('admin.categories.update', $category) }}">
@csrf @method('PUT')
<div class="mb-3">
    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
    <input name="name" class="form-control" value="{{ old('name', $category->name) }}" placeholder="e.g. Maintenance" required>
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">Description</label>
    <textarea name="description" class="form-control" rows="3" placeholder="What kind of documents belong to this category?">{{ old('description', $category->description) }}</textarea>
</div>
<div class="mb-4">
    <label class="form-label fw-semibold" for="keywordsField">Keywords <span class="text-muted fw-normal">(comma-separated, used by rule suggestions)</span></label>
    <div class="input-group">
        <input name="keywords" id="keywordsField" class="form-control" value="{{ old('keywords', $category->keywords) }}" placeholder="maintenance, repair, transformer">
        <button type="button" class="btn btn-outline-success" id="aiKwBtn" title="Generate keywords with AI"><i class="bi bi-magic me-1"></i>Generate with AI</button>
    </div>
    <div class="form-text" id="aiKwMsg">Matching is case-insensitive substring search over extracted text.</div>
</div>
<div class="d-flex gap-2">
    <button class="btn px-4" style="background:#00CC33;color:#fff"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
<hr class="my-4">
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
    <div>
        <div class="fw-semibold text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Delete this category</div>
        <small class="text-muted">Documents keep their files; category links are cleared.</small>
    </div>
    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category? Documents keep their files; links are cleared.')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button></form>
</div>
</div>
<script>
(function () {
    var btn = document.getElementById('aiKwBtn');
    var field = document.getElementById('keywordsField');
    var msg = document.getElementById('aiKwMsg');
    var form = btn && btn.closest('form');
    if (!btn || !field || !form) return;
    btn.addEventListener('click', function () {
        var name = form.querySelector('[name="name"]');
        var desc = form.querySelector('[name="description"]');
        if (!name || !name.value.trim()) {
            msg.textContent = 'Enter the category name first, then generate.';
            name && name.focus();
            return;
        }
        var original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating…';
        msg.textContent = 'Asking AI for keywords…';
        var fd = new FormData();
        fd.append('name', name.value);
        fd.append('description', desc ? desc.value : '');
        fetch(@json(route('admin.categories.ai-keywords')), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: fd
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            btn.disabled = false;
            btn.innerHTML = original;
            if (!res.ok) {
                msg.textContent = (res.data && res.data.error) || 'AI request failed. Try again.';
                return;
            }
            field.value = res.data.keywords;
            msg.textContent = 'AI keywords filled — review them, then Save.';
        }).catch(function () {
            btn.disabled = false;
            btn.innerHTML = original;
            msg.textContent = 'AI request failed. Check connection and try again.';
        });
    });
})();
</script>
@endsection
