@extends('layouts.app')

@section('content')
<style>
    .reset-request-page {
        min-height: 100vh;
        display: grid;
        place-items: center;
        overflow: hidden;
        padding: clamp(1rem, 4vw, 3rem);
        background:
            radial-gradient(ellipse at 14% 12%, rgba(12, 150, 91, .13), transparent 32rem),
            radial-gradient(ellipse at 88% 86%, rgba(93, 164, 119, .13), transparent 34rem),
            #f5f8f5;
    }
    .reset-request-frame {
        width: min(100%, 480px);
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(9, 69, 46, .08);
        border-radius: 1.5rem;
        box-shadow: 0 28px 80px rgba(19, 58, 39, .13);
    }
    .reset-request-header {
        position: relative;
        padding: clamp(2rem, 5vw, 3rem) clamp(2rem, 5vw, 3rem) 1.5rem;
        text-align: center;
    }
    .reset-request-back {
        position: absolute;
        top: 1.25rem;
        left: 1.25rem;
        margin-top: 0;
    }
    .reset-request-bell {
        position: absolute;
        top: 1.25rem;
        right: 1.25rem;
        color: #087a4b;
        font-size: 1.1rem;
        opacity: .85;
    }
    .reset-request-bell:hover { color: #064b34; }
    .reset-request-brand {
        display: flex;
        align-items: center;
        gap: .8rem;
        justify-content: center;
        margin-bottom: 1rem;
    }
    .reset-request-brand img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        padding: 5px;
        background: #fff;
        border-radius: 13px;
    }
    .reset-request-brand span {
        font-weight: 700;
        color: #163a2b;
        font-size: 1.25rem;
    }
    .reset-request-brand small {
        display: block;
        margin-top: .05rem;
        color: #718078;
        font-size: .7rem;
        letter-spacing: .06em;
    }
    .reset-request-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #163a2b;
        margin: .25rem 0 .5rem;
        letter-spacing: -.02em;
    }
    .reset-request-subtitle {
        color: #718078;
        font-size: .9rem;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }
    .reset-request-form {
        padding: 0 clamp(2rem, 5vw, 3rem) clamp(2rem, 5vw, 3rem);
    }
    .reset-request-form .form-label {
        margin-bottom: .5rem;
        color: #294438;
        font-size: .84rem;
        font-weight: 600;
    }
    .reset-request-form .form-control {
        min-height: 48px;
        border-color: #dce6df;
        border-radius: .7rem;
        font-size: .92rem;
    }
    .reset-request-form .form-control:focus {
        border-color: #159461;
        box-shadow: 0 0 0 .22rem rgba(21,148,97,.13);
    }
    .reset-request-form .form-control.is-invalid {
        border-color: #dc3545;
    }
    .reset-request-form textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }
    .reset-request-submit {
        width: 100%;
        min-height: 49px;
        margin-top: 1rem;
        color: #fff;
        background: #087a4b;
        border: 0;
        border-radius: .7rem;
        font-weight: 650;
        font-size: .92rem;
        transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
    }
    .reset-request-submit:hover {
        background: #06623d;
        box-shadow: 0 8px 18px rgba(8,122,75,.2);
        transform: translateY(-1px);
    }
    .reset-request-help {
        margin: 1.5rem 0 0;
        padding: 1rem 1.25rem;
        color: #294438;
        background: #f0f9f3;
        border: 1px solid #d5e8db;
        border-radius: .85rem;
        font-size: .8rem;
        line-height: 1.6;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
    }
    .reset-request-help i {
        color: #087a4b;
        font-size: 1rem;
        flex: 0 0 auto;
    }
    .reset-request-help a {
        color: #087a4b;
        font-weight: 600;
        text-decoration: none;
    }
    .reset-request-help a:hover {
        color: #064b34;
        text-decoration: underline;
    }
    .reset-request-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: #087a4b;
        text-decoration: none;
        font-size: .85rem;
        font-weight: 600;
        margin-top: 0;
    }
    .reset-request-back:hover {
        color: #064b34;
        text-decoration: underline;
    }
    .alert-danger {
        background: #fdecea;
        border: 1px solid #f5c6c7;
        color: #842029;
        border-radius: .7rem;
        padding: .75rem 1rem;
        margin-bottom: 1rem;
        font-size: .88rem;
    }
    .alert-danger i {
        color: #dc3545;
    }
    .alert-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        border-radius: .7rem;
        padding: .75rem 1rem;
        margin-bottom: 1rem;
        font-size: .88rem;
    }
    .alert-success i {
        color: #00A868;
    }
    .invalid-feedback {
        color: #dc3545;
        font-size: .8rem;
        margin-top: .25rem;
    }
    .is-invalid {
        border-color: #dc3545 !important;
    }
</style>

<div class="reset-request-page">
    <div class="reset-request-frame">
        <div class="reset-request-header">
            <a href="{{ route('login') }}" class="reset-request-back"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
            <a href="#" id="resetBell" class="reset-request-bell" aria-label="Notifications"><i class="bi bi-bell-fill"></i></a>
            <div class="reset-request-brand">
                <img src="{{ asset('images/logo.png') }}" alt="PANELCO III logo">
                <span>SmartDocs AI<small>PANELCO III</small></span>
            </div>
            <h1 class="reset-request-title">Forgot your password?</h1>
            <p class="reset-request-subtitle">Enter your Employee ID and explain why you need a password reset. An administrator will review your request.</p>
        </div>

        <div class="reset-request-form">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Request failed.</strong>
                    @foreach($errors->all() as $msg)
                        <span class="d-block mt-1 small">{{ $msg }}</span>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.reset.request.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="employee_id" class="form-label">Employee ID</label>
                    <input id="employee_id" type="text" class="form-control @error('employee_id') is-invalid @enderror" name="employee_id" value="{{ old('employee_id') }}" required autofocus placeholder="0000" maxlength="4" pattern="[0-9]{4}" inputmode="numeric">
                    @error('employee_id')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="mb-3">
                    <label for="reason" class="form-label">Reason for password reset</label>
                    <textarea id="reason" class="form-control @error('reason') is-invalid @enderror" name="reason" required maxlength="1000" placeholder="Please explain why you need a password reset...">{{ old('reason') }}</textarea>
                    @error('reason')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    <small class="text-muted">Please provide a detailed reason so the administrator can verify your identity.</small>
                </div>
                <button type="submit" class="btn reset-request-submit">Submit request <i class="bi bi-send me-1"></i></button>
            </form>

        </div>

        <div class="reset-request-help">
            <i class="bi bi-info-circle me-1"></i> Need help? Contact your administrator at <strong>admin@panelco3.local</strong>.
        </div>
    </div>
