@extends('layouts.app')
@section('content')
<h4>{{ isset($user) ? 'Edit User' : 'New User' }}</h4>
<div class="card p-4">
<form method="POST" action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
@csrf
@if(isset($user)) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-6"><label for="userName" class="form-label">Name</label><input id="userName" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required></div>
<div class="col-md-6"><label for="userEmployeeId" class="form-label">Employee ID</label><input id="userEmployeeId" name="employee_id" type="text" class="form-control" value="{{ old('employee_id', $user->employee_id ?? '') }}" required maxlength="4" pattern="[0-9]{4}" inputmode="numeric" placeholder="0000"></div>
<div class="col-md-6"><label for="standalonePw" class="form-label">Password {{ isset($user) ? '(leave blank to keep)' : '' }}</label><div class="input-group"><input id="standalonePw" name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" {{ isset($user) ? '' : 'required' }}><button type="button" class="btn btn-outline-secondary pw-toggle" data-pw-target="standalonePw" aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button></div><small class="text-muted">8+ characters, uppercase and lowercase, number, and symbol.</small><div class="input-group mt-2"><input name="password_confirmation" id="standalonePwConfirm" type="password" class="form-control" minlength="8" autocomplete="new-password" placeholder="Confirm password"><button type="button" class="btn btn-outline-secondary pw-toggle" data-pw-target="standalonePwConfirm" aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button></div></div>
<div class="col-md-3"><label for="userRole" class="form-label">Role</label><select id="userRole" name="role" class="form-select" required>@foreach ([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_ADMIN] as $r)<option value="{{ $r }}" {{ old('role', $user->role ?? \App\Models\User::ROLE_EMPLOYEE) === $r ? 'selected' : '' }}>{{ \App\Models\User::roleLabel($r) }}</option>@endforeach</select></div>
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

    // Show/hide a password field and keep its eye icon and label in sync.
    function setPwVisible(input, visible) {
        input.type = visible ? 'text' : 'password';
        var btn = document.querySelector('[data-pw-target="' + input.id + '"]');
        if (btn) {
            btn.querySelector('i').className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
            btn.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            btn.setAttribute('title', visible ? 'Hide password' : 'Show password');
        }
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-pw-target]');
        if (!btn) return;
        var input = document.getElementById(btn.getAttribute('data-pw-target'));
        if (input) setPwVisible(input, input.type === 'password');
    });

    // Prefill the standard default password for new accounts; never
    // overwrite a value already present.
    function fillDefaultPassword() {
        [pw, confirm].forEach(function (f) {
            if (!f.value) f.value = 'P@55w0rd';
            setPwVisible(f, false);
        });
    }

    // Chrome autofills after page load — wipe the ID field again when the
    // server sent no input to repopulate (fresh page, no validation errors),
    // then apply the default password.
    @if (! $errors->any() && count(old()) === 0)
    function clearAutofill() {
        var idField = document.querySelector('input[name="employee_id"]');
        if (idField) idField.value = '';
        fillDefaultPassword();
    }
    window.addEventListener('load', clearAutofill);
    clearAutofill();
    @else
    window.addEventListener('load', fillDefaultPassword);
    fillDefaultPassword();
    @endif
})();
</script>
@endsection