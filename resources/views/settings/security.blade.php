@extends('layouts.app')
@section('content')
@php
    $me = auth()->user();
    $val = fn (string $key, string $default = '') => $settings[$key] ?? $default;

    // QR for a 2FA setup that was started but not confirmed yet.
    $pending2fa = session('two_factor_pending_secret');
    $qrSvg = null;
    if ($pending2fa && ! $me->two_factor_enabled) {
        try {
            $g2fa = new \PragmaRX\Google2FA\Google2FA();
            $otpUrl = $g2fa->getQRCodeUrl('PANELCO III SmartDocs AI', $me->email, $pending2fa);
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(320),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $qrSvg = (new \BaconQrCode\Writer($renderer))->writeString($otpUrl);
            $qrSvg = str_replace('<svg ', '<svg style="width:190px;height:190px" ', $qrSvg);
        } catch (\Throwable $e) {
            $qrSvg = null;
        }
    }
@endphp
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
<div>
<h4 class="mb-0 fw-bold">{{ __('settings.security') }}</h4>
<small class="text-muted">Protect your account with a strong password and a second login step.</small>
</div>
@if($me->two_factor_enabled)
<span class="badge rounded-pill text-bg-success p-2"><i class="bi bi-shield-fill-check me-1"></i>2FA ON</span>
@else
<span class="badge rounded-pill border border-secondary text-secondary p-2"><i class="bi bi-shield-exclamation me-1"></i>2FA OFF</span>
@endif
</div>

<div class="row g-4 mb-1">
{{-- Change password --}}
<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-key"></i></span>
<div><div class="fw-semibold">{{ __('settings.change_password') }}</div><small class="text-muted">Min 8 characters. Use a mix of letters and numbers.</small></div>
</div>
<form method="POST" action="{{ route('settings.password') }}">
@csrf
@method('PUT')
<div class="mb-3">
<label class="form-label">{{ __('settings.current_password') }}</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span>
<input type="password" name="current_password" class="form-control" required autocomplete="current-password">
<button type="button" class="btn btn-outline-secondary pw-toggle" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
</div>
<div class="mb-3">
<label class="form-label">{{ __('settings.new_password') }} <span class="text-muted small">{{ __('settings.new_password_hint') }}</span></label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
<input type="password" name="password" id="newPw" class="form-control" required minlength="8" autocomplete="new-password">
<button type="button" class="btn btn-outline-secondary pw-toggle" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
<div class="pw-meter mt-2" aria-hidden="true">
<div class="pw-track"><div class="pw-fill" id="pwFill"></div></div>
<small class="pw-label text-muted d-block mt-1" id="pwLabel">{{ __('settings.new_password_hint') }}</small>
</div>
</div>
<div class="mb-1">
<label class="form-label">{{ __('settings.confirm_password') }}</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
<input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
<button type="button" class="btn btn-outline-secondary pw-toggle" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
</div>
<button class="btn btn-p3 mt-3 px-4"><i class="bi bi-key me-1"></i>{{ __('settings.password_btn') }}</button>
</form>
</div>
</div>

{{-- Two-factor authentication --}}
<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-phone-shield"></i></span>
<div><div class="fw-semibold">Two-Factor Authentication (2FA)</div><small class="text-muted">Google Authenticator or any TOTP app — no SMS needed.</small></div>
</div>

<div class="mt-2">
@if($me->two_factor_enabled)
<div class="alert alert-success d-flex align-items-start gap-2" role="alert">
<i class="bi bi-check-circle-fill mt-1"></i>
<small>Your account requires a 6-digit app code after the password at every sign-in.</small>
</div>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
<form method="POST" action="{{ route('settings.2fa.disable') }}">
@csrf
<button class="btn btn-outline-danger" onclick="return confirm('Turn off two-factor authentication? Your account will be less protected.')"><i class="bi bi-shield-x me-1"></i>Turn OFF 2FA</button>
</form>
<span class="p3-security-chip"><i class="bi bi-phone me-1"></i>Authenticator app active</span>
</div>
@elseif($pending2fa && $qrSvg)
<div class="text-center mb-3">
<div class="p3-qr-box d-inline-block">{!! $qrSvg !!}</div>
</div>
<div class="p3-steps mb-3">
<div class="p3-step-row"><span class="p3-step">1</span><span>Install <b>Google Authenticator</b> (or any TOTP app).</span></div>
<div class="p3-step-row"><span class="p3-step">2</span><span>Scan this QR, or type the key <code>{{ $pending2fa }}</code>.</span></div>
<div class="p3-step-row"><span class="p3-step">3</span><span>Enter the current 6-digit code below.</span></div>
</div>
<form method="POST" action="{{ route('settings.2fa.confirm') }}" class="d-flex gap-2 flex-wrap align-items-center">
@csrf
<input name="code" class="form-control p3-code-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" autocomplete="one-time-code" required>
<button class="btn btn-p3 px-3"><i class="bi bi-shield-check me-1"></i>Activate 2FA</button>
</form>
@elseif($pending2fa)
<div class="alert alert-danger" role="alert">
<small><i class="bi bi-exclamation-triangle-fill me-1"></i>QR could not be generated. Start setup again.</small>
</div>
<form method="POST" action="{{ route('settings.2fa.setup') }}">
@csrf
<button class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise me-1"></i>Restart setup</button>
</form>
@else
<div class="d-flex gap-3 align-items-start mb-3">
<span class="stat-icon" style="width:38px;height:38px;background:#e6f3ea;color:#00743F;flex:0 0 38px"><i class="bi bi-qr-code" style="font-size:1.05rem"></i></span>
<small class="text-muted">After your password, open the app on your phone and type the current 6-digit code. Works offline, nothing is sent by SMS.</small>
</div>
<div class="p3-security-note mb-3"><i class="bi bi-lightbulb-fill me-1"></i>Recommended — blocks sign-in even if your password leaks.</div>
<form method="POST" action="{{ route('settings.2fa.setup') }}">
@csrf
<button class="btn btn-p3 px-4"><i class="bi bi-shield-lock me-1"></i>Enable 2FA</button>
</form>
@endif
</div>

