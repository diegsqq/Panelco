@extends('layouts.app')
@section('content')
<div class="row g-3 mt-1">
<div class="col-lg-8">
@if($document->content || $document->content_header || $document->content_footer)
<div class="card card-p3 p-4 mb-3" id="document-content">
<div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
<h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-1" style="color:#00A868"></i>Document content</h6>
<div class="d-flex gap-1 flex-wrap">
@can('update', $document)<a href="{{ route('documents.edit-content', $document) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil me-1"></i>Edit content</a>@endcan
@can('download', $document)
<a href="{{ route('documents.content-docx', $document) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-word me-1"></i>DOCX</a>
<a href="{{ route('documents.content-pdf', $document) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
@endcan
</div>
</div>
@if($document->content_header)
<div class="small doc-render doc-render-header pb-2 mb-2" style="border-bottom:1px dashed var(--p3-card-border)">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_header) !!}</div>
@endif
<div class="doc-render">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content) !!}</div>
@if($document->content_footer)
<div class="small doc-render doc-render-footer pt-2 mt-2" style="border-top:1px dashed var(--p3-card-border)">{!! app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content_footer) !!}</div>
@endif
@if($document->content_updated_at)
<div class="small text-muted mt-3"><i class="bi bi-clock-history me-1"></i>Edited {{ $document->content_updated_at->format('M d, Y H:i') }}{{ $document->contentEditor?->name ? ' by ' . $document->contentEditor->name : '' }}</div>
@endif
</div>
@endif

