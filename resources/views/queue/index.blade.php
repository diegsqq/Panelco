@extends('layouts.app')
@section('content')
<h3 class="fw-bold mb-3">Processing Queue</h3>
<div class="card card-p3 px-3 py-2 mb-3">
<div class="d-flex flex-wrap gap-4 small">
<span><span class="text-muted">Pending</span> <strong>{{ $stats['pending'] }}</strong></span>
<span><span class="text-muted">Processing</span> <strong>{{ $stats['processing'] }}</strong></span>
<span><span class="text-muted">Completed</span> <strong>{{ $stats['completed'] }}</strong></span>
<span><span class="text-muted">Failed</span> <strong>{{ $stats['failed'] }}</strong></span>
</div>
</div>
<div class="card card-p3 p-0 overflow-hidden">
@forelse ($items as $doc)
<div class="d-flex justify-content-between align-items-center border-bottom p-3 src-card position-relative">
<div><a href="{{ route('documents.show', $doc) }}" class="fw-semibold text-decoration-none stretched-link" style="color:#006030">{{ $doc->title ?? $doc->original_filename }}</a><div><small class="text-muted">uploaded {{ $doc->created_at->diffForHumans() }}@if($doc->processing_error) · <span class="text-danger">{{ $doc->processing_error }}</span>@endif</small></div></div>
<div class="d-flex align-items-center gap-2">
<span class="badge status-{{ $doc->processing_status }}">{{ $doc->processing_status }}</span>
@if ($doc->processing_status === 'failed')<form method="POST" action="{{ route('documents.retry', $doc) }}" style="position:relative;z-index:1">@csrf<button class="btn btn-sm" style="background:#009040;color:#fff">Retry</button></form>@endif
</div>
</div>
@empty
<div class="p-5 text-center text-muted">Queue is clear.</div>
@endforelse
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
