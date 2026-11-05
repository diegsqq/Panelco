@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
    <div>
        <h3 class="fw-bold mb-1">System health</h3>
        <p class="text-muted mb-0">Read-only status for core SmartDocs services and processing queues.</p>
    </div>
    <span class="small text-muted">Checked {{ now()->format('M d, Y H:i:s') }}</span>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card card-p3 p-4 h-100">
            <h5 class="fw-semibold"><i class="bi bi-database me-2" style="color:#00A868"></i>Database</h5>
            <span class="badge text-bg-{{ $database['status'] === 'ok' ? 'success' : 'danger' }} align-self-start">{{ $database['status'] === 'ok' ? 'Available' : 'Unavailable' }}</span>
            <p class="text-muted small mt-2 mb-0">{{ $database['detail'] }}</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-p3 p-4 h-100">
            <h5 class="fw-semibold"><i class="bi bi-list-task me-2" style="color:#00A868"></i>Queue</h5>
            <span class="badge text-bg-{{ ['ok' => 'success', 'warning' => 'warning', 'error' => 'danger', 'info' => 'secondary'][$queue['status']] }} align-self-start">{{ ucfirst($queue['status']) }}</span>
            <p class="text-muted small mt-2 mb-2">{{ $queue['detail'] }}</p>
            @if($queue['pending'] !== null || $queue['failed'] !== null)
                <div class="small d-flex gap-3">
                    @if($queue['pending'] !== null)<span>{{ $queue['pending'] }} queued jobs</span>@endif
                    @if($queue['failed'] !== null)<span class="{{ $queue['failed'] > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $queue['failed'] }} failed jobs</span>@endif
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-p3 p-4 h-100">
            <h5 class="fw-semibold"><i class="bi bi-file-earmark-text me-2" style="color:#00A868"></i>Document processing</h5>
            @if($documents['pending'] === null)
                <p class="text-muted small mb-0">Processing counts are unavailable.</p>
            @else
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge text-bg-warning">{{ $documents['pending'] }} pending</span>
                    <span class="badge text-bg-info">{{ $documents['processing'] }} processing</span>
                    <span class="badge text-bg-{{ $documents['failed'] > 0 ? 'danger' : 'success' }}">{{ $documents['failed'] }} failed</span>
                </div>
            @endif
            <a href="{{ route('queue.index') }}" class="small mt-3">Open processing queue</a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-p3 p-4 h-100">
            <h5 class="fw-semibold"><i class="bi bi-stars me-2" style="color:#00A868"></i>AI provider</h5>
            <span class="badge text-bg-{{ $ai['status'] === 'ok' ? 'success' : 'secondary' }} align-self-start">{{ $ai['status'] === 'ok' ? 'Available' : 'Not configured' }}</span>
            <p class="text-muted small mt-2 mb-0">{{ $ai['detail'] }}</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-p3 p-4 h-100">
            <h5 class="fw-semibold"><i class="bi bi-envelope me-2" style="color:#00A868"></i>Email</h5>
            <p class="mb-0">Configured mail driver: <span class="fw-semibold">{{ $mailDriver }}</span></p>
            <small class="text-muted mt-2">This reports configuration only; it does not send a test email.</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-p3 p-4 h-100">
            <h5 class="fw-semibold"><i class="bi bi-hdd me-2" style="color:#00A868"></i>Private document storage</h5>
            <p class="mb-0">Configured storage driver: <span class="fw-semibold">{{ $storageDriver }}</span></p>
            <small class="text-muted mt-2">Storage credentials and filesystem paths are not displayed.</small>
        </div>
    </div>
</div>
@endsection
