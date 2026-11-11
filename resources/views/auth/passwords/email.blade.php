@extends('layouts.app')

@section('content')
<style>
    .password-page {
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
    .password-page::before {
        position: absolute;
        z-index: -1;
        inset: 0;
        background-image: radial-gradient(rgba(8, 122, 75, .2) .8px, transparent .8px);
        background-position: center;
        background-size: 24px 24px;
        content: "";
        opacity: .28;
        -webkit-mask-image: radial-gradient(ellipse at center, #000 0%, transparent 76%);
        mask-image: radial-gradient(ellipse at center, #000 0%, transparent 76%);
        pointer-events: none;
    }
    .password-frame {
        width: min(100%, 420px);
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(9, 69, 46, .08);
        border-radius: 1.5rem;
        box-shadow: 0 28px 80px rgba(19, 58, 39, .13);
    }
    .password-header {
        padding: clamp(2rem, 5vw, 3rem) clamp(2rem, 5vw, 3rem) 1.5rem;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .password-brand {
        display: flex;
        align-items: center;
        gap: .8rem;
        justify-content: center;
        margin-bottom: 1rem;
    }
    .password-brand img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        padding: 5px;
        background: #fff;
        border-radius: 13px;
    }
    .password-brand span {
        font-weight: 700;
        color: #163a2b;
        font-size: 1.25rem;
    }
    .password-brand small {
        display: block;
        margin-top: .05rem;
        color: #718078;
        font-size: .7rem;
        letter-spacing: .06em;
    }
    .password-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #163a2b;
        margin: .25rem 0 .5rem;
        letter-spacing: -.02em;
    }
    .password-subtitle {
        color: #718078;
        font-size: .9rem;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }
    .password-form {
        padding: 0 clamp(2rem, 5vw, 3rem) clamp(2rem, 5vw, 3rem);
        position: relative;
        z-index: 1;
    }
    .password-form .form-label {
        margin-bottom: .5rem;
        color: #294438;
        font-size: .84rem;
        font-weight: 600;
    }
    .password-form .form-control {
        min-height: 48px;
        border-color: #dce6df;
        border-radius: .7rem;
        font-size: .92rem;
    }
    .password-form .form-control::placeholder {
        color: #a0ada5;
    }
    .password-form .form-control:focus {
        border-color: #159461;
        box-shadow: 0 0 0 .22rem rgba(21,148,97,.13);
    }
    .password-form .form-control.is-invalid {
        border-color: #dc3545;
    }
    .password-submit {
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
    .password-submit:hover {
        background: #06623d;
        box-shadow: 0 8px 18px rgba(8,122,75,.2);
        transform: translateY(-1px);
    }
    .password-submit:active {
        transform: translateY(0);
    }
    .password-help {
        margin-top: 1.5rem;
        padding-top: 1.1rem;
        color: #87948c;
        border-top: 1px solid #edf1ee;
        font-size: .76rem;
        line-height: 1.6;
        text-align: center;
    }
    .password-help a {
        color: #087a4b;
        font-weight: 600;
        text-decoration: none;
    }
    .password-help a:hover {
        color: #064b34;
        text-decoration: underline;
    }
    .password-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: #087a4b;
        text-decoration: none;
        font-size: .85rem;
        font-weight: 600;
        margin-top: 1rem;
    }
    .password-back:hover {
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
    .invalid-feedback {
        color: #dc3545;
        font-size: .8rem;
        margin-top: .25rem;
    }
    .is-invalid {
        border-color: #dc3545 !important;
    }
</style>

<div class="password-page">
    <div class="password-frame">
        <div class="password-header">
            <div class="password-brand">
                <img src="{{ asset('images/logo.png') }}" alt="PANELCO III logo">
                <span>SmartDocs AI<small>PANELCO III</small></span>
            </div>
            <h1 class="password-title">Reset your password</h1>
            <p class="password-subtitle">Enter your email address and we’ll send you a secure reset link.</p>
        </div>

        <div class="password-form">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Something went wrong.</strong>
                    @foreach($errors->all() as $msg)
                        <span class="d-block mt-1 small">{{ $msg }}</span>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="you@panelco3.local">
                    @error('email')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
                <button type="submit" class="btn password-submit">Send reset link <i class="bi bi-envelope ms-1"></i></button>
            </form>

            <a href="{{ route('login') }}" class="password-back"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
        </div>

        <div class="password-help">
            <i class="bi bi-info-circle me-1"></i> Can’t sign in? Contact your administrator at <strong>admin@panelco3.local</strong>.
        </div>
    </div>
</div>
@endsection