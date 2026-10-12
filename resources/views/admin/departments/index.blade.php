@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Departments</h4>
<a href="{{ route('admin.departments.create') }}" class="btn" style="background:#009040;color:#fff">New Department</a>
</div>
<div class="card card-p3 p-0 overflow-hidden">
<table class="table mb-0">
<thead class="table-light"><tr><th>Name</th><th>Description</th><th>Users</th><th>Documents</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
<tbody>
@forelse ($departments as $d)
<tr><td class="fw-semibold">{{ $d->name }}</td><td><small class="text-muted">{{ \Illuminate\Support\Str::limit($d->description ?: '—', 80) }}</small></td><td>{{ $d->users_count }}</td><td>{{ $d->documents_count }}</td><td class="text-end"><a href="{{ route('admin.departments.edit', $d) }}" class="btn btn-sm btn-outline-success">Edit</a></td></tr>
@empty
<tr><td colspan="5" class="text-center text-muted p-4">No departments yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-3">{{ $departments->links() }}</div>
@endsection
