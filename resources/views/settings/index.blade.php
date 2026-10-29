@extends('layouts.app')
@section('content')
@php
    $s = $settings;
    $val = fn (string $key, string $default = '') => $s[$key] ?? $default;
    $on = fn (string $key, string $default = '1') => $val($key, $default) === '1';
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
<div>
<h4 class="mb-0 fw-bold">{{ __('settings.title') }}</h4>
<small class="text-muted">Profile picture, personal details and verification.</small>
</div>
</div>

<div class="row g-4 mb-1">
<div class="col-12">
@php
    $me = auth()->user();
    $fullName = trim((string) $me->name);
    $fallbackFirst = $me->first_name ?: ($fullName !== '' ? explode(' ', $fullName)[0] : '');
    $fallbackLast = $me->last_name ?: (str_contains($fullName, ' ') ? substr($fullName, strlen(explode(' ', $fullName)[0]) + 1) : '');
@endphp
<div class="card card-p3 p-4">
<div class="d-flex align-items-center gap-2 mb-4">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-person-circle"></i></span>
<div><div class="fw-semibold">{{ __('settings.my_account') }}</div><small class="text-muted">{{ __('settings.my_account_hint') }}</small></div>
</div>

{{-- Avatar + identity --}}
<div class="d-flex align-items-center gap-4 mb-4 pb-4 flex-wrap" style="border-bottom:1px dashed var(--p3-card-border)">
<div class="p3-avatar-lg">
<div class="p3-avatar-inner">
@if($me->avatar_path)
<img id="avatarPreview" src="{{ route('profile.avatar') }}?v={{ $me->updated_at?->timestamp }}" alt="{{ $me->name }}">
@else
<img id="avatarPreview" class="d-none" alt="preview">
<span id="avatarInitials" class="fw-bold" style="color:#00743F;font-size:1.7rem">{{ $me->initials() }}</span>
@endif
</div>
<form method="POST" action="{{ route('settings.profile.avatar') }}" enctype="multipart/form-data" id="avatarForm">
@csrf
<input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/png,image/jpeg,image/webp" required>
</form>
<label class="p3-avatar-cam" for="avatarInput" title="{{ __('settings.upload_photo') }}"><i class="bi bi-camera-fill"></i>
</label>
</div>
<div class="flex-grow-1" style="min-width:220px">
<div class="fw-semibold fs-6">{{ $me->name }}</div>
@if($me->job_title)<div class="small" style="color:#00743F">{{ $me->job_title }}</div>@endif
<small class="text-muted d-block">{{ $me->email }}{{ $me->organization ? ' · ' . $me->organization : '' }}</small>
<div class="d-flex gap-2 mt-2 flex-wrap align-items-center">
<label class="btn btn-sm btn-outline-success mb-0" for="avatarInput" style="cursor:pointer"><i class="bi bi-camera me-1"></i>{{ __('settings.upload_photo') }}</label>
<button id="avatarSave" type="submit" form="avatarForm" class="btn btn-sm btn-p3 mb-0 d-none"><i class="bi bi-check-lg me-1"></i>{{ __('settings.save') }}</button>
<button id="avatarCancel" type="button" class="btn btn-sm btn-outline-secondary mb-0 d-none">Cancel</button>
@if($me->avatar_path)
<form method="POST" action="{{ route('settings.profile.avatar.destroy') }}">
@csrf
@method('DELETE')
<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>{{ __('settings.remove') }}</button>
</form>
@endif
</div>
<small class="text-muted d-block mt-2">{{ __('settings.avatar_hint') }}</small>
</div>
</div>
<form method="POST" action="{{ route('settings.profile') }}">
@csrf
@method('PUT')
<div class="row g-3">
<div class="col-12 col-md-6">
<label class="form-label">{{ __('settings.first_name') }}</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span>
<input name="first_name" class="form-control" value="{{ old('first_name', $fallbackFirst) }}" required maxlength="100"></div>
</div>
<div class="col-12 col-md-6">
<label class="form-label">{{ __('settings.last_name') }}</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-person-badge"></i></span>
<input name="last_name" class="form-control" value="{{ old('last_name', $fallbackLast) }}" required maxlength="100"></div>
</div>
<div class="col-12 col-md-6">
<label class="form-label">{{ __('settings.email') }}</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span>
<input name="email" type="email" class="form-control" value="{{ old('email', $me->pending_email ?? $me->email) }}" required maxlength="100"></div>
</div>
<div class="col-12 col-md-6">
<label class="form-label">{{ __('settings.phone') }} <span class="text-muted small">{{ __('settings.optional') }}</span></label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-phone"></i></span>
<input name="phone" class="form-control" value="{{ old('phone', $me->pending_phone ?? $me->phone) }}" placeholder="09XX XXX XXXX" maxlength="30"></div>
</div>
<div class="col-12 col-md-6">
<label class="form-label">{{ __('settings.job_title') }} <span class="text-muted small">{{ __('settings.job_title_hint') }}</span></label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-briefcase"></i></span>
<input name="job_title" class="form-control" value="{{ old('job_title', $me->job_title) }}" placeholder="e.g. Records Officer" maxlength="100"></div>
</div>
<div class="col-12 col-md-6">
<label class="form-label">{{ __('settings.organization') }}</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-building"></i></span>
<input name="organization" class="form-control" value="{{ old('organization', $me->organization) }}" placeholder="PANELCO III" maxlength="150"></div>
</div>
</div>
<div class="mt-3 d-flex align-items-center gap-3 flex-wrap">
<button class="btn btn-p3 px-4"><i class="bi bi-check-lg me-1"></i>{{ __('settings.save_account') }}</button>
<small class="text-muted"><i class="bi bi-shield-check me-1"></i>{{ __('settings.verify_note') }}</small>
</div>
</form>

