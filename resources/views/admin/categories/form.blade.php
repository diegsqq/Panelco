@extends('layouts.app')
@section('content')
<h4>{{ isset($category) ? 'Edit Category' : 'New Category' }}</h4>
<div class="card card-p3 p-4">
<form method="POST" action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
@csrf @if(isset($category)) @method('PUT') @endif
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $category->description ?? '') }}</textarea></div>
<div class="mb-3">
<label class="form-label" for="keywordsField">AI keywords <span class="text-muted fw-normal">(comma-separated, used by rule + AI category suggestions)</span></label>
<div class="input-group">
<input name="keywords" id="keywordsField" class="form-control" value="{{ old('keywords', $category->keywords ?? '') }}" placeholder="maintenance, repair, transformer">
<button type="button" class="btn btn-outline-success" id="aiKwBtn" title="Generate keywords with AI"><i class="bi bi-magic me-1"></i>Generate with AI</button>
</div>
<div class="form-text" id="aiKwMsg">Matching is case-insensitive substring search over extracted text.</div>
</div>
<button class="btn" style="background:#00A868;color:#fff">Save</button>
<a href="{{ route('admin.categories.index') }}" class="btn btn-link">Cancel</a>
</form>
@if(isset($category))
<form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="mt-3" onsubmit="return confirm('Delete this category? Documents keep their files; links are cleared.')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete category</button></form>
@endif
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
