@extends('layouts.app')

@section('content')
<div class="min-vh-100 d-flex" style="background:linear-gradient(135deg,#00A868 0%,#00743F 55%,#1DBF7D 100%)">
    <div class="container align-self-center py-5">
        <div class="card card-p3 mx-auto p-4 p-md-5 bg-white" style="max-width:440px">
            <div class="text-center mb-3">
                <span class="stat-icon mx-auto" style="background:#e6f3ea;color:#00A868"><i class="bi bi-shield-lock-fill"></i></span>
                <h5 class="fw-bold mt-2" style="color:#00A868">Two-Factor Authentication</h5>
                <p class="text-muted small mb-0">Enter the 6-digit code from your authenticator app to finish signing in.</p>
            </div>
            <form method="POST" action="{{ route('two-factor.verify') }}">
                @csrf
                <div class="mb-3">
                    <label for="code" class="form-label">Authentication Code</label>
                    <input id="code" name="code" class="form-control @error('code') is-invalid @enderror text-center"
                        style="letter-spacing:6px;font-size:1.5rem;font-weight:700;max-width:220px;margin:0 auto"
                        inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required autofocus>
                    @error('code')<span class="invalid-feedback d-block text-center" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <button type="submit" class="btn w-100" style="background:#00A868;border-color:#00A868;color:#fff;font-weight:600">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Verify &amp; Sign In
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="small text-decoration-none" style="color:#00743F"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
            </div>
        </div>
    </div>
</div>
@endsection
