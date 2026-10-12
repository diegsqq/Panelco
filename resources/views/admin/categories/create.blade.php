@extends('layouts.app')
@section('content')
<h4>{{ isset($category) ? 'Edit Category' : 'New Category' }}</h4>
<div class="card card-p3 p-4">
<form method="POST" action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
@csrf @if(isset($category)) @method('PUT') @endif
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $category->description ?? '') }}</textarea></div>
<div class="mb-3"><label class="form-label">Keywords (comma-separated, used by rule suggestions)</label><input name="keywords" class="form-control" value="{{ old('keywords', $category->keywords ?? '') }}" placeholder="maintenance, repair, transformer"><div class="form-text">Matching is case-insensitive substring search over extracted text.</div></div>
<button class="btn" style="background:#009040;color:#fff">Save</button>
<a href="{{ route('admin.categories.index') }}" class="btn btn-link">Cancel</a>
</form>
@if(isset($category))
<form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="mt-3" onsubmit="return confirm('Delete this category? Documents keep their files; links are cleared.')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete category</button></form>
@endif
</div>
@endsection
