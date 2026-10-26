@extends('layouts.app')
@section('content')
@php
    $s = $settings;
    $val = fn (string $key, string $default = '') => $s[$key] ?? $default;
    $on = fn (string $key, string $default = '1') => $val($key, $default) === '1';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">My Profile</h4>
    @if(! $isAdmin)<span class="badge" style="background:#fff3cd;color:#856404"><i class="bi bi-eye me-1"></i>View only</span>@endif
</div>

@if($isAdmin)
<form method="POST" action="{{ route('settings.update') }}">
@csrf
@method('PUT')
@endif

@if($isAdmin)
<div class="row g-4">
<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-window"></i></span>
<div><div class="fw-semibold">App Basics</div><small class="text-muted">Name and contact shown across the system.</small></div>
</div>
<div class="row g-3">
<div class="col-md-8">
<label class="form-label">App Name</label>
<input name="app_name" class="form-control" value="{{ old('app_name', $val('app_name', config('app.name'))) }}" {{ $isAdmin ? 'required' : 'disabled' }}>
</div>
<div class="col-md-4">
<label class="form-label">Support Email</label>
<input name="support_email" type="email" class="form-control" value="{{ old('support_email', $val('support_email')) }}" placeholder="it@panelco.gov.ph" {{ $isAdmin ? '' : 'disabled' }}>
</div>
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
<input name="ai_model" class="form-control" value="{{ old('ai_model', $val('ai_model')) }}" placeholder="gpt-4o-mini" {{ $isAdmin ? '' : 'disabled' }}>
</div>
<div class="col-md-3">
<label class="form-label">Temperature</label>
<input name="ai_temperature" type="number" step="0.1" min="0" max="2" class="form-control" value="{{ old('ai_temperature', $val('ai_temperature', '0.2')) }}" {{ $isAdmin ? 'required' : 'disabled' }}>
</div>
<div class="col-md-3">
<label class="form-label">Max Tokens</label>
<input name="ai_max_tokens" type="number" min="64" max="8192" class="form-control" value="{{ old('ai_max_tokens', $val('ai_max_tokens', '1024')) }}" {{ $isAdmin ? 'required' : 'disabled' }}>
</div>
<div class="col-12 d-flex flex-wrap gap-3 pt-1">
<label class="btn btn-sm p3-dept-pill"><input type="checkbox" name="ai_chat_enabled" class="d-none" value="1" {{ $on('ai_chat_enabled') ? 'checked' : '' }} {{ $isAdmin ? '' : 'disabled' }}><i class="bi bi-chat-dots me-1"></i>Lightning</label>
<label class="btn btn-sm p3-dept-pill"><input type="checkbox" name="ai_autofill_enabled" class="d-none" value="1" {{ $on('ai_autofill_enabled') ? 'checked' : '' }} {{ $isAdmin ? '' : 'disabled' }}><i class="bi bi-magic me-1"></i>Upload Autofill</label>
<label class="btn btn-sm p3-dept-pill"><input type="checkbox" name="ai_suggest_enabled" class="d-none" value="1" {{ $on('ai_suggest_enabled') ? 'checked' : '' }} {{ $isAdmin ? '' : 'disabled' }}><i class="bi bi-tags me-1"></i>Category Suggestions</label>
</div>
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
<input name="upload_max_kb" type="number" min="1024" step="1024" class="form-control" value="{{ old('upload_max_kb', $val('upload_max_kb', '51200')) }}" {{ $isAdmin ? 'required' : 'disabled' }}>
</div>
<div class="col-md-8">
<label class="form-label">Allowed Extensions <span class="text-muted small">(comma-separated)</span></label>
<input name="allowed_extensions" class="form-control" value="{{ old('allowed_extensions', $val('allowed_extensions', 'pdf,docx,txt,png,jpg,jpeg')) }}" {{ $isAdmin ? 'required' : 'disabled' }}>
</div>
<div class="col-md-6">
<label class="form-label">Auto-Delete Failed Docs (days)</label>
<input name="failed_cleanup_days" type="number" min="0" class="form-control" value="{{ old('failed_cleanup_days', $val('failed_cleanup_days', '0')) }}" {{ $isAdmin ? 'required' : 'disabled' }}>
<small class="text-muted">0 = never delete.</small>
</div>
</div>
</div>
</div>
</div>

<div class="col-lg-6">
<div class="card card-p3 p-4 h-100">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="stat-icon" style="background:#e6f3ea;color:var(--p3-navy)"><i class="bi bi-shield-lock"></i></span>
<div><div class="fw-semibold">Security</div><small class="text-muted">Session and audit retention.</small></div>
</div>
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Session Timeout (minutes)</label>
<input name="session_timeout_minutes" type="number" min="5" max="1440" class="form-control" value="{{ old('session_timeout_minutes', $val('session_timeout_minutes', '120')) }}" {{ $isAdmin ? 'required' : 'disabled' }}>
</div>
<div class="col-md-6">
<label class="form-label">Audit Log Retention (days)</label>
<input name="audit_retention_days" type="number" min="1" max="3650" class="form-control" value="{{ old('audit_retention_days', $val('audit_retention_days', '90')) }}" {{ $isAdmin ? 'required' : 'disabled' }}>
</div>
</div>
</div>
</div>
</div>
@endif

@if($isAdmin)
<div class="d-flex gap-2 mt-4">
<button class="btn btn-p3 px-4"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
<a href="{{ route('settings.index') }}" class="btn btn-outline-secondary px-4">Reset</a>
</div>
@endif
@if(!$isAdmin)
<form method="POST" action="{{ route('settings.update') }}">
@csrf
@method('PUT')

<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="{{ old('name', auth()->user()->name) }}" required>
    </div>
    <div class="col-12">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
    </div>
    <div class="col-12">
        <label class="form-label">Password (leave blank to keep current)</label>
        <input name="password" type="password" class="form-control" placeholder="Leave blank to keep current password">
    </div>
    <div class="col-12 mt-3">
        <button class="btn btn-p3 px-4"><i class="bi bi-check-lg me-1"></i>Update Profile</button>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</div>
</form>
@endif
@endsection