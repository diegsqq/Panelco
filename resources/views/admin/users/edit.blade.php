@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Edit User</h4>
<a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Users</a>
</div>
<div class="card card-p3 p-4">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-person-gear"></i></span>
<div>
<div class="fw-semibold">{{ $user->name }}</div>
<small class="text-muted">Update the account details below.</small>
</div>
</div>
<form method="POST" action="{{ route('admin.users.update', $user) }}">
@csrf
@method('PUT')
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Full Name</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
</div>
<div class="col-md-6">
<label class="form-label">Email</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span><input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
</div>
<div class="col-md-6">
<label class="form-label">New Password <span class="text-muted small">(leave blank to keep current)</span></label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span><input name="password" type="password" class="form-control"></div>
<small class="text-muted">Minimum 8 characters.</small>
</div>
<div class="col-md-6">
<label class="form-label">Confirm New Password</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span><input name="password_confirmation" type="password" class="form-control"></div>
</div>
<div class="col-md-4">
<label class="form-label">Role</label>
<select name="role" class="form-select" required>@foreach (\App\Models\User::ROLES as $r)<option value="{{ $r }}" {{ old('role', $user->role) === $r ? 'selected' : '' }}>{{ \App\Models\User::roleLabel($r) }}</option>@endforeach</select>
</div>
<div class="col-md-4">
<label class="form-label">Primary Department</label>
<select name="department_id" class="form-select"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ (string) old('department_id', $user->department_id) === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select>
</div>
<div class="col-md-4">
<label class="form-label">Additional Departments</label>
<div class="border rounded p-2 d-flex flex-wrap gap-2" style="min-height:44px;background:#fff">
@forelse ($departments as $d)
<label class="btn btn-sm p3-dept-pill">
<input type="checkbox" name="departments[]" value="{{ $d->id }}" class="d-none" {{ in_array($d->id, old('departments', $user->departments->pluck('id')->toArray())) ? 'checked' : '' }}>{{ $d->name }}
</label>
@empty
<small class="text-muted align-self-center">No departments yet.</small>
@endforelse
</div>
<small class="text-muted">Click a department to toggle it.</small>
</div>
</div>
<div class="d-flex gap-2 mt-4">
<button class="btn btn-p3 px-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
<a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
<div class="card card-p3 p-3 mt-4 border-danger">
<div class="d-flex justify-content-between align-items-center">
<div>
<div class="fw-semibold text-danger">Danger Zone</div>
<small class="text-muted">Deleting removes the user and revokes document access. This cannot be undone.</small>
</div>
<form method="POST" action="{{ route('admin.users.destroy', $user) }}" id="deleteUserForm">@csrf @method('DELETE')<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="bi bi-trash me-1"></i>Delete User</button></form>
</div>
</div>
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content" style="border-radius:1rem;overflow:hidden">
<div class="modal-header border-0 pb-0 justify-content-center">
<span class="stat-icon" style="background:#f8d7da;color:#721c24"><i class="bi bi-exclamation-triangle-fill"></i></span>
<button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body pt-2 text-center">
<h6 class="fw-bold">Delete User</h6>
<p class="text-muted small mb-3">You are about to permanently delete <strong>{{ $user->name }}</strong> and revoke their document access. This action cannot be undone.</p>
<label class="form-label small">Type 'CONFIRM' to continue.</label>
<input type="text" id="deleteConfirmInput" class="form-control text-uppercase text-center" placeholder="" autocomplete="off">
</div>
<div class="modal-footer border-0 pt-0 justify-content-center">
<button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
<button type="submit" form="deleteUserForm" id="deleteConfirmBtn" class="btn btn-danger px-4" disabled><i class="bi bi-trash me-1"></i>Delete</button>
</div>
</div>
</div>
</div>
<script>
(function () {
    var modal = document.getElementById('deleteUserModal');
    var input = document.getElementById('deleteConfirmInput');
    var btn = document.getElementById('deleteConfirmBtn');
    if (!modal || !input || !btn) return;
    input.addEventListener('input', function () { btn.disabled = input.value !== 'CONFIRM'; });
    modal.addEventListener('shown.bs.modal', function () { input.value = ''; btn.disabled = true; input.focus(); });
})();
</script>
@endsection
