@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Categories</h4>
<a href="{{ route('admin.categories.create') }}" class="btn" style="background:#009040;color:#fff">New Category</a>
</div>
<div class="card card-p3 p-0 overflow-hidden">
<table class="table mb-0">
<thead class="table-light"><tr><th>Name</th><th>Keywords</th><th>Documents</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
<tbody>
@forelse ($categories as $c)
<tr><td class="fw-semibold">{{ $c->name }}</td><td><small class="text-muted" title="{{ $c->keywords }}">{{ \Illuminate\Support\Str::limit($c->keywords ?: '—', 80) }}</small></td><td>{{ $c->documents_count }}</td><td class="text-end"><a href="{{ route('admin.categories.edit', $c) }}" class="btn btn-sm btn-outline-success">Edit</a></td></tr>
@empty
<tr><td colspan="4" class="text-center text-muted p-4">No categories yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-3">{{ $categories->links() }}</div>
@endsection
