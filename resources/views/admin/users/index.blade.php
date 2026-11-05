@extends('layouts.app')
@section('content')
@php $openForm = $errors->any() || count(old()) > 0; @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">User Management</h4>
<button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#newUserModal"><i class="bi bi-person-plus-fill me-1"></i>New User</button>
</div>
<div class="modal fade" id="newUserModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
<div class="modal-content" style="border-radius:1rem;overflow:hidden">
<div class="modal-header border-0 pb-0">
<div class="d-flex align-items-center gap-2">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-person-plus-fill"></i></span>
<div>
<div class="fw-semibold">Create Employee Account</div>
<small class="text-muted">New accounts default to Employee. Change the role only when elevated access is required.</small>
</div>
</div>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body pt-3">
<form method="POST" action="{{ route('admin.users.store') }}" autocomplete="off" id="userCreateFormEl">
@csrf
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Full Name</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span><input name="name" class="form-control" value="{{ old('name') }}" placeholder="Juan Dela Cruz" required></div>
</div>
<div class="col-md-6">
<label class="form-label">Email</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span><input name="email" type="email" class="form-control" value="{{ old('email') }}" placeholder="juan@panelco.gov.ph" autocomplete="off" autocapitalize="off" spellcheck="false" required></div>
</div>
<div class="col-md-6">
<label class="form-label">Password</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span><input name="password" id="newUserPw" type="password" class="form-control" minlength="12" autocomplete="new-password" required></div>
<small class="text-muted">12+ characters, uppercase and lowercase, number, and symbol.</small>
</div>
<div class="col-md-6">
<label class="form-label">Confirm Password</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span><input name="password_confirmation" id="newUserPwConfirm" type="password" class="form-control" minlength="12" autocomplete="new-password" required></div>
</div>
<div class="col-md-4">
<label class="form-label">Role</label>
<select name="role" class="form-select" required>@foreach ([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_ADMIN] as $r)<option value="{{ $r }}" {{ old('role', \App\Models\User::ROLE_EMPLOYEE) === $r ? 'selected' : '' }}>{{ \App\Models\User::roleLabel($r) }}</option>@endforeach</select>
</div>
<div class="col-md-4">
<label class="form-label">Primary Department</label>
<select name="department_id" class="form-select"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ (string) old('department_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select>
</div>
</div>
</form>
</div>
<div class="modal-footer border-0 pt-0">
<button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
<button type="submit" form="userCreateFormEl" class="btn btn-outline-success px-4"><i class="bi bi-check-lg me-1"></i>Save</button>
</div>
</div>
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
    var modalEl = document.getElementById('newUserModal');
    var form = document.getElementById('userCreateFormEl');
    if (!modalEl || !form) return;

    // Empty the create form whenever the modal closes, and right after a
    // successful save, so no previous user's details linger for the next one.
    function clearForm() {
        form.reset();
        ['newUserPw', 'newUserPwConfirm'].forEach(function (id) {
            var f = document.getElementById(id);
            if (f) { f.value = ''; f.type = 'password'; }
        });
    }

    modalEl.addEventListener('hidden.bs.modal', clearForm);

    // Chrome autofills after page load, so when the server sent no input to
    // repopulate (fresh page or successful save), wipe email/password again.
    @if (! $errors->any() && count(old()) === 0)
    function clearAutofill() {
        var email = form.querySelector('input[name="email"]');
        if (email) email.value = '';
        ['newUserPw', 'newUserPwConfirm'].forEach(function (id) {
            var f = document.getElementById(id);
            if (f) { f.value = ''; f.type = 'password'; }
        });
    }
    window.addEventListener('load', clearAutofill);
    clearAutofill();
    @endif

    // Validation errors (or repopulated input) reopen the modal so the fixes
    // are visible; a clean page stays closed.
    @if ($openForm)
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    @endif

    @if (session('status') && ! $errors->any())
        clearForm();
    @endif
})();
</script>
@endsection
