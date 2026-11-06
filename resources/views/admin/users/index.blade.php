@extends('layouts.app')
@section('content')
@php $openForm = $errors->any() || count(old()) > 0; @endphp
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
<h4 class="mb-0">User Management</h4>
<div class="d-flex align-items-center gap-2 ms-auto">
<form method="GET" action="{{ route('admin.users.index') }}" class="d-flex align-items-center gap-2">
<label for="accessReviewFilter" class="small text-muted text-nowrap">Access review</label>
<select name="access_review" id="accessReviewFilter" class="form-select form-select-sm" onchange="this.form.submit()">
<option value="">All accounts</option>
<option value="dormant" @selected(($filters['access_review'] ?? '') === 'dormant')>No sign-in in 90+ days</option>
</select>
<label for="twoFactorFilter" class="small text-muted text-nowrap">2FA</label>
<select name="two_factor" id="twoFactorFilter" class="form-select form-select-sm" onchange="this.form.submit()">
<option value="">All users</option>
<option value="enabled" @selected(($filters['two_factor'] ?? '') === 'enabled')>Enabled</option>
<option value="disabled" @selected(($filters['two_factor'] ?? '') === 'disabled')>Not enabled</option>
</select>
<noscript><button class="btn btn-sm btn-outline-secondary">Filter</button></noscript>
</form>
<button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#newUserModal"><i class="bi bi-person-plus-fill me-1"></i>New User</button>
</div>
</div>
@if (($filters['access_review'] ?? '') === 'dormant')
<div class="alert alert-info py-2">Showing accounts with no recorded successful sign-in within the last 90 days, including accounts with no sign-in history. Review each account before changing its access; no accounts are automatically deactivated.</div>
@endif
<div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="newUserModalLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
<div class="modal-content" style="border-radius:1rem;overflow:hidden">
<div class="modal-header border-0 pb-0">
<div class="d-flex align-items-center gap-2">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-person-plus-fill"></i></span>
<div>
<div id="newUserModalLabel" class="fw-semibold">Create Employee Account</div>
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
<label for="newUserName" class="form-label">Full Name</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span><input id="newUserName" name="name" class="form-control" value="{{ old('name') }}" placeholder="Juan Dela Cruz" required></div>
</div>
<div class="col-md-6">
<label for="newUserEmail" class="form-label">Email</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span><input id="newUserEmail" name="email" type="email" class="form-control" value="{{ old('email') }}" placeholder="juan@panelco.gov.ph" autocomplete="off" autocapitalize="off" spellcheck="false" required></div>
</div>
<div class="col-md-6">
<label for="newUserPw" class="form-label">Password</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span><input id="newUserPw" name="password" type="password" class="form-control" minlength="12" autocomplete="new-password" required></div>
<small class="text-muted">12+ characters, uppercase and lowercase, number, and symbol.</small>
</div>
<div class="col-md-6">
<label for="newUserPwConfirm" class="form-label">Confirm Password</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span><input id="newUserPwConfirm" name="password_confirmation" type="password" class="form-control" minlength="12" autocomplete="new-password" required></div>
</div>
<div class="col-md-4">
<label for="newUserRole" class="form-label">Role</label>
<select id="newUserRole" name="role" class="form-select" required>@foreach ([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_ADMIN] as $r)<option value="{{ $r }}" {{ old('role', \App\Models\User::ROLE_EMPLOYEE) === $r ? 'selected' : '' }}>{{ \App\Models\User::roleLabel($r) }}</option>@endforeach</select>
</div>
<div class="col-md-4">
<label for="newUserDepartment" class="form-label">Primary Department</label>
<select id="newUserDepartment" name="department_id" class="form-select"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ (string) old('department_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select>
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
<thead class="table-light"><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">2FA</th><th scope="col">Department</th><th scope="col">Last sign-in</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
<tbody>
@forelse ($users as $u)
<tr>
<td class="fw-semibold">{{ $u->name }}</td><td class="text-break">{{ $u->email }}</td>
<td><span class="badge text-capitalize" style="background:#e6f3ea;color:var(--p3-navy)">{{ \App\Models\User::roleLabel($u->role) }}</span></td>
<td><span class="badge {{ $u->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
<td><span class="badge {{ $u->two_factor_enabled ? 'text-bg-success' : 'text-bg-warning' }}">{{ $u->two_factor_enabled ? 'Enabled' : 'Not enabled' }}</span></td>
<td>{{ $u->department?->name ?? '—' }}</td><td class="text-nowrap">{{ $u->last_successful_sign_in_at ? \Illuminate\Support\Carbon::parse($u->last_successful_sign_in_at)->format('M d, Y') : 'Never' }}</td>
<td class="text-end"><a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-success">Edit</a></td>
</tr>
@empty
<tr><td colspan="8" class="text-center text-muted p-4">No users match these filters.</td></tr>
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
