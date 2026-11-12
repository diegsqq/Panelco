@extends('layouts.app')

@section('content')
<style>
    .login-page {
        --login-green: #087a4b;
        --login-deep: #064b34;
        position: relative;
        isolation: isolate;
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
    .login-page::before {
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
    .login-page::after {
        position: absolute;
        z-index: -1;
        top: -13rem;
        right: -9rem;
        width: clamp(19rem, 38vw, 34rem);
        aspect-ratio: 1;
        border: 1px solid rgba(8, 122, 75, .1);
        border-radius: 50%;
        box-shadow: 0 0 0 2.5rem rgba(8, 122, 75, .025), 0 0 0 5rem rgba(8, 122, 75, .02);
        content: "";
        pointer-events: none;
    }
    .login-frame {
        width: min(100%, 1060px);
        min-height: 630px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(9, 69, 46, .08);
        border-radius: 1.5rem;
        box-shadow: 0 28px 80px rgba(19, 58, 39, .13);
    }
    .login-intro {
        position: relative;
        isolation: isolate;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
        padding: clamp(2rem, 5vw, 3.5rem);
        color: #fff;
        background: linear-gradient(145deg, #0b8955 0%, #076b45 48%, #064b34 100%);
    }
    .login-intro::before,
    .login-intro::after {
        position: absolute;
        z-index: -1;
        content: "";
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 50%;
        pointer-events: none;
    }
    .login-intro::before { width: 440px; height: 440px; right: -230px; top: 17%; }
    .login-intro::after { width: 340px; height: 340px; right: -180px; top: 25%; }
    .login-illustration {
        position: absolute;
        z-index: 0;
        right: -1.5rem;
        bottom: 4.5rem;
        width: clamp(150px, 23vw, 235px);
        height: auto;
        color: #d7f6e5;
        opacity: .16;
        pointer-events: none;
    }
    .login-brand, .login-copy, .login-secure { position: relative; z-index: 1; }
    .login-brand { display: flex; align-items: center; gap: .8rem; color: #fff; }
    .login-brand img { width: 48px; height: 48px; object-fit: contain; padding: 5px; background: #fff; border-radius: 13px; }
    .login-brand small { display: block; margin-top: .12rem; color: rgba(255,255,255,.72); letter-spacing: .08em; }
    .login-copy { max-width: 430px; margin: 4.5rem 0 3rem; }
    .login-eyebrow { color: #b7f2d2; font-size: .75rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .login-copy h1 { margin: 1rem 0; font-size: clamp(2rem, 3.5vw, 2.8rem); font-weight: 700; letter-spacing: -.04em; line-height: 1.12; }
    .login-copy > p { max-width: 390px; color: rgba(255,255,255,.78); line-height: 1.7; }
    .login-features { display: grid; gap: 1rem; margin-top: 2rem; }
    .login-feature { display: flex; align-items: center; gap: .8rem; color: rgba(255,255,255,.92); font-size: .9rem; }
    .login-feature i { display: grid; width: 30px; height: 30px; place-items: center; color: #b7f2d2; background: rgba(255,255,255,.12); border-radius: 9px; }
    .login-secure { display: flex; align-items: center; gap: .55rem; color: rgba(255,255,255,.7); font-size: .8rem; }
    .login-secure i { color: #b7f2d2; }
    .login-form-panel { display: grid; place-items: center; padding: clamp(2rem, 5vw, 4rem); }
    .login-form-content { width: min(100%, 370px); }
    .login-form-content h2 { margin: .25rem 0 .45rem; color: #163a2b; font-size: 1.8rem; font-weight: 700; letter-spacing: -.035em; }
    .login-form-content .login-subtitle { margin-bottom: 2rem; color: #718078; font-size: .92rem; }
    .login-form-content .form-label { margin-bottom: .5rem; color: #294438; font-size: .84rem; font-weight: 600; }
    .login-form-content .form-control { min-height: 48px; border-color: #dce6df; border-radius: .7rem; font-size: .92rem; }
    .login-form-content .form-control::placeholder { color: #a0ada5; }
    .login-form-content .form-control:focus { border-color: #159461; box-shadow: 0 0 0 .22rem rgba(21,148,97,.13); }
    .login-form-content .input-group .form-control { border-radius: .7rem 0 0 .7rem; }
    .login-form-content .input-group .btn { border-color: #dce6df; border-radius: 0 .7rem .7rem 0; color: #617369; }
    .login-form-content .input-group .btn:focus { box-shadow: 0 0 0 .22rem rgba(21,148,97,.13); z-index: 3; }
    .login-form-content .form-check-input:checked { background-color: #087a4b; border-color: #087a4b; }
    .login-form-content a { color: #087a4b; font-weight: 600; text-decoration: none; }
    .login-form-content a:hover { color: #064b34; text-decoration: underline; }
    .login-submit { min-height: 49px; margin-top: .35rem; color: #fff; background: #087a4b; border: 0; border-radius: .7rem; font-weight: 650; transition: background .18s ease, transform .18s ease, box-shadow .18s ease; }
    .login-submit:hover { color: #fff; background: #06623d; box-shadow: 0 8px 18px rgba(8,122,75,.2); transform: translateY(-1px); }
    .login-help { margin-top: 1.5rem; padding-top: 1.1rem; color: #87948c; border-top: 1px solid #edf1ee; font-size: .76rem; line-height: 1.6; }
    .login-form-content .alert-danger {
        background: #fdecea; border: 1px solid #f5c6c7; color: #842029; border-radius: .7rem; padding: .75rem 1rem;
    }
    .login-form-content .alert-danger i { color: #dc3545; }
    @media (max-width: 767.98px) {
        .login-page { display: block; padding: 1rem; }
        .login-frame { min-height: calc(100vh - 2rem); grid-template-columns: 1fr; border-radius: 1.2rem; }
        .login-intro { min-height: 205px; padding: 1.5rem; }
        .login-copy { margin: 1.7rem 0 0; }
        .login-copy h1 { max-width: 440px; margin: .55rem 0; font-size: 1.65rem; }
        .login-copy > p, .login-features, .login-secure { display: none; }
        .login-illustration { right: -2rem; bottom: -1.5rem; width: 145px; opacity: .1; }
        .login-form-panel { padding: 2rem 1.5rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .login-submit { transition: none; }
    }
</style>

<div class="login-page">
    <div class="login-frame">
        <section class="login-intro" aria-label="About SmartDocs AI">
            <svg class="login-illustration" viewBox="0 0 220 250" fill="none" aria-hidden="true">
                <path d="M56 19h85l39 39v157a12 12 0 0 1-12 12H56a12 12 0 0 1-12-12V31a12 12 0 0 1 12-12Z" stroke="currentColor" stroke-width="3"/>
                <path d="M141 20v38h38M72 91h79M72 116h92M72 141h72" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                <circle cx="151" cy="180" r="31" fill="currentColor" fill-opacity=".12" stroke="currentColor" stroke-width="3"/>
                <path d="m137 180 10 10 19-21" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="login-brand">
                <img src="{{ asset('images/logo.png') }}" alt="PANELCO III logo">
                <span class="fw-bold">SmartDocs AI<small>PANELCO III</small></span>
            </div>
            <div class="login-copy">
                <div class="login-eyebrow">Your cooperative, connected</div>
                <h1>Find the right record. Get a clear answer.</h1>
                <p>One secure place to explore your cooperative's documents and the knowledge inside them.</p>
                <div class="login-features">
                    <div class="login-feature"><i class="bi bi-folder2-open"></i><span>Documents organized and easy to find</span></div>
                    <div class="login-feature"><i class="bi bi-chat-square-text"></i><span>Helpful summaries grounded in your records</span></div>
                    <div class="login-feature"><i class="bi bi-shield-check"></i><span>Private access for authorized employees</span></div>
                </div>
            </div>
            <div class="login-secure"><i class="bi bi-lock-fill"></i> Secure employee access</div>
        </section>

        <section class="login-form-panel">
            <div class="login-form-content">
                <div class="login-eyebrow" style="color:#087a4b">Welcome back</div>
                <h2>Employee login</h2>
                <p class="login-subtitle">Sign in with your PANELCO III account to continue.</p>
                @if ($errors->any())
                    <div class="alert alert-danger mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Sign-in failed.</strong>
                        @foreach($errors->all() as $msg)
                            <span class="d-block mt-1 small">{{ $msg }}</span>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input id="employee_id" type="text" class="form-control @error('employee_id') is-invalid @enderror" name="employee_id" value="{{ old('employee_id') }}" required autocomplete="username" autofocus placeholder="0000" maxlength="4" pattern="[0-9]{4}" inputmode="numeric">
                        @error('employee_id')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Enter your password">
                            <button type="button" class="btn btn-outline-secondary" id="pwToggle" aria-label="Show password" aria-controls="password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                        @error('password')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small text-secondary" for="remember">Remember me</label>
                        </div>

                    </div>
                    <button type="submit" class="btn login-submit w-100">Sign in <i class="bi bi-arrow-right ms-1"></i></button>
                </form>
                <div class="login-help"><i class="bi bi-info-circle me-1"></i> Need access? Contact your administrator. Default password: P@55w0rd. Demo accounts: 0001 / 0002 / 0003.</div>
            </div>
        </section>
    </div>
</div>
<script>
(function () {
    var toggle = document.getElementById('pwToggle');
    var password = document.getElementById('password');
    if (!toggle || !password) return;
    toggle.addEventListener('click', function () {
        var show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        toggle.innerHTML = show ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>' : '<i class="bi bi-eye" aria-hidden="true"></i>';
        toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
    });
})();
</script>
@endsection



