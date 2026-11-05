@extends('layouts.app')
@section('content')
@php $showForm = $errors->any() || count(old()) > 0; @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">User Management</h4>
<button class="btn btn-outline-success" data-bs-toggle="collapse" data-bs-target="#userCreateForm" aria-expanded="{{ $showForm ? 'true' : 'false' }}"><i class="bi bi-person-plus-fill me-1"></i>New User</button>
</div>
<div class="collapse {{ $showForm ? 'show' : '' }} mb-4" id="userCreateForm">
<div class="card card-p3 p-4">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-person-plus-fill"></i></span>
<div>
<div class="fw-semibold">Create Employee Account</div>
<small class="text-muted">New accounts default to Employee. Change the role only when elevated access is required.</small>
</div>
</div>
<form method="POST" action="{{ route('admin.users.store') }}">
@csrf
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Full Name</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span><input name="name" class="form-control" value="{{ old('name') }}" placeholder="Juan Dela Cruz" required></div>
</div>
<div class="col-md-6">
<label class="form-label">Email</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span><input name="email" type="email" class="form-control" value="{{ old('email') }}" placeholder="juan@panelco.gov.ph" required></div>
</div>
<div class="col-md-6">
<label class="form-label">Password</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span><input name="password" type="password" class="form-control" minlength="12" required></div>
<small class="text-muted">12+ characters, uppercase and lowercase, number, and symbol.</small>
</div>
<div class="col-md-6">
<label class="form-label">Confirm Password</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span><input name="password_confirmation" type="password" class="form-control" minlength="12" required></div>
</div>
<div class="col-md-4">
<label class="form-label">Role</label>
<select name="role" class="form-select" required>@foreach ([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_ADMIN] as $r)<option value="{{ $r }}" {{ old('role', \App\Models\User::ROLE_EMPLOYEE) === $r ? 'selected' : '' }}>{{ \App\Models\User::roleLabel($r) }}</option>@endforeach</select>
</div>
<div class="col-md-4">
<label class="form-label">Primary Department</label>
<select name="department_id" class="form-select"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ (string) old('department_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select>
</div>
<div class="col-md-4" id="createAddDeptField">
<label class="form-label">Additional Departments</label>
<div class="border rounded p-2 d-flex flex-wrap gap-2" style="min-height:44px;background:#fff">
@forelse ($departments as $d)
<label class="btn btn-sm p3-dept-pill">
<input type="checkbox" name="departments[]" value="{{ $d->id }}" class="form-check-input me-1" {{ in_array($d->id, old('departments', [])) ? 'checked' : '' }}>{{ $d->name }}
</label>
@empty
<small class="text-muted align-self-center">No departments yet.</small>
@endforelse
</div>
<small class="text-muted">Select or clear a checkbox to add or remove a department.</small>
</div>
</div>
<div class="d-flex gap-2 mt-4">
<button class="btn btn-outline-success px-4"><i class="bi bi-check-lg me-1"></i>Save</button>
<button type="button" class="btn btn-outline-secondary px-4" data-bs-toggle="collapse" data-bs-target="#userCreateForm">Cancel</button>
</div>
</form>
</div>
</div>
<div class="card card-p3 p-0 overflow-hidden">
<table class="table mb-0 align-middle">
<thead class="table-light"><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Department</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
<tbody>
@forelse ($users as $u)
<tr>
<td class="fw-semibold">{{ $u->name }}</td><td class="text-break">{{ $u->email }}</td>
<td><span class="badge text-capitalize" style="background:#e6f3ea;color:var(--p3-navy)">{{ \App\Models\User::roleLabel($u->role) }}</span></td>
<td><span class="badge {{ $u->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>{{ $u->department?->name ?? '—' }}</td>
<td class="text-end"><a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-success">Edit</a></td>
</tr>
@empty
<tr><td colspan="6" class="text-center text-muted p-4">No users yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-3">{{ $users->links() }}</div>
<script>
(function () {
    var role = document.querySelector('#userCreateForm select[name="role"]');
    var field = document.getElementById('createAddDeptField');
    if (!role || !field) return;
    function sync() { field.classList.toggle('d-none', role.value === @json(\App\Models\User::ROLE_ADMIN)); }
    role.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
