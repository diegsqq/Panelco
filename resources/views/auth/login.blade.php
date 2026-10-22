@extends('layouts.app')

@section('content')
<div class="min-vh-100 d-flex" style="background:linear-gradient(135deg,#00CC33 0%,#007A24 55%,#2ED656 100%)">
    <div class="container align-self-center py-5">
        <div class="row g-0 shadow overflow-hidden mx-auto" style="max-width:960px;border-radius:1rem">
            <div class="col-md-6 p-4 p-md-5 text-white d-flex flex-column align-items-center justify-content-center text-center" style="background:rgba(0,0,0,.25)">
                <img src="{{ asset('images/logo.png') }}" alt="PANELCO III logo" style="height:110px" class="mb-3">
                <div class="fw-bold fs-4">SmartDocs AI</div>
                <p class="mt-2 mb-4" style="color:#cdebd6;max-width:340px">Smart, secure, and searchable — your cooperative's documents, made simple.</p>
                <div class="d-flex flex-column gap-2 small text-start" style="color:#cdebd6">
                    <div><i class="bi bi-check-circle-fill me-2" style="color:#7ee2a8"></i>Upload and classify documents instantly</div>
                    <div><i class="bi bi-check-circle-fill me-2" style="color:#7ee2a8"></i>Get AI summaries with citations</div>
                    <div><i class="bi bi-check-circle-fill me-2" style="color:#7ee2a8"></i>Ask questions — answers from real records</div>
                </div>
                <div class="mt-4 small" style="color:#9ed2ae"><i class="bi bi-shield-lock me-1"></i>Authorized access only</div>
            </div>
            <div class="col-md-6 p-4 p-md-5 bg-white">
                <h5 class="fw-bold mt-2" style="color:#00CC33">Employee Login</h5>
                <p class="text-muted small">Need access? Contact your administrator.</p>
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="you@panelco3.local">
                        @error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                        <button type="button" class="btn btn-outline-secondary" id="pwToggle" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                        @error('password')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}><label class="form-check-label small" for="remember">Remember Me</label></div>
                        @if (Route::has('password.request'))<a class="small" href="{{ route('password.request') }}">Forgot password?</a>@endif
                    </div>
                    <button type="submit" class="btn w-100" style="background:#00CC33;border-color:#00CC33;color:#fff;font-weight:600">Login to SmartDocs</button>
                </form>
                <script>
                (function () {
                    var t = document.getElementById('pwToggle');
                    var p = document.getElementById('password');
                    if (!t || !p) return;
                    t.addEventListener('click', function () {
                        var show = p.type === 'password';
                        p.type = show ? 'text' : 'password';
                        t.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
                        t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                    });
                })();
                </script>
                <p class="text-muted mt-3 mb-0" style="font-size:.78rem">Demo accounts: admin / manager / employee @panelco3.local.</p>
            </div>
        </div>
    </div>
</div>
@endsection
