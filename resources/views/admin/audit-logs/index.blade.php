@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Audit Logs</h4>
<form method="GET" class="d-flex gap-2">
<select name="category" class="form-select form-select-sm" aria-label="Filter by event category">
<option value="">All activity</option>
<option value="auth" {{ request('category') === 'auth' ? 'selected' : '' }}>Sign-in &amp; security</option>
<option value="document" {{ request('category') === 'document' ? 'selected' : '' }}>Document activity</option>
<option value="user" {{ request('category') === 'user' ? 'selected' : '' }}>User administration</option>
</select>
<input name="action" class="form-control form-control-sm" value="{{ request('action') }}" placeholder="Filter by action…">
<button class="btn btn-sm btn-outline-success">Filter</button>
</form>
</div>
<div class="card card-p3 p-0 overflow-hidden">
<table class="table mb-0 small">
<thead class="table-light"><tr><th>Time</th><th>User</th><th>Action</th><th>Target</th><th>Context</th></tr></thead>
<tbody>
@forelse ($logs as $log)
<tr>
<td title="{{ $log->created_at }}">{{ $log->created_at->format('M d, H:i') }}</td>
<td>{{ $log->user?->name ?? 'system' }}</td>
<td>{{ \Illuminate\Support\Str::headline($log->action) }}</td>
<td>{{ $log->entity_type ? class_basename($log->entity_type) . ' #' . $log->entity_id : '—' }}</td>
<td>
@if($log->metadata['identifier'] ?? false)<div>{{ $log->metadata['identifier'] }}</div>@endif
@if($log->metadata['ip'] ?? false)<div class="text-muted">{{ $log->metadata['ip'] }}</div>@endif
@if($log->metadata['user_agent'] ?? false)<small class="text-muted" title="{{ $log->metadata['user_agent'] }}">{{ \Illuminate\Support\Str::limit($log->metadata['user_agent'], 60) }}</small>@endif
</td>
</tr>
@empty
<tr><td colspan="5" class="text-center text-muted p-4">No logs yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
