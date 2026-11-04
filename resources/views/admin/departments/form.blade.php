@extends('layouts.app')
@section('content')
<h4>{{ isset($department) ? 'Edit Department' : 'New Department' }}</h4>
<p class="text-muted">Use the approved PANELCO III department name or acronym exactly as provided.</p>
<div class="card card-p3 p-4">
<form method="POST" action="{{ isset($department) ? route('admin.departments.update', $department) : route('admin.departments.store') }}">
@csrf @if(isset($department)) @method('PUT') @endif
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $department->name ?? '') }}" required></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $department->description ?? '') }}</textarea></div>
<div class="mb-3"><label class="form-label">Classification keywords</label><input name="keywords" class="form-control" value="{{ old('keywords', $department->keywords ?? '') }}" placeholder="comma-separated terms or phrases"><div class="form-text">Used to suggest this department from document content. Use terms that clearly identify work handled by this unit.</div></div>
<button class="btn" style="background:#ffffff;color:#00A868;border:1px solid #00A868">Save</button>
<a href="{{ route('admin.departments.index') }}" class="btn btn-link">Cancel</a>
</form>
@if(isset($department))
<form method="POST" action="{{ route('admin.departments.destroy', $department) }}" class="mt-3" onsubmit="return confirm('Delete this department? Departments assigned to users or documents cannot be deleted.')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete department</button></form>
@endif
</div>
@endsection
