@extends('layouts.app')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
<div><h3 class="fw-bold mb-0">Document Library</h3></div>
<div class="d-flex flex-wrap gap-2 align-items-center">
<form method="GET" action="{{ route('documents.library') }}" class="d-flex align-items-center gap-2" role="search">
    <div class="input-group" style="min-width:16rem">
        <span class="input-group-text" style="background:#f2f7f3;border-color:#d5e8db;color:#00A868"><i class="bi bi-search"></i></span>
        <input type="search" name="q" value="{{ $search ?? request('q') }}" class="form-control" placeholder="Search title, file, category…" aria-label="Search documents" style="border-color:#d5e8db">
    </div>
    <button type="submit" class="btn btn-sm" style="background:#00A868;border-color:#00A868;color:#fff;font-weight:600">Search</button>
</form>
<a href="{{ route('documents.upload') }}" class="btn" style="background:#00A868;border-color:#00A868;color:#fff;font-weight:600"><i class="bi bi-cloud-upload me-1"></i>Upload</a>
</div>
</div>

<div class="card card-p3 p-0 overflow-hidden">
@forelse ($documents as $doc)
@php
$mime = $doc->mime_type ?? '';
$icon = str_contains($mime, 'pdf') ? ['bi-file-earmark-pdf-fill', '#dc3545'] : (str_contains($mime, 'word') ? ['bi-file-earmark-word-fill', '#0d6efd'] : (str_starts_with($mime, 'image/') ? ['bi-file-earmark-image-fill', '#00A868'] : (str_contains($mime, 'text') ? ['bi-file-earmark-text-fill', '#6c757d'] : ['bi-file-earmark-fill', '#00A868'])));
@endphp
<div class="d-flex justify-content-between align-items-center border-bottom p-3 src-card position-relative">
<div class="d-flex gap-3">
<span class="stat-icon" style="background:#f2f7f3;color:{{ $icon[1] }}"><i class="bi {{ $icon[0] }}"></i></span>
<div>
<a href="{{ route('documents.show', $doc) }}" class="fw-semibold text-decoration-none stretched-link" style="color:#00743F">{{ $doc->title ?? $doc->original_filename }}</a>
<div><small class="text-muted">@if($doc->title && $doc->title !== pathinfo($doc->original_filename, PATHINFO_FILENAME)){{ $doc->original_filename }} · @endif{{ number_format($doc->file_size / 1024, 1) }} KB · {{ $doc->category?->name ?? $doc->suggestedCategory?->name ?? 'Uncategorized' }} · {{ $doc->created_at->diffForHumans() }}</small></div>
</div>
</div>
</div>
@empty
<div class="p-5 text-center"><i class="bi bi-inbox fs-2 text-muted"></i><p class="fw-semibold mt-2 mb-2">No documents found.</p><a href="{{ route('documents.upload') }}" class="btn btn-sm" style="background:#00A868;color:#fff">Upload a document</a></div>
@endforelse
</div>
@endsection
