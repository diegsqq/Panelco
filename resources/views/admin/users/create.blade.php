@extends('layouts.app')
@section('content')
<h4>{{ isset($user) ? 'Edit User' : 'New User' }}</h4>
<div class="card p-4">
<form method="POST" action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
@csrf
@if(isset($user)) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required></div>
<div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required></div>
<div class="col-md-6"><label class="form-label">Password {{ isset($user) ? '(leave blank to keep)' : '' }}</label><input name="password" type="password" class="form-control" minlength="12" {{ isset($user) ? '' : 'required' }}><small class="text-muted">12+ characters, uppercase and lowercase, number, and symbol.</small><input name="password_confirmation" type="password" class="form-control mt-2" minlength="12" placeholder="Confirm password"></div>
<div class="col-md-3"><label class="form-label">Role</label><select name="role" class="form-select" required>@foreach ([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_ADMIN] as $r)<option value="{{ $r }}" {{ old('role', $user->role ?? \App\Models\User::ROLE_EMPLOYEE) === $r ? 'selected' : '' }}>{{ \App\Models\User::roleLabel($r) }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Primary Department</label><select name="department_id" class="form-select"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ (string) old('department_id', $user->department_id ?? '') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select></div>
<div class="col-12" id="createAddDeptField"><label class="form-label">Additional Departments</label><select name="departments[]" class="form-select" multiple>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ in_array($d->id, old('departments', isset($user) ? $user->departments->pluck('id')->toArray() : [])) ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select></div>
</div>
<button class="btn btn-primary mt-3">Save</button>
<a href="{{ route('admin.users.index') }}" class="btn btn-link mt-3">Cancel</a>
</form>
@if(isset($user))
<form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-3" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete user</button></form>
@endif
</div>
<script>
(function () {
    var role = document.querySelector('select[name="role"]');
    var field = document.getElementById('createAddDeptField');
    if (!role || !field) return;
    function sync() { field.classList.toggle('d-none', role.value === @json(\App\Models\User::ROLE_ADMIN)); }
    role.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
