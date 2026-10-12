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
<div class="col-md-6"><label class="form-label">Password {{ isset($user) ? '(leave blank to keep)' : '' }}</label><input name="password" type="password" class="form-control" {{ isset($user) ? '' : 'required' }}><input name="password_confirmation" type="password" class="form-control mt-2" placeholder="Confirm password"></div>
<div class="col-md-3"><label class="form-label">Role</label><select name="role" class="form-select" required>@foreach (\App\Models\User::ROLES as $r)<option value="{{ $r }}" {{ old('role', $user->role ?? '') === $r ? 'selected' : '' }}>{{ str_replace('_',' ',$r) }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Primary Department</label><select name="department_id" class="form-select"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ (string) old('department_id', $user->department_id ?? '') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Additional Departments</label><select name="departments[]" class="form-select" multiple>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ in_array($d->id, old('departments', isset($user) ? $user->departments->pluck('id')->toArray() : [])) ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select></div>
</div>
<button class="btn btn-primary mt-3">Save</button>
<a href="{{ route('admin.users.index') }}" class="btn btn-link mt-3">Cancel</a>
</form>
@if(isset($user))
<form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-3" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete user</button></form>
@endif
</div>
@endsection
