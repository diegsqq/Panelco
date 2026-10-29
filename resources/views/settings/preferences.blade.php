@extends('layouts.app')
@section('content')
@php
    $me = auth()->user();
    $s = $settings ?? [];
    $val = fn (string $key, string $default = '') => $s[$key] ?? $default;
    $on = fn (string $key, string $default = '1') => $val($key, $default) === '1';
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
<div>
<h4 class="mb-0 fw-bold">{{ __('settings.preferences') }}</h4>
<small class="text-muted">How SmartDocs looks and what it emails you about.</small>
</div>
</div>

<div class="row g-4 mb-1">
<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-palette"></i></span>
<div><div class="fw-semibold">{{ __('settings.appearance') }}</div><small class="text-muted">Light or dark theme — applies to your account only.</small></div>
</div>
<div class="d-flex gap-3">
<form method="POST" action="{{ route('settings.theme') }}" class="flex-fill d-flex">
@csrf
<input type="hidden" name="theme" value="light">
<button type="submit" class="p3-theme-tile w-100 {{ $me->theme !== 'dark' ? 'on' : '' }}">
<i class="bi bi-sun-fill"></i><span>{{ __('settings.light') }}</span>
@if($me->theme !== 'dark')<i class="bi bi-check-circle-fill ms-auto" style="font-size:.95rem"></i>@endif
</button>
</form>
<form method="POST" action="{{ route('settings.theme') }}" class="flex-fill d-flex">
@csrf
<input type="hidden" name="theme" value="dark">
<button type="submit" class="p3-theme-tile w-100 {{ $me->theme === 'dark' ? 'on' : '' }}">
<i class="bi bi-moon-stars-fill"></i><span>{{ __('settings.dark') }}</span>
@if($me->theme === 'dark')<i class="bi bi-check-circle-fill ms-auto" style="font-size:.95rem"></i>@endif
</button>
</form>
</div>
</div>
</div>