{{-- Document workflow & tracking --}}
<div class="card card-p3 p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
        <h6 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-1" style="color:#00A868"></i>Document workflow</h6>
        <span class="badge text-bg-secondary">{{ str_replace('_', ' ', ucfirst($document->workflow_status ?? 'draft')) }}</span>
    </div>
    <div class="small mb-2">
        <strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($document->workflow_status ?? 'draft')) }}
        @if($document->submitted_at) · Submitted {{ $document->submitted_at->format('M d, Y H:i') }} @endif
        @if($document->reviewed_at) · Reviewed {{ $document->reviewed_at->format('M d, Y H:i') }} by {{ $document->reviewedBy?->name ?? '—' }} @endif
        @if($document->assignedTo) · Assigned to {{ $document->assignedTo->name }} @endif
    </div>
    @if($document->review_decision_note)
        <div class="small text-muted mb-2"><strong>Review note:</strong> {{ $document->review_decision_note }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        @can('update', $document)
            <form method="POST" action="{{ route('documents.workflow-submit', $document) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Submit</button></form>
        @endcan
        @can('approve', $document)
            <form method="POST" action="{{ route('documents.workflow-review', $document) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Start review</button></form>
            <form method="POST" action="{{ route('documents.workflow-approve', $document) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
            <form method="POST" action="{{ route('documents.workflow-reject', $document) }}" class="d-inline">
                @csrf
                <input type="hidden" name="review_note" value="Returned for correction.">
                <button class="btn btn-sm btn-outline-danger">Reject</button>
            </form>
            <form method="POST" action="{{ route('documents.workflow-archive', $document) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Archive</button></form>
        @endcan
    </div>

    @can('approve', $document)
    <form method="POST" action="{{ route('documents.workflow-assign', $document) }}" class="row g-2 align-items-end mb-3">
        @csrf
        <div class="col-sm-8">
            <label class="form-label small mb-1">Assign to</label>
            <select name="assigned_to" class="form-select form-select-sm">
                <option value="">— Unassigned —</option>
                @foreach(\App\Models\User::orderBy('name')->get() as $u)
                    <option value="{{ $u->id }}" @selected($document->assigned_to === $u->id)>{{ $u->name }} ({{ $u->role }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4">
            <button class="btn btn-sm btn-outline-success w-100">Assign</button>
        </div>
    </form>
    @endcan

    <h6 class="fw-semibold small mb-2">Reviewer comments</h6>
    @forelse($document->comments as $comment)
        <div class="border-bottom py-2 small">
            <div class="fw-semibold">{{ $comment->user?->name ?? 'User' }} <span class="text-muted">· {{ $comment->created_at->format('M d, Y H:i') }}</span></div>
            @if($comment->section_ref)<div class="text-muted small">Section: {{ $comment->section_ref }}</div>@endif
            <div>{{ $comment->body }}</div>
        </div>
    @empty
        <p class="small text-muted mb-2">No comments yet.</p>
    @endforelse

    @can('view', $document)
    <form method="POST" action="{{ route('documents.workflow-comment', $document) }}" class="mt-2">
        @csrf
        <select name="section_ref" class="form-select form-select-sm mb-2">
            <option value="">— General comment —</option>
            @if($document->content)
                @php
                    preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', app(\App\Services\DocumentContentSanitizer::class)->sanitize($document->content), $headings);
                @endphp
                @foreach($headings[1] as $h)
                    @php $label = trim(strip_tags($h)); @endphp
                    @if($label !== '')
                        <option value="{{ $label }}">{{ $label }}</option>
                    @endif
                @endforeach
            @endif
        </select>
        <textarea name="body" class="form-control form-control-sm mb-2" rows="2" maxlength="2000" placeholder="Add a comment…" required></textarea>
        <button class="btn btn-sm btn-outline-success">Add comment</button>
    </form>
    @endcan
</div>
<div class="card card-p3 p-4">
<div class="d-flex justify-content-between align-items-start">
<div><h4 class="fw-bold mb-1">{{ $document->title ?? $document->original_filename }}</h4><small class="text-muted">{{ $document->original_filename }} · {{ $document->mime_type }} · {{ number_format($document->file_size / 1024, 1) }} KB</small></div>
@unless($document->processing_status === 'completed')<span class="badge status-{{ $document->processing_status }}">{{ $document->processing_status }}</span>@endunless
</div>
<hr>
@can('download', $document)
@if(str_starts_with($document->mime_type ?? '', 'image/'))
<div class="text-center mb-3 p-3 rounded" style="background:#f2f7f3;border:1px solid #d5e8db">
    <img src="{{ route('documents.thumb', $document) }}" alt="{{ $document->original_filename }}"
        style="max-width:100%;max-height:440px;border-radius:.5rem;box-shadow:0 2px 8px rgba(10,31,92,.12)">
    <div class="small text-muted mt-2"><i class="bi bi-image me-1"></i>{{ $document->original_filename }}</div>
</div>
@endif
@endcan
<dl class="row small mb-0">
<dt class="col-sm-4"><i class="bi bi-file-earmark me-1" style="color:#00A868"></i>Type</dt><dd class="col-sm-8">{{ $document->document_type ?? '—' }}</dd>
<dt class="col-sm-4"><i class="bi bi-tags me-1" style="color:#00A868"></i>Category</dt><dd class="col-sm-8">{{ $document->category?->name ?? $document->suggestedCategory?->name ?? '—' }}@if(!$document->category && $document->suggestedCategory) <span class="text-muted">suggested{{ $document->classification_confidence !== null ? ' · ' . round($document->classification_confidence * 100) . '%' : '' }}</span>@endif</dd>
<dt class="col-sm-4"><i class="bi bi-building me-1" style="color:#00A868"></i>Department</dt><dd class="col-sm-8">{{ $document->department?->name ?? $document->suggestedDepartment?->name ?? '—' }}@if(!$document->department && $document->suggestedDepartment) <span class="text-muted">suggested{{ $document->department_classification_confidence !== null ? ' · ' . round($document->department_classification_confidence * 100) . '% match' : '' }}</span>@endif</dd>
<dt class="col-sm-4"><i class="bi bi-calendar me-1" style="color:#00A868"></i>Document date</dt><dd class="col-sm-8">{{ $document->document_date?->format('Y-m-d') ?? '—' }}</dd>
@if($document->expiration_date)
@php
    $expirationDays = now()->startOfDay()->diffInDays($document->expiration_date->startOfDay(), false);
@endphp
<dt class="col-sm-4"><i class="bi bi-hourglass-split me-1" style="color:#00A868"></i>Expiration date</dt><dd class="col-sm-8">
<span class="badge {{ $expirationDays < 0 ? 'text-bg-danger' : ($expirationDays <= 30 ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $document->expiration_date->format('Y-m-d') }}</span>
<span class="text-muted">{{ $expirationDays < 0 ? ' · expired ' . abs($expirationDays) . ' day(s) ago' : ($expirationDays === 0 ? ' · expires today' : ($expirationDays <= 30 ? ' · expires in ' . $expirationDays . ' day(s)' : '')) }}</span>
</dd>
@endif
@if($document->reference_number)<dt class="col-sm-4"><i class="bi bi-hash me-1" style="color:#00A868"></i>Reference number</dt><dd class="col-sm-8">{{ $document->reference_number }}</dd>@endif
@if($document->sender)<dt class="col-sm-4"><i class="bi bi-send me-1" style="color:#00A868"></i>Sender</dt><dd class="col-sm-8">{{ $document->sender }}</dd>@endif
@if($document->recipient)<dt class="col-sm-4"><i class="bi bi-inbox me-1" style="color:#00A868"></i>Recipient</dt><dd class="col-sm-8">{{ $document->recipient }}</dd>@endif
<dt class="col-sm-4"><i class="bi bi-cloud-upload me-1" style="color:#00A868"></i>Uploaded</dt><dd class="col-sm-8">{{ $document->created_at->format('M d, Y') }} by {{ $document->uploader?->name ?? '—' }}</dd>
<dt class="col-sm-4"><i class="bi bi-clipboard-check me-1" style="color:#00A868"></i>Document approval</dt><dd class="col-sm-8">
<span class="badge {{ $document->approval_status === 'approved' ? 'text-bg-success' : ($document->approval_status === 'rejected' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst($document->approval_status ?? 'pending') }}</span>
@if($document->approval_reviewed_at)<span class="text-muted"> · {{ $document->approval_reviewed_at->format('M d, Y') }} by {{ $document->approvalReviewer?->name ?? '—' }}</span>@endif
@if($document->approval_review_note)<div class="text-muted mt-1">{{ $document->approval_review_note }}</div>@endif
</dd>
@if($document->suggestedCategory || $document->suggestedDepartment || $document->classification_reviewed_at)
<dt class="col-sm-4"><i class="bi bi-check-circle me-1" style="color:#00A868"></i>Classification review</dt><dd class="col-sm-8">
<span class="badge {{ $document->classification_review_status === 'approved' ? 'text-bg-success' : ($document->classification_review_status === 'rejected' ? 'text-bg-secondary' : 'text-bg-warning') }}">{{ ucfirst($document->classification_review_status ?? 'pending') }}</span>
@if($document->classification_reviewed_at)<span class="text-muted"> · {{ $document->classification_reviewed_at->format('M d, Y') }} by {{ $document->classificationReviewer?->name ?? '—' }}</span>@endif
@if($document->classification_review_note)<div class="text-muted mt-1">{{ $document->classification_review_note }}</div>@endif
</dd>
@endif
<dt class="col-sm-4"><i class="bi bi-key me-1" style="color:#00A868"></i>Keywords</dt><dd class="col-sm-8">@if($document->keywords)@foreach(array_filter(array_map('trim', explode(',', $document->keywords))) as $kw)<span class="badge me-1 mb-1" style="background:#e6f3ea;color:#00743F">{{ $kw }}</span>@endforeach @endif</dd>
@if ($document->processing_error)<dt class="col-sm-4"><i class="bi bi-exclamation-triangle me-1" style="color:#dc3545"></i>Error</dt><dd class="col-sm-8 text-danger">{{ $document->processing_error }}</dd>@endif
</dl>
</div>
<div class="card card-p3 p-4 mt-3 p3-summary @if($summaryCheck !== null && $summaryCheck['verified']) is-verified @elseif($summaryCheck !== null && $summaryCheck['unsupported']) is-warn @endif">
<div class="d-flex justify-content-between align-items-center mb-2">
<h6 class="fw-bold mb-0" id="document-summary"><i class="bi bi-journal-text me-1" style="color:#00A868"></i>Document Summary</h6>
@if($summaryCheck !== null && $summaryCheck['verified'])
<span class="badge" id="sum-verified" style="background:#d4edda;color:#155724"><i class="bi bi-patch-check-fill me-1"></i>verified</span>
@elseif($summaryCheck !== null && count($summaryCheck['unsupported']) > 0)
<span class="badge" id="sum-unverified" style="background:#fff3cd;color:#856404"><i class="bi bi-exclamation-triangle-fill me-1"></i>unverified</span>
@elseif($summaryCheck !== null)
<span class="badge" id="sum-nofigures" style="background:#e9ecef;color:#6c757d">no figures to check</span>
@endif
</div>
@if($document->summary)
@php
    // Highlight monetary amounts, percentages, and decimal figures so the
    // numbers pop out of the prose.
    $highlight = function (string $s): string {
        return preg_replace(
            '/(₱\s?[\d,]+(?:\.\d+)?|\b\d[\d,]*(?:\.\d+)?\s?%|\b\d{1,3}(?:,\d{3})+(?:\.\d+)?|\b\d+\.\d+\b)/u',
            '<span class="p3-fig">$1</span>',
            e($s)
        );
    };
    $sentences = preg_split('/(?<=[.!?])\s+/u', trim($document->summary)) ?: [];
    $sentences = array_values(array_filter($sentences, fn ($s) => trim($s) !== ''));
@endphp
@if(count($sentences) > 1)
<ul class="mb-0 ps-3 p3-summary-list">
@foreach($sentences as $s)
<li class="mb-2">{!! $highlight($s) !!}</li>
@endforeach
</ul>
@else
<p class="mb-0 p3-summary-text">{!! $highlight($document->summary) !!}</p>
@endif
@if($summaryCheck !== null && ! $summaryCheck['verified'] && $summaryCheck['unsupported'])
<div class="small mt-3 p-2 rounded" style="background:#fff3cd;color:#856404"><i class="bi bi-exclamation-triangle-fill me-1"></i>Not found in the document text: <strong>{{ implode(', ', $summaryCheck['unsupported']) }}</strong></div>
@endif
@elseif($document->processing_status === 'completed')
<p class="mb-0 text-muted">No summary available.</p>
@else
<p class="mb-0 text-muted">Still processing.</p>
@endif
</div>
<div class="card card-p3 p-4 mt-3">
<h6 class="fw-bold"><i class="bi bi-stars me-1" style="color:#00A868"></i>Important Details</h6>
@if(count($details))
<ul class="small mb-0 ps-4">@foreach($details as $d)<li class="mb-1">{{ $d }}</li>@endforeach</ul>
@elseif($document->processing_status === 'completed')
<p class="small text-muted mb-0">No distinct details were identified in the extracted text.</p>
@else
<p class="small text-muted mb-0">Details will be available after processing.</p>
@endif
</div>
<div class="card card-p3 p-4 mt-3">
<h6 class="fw-bold"><i class="bi bi-wrench-adjustable-circle me-1" style="color:#00A868"></i>Corrective Actions</h6>
@if(count($correctiveActions))
<ul class="small mb-0 ps-4">@foreach($correctiveActions as $action)<li class="mb-1">{{ $action }}</li>@endforeach</ul>
@elseif($document->processing_status === 'completed')
<p class="small text-muted mb-0">No corrective actions were identified in the extracted text.</p>
@else
<p class="small text-muted mb-0">Corrective actions will be available after processing.</p>
@endif
</div>
@if(count($tables))
<div class="card card-p3 p-4 mt-3" id="cited-text">
<h6 class="fw-bold">Document text</h6>
@if(count($tables))
@foreach($tables as $table)
@if($table['caption'])<p class="small fw-semibold mb-2" style="color:#00743F">{{ $table['caption'] }}</p>@endif
<div class="table-responsive p3-table-wrap">
<table class="table table-p3 align-middle mb-0">
<thead><tr>@foreach($table['headers'] as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
<tbody>
@foreach($table['rows'] as $row)
<tr>
@foreach($row as $j => $cell)
@if($j === 0)<td><span class="p3-cat-pill p3-cat-{{ Str::slug($cell) }}">{{ $cell }}</span></td>
@elseif($j === count($row) - 1)<td class="p3-cell-interp">{{ $cell }}</td>
@elseif(is_numeric(str_replace(['%', ' ', '₱'], '', $cell)))<td><span class="p3-result">{{ $cell }}</span></td>
@elseif(str_contains($cell, '÷') || str_contains($cell, '×') || str_contains($cell, '=') || str_contains($cell, '+') || str_contains($cell, '/'))<td><code class="p3-formula">{{ $cell }}</code></td>
@else<td class="fw-semibold">{{ $cell }}</td>
@endif
@endforeach
</tr>
@endforeach
</tbody>
</table>
</div>
@endforeach

@else
@endif
</div>
@endif
</div>
<div class="col-lg-4 d-flex flex-column gap-3">
<div class="card card-p3 p-3">
<h6 class="fw-bold">Actions</h6>
<div class="d-grid gap-2">
@can('download', $document)<button type="button" class="btn btn-sm w-100 btn-outline-success" data-bs-toggle="modal" data-bs-target="#viewerModal"><i class="bi bi-eye-fill me-1"></i>View document</button>@endcan
@can('view', $document)
<form method="POST" action="{{ route('documents.favorite', $document) }}" class="d-grid">@csrf<button class="btn btn-sm w-100 btn-outline-secondary"><i class="bi bi-star me-1"></i>{{ $document->favoritedBy->contains('id', auth()->id()) ? 'Unfavorite' : 'Favorite' }}</button></form>
@endcan
@can('download', $document)<button type="button" class="btn btn-sm w-100 btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#printContentModal"><i class="bi bi-printer me-1"></i>Print content</button>@endcan
@can('download', $document)<a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Download</a>@endcan
@can('download', $document)<button type="button" class="btn btn-outline-success btn-sm w-100" id="printDocBtn"><i class="bi bi-printer me-1"></i>Print document</button>@endcan
@can('update', $document)<a href="{{ route('documents.edit', $document) }}" class="btn btn-outline-success btn-sm w-100"><i class="bi bi-pencil me-1"></i>Edit document</a>@endcan
@can('update', $document)<a href="{{ route('documents.edit-content', $document) }}" class="btn btn-outline-success btn-sm w-100"><i class="bi bi-file-earmark-text me-1"></i>Edit content</a>@endcan
@if ($document->processing_status === 'failed')
@can('update', $document)<form method="POST" action="{{ route('documents.retry', $document) }}">@csrf<button class="btn btn-sm w-100" style="background:#ffffff;color:#00A868;border:1px solid #00A868">Retry processing</button></form>@endcan
@endif
@can('delete', $document)<form method="POST" action="{{ route('documents.destroy', $document) }}" id="deleteDocForm">@csrf @method('DELETE')<button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#deleteDocModal"><i class="bi bi-trash me-1"></i>Delete</button></form>@endcan
</div>
</div>

<div class="card card-p3 p-3">
<h6 class="fw-bold"><i class="bi bi-clock-history me-1" style="color:#00A868"></i>Version history</h6>
<div class="small mb-2"><span class="badge text-bg-success">Current</span> Version {{ $document->version_number }} · {{ $document->original_filename }} · {{ $document->currentVersionUploader?->name ?? '—' }}</div>
@forelse($document->versions as $version)
<div class="border-top pt-2 mt-2 small">
<div class="d-flex justify-content-between align-items-start gap-2">
<div>
<div class="fw-semibold">Version {{ $version->version_number }}</div>
<div class="text-muted">{{ $version->original_filename }} · {{ number_format($version->file_size / 1024, 1) }} KB</div>
<div class="text-muted">{{ $version->created_at->format('M d, Y H:i') }} · {{ $version->uploader?->name ?? '—' }}</div>
</div>
@can('download', $document)
<a href="{{ route('documents.versions.download', [$document, $version]) }}" class="btn btn-sm btn-outline-success text-nowrap" aria-label="Download version {{ $version->version_number }}"><i class="bi bi-download"></i></a>
@endcan
@can('update', $document)
<form method="POST" action="{{ route('documents.versions.restore', [$document, $version]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary text-nowrap" aria-label="Restore version {{ $version->version_number }}"><i class="bi bi-arrow-counterclockwise"></i></button></form>
@endcan
</div>
</div>
@empty
<p class="small text-muted mb-0">No previous versions. Replacing this file will add the current file to this history.</p>
@endforelse
</div>

@can('update', $document)
<div class="card card-p3 p-3 mt-3">
    <h6 class="fw-bold"><i class="bi bi-people me-1" style="color:#00A868"></i>Sharing</h6>
    <form method="POST" action="{{ route('documents.share', $document) }}">
        @csrf
        <select name="user_ids[]" class="form-select form-select-sm mb-2" multiple size="6">
            @foreach(\App\Models\User::orderBy('name')->get() as $u)
                <option value="{{ $u->id }}" @selected($document->authorizedUsers->contains($u->id))>{{ $u->name }} ({{ $u->role }})</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-outline-success w-100">Update sharing</button>
    </form>
    <p class="small text-muted mt-2 mb-0">Hold Ctrl/Cmd to select multiple employees.</p>
</div>
@endcan

@can('update', $document)
<div class="card card-p3 p-3 mt-3">
    <h6 class="fw-bold"><i class="bi bi-tags me-1" style="color:#00A868"></i>Tags</h6>
    <form method="POST" action="{{ route('documents.tags', $document) }}">
        @csrf
        <input type="text" name="tags" class="form-control form-control-sm mb-2" value="{{ implode(', ', $document->tags ?? []) }}" placeholder="comma, separated, tags">
        <button class="btn btn-sm btn-outline-success w-100">Update tags</button>
    </form>
    @if(!empty($document->tags))
        <div class="mt-2">@foreach($document->tags as $tag)<span class="badge me-1" style="background:#e6f3ea;color:#00743F">{{ $tag }}</span>@endforeach</div>
    @endif
</div>
@endcan

<div class="card card-p3 p-3 mt-3">
    <h6 class="fw-bold"><i class="bi bi-clock-history me-1" style="color:#00A868"></i>Access history</h6>
    @forelse($document->accessLogs->take(8) as $log)
        <div class="small border-bottom py-1">{{ $log->user?->name ?? 'Guest' }} · {{ $log->action }} · {{ $log->created_at->format('M d, Y H:i') }}</div>
    @empty
        <p class="small text-muted mb-0">No access recorded yet.</p>
    @endforelse
</div>

@if($similarDocuments->isNotEmpty())
<div class="card card-p3 p-3">
<h6 class="fw-bold"><i class="bi bi-files me-1" style="color:#00A868"></i>Potentially similar documents</h6>
<p class="small text-muted">These documents you can access have substantially similar text. Similarity is a review aid, not proof of duplication.</p>
@foreach($similarDocuments as $match)
<div class="border-top pt-2 mt-2 small">
<div class="d-flex justify-content-between gap-2">
<a href="{{ route('documents.show', $match['document']) }}" class="fw-semibold text-decoration-none">{{ $match['document']->title ?? $match['document']->original_filename }}</a>
<span class="badge text-bg-secondary flex-shrink-0">{{ $match['similarity'] }}% similarity</span>
</div>
<div class="text-muted">{{ $match['document']->original_filename }}{{ $match['document']->document_date ? ' · ' . $match['document']->document_date->format('Y-m-d') : '' }}</div>
</div>
@endforeach
</div>
@endif

@can('approve', $document)
@if($document->processing_status === 'completed')
<div class="card card-p3 p-3">
<h6 class="fw-bold"><i class="bi bi-clipboard-check me-1" style="color:#00A868"></i>Document approval</h6>
<p class="small text-muted">This decision is separate from approving AI category and department suggestions.</p>
<form method="POST" action="{{ route('documents.approve-document', $document) }}" class="mb-3">
@csrf
<label class="form-label small fw-semibold" for="document_approval_note">Approval note (optional)</label>
<textarea id="document_approval_note" name="review_note" class="form-control form-control-sm mb-2" rows="2" maxlength="1000">{{ old('review_note') }}</textarea>
<button class="btn btn-sm btn-outline-success w-100"><i class="bi bi-check-lg me-1"></i>Approve document</button>
</form>
<form method="POST" action="{{ route('documents.reject-document', $document) }}">
@csrf
<label class="form-label small fw-semibold" for="document_rejection_note">Reason for rejection</label>
<textarea id="document_rejection_note" name="review_note" class="form-control form-control-sm mb-2" rows="2" maxlength="1000" required>{{ old('review_note') }}</textarea>
<button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-x-lg me-1"></i>Reject document</button>
</form>
</div>
@endif
@endcan

@if ($document->suggestedCategory || $document->suggestedDepartment)
@can('approve', $document)
<div class="card card-p3 p-3">
<h6 class="fw-bold"><i class="bi bi-clipboard-check me-1" style="color:#00A868"></i>Classification review</h6>
<p class="small text-muted mb-3">Suggestions are based on extracted document text and configured matching signals. Confidence is a guide, not a guarantee. Review the document before confirming.</p>
@if ($document->suggestedCategory)
<div class="small mb-1"><span class="fw-semibold">Suggested category:</span> {{ $document->suggestedCategory->name }}
@if ($document->classification_confidence !== null)<span class="text-muted"> · {{ round($document->classification_confidence * 100) }}% confidence</span>@else<span class="text-muted"> · keyword suggestion</span>@endif
@if ($document->suggested_category_reason)<div class="text-muted">{{ $document->suggested_category_reason }}</div>@endif
</div>
@endif
@if ($document->suggestedDepartment)
<div class="small mb-3"><span class="fw-semibold">Suggested department:</span> {{ $document->suggestedDepartment->name }}
@if ($document->department_classification_confidence !== null)<span class="text-muted"> · {{ round($document->department_classification_confidence * 100) }}% match</span>@endif
@if ($document->suggested_department_reason)<div class="text-muted">{{ $document->suggested_department_reason }}</div>@endif
</div>
@endif
<form method="POST" action="{{ route('documents.approve', $document) }}" class="mb-2">
@csrf
<label class="form-label small fw-semibold" for="review_category_id">Confirm or correct category</label>
<select id="review_category_id" name="category_id" class="form-select form-select-sm mb-2">
<option value="">— No category —</option>
@foreach ($categories as $category)
<option value="{{ $category->id }}" @selected((string) old('category_id', $document->suggested_category_id ?? $document->category_id) === (string) $category->id)>{{ $category->name }}</option>
@endforeach
</select>
<label class="form-label small fw-semibold" for="review_department_id">Confirm or correct department</label>
<select id="review_department_id" name="department_id" class="form-select form-select-sm mb-2">
<option value="">— No department —</option>
@foreach ($departments as $department)
<option value="{{ $department->id }}" @selected((string) old('department_id', $document->suggested_department_id ?? $document->department_id) === (string) $department->id)>{{ $department->name }}</option>
@endforeach
</select>
<label class="form-label small fw-semibold" for="approval_review_note">Review note (optional)</label>
<textarea id="approval_review_note" name="review_note" class="form-control form-control-sm mb-2" rows="2" maxlength="1000">{{ old('review_note') }}</textarea>
<button class="btn btn-sm btn-outline-success w-100"><i class="bi bi-check-lg me-1"></i>Approve classification</button>
</form>
<form method="POST" action="{{ route('documents.reject-classification', $document) }}">
@csrf
<label class="form-label small fw-semibold" for="rejection_review_note">Reason for rejection (optional)</label>
<textarea id="rejection_review_note" name="review_note" class="form-control form-control-sm mb-2" rows="2" maxlength="1000"></textarea>
<button class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-x-lg me-1"></i>Reject suggestions</button>
</form>
</div>
@endcan
@endif

<div class="card card-p3 p-3">
<h6 class="fw-bold"><i class="bi bi-lightning-charge-fill me-1" style="color:#00A868"></i>Ask Lightning AI about this document</h6>
<div class="d-flex flex-wrap gap-2" id="askChips">
<button type="button" class="btn btn-sm btn-outline-success ask-chip" data-question="Summarize this document in vertical sections: Document Summary, Important Details, and Corrective Actions. Use only facts from the document.">Summarize it</button>
<button type="button" class="btn btn-sm btn-outline-success ask-chip" data-question="What are the important details in this document?">Important details</button>
<button type="button" class="btn btn-sm btn-outline-success ask-chip" data-question="What corrective actions were recorded in this document?">Corrective actions</button>
</div>
<div class="d-flex gap-2 mt-2">
<input type="text" id="askInput" class="form-control form-control-sm" maxlength="2000" placeholder="Ask anything about this document…" aria-label="Ask about this document">
<button type="button" class="btn btn-sm" id="askSendBtn" style="background:#ffffff;color:#00A868;border:1px solid #00A868" aria-label="Send"><i class="bi bi-send-fill"></i></button>
</div>
<div id="askResult" class="d-none mt-3"></div>
</div>
</div>

</div>
{{-- View-only document modal: the file is fetched as an ephemeral blob (no
     shareable file URL), rendered without browser toolbars, and the object
     URL is revoked on close. --}}
{{-- Print content modal --}}
@can('download', $document)
<div class="modal fade" id="printContentModal" tabindex="-1" aria-labelledby="printContentLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printContentLabel"><i class="bi bi-printer me-1"></i>Print document content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Scope</label>
                        <div class="form-check"><input class="form-check-input" type="radio" name="printScope" id="scopeAll" value="all" checked><label class="form-check-label" for="scopeAll">Entire document</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="printScope" id="scopeParas" value="paragraphs"><label class="form-check-label" for="scopeParas">Selected paragraphs</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="printScope" id="scopeSections" value="sections"><label class="form-check-label" for="scopeSections">Selected sections</label></div>

                        <div id="paragraphList" class="mt-2 d-none" style="max-height:240px;overflow:auto;border:1px solid var(--p3-card-border);border-radius:.5rem;padding:.5rem">
                        </div>
                        <div id="sectionList" class="mt-2 d-none" style="max-height:240px;overflow:auto;border:1px solid var(--p3-card-border);border-radius:.5rem;padding:.5rem">
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="includeHF" checked>
                            <label class="form-check-label small" for="includeHF">Include header & footer</label>
                        </div>

                        <div class="mt-3">
                            <label class="form-label small fw-semibold">Paper size</label>
                            <select id="printPaper" class="form-select form-select-sm">
                                <option value="A4" @selected(($document->page_paper ?? 'a4') === 'a4')>A4</option>
                                <option value="Letter" @selected(($document->page_paper ?? 'a4') === 'letter')>Letter</option>
                                <option value="Legal" @selected(($document->page_paper ?? 'a4') === 'legal')>Legal</option>
                            </select>
                        </div>
                        <div class="mt-2">
                            <label class="form-label small fw-semibold">Orientation</label>
                            <select id="printOrientation" class="form-select form-select-sm">
                                <option value="portrait" @selected(($document->page_orientation ?? 'portrait') === 'portrait')>Portrait</option>
                                <option value="landscape" @selected(($document->page_orientation ?? 'portrait') === 'landscape')>Landscape</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold">Preview</label>
                        <iframe id="printPreviewFrame" style="width:100%;height:360px;border:1px solid var(--p3-card-border);border-radius:.5rem;background:#fff"></iframe>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="previewBtn"><i class="bi bi-eye me-1"></i>Refresh preview</button>
                            <button type="button" class="btn btn-sm btn-outline-success" id="printNowContentBtn"><i class="bi bi-printer me-1"></i>Print</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="downloadSelectionBtn"><i class="bi bi-download me-1"></i>Download PDF</button>
                        </div>
                        <p class="small text-muted mt-2 mb-0">Use the browser print dialog to choose specific pages. Selected paragraphs/sections can be printed or downloaded as a PDF.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
<div class="modal fade" id="viewerModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-centered">
<div class="modal-content p3-modal d-flex flex-column" style="height:88vh">
<div class="d-flex justify-content-between align-items-center px-3 py-2" style="border-bottom:1px solid var(--p3-card-border)">
<span class="fw-bold text-truncate" style="max-width:70%"><i class="bi bi-eye-fill me-1" style="color:#00A868"></i>{{ $document->original_filename }}</span>
<div class="d-flex align-items-center gap-2">
<button type="button" class="btn btn-sm btn-outline-success" id="viewerPrintBtn"><i class="bi bi-printer me-1"></i>Print</button>
<a href="{{ route('documents.pdf', $document) }}" class="badge text-decoration-none" style="background:#e6f3ea;color:#1F7A2B;cursor:pointer">Convert to PDF</a>
</div>
</div>
<div id="viewerBody" class="flex-grow-1" style="overflow:auto;background:#f1f5f3;min-height:0;text-align:center">
<span class="text-muted small"><i class="bi bi-hour2"></i> Loading…</span>
</div>
</div>
</div>
</div>
<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
<div class="modal-dialog modal-dialog-centered" style="max-width:420px;width:calc(100% - 2rem)">
<div class="modal-content p3-modal">
<div class="text-center pt-4 px-4">
<div class="p3-meow"><img src="{{ asset('images/meow.gif') }}" alt="meow" style="width:90px;height:90px;object-fit:contain"></div>
<h5 class="fw-bold mt-3 mb-0">Delete this document?</h5>
<p class="text-muted mb-0 mt-2" style="font-size:.85rem">This permanently removes the file and its indexed passages. This action cannot be undone.</p>
</div>
<div class="p3-modal-actions">
<button type="button" class="btn btn-outline-secondary px-4 flex-fill" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-outline-danger px-4 flex-fill" id="confirmDeleteDocBtn"><i class="bi bi-trash me-1"></i>Delete</button>
</div>
</div>
</div>
</div>
<script>
(function () {
    var askUrl = @json(route('documents.ask', $document));
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var result = document.getElementById('askResult');
    var input = document.getElementById('askInput');
    var sendBtn = document.getElementById('askSendBtn');
    var chips = document.querySelectorAll('.ask-chip');
    if (!result) return;
    var busy = false;

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function ask(question) {
        if (busy || !question) return;
        busy = true;
        chips.forEach(function (c) { c.disabled = true; });
        if (sendBtn) sendBtn.disabled = true;
        result.classList.remove('d-none');
        result.innerHTML = '<div class="d-flex align-items-center gap-2 small text-muted"><span class="spinner-border spinner-border-sm" style="width:.85rem;height:.85rem"></span>Lightning AI is reading this document…</div>';

        fetch(askUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: question })
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                var sources = (res.body.sources || []).map(function (s, i) {
                    return '<li class="mb-1"><span class="fw-semibold">' + esc(s.title) + '</span>'
                        + (s.page ? ' <span class="text-muted">(p. ' + esc(s.page) + ')</span>' : '')
                        + '<div class="text-muted" style="font-size:.78rem">' + esc(s.excerpt) + '</div></li>';
                }).join('');

                result.innerHTML =
                    '<div class="p-3" style="background:#f7fbf8;border:1px solid #d5e8db;border-radius:.75rem">'
                    + '<div class="d-flex align-items-center gap-2 mb-2 small fw-semibold" style="color:#00743F"><i class="bi bi-lightning-charge-fill"></i>Lightning AI</div>'
                    + '<div class="ask-answer" style="font-size:.88rem;color:#2f3b35">' + (res.ok && res.body.answer ? res.body.answer : '<span class="text-danger">Could not reach the server. Try again.</span>') + '</div>'
                    + (sources ? '<div class="mt-3 pt-2 border-top small"><div class="fw-semibold text-muted mb-1" style="font-size:.72rem">SOURCES</div><ul class="ps-3 mb-0">' + sources + '</ul></div>' : '')
                    + '</div>';
            })
            .catch(function () {
                result.innerHTML = '<div class="p-3 small text-danger" style="background:#fdeaea;border:1px solid #f5c2c2;border-radius:.75rem"><i class="bi bi-exclamation-triangle me-1"></i>Could not reach the server. Try again.</div>';
            })
            .then(function () {
                busy = false;
                chips.forEach(function (c) { c.disabled = false; });
                if (sendBtn) sendBtn.disabled = false;
            });
    }

    chips.forEach(function (c) {
        c.addEventListener('click', function () { ask(c.getAttribute('data-question')); });
    });

    function sendTyped() {
        var q = (input && input.value || '').trim();
        if (!q) return;
        ask(q);
        if (input) input.value = '';
    }
    if (sendBtn) sendBtn.addEventListener('click', sendTyped);
    if (input) input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); sendTyped(); } });
})();
</script>
<script>
(function () {
    var printModal = document.getElementById('printContentModal');
    if (!printModal) return;

    var headerEl = document.querySelector('#document-content .doc-render-header');
    var bodyEl = document.querySelector('#document-content .doc-render');
    var footerEl = document.querySelector('#document-content .doc-render-footer');

    var paragraphList = document.getElementById('paragraphList');
    var sectionList = document.getElementById('sectionList');
    var previewFrame = document.getElementById('printPreviewFrame');

    var MARGIN_MM = { narrow: 12.7, normal: 25.4, wide: 50.8 };

    function topBlocks() {
        return bodyEl ? Array.from(bodyEl.children) : [];
    }

    function blockLabel(el) {
        var t = (el.textContent || '').trim().replace(/\s+/g, ' ');
        if (t.length > 70) t = t.slice(0, 70) + '…';
        return t || ('<' + el.tagName.toLowerCase() + '>');
    }

    function buildSections() {
        var sections = [];
        var current = null;
        topBlocks().forEach(function (el) {
            if (/^H[1-6]$/.test(el.tagName)) {
                current = { title: blockLabel(el), blocks: [el] };
                sections.push(current);
            } else {
                if (!current) {
                    current = { title: 'Introduction', blocks: [] };
                    sections.push(current);
                }
                current.blocks.push(el);
            }
        });
        if (sections.length === 0) sections.push({ title: 'Document', blocks: topBlocks() });
        return sections;
    }

    function renderChecklist(container, items, name) {
        container.innerHTML = '';
        items.forEach(function (item, i) {
            var id = name + '_' + i;
            var wrap = document.createElement('div');
            wrap.className = 'form-check';
            var cb = document.createElement('input');
            cb.className = 'form-check-input';
            cb.type = 'checkbox';
            cb.id = id;
            cb.value = String(i);
            cb.checked = true;
            var lbl = document.createElement('label');
            lbl.className = 'form-check-label small';
            lbl.htmlFor = id;
            lbl.textContent = item.label;
            wrap.appendChild(cb);
            wrap.appendChild(lbl);
            container.appendChild(wrap);
        });
    }

    function selectedParagraphIndices() {
        var out = [];
        paragraphList.querySelectorAll('input[type="checkbox"]').forEach(function (cb, i) {
            if (cb.checked) out.push(i);
        });
        return out;
    }

    function selectedSectionIndices() {
        var out = [];
        sectionList.querySelectorAll('input[type="checkbox"]').forEach(function (cb, i) {
            if (cb.checked) out.push(i);
        });
        return out;
    }

    function collectHtml() {
        var scope = document.querySelector('input[name="printScope"]:checked').value;
        var blocks = topBlocks();
        var chosen = [];
        if (scope === 'all') {
            chosen = blocks.slice();
        } else if (scope === 'paragraphs') {
            var idx = selectedParagraphIndices();
            chosen = idx.map(function (i) { return blocks[i]; }).filter(Boolean);
        } else if (scope === 'sections') {
            var secs = buildSections();
            var sidx = selectedSectionIndices();
            sidx.forEach(function (i) {
                var sec = secs[i];
                if (sec) sec.blocks.forEach(function (b) { chosen.push(b); });
            });
        }
        return chosen.map(function (el) { return el.outerHTML; }).join('\n');
    }

    function paperValue() {
        var p = document.getElementById('printPaper').value;
        return p; // A4/Letter/Legal
    }

    function buildHtml() {
        var paper = paperValue();
        var orientation = document.getElementById('printOrientation').value;
        var includeHF = document.getElementById('includeHF').checked;
        var selectedHtml = collectHtml();
        var headerHtml = includeHF && headerEl ? headerEl.outerHTML : '';
        var footerHtml = includeHF && footerEl ? footerEl.outerHTML : '';
        return '<!doctype html><html><head><title></title>'
            + '<style>'
            + '@page{size:' + paper + ' ' + orientation + ';margin:18mm;}'
            + 'body{font-family:Georgia,serif;font-size:11pt;color:#111;line-height:1.6;}'
            + 'table{border-collapse:collapse;width:100%;margin:.5rem 0;}'
            + 'td,th{border:1px solid #999;padding:.35rem .5rem;}'
            + 'img{max-width:100%;}'
            + '</style></head><body>'
            + headerHtml
            + selectedHtml
            + footerHtml
            + '</body></html>';
    }

    function refreshLists() {
        var scope = document.querySelector('input[name="printScope"]:checked').value;
        paragraphList.classList.toggle('d-none', scope !== 'paragraphs');
        sectionList.classList.toggle('d-none', scope !== 'sections');
        if (scope === 'paragraphs') {
            renderChecklist(paragraphList, topBlocks().map(function (el, i) {
                return { label: (i + 1) + '. ' + blockLabel(el) };
            }), 'para');
        } else if (scope === 'sections') {
            renderChecklist(sectionList, buildSections().map(function (s) {
                return { label: s.title + ' (' + s.blocks.length + ' block' + (s.blocks.length === 1 ? '' : 's') + ')' };
            }), 'sec');
        }
    }

    function refreshPreview() {
        var html = buildHtml();
        var doc = previewFrame.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
    }

    printModal.addEventListener('show.bs.modal', function () {
        refreshLists();
        setTimeout(refreshPreview, 100);
    });

    document.querySelectorAll('input[name="printScope"]').forEach(function (r) {
        r.addEventListener('change', function () { refreshLists(); refreshPreview(); });
    });
    document.getElementById('printPaper').addEventListener('change', refreshPreview);
    document.getElementById('printOrientation').addEventListener('change', refreshPreview);
    document.getElementById('includeHF').addEventListener('change', refreshPreview);
    document.getElementById('previewBtn').addEventListener('click', refreshPreview);

    document.getElementById('printNowContentBtn').addEventListener('click', function () {
        var html = buildHtml();
        var iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
        var d = iframe.contentWindow.document;
        d.open();
        d.write(html);
        d.close();
        setTimeout(function () {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            setTimeout(function () { if (iframe.parentNode) iframe.parentNode.removeChild(iframe); }, 1000);
        }, 500);
    });

    document.getElementById('downloadSelectionBtn').addEventListener('click', function () {
        var paper = paperValue();
        var orientation = document.getElementById('printOrientation').value;
        var includeHF = document.getElementById('includeHF').checked;
        var html = collectHtml();
        var headerHtml = includeHF && headerEl ? headerEl.outerHTML : '';
        var footerHtml = includeHF && footerEl ? footerEl.outerHTML : '';
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch(@json(route('documents.content-pdf-selection', $document)), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/pdf', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                html: html,
                header: headerHtml,
                footer: footerHtml,
                page_paper: paper.toLowerCase() === 'a4' ? 'a4' : (paper.toLowerCase() === 'letter' ? 'letter' : 'legal'),
                page_orientation: orientation,
                page_margins: 'normal'
            }),
            credentials: 'same-origin'
        }).then(function (res) {
            if (!res.ok) throw new Error('Download failed (' + res.status + ')');
            return res.blob();
        }).then(function (blob) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'document-selection.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }).catch(function (err) { alert(err.message); });
    });
})();
</script>
<script>
(function () {
    var modal = document.getElementById('viewerModal');
    if (!modal) return;
    var body = document.getElementById('viewerBody');
    var objectUrl = null;
    var pendingPrint = false;

    function printHtml(html) {
        var iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
        var doc = iframe.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(function () { if (iframe.parentNode) iframe.parentNode.removeChild(iframe); }, 1000);
    }
    function printNow() {
        var pdfFrame = body.querySelector('iframe');
        if (pdfFrame) {
            try {
                if (pdfFrame.contentDocument) pdfFrame.contentDocument.title = '';
                pdfFrame.contentWindow.focus();
                pdfFrame.contentWindow.print();
                pendingPrint = false;
                return;
            } catch (e) { /* fall through */ }
        }
        // Print the viewer content with the same styling as on screen.
        // Bottom padding is intentionally omitted: it pushed a sliver of
        // content onto a second, otherwise empty page.
        var styles = '';
        document.querySelectorAll('link[rel="stylesheet"], style').forEach(function (s) { styles += s.outerHTML; });
        var frame = document.createElement('iframe');
        frame.style.position = 'fixed';
        frame.style.right = '0';
        frame.style.bottom = '0';
        frame.style.width = '0';
        frame.style.height = '0';
        frame.style.border = '0';
        document.body.appendChild(frame);
        var d = frame.contentWindow.document;
        d.open();
        d.write('<!doctype html><html><head><title></title>' + styles + '<style>@page{margin:0}body{background:#fff;margin:0;padding:1.5cm 1.5cm 0 1.5cm;}</style></head><body>' + body.innerHTML + '</body></html>');
        d.close();
        setTimeout(function () {
            frame.contentWindow.focus();
            frame.contentWindow.print();
            setTimeout(function () { if (frame.parentNode) frame.parentNode.removeChild(frame); }, 1000);
        }, 500);
        pendingPrint = false;
    }

    var printBtn = document.getElementById('printDocBtn');
    var viewerPrintBtn = document.getElementById('viewerPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            if (modal.classList.contains('show')) {
                printNow();
            } else {
                pendingPrint = true;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    }
    if (viewerPrintBtn) {
        viewerPrintBtn.addEventListener('click', function () { printNow(); });
    }
    // Ctrl/Cmd+P opens the browser's own print dialog, which stamps page
    // count, date/time and the page title onto the output — no web code can
    // remove those. Route the shortcut through the app's clean print flow
    // instead (PDF viewer print or PDF export: no header/footer text).
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 'p') {
            e.preventDefault();
            if (printBtn) {
                printBtn.click();
            } else {
                pendingPrint = true;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        }
    });
    var confirmDeleteBtn = document.getElementById('confirmDeleteDocBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            var f = document.getElementById('deleteDocForm');
            if (f) f.submit();
        });
    }

    // Best-effort view-only hardening: no right-click menu, no text
    // selection, no dragging inside the viewer.
    body.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    body.addEventListener('dragstart', function (e) { e.preventDefault(); });
    body.addEventListener('copy', function (e) { e.preventDefault(); });
    document.addEventListener('selectstart', function (e) {
        if (modal.classList.contains('show') && body.contains(e.target)) e.preventDefault();
    });

    function clear() {
        body.innerHTML = '<span class="text-muted small"><i class="bi bi-hour2"></i> Loading…</span>';
        if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
    }

    var DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    // mammoth.js converts DOCX to HTML in the browser; loaded lazily the
    // first time a .docx is opened so other documents never pay for it.
    function loadMammoth() {
        if (window.mammoth) return Promise.resolve();
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.8.0/mammoth.browser.min.js';
            s.onload = resolve;
            s.onerror = function () { reject(new Error('mammoth failed to load')); };
            document.head.appendChild(s);
        });
    }

    function renderDocx(blob) {
        body.innerHTML = '<span class="text-muted small"><i class="bi bi-hour2"></i> Rendering document…</span>';
        blob.arrayBuffer()
            .then(function (buffer) { return loadMammoth().then(function () { return buffer; }); })
            .then(function (buffer) { return mammoth.convertToHtml({ arrayBuffer: buffer }); })
            .then(function (result) {
                body.innerHTML = '<div class="p3-docx" style="user-select:none;text-align:left">' + result.value + '</div>';
                if (pendingPrint) setTimeout(printNow, 600);
            })
            .catch(function () {
                body.innerHTML = '<div class="text-center small text-muted p-4">'
                    + '<i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>'
                    + 'Could not render this DOCX in the browser. '
                    + 'Use <strong>Download</strong> to open it in Word.</div>';
            });
    }

    function render(blob) {
        var mime = (blob.type || '').toLowerCase();
        var name = (@json($document->original_filename) || '').toLowerCase();
        var isDocx = mime === DOCX_MIME || name.slice(-5) === '.docx';

        if (isDocx) { renderDocx(blob); return; }

        objectUrl = URL.createObjectURL(blob);
        var html;
        if (mime === 'application/pdf') {
            // toolbar=0 hides the PDF viewer's own download/print controls
            // where the browser honors it.
            html = '<iframe src="' + objectUrl + '#toolbar=0&navpanes=0&view=FitH" '
                + 'style="width:100%;height:100%;border:0;background:#fff" '
                + 'oncontextmenu="return false"></iframe>';
        } else if (mime.indexOf('image/') === 0) {
            html = '<img src="' + objectUrl + '" alt="Document preview" draggable="false" '
                + 'style="max-width:100%;max-height:100%;-webkit-user-drag:none;user-select:none">';
        } else {
            html = '<pre class="small mb-0 p-3" style="white-space:pre-wrap;user-select:none"></pre>';
        }
        body.innerHTML = html;
        if (html.indexOf('<pre') === 0) {
            blob.text().then(function (t) { body.querySelector('pre').textContent = t; });
        }
        var iframe = body.querySelector('iframe');
        if (iframe && pendingPrint) {
            iframe.onload = function () { setTimeout(printNow, 400); };
        }
        if (pendingPrint) setTimeout(printNow, 1200);
    }

    modal.addEventListener('shown.bs.modal', function () {
        clear();
        fetch(@json(route('documents.view', $document)), { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.blob();
            })
            .then(render)
            .catch(function () {
                body.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Could not load the document.</span>';
            });
    });
    modal.addEventListener('hidden.bs.modal', clear);
})();
</script>
<style>
    /* Sanitized rich content saved from the in-app editor. */
    .doc-render { line-height: 1.7; overflow-wrap: anywhere; }
    .doc-render h1 { font-size: 1.6rem; } .doc-render h2 { font-size: 1.35rem; }
    .doc-render h3 { font-size: 1.15rem; } .doc-render h4 { font-size: 1.02rem; }
    .doc-render table { border-collapse: collapse; width: 100%; margin: .75rem 0; }
    .doc-render td, .doc-render th { border: 1px solid #cfd8d3; padding: .4rem .6rem; }
    .doc-render th { background: #f1f6f3; font-weight: 600; }
    .doc-render img { max-width: 100%; height: auto; border-radius: .35rem; }
    .doc-render blockquote { border-left: 3px solid #00A868; margin: .75rem 0; padding: .25rem 0 .25rem .9rem; color: #4c5a53; }
    .doc-render hr { border: 0; border-top: 1px solid var(--p3-card-border); margin: 1rem 0; }
    .doc-render a { color: #00743F; }
    [data-theme="dark"] .doc-render th { background: #1f2b24; }
    [data-theme="dark"] .doc-render td, [data-theme="dark"] .doc-render th { border-color: #2c3d34; }
    [data-theme="dark"] .doc-render blockquote { color: #b8c4bd; }
    @media print { #document-content { border: 0 !important; box-shadow: none !important; } }
</style>
@endsection



