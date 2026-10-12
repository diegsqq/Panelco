@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Audit Logs</h4>
<form method="GET" class="d-flex gap-2"><input name="action" class="form-control form-control-sm" value="{{ request('action') }}" placeholder="Filter by action…"><button class="btn btn-sm btn-outline-success">Filter</button></form>
</div>
<div class="card card-p3 p-0 overflow-hidden">
<table class="table mb-0 small">
<thead class="table-light"><tr><th>Time</th><th>User</th><th>Action</th><th>Target</th></tr></thead>
<tbody>
@forelse ($logs as $log)
<tr><td title="{{ $log->created_at }}">{{ $log->created_at->format('M d, H:i') }}</td><td>{{ $log->user?->name ?? 'system' }}</td><td>{{ \Illuminate\Support\Str::headline($log->action) }}</td><td>{{ $log->entity_type ? class_basename($log->entity_type) . ' #' . $log->entity_id : '—' }}</td></tr>
@empty
<tr><td colspan="4" class="text-center text-muted p-4">No logs yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
