@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h3 class="fw-bold mb-1">Document approvals</h3>
        <p class="text-muted mb-0">Review processed documents you are authorized to access.</p>
    </div>
    <a href="{{ route('documents.library') }}" class="btn btn-outline-secondary">Document library</a>
</div>
<div class="card card-p3 p-0 overflow-hidden">
    @forelse ($documents as $document)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom p-3">
            <div class="flex-grow-1" style="min-width:16rem">
                <a href="{{ route('documents.show', $document) }}" class="fw-semibold text-decoration-none" style="color:#00743F">{{ $document->title ?? $document->original_filename }}</a>
                <div class="small text-muted">{{ $document->original_filename }} · uploaded by {{ $document->uploader?->name ?? '—' }} · {{ $document->created_at->format('M d, Y') }}</div>
                <div class="small text-muted">{{ $document->department?->name ?? 'No department' }}</div>
            </div>
            <span class="badge text-bg-warning">Pending approval</span>
            <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-outline-success">Review document</a>
        </div>
    @empty
        <div class="p-5 text-center text-muted">
            <i class="bi bi-clipboard-check fs-2"></i>
            <p class="fw-semibold mt-2 mb-0">No documents are waiting for approval.</p>
        </div>
    @endforelse
</div>
<div class="mt-3">{{ $documents->links('pagination::bootstrap-5') }}</div>
@endsection