@if($me->pending_email)
<div class="alert mt-3 mb-0" style="background:#fff3cd;border:1px solid #ffe69c;border-radius:.75rem">
<div class="fw-bold" style="color:#856404"><i class="bi bi-envelope-check me-1"></i>Verify new email address</div>
<small style="color:#856404">Enter the 6-digit code sent to <b>{{ $me->pending_email }}</b> to activate it.</small>
<form method="POST" action="{{ route('settings.profile.verify-email') }}" class="d-flex gap-2 mt-2 flex-wrap">
@csrf
@method('PUT')
<input name="code" class="form-control p3-code-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required>
<button class="btn btn-sm btn-p3 px-3">Verify Email</button>
</form>
<form method="POST" action="{{ route('settings.profile.resend') }}" class="mt-1">
@csrf
<input type="hidden" name="type" value="email">
<button class="btn btn-link p-0 text-decoration-none" style="color:#00743F;font-size:.8rem">Didn't get the code? Resend</button>
</form>
</div>
@endif

@if($me->pending_phone)
<div class="alert mt-3 mb-0" style="background:#fff3cd;border:1px solid #ffe69c;border-radius:.75rem">
<div class="fw-bold" style="color:#856404"><i class="bi bi-phone me-1"></i>Verify new contact number</div>
<small style="color:#856404">Enter the 6-digit code sent to your email <b>{{ $me->email }}</b> to activate <b>{{ $me->pending_phone }}</b>.</small>
<form method="POST" action="{{ route('settings.profile.verify-phone') }}" class="d-flex gap-2 mt-2 flex-wrap">
@csrf
@method('PUT')
<input name="code" class="form-control p3-code-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required>
<button class="btn btn-sm btn-p3 px-3">Verify Number</button>
</form>
<form method="POST" action="{{ route('settings.profile.resend') }}" class="mt-1">
@csrf
<input type="hidden" name="type" value="phone">
<button class="btn btn-link p-0 text-decoration-none" style="color:#00743F;font-size:.8rem">Didn't get the code? Resend</button>
</form>
</div>
@endif
</div>
</div>
</div>

{{-- App Basics, AI Settings, and Documents moved to Settings > Preferences. --}}
<script>
(function () {
    // Avatar: pick image -> preview + Save/Cancel appear -> Save uploads.
    var input = document.getElementById('avatarInput');
    if (!input) return;
    var preview = document.getElementById('avatarPreview');
    var initials = document.getElementById('avatarInitials');
    var save = document.getElementById('avatarSave');
    var cancel = document.getElementById('avatarCancel');
    var originalSrc = preview ? preview.getAttribute('src') : null;
    var originalShown = preview ? !preview.classList.contains('d-none') : false;

    function hideButtons() {
        if (save) save.classList.add('d-none');
        if (cancel) cancel.classList.add('d-none');
    }

    input.addEventListener('change', function () {
        if (!input.files || !input.files[0]) return;
        if (preview) {
            preview.src = URL.createObjectURL(input.files[0]);
            preview.classList.remove('d-none');
            if (initials) initials.classList.add('d-none');
        }
        if (save) save.classList.remove('d-none');
        if (cancel) cancel.classList.remove('d-none');
    });

    if (cancel) cancel.addEventListener('click', function () {
        input.value = ''; // required: clear so re-picking same file fires change
        if (preview) {
            if (originalSrc) {
                preview.src = originalSrc;
                preview.classList.toggle('d-none', !originalShown);
            } else {
                preview.classList.add('d-none');
            }
        }
        if (initials) initials.classList.toggle('d-none', originalShown);
        hideButtons();
    });
})();
</script>
@endsection
