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
@unless ($user->isAdmin())
<div class="col-12">
<fieldset>
<legend class="form-label">Additional Departments</legend>
<input type="hidden" name="manage_departments" value="1">
<small class="form-text text-muted d-block mb-2">Select the departments this user can access in addition to their primary department. Uncheck a department to remove access.</small>
<div class="row g-2">
@foreach ($departments as $d)
<div class="col-md-6 col-lg-4">
<div class="form-check">
<input class="form-check-input" type="checkbox" name="departments[]" id="department-{{ $d->id }}" value="{{ $d->id }}" {{ in_array($d->id, $selectedDepartmentIds) ? 'checked' : '' }}>
<label class="form-check-label" for="department-{{ $d->id }}">{{ $d->name }}</label>
</div>
</div>
@endforeach
</div>
</fieldset>
</div>
@endunless
</div>
<div class="d-flex gap-2 mt-4">
<button class="btn btn-outline-success px-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
<a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
<div class="card card-p3 p-4 mt-4">
<h5 class="fw-bold mb-1"><i class="bi bi-person-check me-1" style="color:#00A868"></i>Access review</h5>
<small class="text-muted d-block mb-3">Review this account’s current access and recent successful sign-in before making changes.</small>
<dl class="row mb-0">
<dt class="col-sm-4 text-muted">Account status</dt>
<dd class="col-sm-8"><span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></dd>
<dt class="col-sm-4 text-muted">Role</dt>
<dd class="col-sm-8">{{ \App\Models\User::roleLabel($user->role) }}</dd>
<dt class="col-sm-4 text-muted">Department access</dt>
<dd class="col-sm-8">
@if ($user->isAdmin())
All departments (administrator access)
@else
<div>Primary: {{ $user->department?->name ?? 'None' }}</div>
<div>Additional: {{ $user->departments->isNotEmpty() ? $user->departments->pluck('name')->join(', ') : 'None' }}</div>
@endif
</dd>
<dt class="col-sm-4 text-muted">Two-factor authentication</dt>
<dd class="col-sm-8">{{ $user->two_factor_enabled ? 'Enabled' : 'Not enabled' }}</dd>
<dt class="col-sm-4 text-muted">Last successful sign-in</dt>
<dd class="col-sm-8">
@if ($lastSuccessfulSignIn)
<time datetime="{{ $lastSuccessfulSignIn->created_at->toIso8601String() }}">{{ $lastSuccessfulSignIn->created_at->format('M d, Y H:i') }}</time>
@else
No successful sign-in recorded
@endif
</dd>
</dl>
</div>
@if($user->two_factor_enabled && (int) $user->id !== (int) auth()->id())
<div class="card card-p3 p-3 mt-4">
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
<div>
<div class="fw-semibold"><i class="bi bi-shield-lock me-1" style="color:#00A868"></i>Two-factor authentication</div>
<small class="text-muted">Enabled. Reset only if the user has lost access to their authenticator; they will be notified by email.</small>
</div>
<form method="POST" action="{{ route('admin.users.two-factor.reset', $user) }}" onsubmit="return confirm('Reset this user’s two-factor authentication? They will be able to sign in with their password and must set up an authenticator again.')">
@csrf
<button class="btn btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset 2FA</button>
</form>
</div>
</div>
@endif
<div class="card card-p3 p-4 mt-4">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<div>
<h5 class="fw-bold mb-1"><i class="bi bi-clock-history me-1" style="color:#00A868"></i>Recent sign-in activity</h5>
<small class="text-muted">Recent authentication events for this account, visible to administrators.</small>
</div>
<span class="badge text-bg-secondary">Last 20 events</span>
</div>
@forelse($loginHistory as $event)
@php
    $eventLabels = [
        'auth.login_succeeded' => ['Signed in successfully', 'text-bg-success'],
        'auth.login_failed' => ['Failed sign-in attempt', 'text-bg-danger'],
        'auth.login_locked_out' => ['Sign-in temporarily locked', 'text-bg-danger'],
        'auth.two_factor_challenge_started' => ['Two-factor challenge started', 'text-bg-warning'],
        'auth.two_factor_failed' => ['Incorrect two-factor code', 'text-bg-danger'],
        'auth.logged_out' => ['Signed out', 'text-bg-secondary'],
    ];
    [$eventLabel, $eventBadge] = $eventLabels[$event->action] ?? ['Sign-in activity', 'text-bg-secondary'];
@endphp
<div class="d-flex justify-content-between align-items-start gap-3 border-top py-3">
<div class="min-width-0">
<span class="badge {{ $eventBadge }}">{{ $eventLabel }}</span>
@if($event->action === 'auth.login_locked_out')
<div class="small text-muted mt-1">Repeated failed attempts triggered a temporary sign-in lockout.</div>
@endif
<div class="small text-muted mt-1">
{{ $event->metadata['ip'] ?? 'IP unavailable' }}
@if(!empty($event->metadata['user_agent'])) · {{ \Illuminate\Support\Str::limit($event->metadata['user_agent'], 140) }}@endif
</div>
</div>
<time class="small text-muted text-nowrap" datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('M d, Y H:i') }}</time>
</div>
@empty
<p class="text-muted mb-0">No sign-in activity has been recorded for this account yet.</p>
@endforelse
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