</div>
</div>
</div>

<style>
/* Security page */
.p3-steps { display: flex; flex-direction: column; gap: .55rem; }
.p3-step-row { display: flex; align-items: center; gap: .65rem; font-size: .84rem; color: #4b5a52; }
[data-theme="dark"] .p3-step-row { color: #dfe8e3; }
.p3-step { width: 24px; height: 24px; flex: 0 0 24px; border-radius: 50%; background: var(--p3-navy); color: #fff; font-size: .72rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,168,104,.3); }
.p3-security-note { font-size: .8rem; color: #00743F; background: #e6f3ea; border: 1px dashed #b7e0c8; border-radius: .65rem; padding: .5rem .75rem; }
[data-theme="dark"] .p3-security-note { color: #7fe0ac; background: #12291d; border-color: #1d4432; }
.p3-security-chip { font-size: .75rem; font-weight: 600; color: #155724; background: #d4edda; border: 1px solid #b7e1c3; border-radius: 2rem; padding: .3rem .8rem; }
[data-theme="dark"] .p3-security-chip { color: #8fe0b3; background: #12291d; border-color: #1d4432; }
[data-theme="dark"] .stat-icon { color: #7fe0ac; }
/* Password strength meter */
.pw-track { height: 6px; border-radius: 99px; background: #e3ebe6; overflow: hidden; }
[data-theme="dark"] .pw-track { background: #22302a; }
.pw-fill { height: 100%; width: 0; border-radius: 99px; background: #dc3545; transition: width .3s ease, background-color .3s ease; }
.pw-label { font-size: .75rem; }
</style>

<script>
(function () {
    // Show / hide password toggles
    document.querySelectorAll('.pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    // Live strength meter on the new-password field
    var pw = document.getElementById('newPw');
    var fill = document.getElementById('pwFill');
    var label = document.getElementById('pwLabel');
    if (!pw || !fill || !label) return;
    var levels = [
        { w: 15,  c: '#dc3545', t: 'Too short' },
        { w: 40,  c: '#dc3545', t: 'Weak — add numbers' },
        { w: 65,  c: '#f0ad4e', t: 'Fair — add symbols or uppercase' },
        { w: 85,  c: '#f0ad4e', t: 'Good password' },
        { w: 100, c: '#00A868', t: 'Strong password' }
    ];
    pw.addEventListener('input', function () {
        var v = pw.value;
        if (!v) { fill.style.width = '0'; label.textContent = '{{ __('settings.new_password_hint') }}'; return; }
        var score = 0;
        if (v.length >= 8) score = 1;
        if (v.length >= 10 && /[A-Za-z]/.test(v) && /[0-9]/.test(v)) score = 2;
        if (v.length >= 12) score = 3;
        if (v.length >= 14 && /[^A-Za-z0-9]/.test(v) && /[A-Z]/.test(v)) score = 4;
        var lvl = levels[score];
        fill.style.width = lvl.w + '%';
        fill.style.background = lvl.c;
        label.textContent = lvl.t;
        label.style.color = lvl.c;
    });
})();
</script>
{{-- Session & audit retention --}}
<div class="row g-4 mb-1 mt-1">
<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-hourglass-split"></i></span>
<div><div class="fw-semibold">Session and audit retention</div><small class="text-muted">Idle sign-out window and how long audit logs are kept.</small></div>
</div>
@if($isAdmin)
<form method="POST" action="{{ route('settings.retention') }}">
@csrf
@method('PUT')
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Session Timeout (minutes)</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-clock"></i></span>
<input name="session_timeout_minutes" type="number" min="5" max="1440" class="form-control" value="{{ old('session_timeout_minutes', $val('session_timeout_minutes', '120')) }}" required></div>
<small class="text-muted">5 – 1440 minutes. Users are signed out after this idle period.</small>
</div>
<div class="col-md-6">
<label class="form-label">Audit Log Retention (days)</label>
<div class="input-group"><span class="input-group-text"><i class="bi bi-journal-text"></i></span>
<input name="audit_retention_days" type="number" min="1" max="3650" class="form-control" value="{{ old('audit_retention_days', $val('audit_retention_days', '90')) }}" required></div>
<small class="text-muted">1 – 3650 days. Older audit entries are purged automatically.</small>
</div>
</div>
<button class="btn btn-p3 mt-3 px-4"><i class="bi bi-check-lg me-1"></i>{{ __('settings.save') }}</button>
</form>
@else
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Session Timeout (minutes)</label>
<input type="number" class="form-control" value="{{ $val('session_timeout_minutes', '120') }}" disabled>
</div>
<div class="col-md-6">
<label class="form-label">Audit Log Retention (days)</label>
<input type="number" class="form-control" value="{{ $val('audit_retention_days', '90') }}" disabled>
</div>
</div>
<div class="p3-security-note mt-3"><i class="bi bi-lock-fill me-1"></i>Only admins can change retention settings.</div>
@endif
</div>
</div>
</div>
@endsection
