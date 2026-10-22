@extends('layouts.app')
@section('content')
<h4>{{ isset($department) ? 'Edit Department' : 'New Department' }}</h4>
<div class="card card-p3 p-4">
<form method="POST" action="{{ isset($department) ? route('admin.departments.update', $department) : route('admin.departments.store') }}">
@csrf @if(isset($department)) @method('PUT') @endif
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $department->name ?? '') }}" required></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $department->description ?? '') }}</textarea></div>
<button class="btn" style="background:#00CC33;color:#fff">Save</button>
<a href="{{ route('admin.departments.index') }}" class="btn btn-link">Cancel</a>
</form>
@if(isset($department))
<form method="POST" action="{{ route('admin.departments.destroy', $department) }}" class="mt-3" onsubmit="return confirm('Delete this department? Member links are cleared, files are kept.')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete department</button></form>
@endif
</div>
@endsection
