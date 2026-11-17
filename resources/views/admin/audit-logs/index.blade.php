@extends('layouts.app')

@section('content')
<style>
    .audit-table {
        --bs-table-bg: transparent;
    }
    .audit-table thead th {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 700;
        color: #6c757d;
        background: #f7faf8;
        border-bottom: 2px solid #e2efe6;
        padding: .6rem .9rem;
    }
    [data-theme="dark"] .audit-table thead th {
        color: #8fa59a;
        background: #101a14;
        border-bottom-color: #26362e;
    }
    .audit-table tbody tr { line-height: 1.35; }
    .audit-table tbody td {
        font-size: .82rem;
        padding: .55rem .9rem;
        vertical-align: middle;
    }
    [data-theme="dark"] .audit-table tbody td {
        color: #c3d0c8;
    }
    .audit-table tbody tr:hover {
        background: #f4faf6;
    }
    [data-theme="dark"] .audit-table tbody tr:hover {
        background: #12291d;
    }
    .audit-table .text-nowrap {
        white-space: nowrap;
    }
    #main-content > div.mt-3 {
        margin-top: .75rem;
    }
    .pagination {
        margin-bottom: 0;
    }
    .pagination .page-link {
        padding: .35rem .7rem;
        font-size: .8rem;
        border-radius: .5rem;
    }
    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        padding: .35rem .55rem;
    }
    #main-content > div.mt-3 > nav {
        justify-content: center;
    }
    #main-content > div.mt-3 > nav > div {
        justify-content: center;
        align-items: center;
        gap: .5rem;
        padding: .5rem .75rem;
        background: #f8f9fa;
        border-radius: .5rem;
        border: 1px solid #dee2e6;
    }
    [data-theme="dark"] #main-content > div.mt-3 > nav > div {
        background: #12291d;
        border-color: #26362e;
    }
    #main-content > div.card.card-p3.p-0.overflow-hidden > table > tbody > tr:nth-child(5) {
        height: 44px;
    }
    #main-content > div.card.card-p3.p-0.overflow-hidden > table > tbody > tr:nth-child(5) > td {
        padding-top: .55rem;
        padding-bottom: .55rem;
        line-height: 1.35;
    }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Audit Logs</h4>
    <form method="GET" class="d-flex flex-wrap gap-2" role="search">
        <select name="category" class="form-select form-select-sm" aria-label="Filter by event category">
        <option value="">All activity</option>
        <option value="auth" {{ request('category') === 'auth' ? 'selected' : '' }}>Sign-in &amp; security</option>
        <option value="document" {{ request('category') === 'document' ? 'selected' : '' }}>Document activity</option>
        <option value="user" {{ request('category') === 'user' ? 'selected' : '' }}>User administration</option>
        </select>
        <input name="action" class="form-control form-control-sm" value="{{ request('action') }}" placeholder="Filter by action…" aria-label="Filter by action">
        <input name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Search users or review notes…" aria-label="Search audit users, actions, and review notes">
        <button class="btn btn-sm btn-outline-success">Filter</button>
        </form>
</div>
<div class="card card-p3 p-0 overflow-hidden">
    <table class="table mb-0 align-middle audit-table">
        <thead class="table-light">
            <tr>
                <th scope="col">ID Number</th>
                <th scope="col">Name</th>
                <th scope="col">Action</th>
                <th scope="col">Target</th>
                <th scope="col">Details</th>
                <th scope="col">Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
            <tr>
                <td class="text-nowrap"><span class="fw-semibold">{{ $log->user?->employee_id ?? '—' }}</span></td>
                <td>{{ $log->user?->name ?? 'System' }}</td>
                <td><span class="badge" style="background:#e6f3ea;color:#00743F">{{ \Illuminate\Support\Str::headline($log->action) }}</span></td>
                <td>{{ $log->entity_type ? class_basename($log->entity_type) . ' #' . $log->entity_id : '—' }}</td>
                <td>
        @if($log->metadata['identifier'] ?? false)<div>{{ $log->metadata['identifier'] }}</div>@endif
        @if($log->metadata['ip'] ?? false)<div class="text-muted">{{ $log->metadata['ip'] }}</div>@endif
        @if($log->metadata['user_agent'] ?? false)<small class="text-muted" title="{{ $log->metadata['user_agent'] }}">{{ \Illuminate\Support\Str::limit($log->metadata['user_agent'], 60) }}</small>@endif
        @if($log->metadata['note'] ?? false)<div class="mt-1">{{ $log->metadata['note'] }}</div>@endif
                </td>
                <td class="text-nowrap" title="{{ $log->created_at }}">{{ $log->created_at->format('M d, H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted p-4">No logs yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
