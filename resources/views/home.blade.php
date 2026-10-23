@extends('layouts.app')

@section('content')
<style>
    .recent-row { transition: background-color .15s ease; }
    .recent-row:hover { background: #e6f3ea; }
    .recent-row + .recent-row { border-top: 0; }
    .recent-list .recent-row { flex: 1 0 auto; align-items: center; }
    .recent-list .recent-row:last-child { border-bottom: 0 !important; }
</style>
<div class="p-3 mb-3 text-white d-flex flex-wrap justify-content-between align-items-center" style="background:linear-gradient(135deg,#00A868,#1DBF7D);border-radius:1rem">
    <div>
        <h4 class="fw-bold mb-0">Good day, {{ auth()->user()->name }}</h4>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('documents.upload') }}" class="btn" style="background:#ffffff;color:#00A868;font-weight:700"><i class="bi bi-cloud-upload me-1"></i>Upload</a>
        <a href="{{ route('chat.index') }}" class="btn btn-outline-light"><i class="bi bi-chat-dots me-1"></i>Ask Lightning</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card card-p3 lift p-3 d-flex flex-column" style="height:calc(100vh - 150px);min-height:400px">
            <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0">Recently uploaded</h6><a href="{{ route('documents.library') }}" class="small">View library</a></div>
            <div class="recent-list flex-grow-1" style="min-height:0;display:flex;flex-direction:column;overflow-y:auto;overflow-x:hidden;padding-right:2px">
            @forelse ($recent as $doc)
                @php
                    $mime = $doc->mime_type ?? '';
                    $docIcon = str_contains($mime, 'pdf') ? ['bi-file-earmark-pdf-fill', '#dc3545', '#fdeaea']
                        : (str_contains($mime, 'word') ? ['bi-file-earmark-word-fill', '#0d6efd', '#e7f0ff']
                        : (str_starts_with($mime, 'image/') ? ['bi-file-earmark-image-fill', '#00A868', '#e6f3ea']
                        : (str_contains($mime, 'text') ? ['bi-file-earmark-text-fill', '#6c757d', '#f1f3f5']
                        : ['bi-file-earmark-fill', '#00A868', '#e6f3ea'])));
                @endphp
                <a href="{{ route('documents.show', $doc) }}" class="d-flex gap-3 align-items-center recent-row text-decoration-none border-bottom py-1 px-2" style="border-color:#e8f0ea !important;border-radius:.5rem">
                    <span class="stat-icon flex-shrink-0" style="width:34px;height:34px;background:{{ $docIcon[2] }};color:{{ $docIcon[1] }};font-size:.95rem"><i class="bi {{ $docIcon[0] }}"></i></span>
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="fw-semibold text-truncate" style="color:#00743F;font-size:.92rem">{{ $doc->title ?? $doc->original_filename }}</div>
                        <div class="text-truncate" style="font-size:.72rem;color:#7d8f86">{{ $doc->category?->name ?? 'Uncategorized' }}{{ $doc->department ? ' · '.$doc->department->name : '' }} · {{ $doc->created_at->diffForHumans() }}</div>
                        @if($doc->summary)<div class="text-truncate" style="font-size:.74rem;color:#9aa8a1">{{ $doc->summary }}</div>@endif
                    </div>
                    @unless($doc->processing_status === 'completed')<span class="badge status-{{ $doc->processing_status }} flex-shrink-0">{{ $doc->processing_status }}</span>@endunless
                    <i class="bi bi-chevron-right flex-shrink-0" style="font-size:.7rem;color:#b7c4bd"></i>
                </a>
            @empty
                <div class="text-center py-4"><i class="bi bi-inbox fs-2 text-muted"></i><p class="fw-semibold mt-2 mb-2">No documents yet.</p><a href="{{ route('documents.upload') }}" class="btn btn-sm" style="background:#00A868;color:#fff">Upload your first document</a></div>
            @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5 d-flex flex-column gap-2">
        <div class="card card-p3 p-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="fw-bold mb-0">Category distribution</h6>
            <span class="badge" style="background:#e6f3ea;color:#00743F">{{ $stats['total'] }} docs</span>
            </div>
            @if($categoryDistribution->isNotEmpty())
            @php($max = max($categoryDistribution->max('total'), 1))
            @foreach ($categoryDistribution as $row)
                <div class="d-flex align-items-center gap-2" style="margin-bottom:.35rem">
                    <span class="text-truncate" style="width:35%;min-width:0;font-size:.82rem">{{ $row->category?->name ?? 'Uncategorized' }}</span>
                    <div class="flex-grow-1" style="height:14px;background:#eef4f0;border-radius:4px;overflow:hidden">
                        <div style="width:{{ round($row->total / $max * 100) }}%;height:100%;background:linear-gradient(90deg,#00A868,#1DBF7D);border-radius:4px"></div>
                    </div>
                    <strong style="width:40px;text-align:right;font-size:.85rem">{{ $row->total }}</strong>
                </div>
            @endforeach
            @else
                <p class="text-muted mb-0 small">No categories yet.</p>
            @endif
            <small class="text-muted">Categories are configurable.</small>
        </div>
        <div class="card card-p3 p-3">
            <h6 class="fw-bold">Recent conversations</h6>
            @forelse ($recentConversations as $c)
                <div class="border-bottom py-1 small"><a href="{{ route('chat.show', $c) }}" class="text-decoration-none" style="color:#00743F">{{ $c->title }}</a> <span class="text-muted">· {{ $c->created_at->diffForHumans() }}</span></div>
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