</div>

<div class="reset-status-toast" id="resetStatusToast">
    <div class="rst-ico" id="resetStatusIcon"><i class="bi bi-bell-fill"></i></div>
    <div class="rst-body">
        <div class="rst-title" id="resetStatusTitle">Notifications</div>
        <div class="rst-msg" id="resetStatusMsg">Enter your Employee ID, then click the bell to check your request status.</div>
        <div class="rst-pw" id="resetStatusPw" style="display:none;"><i class="bi bi-key me-1"></i>Default password: P@55w0rd</div>
    </div>
    <button type="button" class="rst-close" id="resetStatusClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
</div>

<style>
    .reset-status-toast {
        position: fixed; top: 1.25rem; left: 50%; transform: translateX(-50%) translateY(-120%);
        z-index: 1080; min-width: 300px; max-width: 460px; background: #fff; border: 1px solid #e5e5e5;
        border-radius: 1rem; box-shadow: 0 12px 32px rgba(0,0,0,.18); padding: .9rem 1.1rem;
        display: flex; align-items: flex-start; gap: .75rem; transition: transform .35s ease, opacity .35s ease; opacity: 0;
    }
    .reset-status-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
    .reset-status-toast .rst-ico { width: 40px; height: 40px; flex: 0 0 40px; border-radius: .7rem; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: #e6f3ea; color: #00A868; }
    .reset-status-toast .rst-body { flex: 1 1 auto; min-width: 0; }
    .reset-status-toast .rst-title { font-weight: 700; font-size: .92rem; color: #1a1a2e; margin: 0 0 .2rem; }
    .reset-status-toast .rst-msg { font-size: .82rem; color: #5a5a5a; line-height: 1.45; margin: 0; }
    .reset-status-toast .rst-close { background: transparent; border: 0; color: #9a9ab0; font-size: 1rem; cursor: pointer; padding: 0; line-height: 1; }
    .reset-status-toast .rst-close:hover { color: #555; }
    .reset-status-toast .rst-pw { margin-top: .4rem; padding: .35rem .6rem; background: #f5f5f5; border-radius: .5rem; font-size: .78rem; color: #1a1a2e; font-weight: 600; display: inline-block; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var bell = document.getElementById('resetBell');
    var toast = document.getElementById('resetStatusToast');
    var title = document.getElementById('resetStatusTitle');
    var msg = document.getElementById('resetStatusMsg');
    var icon = document.getElementById('resetStatusIcon');
    var pw = document.getElementById('resetStatusPw');
    var closeBtn = document.getElementById('resetStatusClose');
    var empInput = document.getElementById('employee_id');

    function showToast(t, m, type) {
        title.textContent = t;
        msg.textContent = m;
        if (type === 'approved') {
            icon.style.background = '#e6f3ea'; icon.style.color = '#00A868';
            icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            pw.style.display = 'inline-block';
        } else if (type === 'declined') {
            icon.style.background = '#f8d7da'; icon.style.color = '#dc3545';
            icon.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
            pw.style.display = 'none';
        } else if (type === 'pending') {
            icon.style.background = '#fff3cd'; icon.style.color = '#856404';
            icon.innerHTML = '<i class="bi bi-hourglass-split"></i>';
            pw.style.display = 'none';
        } else {
            icon.style.background = '#e7f1fb'; icon.style.color = '#0d6efd';
            icon.innerHTML = '<i class="bi bi-bell-fill"></i>';
            pw.style.display = 'none';
        }
        toast.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { toast.classList.remove('show'); }, 6000);
    }

    if (closeBtn) closeBtn.addEventListener('click', function () { toast.classList.remove('show'); });

    if (bell) {
        bell.addEventListener('click', function (e) {
            e.preventDefault();
            var empId = (empInput && empInput.value ? empInput.value.trim() : '');
            if (!empId || empId.length !== 4) {
                showToast('Notifications', 'Enter your 4-digit Employee ID first, then click the bell.', 'info');
                return;
            }
            fetch('{{ route('password.reset.request.status') }}?employee_id=' + encodeURIComponent(empId), {
                headers: { 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'approved') {
                    showToast('Request Approved', 'Your password has been reset to the default. Sign in with your Employee ID and the default password.', 'approved');
                } else if (data.status === 'declined') {
                    showToast('Request Declined', data.admin_note ? ('Your request was declined: ' + data.admin_note) : 'Your password reset request was declined by the administrator.', 'declined');
                } else if (data.status === 'pending') {
                    showToast('Request Pending', 'Your password reset request is still under review by an administrator.', 'pending');
                } else {
                    showToast('No Request Found', 'No password reset request found for this Employee ID.', 'info');
                }
            }).catch(function () {
                showToast('Error', 'Could not fetch notification status. Please try again.', 'info');
            });
        });
    }
});
</script>
@endsection