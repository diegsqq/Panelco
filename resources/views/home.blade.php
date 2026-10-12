@extends('layouts.app')

@section('content')
<div class="p-4 mb-4 text-white d-flex flex-wrap justify-content-between align-items-center" style="background:linear-gradient(135deg,#009040,#00a040);border-radius:1rem">
    <div>
        <h3 class="fw-bold mb-0">Good day, {{ auth()->user()->name }}</h3>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('documents.upload') }}" class="btn" style="background:#ffffff;color:#009040;font-weight:700"><i class="bi bi-cloud-upload me-1"></i>Upload</a>
        <a href="{{ route('chat.index') }}" class="btn btn-outline-light"><i class="bi bi-chat-dots me-1"></i>Ask AI</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-p3 lift p-3 h-100"><div class="d-flex justify-content-between align-items-center"><small class="text-muted">Total uploaded</small><span class="stat-icon" style="background:#e6f3ea;color:#009040"><i class="bi bi-files"></i></span></div><h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3></div></div>
    <div class="col-md-3"><div class="card card-p3 lift p-3 h-100"><div class="d-flex justify-content-between align-items-center"><small class="text-muted">Processed</small><span class="stat-icon" style="background:#e6f3ea;color:#009040"><i class="bi bi-check-circle"></i></span></div><h3 class="fw-bold mb-0">{{ $stats['completed'] }}</h3></div></div>
    <div class="col-md-3"><div class="card card-p3 lift p-3 h-100"><div class="d-flex justify-content-between align-items-center"><small class="text-muted">Awaiting processing</small><span class="stat-icon" style="background:#e6f3ea;color:#009040"><i class="bi bi-hourglass-split"></i></span></div><h3 class="fw-bold mb-0">{{ $stats['pending'] }}</h3></div></div>
    <div class="col-md-3"><div class="card card-p3 lift p-3 h-100"><div class="d-flex justify-content-between align-items-center"><small class="text-muted">Failed jobs</small><span class="stat-icon" style="background:#e6f3ea;color:#009040"><i class="bi bi-exclamation-triangle"></i></span></div><h3 class="fw-bold mb-0">{{ $stats['failed'] }}</h3></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card card-p3 lift p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0">Recently uploaded</h6><a href="{{ route('documents.library') }}" class="small">View library</a></div>
            @forelse ($recent as $doc)
                <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                    <div class="d-flex gap-2">
                        <span class="stat-icon" style="background:#e6f3ea;color:#009040;font-size:1rem"><i class="bi bi-file-earmark-text"></i></span>
                        <div><div class="fw-semibold">{{ $doc->title ?? $doc->original_filename }}</div><small class="text-muted">{{ $doc->category?->name ?? 'Uncategorized' }}{{ $doc->department ? ' · '.$doc->department->name : '' }} · {{ $doc->created_at->diffForHumans() }}</small></div>
                    </div>
                    <span class="badge status-{{ $doc->processing_status }}">{{ $doc->processing_status }}</span>
                </div>
            @empty
                <div class="text-center py-4"><i class="bi bi-inbox fs-2 text-muted"></i><p class="fw-semibold mt-2 mb-2">No documents yet.</p><a href="{{ route('documents.upload') }}" class="btn btn-sm" style="background:#009040;color:#fff">Upload your first document</a></div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-5 d-flex flex-column gap-3">
        <div class="card card-p3 p-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="fw-bold mb-0">Category distribution</h6>
            <span class="badge" style="background:#e6f3ea;color:#006030">{{ $stats['total'] }} docs</span>
            </div>
            @if($categoryDistribution->isNotEmpty())
            @foreach ($categoryDistribution as $row)
                @php($pct = $stats['total'] ? round($row->total / max($stats['total'],1) * 100) : 0)
                <div class="mb-2"><div class="d-flex justify-content-between small"><span>{{ $row->category?->name ?? 'Uncategorized' }}</span><strong>{{ $row->total }}</strong></div><div class="progress" style="height:6px"><div class="progress-bar" style="width:{{ $pct }}%;background:#009040"></div></div></div>
            @endforeach
            @else
                <p class="text-muted mb-0 small">No categories yet.</p>
            @endif
            <small class="text-muted">Categories are configurable.</small>
        </div>
        <div class="card card-p3 p-3">
            <h6 class="fw-bold">Recent conversations</h6>
            @forelse ($recentConversations as $c)
                <div class="border-bottom py-1 small"><a href="{{ route('chat.show', $c) }}" class="text-decoration-none" style="color:#006030">{{ $c->title }}</a> <span class="text-muted">· {{ $c->created_at->diffForHumans() }}</span></div>
            @empty
                <p class="text-muted mb-0 small">No conversations yet.</p>
            @endforelse
        </div>
        @can('manage-documents')
        <div class="card card-p3 p-3">
            <h6 class="fw-bold">Recent activity</h6>
            @forelse ($recentAudit as $log)
                <div class="border-bottom py-1 small">{{ \Illuminate\Support\Str::headline($log->action) }} <span class="text-muted">· {{ $log->user?->name ?? 'system' }} · {{ $log->created_at->diffForHumans() }}</span></div>
            @empty
                <p class="text-muted mb-0 small">No activity yet.</p>
            @endforelse
        </div>
        @endcan
    </div>
</div>
@endsection
