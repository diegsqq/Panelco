@extends('layouts.app')
@section('content')
<a href="{{ route('documents.library') }}" class="small text-decoration-none" style="color:#009040">&larr; Back to library</a>
<div class="row g-3 mt-1">
<div class="col-lg-8">
<div class="card card-p3 p-4">
<div class="d-flex justify-content-between align-items-start">
<div><h4 class="fw-bold mb-1">{{ $document->title ?? $document->original_filename }}</h4><small class="text-muted">{{ $document->original_filename }} · {{ $document->mime_type }} · {{ number_format($document->file_size / 1024, 1) }} KB</small></div>
<span class="badge status-{{ $document->processing_status }}">{{ $document->processing_status }}</span>
</div>
<hr>
<dl class="row small mb-0">
<dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ $document->document_type ?? '—' }}</dd>
<dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $document->category?->name ?? $document->suggestedCategory?->name ?? '—' }}@if(!$document->category && $document->suggestedCategory) <span class="text-muted">suggested{{ $document->classification_confidence !== null ? ' · ' . round($document->classification_confidence * 100) . '%' : '' }}</span>@endif</dd>
<dt class="col-sm-4">Department</dt><dd class="col-sm-8">{{ $document->department?->name ?? '—' }}</dd>
<dt class="col-sm-4">Document date</dt><dd class="col-sm-8">{{ $document->document_date?->format('Y-m-d') ?? '—' }}</dd>
<dt class="col-sm-4">Uploaded</dt><dd class="col-sm-8">{{ $document->created_at->format('M d, Y') }} by {{ $document->uploader?->name ?? '—' }}</dd>
<dt class="col-sm-4">Approved</dt><dd class="col-sm-8">{{ $document->approved_at ? $document->approved_at->format('M d, Y') . ' by ' . ($document->approver?->name ?? '—') : '—' }}</dd>
<dt class="col-sm-4">Keywords</dt><dd class="col-sm-8">@if($document->keywords)@foreach(array_filter(array_map('trim', explode(',', $document->keywords))) as $kw)<span class="badge me-1 mb-1" style="background:#e6f3ea;color:#006030">{{ $kw }}</span>@endforeach@else<span class="text-muted">—</span>@endif</dd>
@if ($document->processing_error)<dt class="col-sm-4">Error</dt><dd class="col-sm-8 text-danger">{{ $document->processing_error }}</dd>@endif
</dl>
@can('approve', $document)
<hr>
<form method="POST" action="{{ route('documents.approve', $document) }}" class="row g-2 align-items-end">
@csrf
<div class="col-md-7"><label class="form-label small fw-semibold" for="category_id">Approve classification</label><select id="category_id" name="category_id" class="form-select form-select-sm">@foreach($categories as $c)<option value="{{ $c->id }}" {{ (int) old('category_id', $document->suggested_category_id ?? $document->category_id) === (int) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-5"><button class="btn btn-sm w-100" style="background:#009040;color:#fff">Approve classification</button></div>
</form>
@endcan
</div>
<div class="card card-p3 p-4 mt-3">
<h6 class="fw-bold">Summary <span class="badge" style="background:#e6f3ea;color:#006030">unverified</span></h6>@if($document->summary)
<p class="mb-0">{{ $document->summary }}</p>
@elseif($document->processing_status === 'completed')
<p class="mb-0 text-muted">No summary available.</p>
@else
<p class="mb-0 text-muted">Still processing.</p>
@endif
</div>
@if($highlightedHtml)
<div class="card card-p3 p-4 mt-3" id="cited-text">
<h6 class="fw-bold">Document text</h6>
<p class="small" style="white-space:pre-wrap">{!! $highlightedHtml !!}</p>
</div>
@endif
@if(!empty($details))
<div class="card card-p3 p-4 mt-3">
<h6 class="fw-bold">Important details</h6>
<ul class="small mb-0 ps-4">@foreach($details as $d)<li class="mb-1">{{ $d }}</li>@endforeach</ul>
</div>
@endif
<div class="card card-p3 p-4 mt-3">
<h6 class="fw-bold"><i class="bi bi-lightning-charge-fill me-1" style="color:#009040"></i>Ask Lightning about this document</h6>
<div class="d-flex flex-wrap gap-2">
<a class="btn btn-sm btn-outline-success" href="{{ route('chat.index', ['q' => 'Summarize ' . ($document->title ?? $document->original_filename)]) }}">Summarize it</a>
<a class="btn btn-sm btn-outline-success" href="{{ route('chat.index', ['q' => 'What are the important details in ' . ($document->title ?? $document->original_filename) . '?']) }}">Important details</a>
<a class="btn btn-sm btn-outline-success" href="{{ route('chat.index', ['q' => 'What corrective actions were recorded in ' . ($document->title ?? $document->original_filename) . '?']) }}">Corrective actions</a>
</div>
</div>
</div>
<div class="col-lg-4 d-flex flex-column gap-3">
<div class="card card-p3 p-3">
<h6 class="fw-bold">Actions</h6>
<div class="d-grid gap-2">
@can('download', $document)<a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Download</a>@endcan
@if ($document->processing_status === 'failed')
@can('update', $document)<form method="POST" action="{{ route('documents.retry', $document) }}">@csrf<button class="btn btn-sm w-100" style="background:#009040;color:#fff">Retry processing</button></form>@endcan
@endif
@can('delete', $document)<form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Delete this document?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm w-100">Delete</button></form>@endcan
</div>
</div>
<div class="card card-p3 p-3">
<h6 class="fw-bold">Processing</h6>
<p class="small mb-1">Status: <strong>{{ $document->processing_status }}</strong></p>
<p class="small text-muted mb-0">{{ $document->chunks->count() }} indexed passages.</p>
</div>
</div>
</div>
@endsection
