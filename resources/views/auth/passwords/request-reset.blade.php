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
        padding: clamp(2rem, 5vw, 3rem) clamp(2rem, 5vw, 3rem) 1.5rem;
        text-align: center;
    }
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
        margin-top: 1.5rem;
        padding-top: 1.1rem;
        color: #87948c;
        border-top: 1px solid #edf1ee;
        font-size: .76rem;
        line-height: 1.6;
        text-align: center;
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
        margin-top: 1rem;
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

            <a href="{{ route('login') }}" class="reset-request-back"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
        </div>

        <div class="reset-request-help">
            <i class="bi bi-info-circle me-1"></i> Need help? Contact your administrator at <strong>admin@panelco3.local</strong>.
        </div>
    </div>
</div>
@endsection