<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-translate"></i></span>
<div><div class="fw-semibold">{{ __('settings.language') }}</div><small class="text-muted">Interface language for menus and settings.</small></div>
</div>
<form method="POST" action="{{ route('settings.locale') }}" class="d-flex gap-2 flex-wrap">
@csrf
<div class="input-group flex-grow-1" style="min-width:200px">
<span class="input-group-text"><i class="bi bi-globe2"></i></span>
<select name="locale" class="form-select">
<option value="en" {{ ($me->locale ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
<option value="tl" {{ ($me->locale ?? 'en') === 'tl' ? 'selected' : '' }}>Tagalog / Filipino</option>
</select>
</div>
<button class="btn btn-p3 px-4"><i class="bi bi-check-lg me-1"></i>{{ __('settings.save') }}</button>
</form>
</div>
</div>
</div>

<div class="row g-4 mb-1">
<div class="col-12">
<div class="card card-p3 p-4">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-bell"></i></span>
<div><div class="fw-semibold">{{ __('settings.notifications') }}</div><small class="text-muted">Choose which emails SmartDocs sends you.</small></div>
</div>
<form method="POST" action="{{ route('settings.notifications') }}">
@csrf
<div class="row g-3">
<div class="col-lg-4">
<label class="p3-notif-row w-100 h-100 mb-0" style="cursor:pointer">
<i class="bi bi-check2-circle"></i>
<span class="flex-grow-1">
<span class="fw-semibold small d-block">{{ __('settings.notify_done') }}</span>
<small class="text-muted">A short email when text extraction finishes.</small>
</span>
<span class="form-check form-switch m-0" style="flex:0 0 auto"><input type="checkbox" class="form-check-input" role="switch" name="notify_doc_done" value="1" {{ $me->notify_doc_done ? 'checked' : '' }} style="cursor:pointer"></span>
</label>
</div>
<div class="col-lg-4">
<label class="p3-notif-row w-100 h-100 mb-0" style="cursor:pointer">
<i class="bi bi-x-octagon"></i>
<span class="flex-grow-1">
<span class="fw-semibold small d-block">{{ __('settings.notify_failed') }}</span>
<small class="text-muted">Get told right away when processing fails.</small>
</span>
<span class="form-check form-switch m-0" style="flex:0 0 auto"><input type="checkbox" class="form-check-input" role="switch" name="notify_doc_failed" value="1" {{ $me->notify_doc_failed ? 'checked' : '' }} style="cursor:pointer"></span>
</label>
</div>
<div class="col-lg-4">
<label class="p3-notif-row w-100 h-100 mb-0" style="cursor:pointer">
<i class="bi bi-fingerprint"></i>
<span class="flex-grow-1">
<span class="fw-semibold small d-block">{{ __('settings.notify_login') }}</span>
<small class="text-muted">Security alert for every new sign-in.</small>
</span>
<span class="form-check form-switch m-0" style="flex:0 0 auto"><input type="checkbox" class="form-check-input" role="switch" name="notify_login_alert" value="1" {{ $me->notify_login_alert ? 'checked' : '' }} style="cursor:pointer"></span>
</label>
</div>
</div>
<div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
<button class="btn btn-p3 px-4"><i class="bi bi-check-lg me-1"></i>{{ __('settings.save_prefs') }}</button>
<small class="text-muted"><i class="bi bi-envelope-paper me-1"></i>Emails go to <b>{{ $me->email }}</b></small>
</div>
</form>
</div>
</div>
</div>
@if($isAdmin ?? false)
{{-- App Basics, AI Settings, Documents — moved from Settings > General --}}
<div class="d-flex align-items-center gap-2 mb-3 mt-4">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-sliders"></i></span>
<div><div class="fw-semibold">System</div><small class="text-muted">App name, AI behavior, and document rules.</small></div>
</div>
<form method="POST" action="{{ route('settings.update') }}">
@csrf
@method('PUT')
<div class="row g-4 mb-1">
<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-window"></i></span>
<div><div class="fw-semibold">App Basics</div><small class="text-muted">Name and contact shown across the system.</small></div>
</div>
<div class="row g-3">
<div class="col-md-8">
<label class="form-label">App Name</label>
<input name="app_name" class="form-control" value="{{ old('app_name', $val('app_name', config('app.name'))) }}" required>
</div>
<div class="col-md-4">
<label class="form-label">Support Email</label>
<input name="support_email" type="email" class="form-control" value="{{ old('support_email', $val('support_email')) }}" placeholder="it@panelco.gov.ph">
</div>
</div>
</div>
</div>

<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-robot"></i></span>
<div><div class="fw-semibold">AI Settings</div><small class="text-muted">Model behavior and feature toggles.</small></div>
</div>
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Model Override <span class="text-muted small">(blank = .env)</span></label>
<input name="ai_model" class="form-control" value="{{ old('ai_model', $val('ai_model')) }}" placeholder="gpt-4o-mini">
</div>
<div class="col-md-3">
<label class="form-label">Temperature</label>
<input name="ai_temperature" type="number" step="0.1" min="0" max="2" class="form-control" value="{{ old('ai_temperature', $val('ai_temperature', '0.2')) }}" required>
</div>
<div class="col-md-3">
<label class="form-label">Max Tokens</label>
<input name="ai_max_tokens" type="number" min="64" max="8192" class="form-control" value="{{ old('ai_max_tokens', $val('ai_max_tokens', '1024')) }}" required>
</div>
<div class="col-12 d-flex flex-wrap gap-3 pt-1">
<label class="btn btn-sm p3-dept-pill"><input type="checkbox" name="ai_chat_enabled" class="d-none" value="1" {{ $on('ai_chat_enabled') ? 'checked' : '' }}><i class="bi bi-chat-dots me-1"></i>Lightning</label>
<label class="btn btn-sm p3-dept-pill"><input type="checkbox" name="ai_autofill_enabled" class="d-none" value="1" {{ $on('ai_autofill_enabled') ? 'checked' : '' }}><i class="bi bi-magic me-1"></i>Upload Autofill</label>
<label class="btn btn-sm p3-dept-pill"><input type="checkbox" name="ai_suggest_enabled" class="d-none" value="1" {{ $on('ai_suggest_enabled') ? 'checked' : '' }}><i class="bi bi-tags me-1"></i>Category Suggestions</label>
</div>
</div>
</div>
</div>

<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-cloud-arrow-up"></i></span>
<div><div class="fw-semibold">Documents</div><small class="text-muted">Upload limits and cleanup rules.</small></div>
</div>
<div class="row g-3">
<div class="col-md-4">
<label class="form-label">Max Upload (KB)</label>
<input name="upload_max_kb" type="number" min="1024" step="1024" class="form-control" value="{{ old('upload_max_kb', $val('upload_max_kb', '51200')) }}" required>
</div>
<div class="col-md-8">
<label class="form-label">Allowed Extensions <span class="text-muted small">(comma-separated)</span></label>
<input name="allowed_extensions" class="form-control" value="{{ old('allowed_extensions', $val('allowed_extensions', 'pdf,docx,txt,png,jpg,jpeg')) }}" required>
</div>
<div class="col-md-6">
<label class="form-label">Auto-Delete Failed Docs (days)</label>
<input name="failed_cleanup_days" type="number" min="0" class="form-control" value="{{ old('failed_cleanup_days', $val('failed_cleanup_days', '0')) }}" required>
<small class="text-muted">0 = never delete.</small>
</div>
</div>
</div>
</div>
</div>

<div class="d-flex gap-2 mt-3">
<button class="btn btn-p3 px-4"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
<a href="{{ route('settings.preferences') }}" class="btn btn-outline-secondary px-4">Reset</a>
</div>
</form>
@endif
@endsection
