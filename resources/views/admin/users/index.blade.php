@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">User Management</h4>
<a href="{{ route('admin.users.create') }}" class="btn" style="background:#009040;color:#fff">New User</a>
</div>
<div class="card card-p3 p-0 overflow-hidden">
<table class="table mb-0">
<thead class="table-light"><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
<tbody>
@forelse ($users as $u)
<tr>
<td>{{ $u->name }}</td><td class="text-break">{{ $u->email }}</td>
<td class="text-capitalize">{{ str_replace('_',' ',$u->role) }}</td>
<td>{{ $u->department?->name ?? '—' }}</td>
<td class="text-end"><a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-success">Edit</a></td>
</tr>
@empty
<tr><td colspan="5" class="text-center text-muted p-4">No users yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
