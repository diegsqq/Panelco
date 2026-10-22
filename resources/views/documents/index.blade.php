@extends('layouts.app')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
<div><h3 class="fw-bold mb-0">Document Library</h3></div>
<a href="{{ route('documents.upload') }}" class="btn" style="background:#00CC33;border-color:#00CC33;color:#fff;font-weight:600"><i class="bi bi-cloud-upload me-1"></i>Upload</a>
</div>
<div class="card card-p3 p-3 mb-3">
<form method="GET" action="{{ route('documents.library') }}" class="row g-2">
<div class="col-md-5"><input name="q" class="form-control" value="{{ request('q') }}" placeholder="Search title, filename, type…"></div>
<div class="col-md-3"><select name="category" class="form-select"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}" {{ (string) request('category') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-2"><select name="status" class="form-select"><option value="">All states</option>@foreach (['pending','processing','completed','failed'] as $s)<option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-2 d-grid"><button class="btn btn-outline-success">Filter</button></div>
</form>
</div>
<div class="card card-p3 p-0 overflow-hidden">
@forelse ($documents as $doc)
@php
$mime = $doc->mime_type ?? '';
$icon = str_contains($mime, 'pdf') ? ['bi-file-earmark-pdf-fill', '#dc3545'] : (str_contains($mime, 'word') ? ['bi-file-earmark-word-fill', '#0d6efd'] : (str_starts_with($mime, 'image/') ? ['bi-file-earmark-image-fill', '#00CC33'] : (str_contains($mime, 'text') ? ['bi-file-earmark-text-fill', '#6c757d'] : ['bi-file-earmark-fill', '#00CC33'])));
@endphp
<div class="d-flex justify-content-between align-items-center border-bottom p-3 src-card position-relative">
<div class="d-flex gap-3">
<span class="stat-icon" style="background:#f2f7f3;color:{{ $icon[1] }}"><i class="bi {{ $icon[0] }}"></i></span>
<div>
<a href="{{ route('documents.show', $doc) }}" class="fw-semibold text-decoration-none stretched-link" style="color:#007A24">{{ $doc->title ?? $doc->original_filename }}</a>
<div><small class="text-muted">@if($doc->title && $doc->title !== pathinfo($doc->original_filename, PATHINFO_FILENAME)){{ $doc->original_filename }} · @endif{{ number_format($doc->file_size / 1024, 1) }} KB · {{ $doc->category?->name ?? $doc->suggestedCategory?->name ?? 'Uncategorized' }} · {{ $doc->created_at->diffForHumans() }}</small></div>
</div>
</div>
<div class="d-flex align-items-center gap-2">
<span class="badge status-{{ $doc->processing_status }}">{{ $doc->processing_status }}</span>
</div>
</div>
@empty
<div class="p-5 text-center"><i class="bi bi-inbox fs-2 text-muted"></i><p class="fw-semibold mt-2 mb-2">No documents found.</p><a href="{{ route('documents.upload') }}" class="btn btn-sm" style="background:#00CC33;color:#fff">Upload a document</a></div>
@endforelse
</div>
<div class="mt-3">{{ $documents->links() }}</div>
@endsection
