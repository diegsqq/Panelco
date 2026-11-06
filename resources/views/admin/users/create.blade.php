@extends('layouts.app')
@section('content')
<h4>{{ isset($user) ? 'Edit User' : 'New User' }}</h4>
<div class="card p-4">
<form method="POST" action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
@csrf
@if(isset($user)) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-6"><label for="userName" class="form-label">Name</label><input id="userName" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required></div>
<div class="col-md-6"><label for="userEmail" class="form-label">Email</label><input id="userEmail" name="email" type="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" autocomplete="off" autocapitalize="off" spellcheck="false" required></div>
<div class="col-md-6"><label for="standalonePw" class="form-label">Password {{ isset($user) ? '(leave blank to keep)' : '' }}</label><div class="input-group"><input id="standalonePw" name="password" type="password" class="form-control" minlength="12" autocomplete="new-password" {{ isset($user) ? '' : 'required' }}></div><small class="text-muted">12+ characters, uppercase and lowercase, number, and symbol.</small><input name="password_confirmation" id="standalonePwConfirm" type="password" class="form-control mt-2" minlength="12" autocomplete="new-password" placeholder="Confirm password"></div>
<div class="col-md-3"><label for="userRole" class="form-label">Role</label><select id="userRole" name="role" class="form-select" required>@foreach ([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_ADMIN] as $r)<option value="{{ $r }}" {{ old('role', $user->role ?? \App\Models\User::ROLE_EMPLOYEE) === $r ? 'selected' : '' }}>{{ \App\Models\User::roleLabel($r) }}</option>@endforeach</select></div>
<div class="col-md-3"><label for="userDepartment" class="form-label">Primary Department</label><select id="userDepartment" name="department_id" class="form-select"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" {{ (string) old('department_id', $user->department_id ?? '') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select></div>
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
    var pw = document.getElementById('standalonePw');
    var confirm = document.getElementById('standalonePwConfirm');
    if (!pw || !confirm) return;

    // Chrome autofills after page load — wipe email/password again when the
    // server sent no input to repopulate (fresh page, no validation errors).
    @if (! $errors->any() && count(old()) === 0)
    function clearAutofill() {
        var email = document.querySelector('input[name="email"]');
        if (email) email.value = '';
        pw.value = '';
        pw.type = 'password';
        confirm.value = '';
        confirm.type = 'password';
    }
    window.addEventListener('load', clearAutofill);
    clearAutofill();
    @endif
})();
</script>
@endsection
