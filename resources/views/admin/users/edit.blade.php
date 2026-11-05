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
<span class="badge ms-2 {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
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
<div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span><input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" autocomplete="off" autocapitalize="off" spellcheck="false" required></div>
</div>
<div class="col-md-6">
<label class="form-label">New Password <span class="text-muted small">(leave blank to keep current)</span></label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span><input name="password" id="editUserPw" type="password" class="form-control" minlength="12" autocomplete="new-password"></div>
<small class="text-muted">12+ characters, uppercase and lowercase, number, and symbol.</small>
</div>
<div class="col-md-6">
<label class="form-label">Confirm New Password</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span><input name="password_confirmation" id="editUserPwConfirm" type="password" class="form-control" minlength="12" autocomplete="new-password"></div>
</div>
<div class="col-md-4">
<label class="form-label">Role</label>
<select name="role" class="form-select" required>@foreach (\App\Models\User::ROLES as $r)<option value="{{ $r }}" {{ old('role', $user->role) === $r ? 'selected' : '' }}>{{ \App\Models\User::roleLabel($r) }}</option>@endforeach</select>
</div>
<div class="col-md-4">
<label class="form-label">Primary Department</label>
<select name="department_id" class="form-select"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ (string) old('department_id', $user->department_id) === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select>
</div>
</div>
<div class="d-flex gap-2 mt-4">
<button class="btn btn-outline-success px-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
<a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
@unless ($user->isAdmin())
<div class="card card-p3 p-3 mt-4">
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
<div>
<div class="fw-semibold">Account access</div>
<small class="text-muted">{{ $user->is_active ? 'Active accounts can sign in and use their assigned permissions.' : 'Inactive accounts cannot sign in or continue using an existing session.' }}</small>
</div>
<form method="POST" action="{{ route('admin.users.status', $user) }}">
@csrf
@method('PATCH')
<input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
<button class="btn {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" @if((int) $user->id === (int) auth()->id() && $user->is_active) disabled title="You cannot deactivate your own account" @endif>
<i class="bi {{ $user->is_active ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>{{ $user->is_active ? 'Deactivate Account' : 'Activate Account' }}
</button>
</form>
</div>
</div>
@endunless
@endsection
