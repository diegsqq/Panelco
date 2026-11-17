@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-p3 p-4 mb-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="stat-icon" style="background:#e6f3ea;color:#00A868"><i class="bi bi-question-circle"></i></span>
                    <div>
                        <h2 class="fw-bold mb-0">Help &amp; Support</h2>
                        <p class="text-muted mb-0">Common questions about PANELCO III SmartDocs.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-lg-4">
            <div class="card card-p3 p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="stat-icon" style="background:#e7f1fb;color:#0d6efd"><i class="bi bi-person"></i></span>
                    <div><div class="fw-semibold">Account</div></div>
                </div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ route('settings.index') }}"><i class="bi bi-person-circle me-2"></i>Profile and personal details</a></li>
                    <li class="mb-2"><a href="{{ route('settings.security') }}"><i class="bi bi-shield-lock me-2"></i>Password and security</a></li>
                    <li class="mb-2"><a href="{{ route('settings.preferences') }}"><i class="bi bi-palette me-2"></i>Appearance and theme</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card card-p3 p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="stat-icon" style="background:#e6f3ea;color:#00A868"><i class="bi bi-file-earmark-text"></i></span>
                    <div><div class="fw-semibold">Documents</div></div>
                </div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ route('documents.upload') }}"><i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload a document</a></li>
                    <li class="mb-2"><a href="{{ route('documents.library') }}"><i class="bi bi-folder-fill me-2"></i>Browse document library</a></li>
                    <li class="mb-2"><a href="{{ route('documents.trash') }}"><i class="bi bi-trash3 me-2"></i>Recycle bin</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card card-p3 p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="stat-icon" style="background:#fff3cd;color:#856404"><i class="bi bi-bell"></i></span>
                    <div><div class="fw-semibold">Notifications</div></div>
                </div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ route('notifications.index') }}"><i class="bi bi-bell-fill me-2"></i>View all notifications</a></li>
                    <li class="mb-2"><a href="{{ route('documents.library') }}"><i class="bi bi-bag-check me-2"></i>Password reset requests</a></li>
                </ul>
            </div>
        </div>
    </div>

    @can('admin')
    <div class="row g-3 mt-4">
        <div class="col-12">
            <div class="card card-p3 p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="stat-icon" style="background:#f8d7da;color:#721c24"><i class="bi bi-people"></i></span>
                    <div><div class="fw-semibold">Administration</div></div>
                </div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ route('admin.users.index') }}"><i class="bi bi-people me-2"></i>Manage users</a></li>
                    <li class="mb-2"><a href="{{ route('admin.audit-logs.index') }}"><i class="bi bi-journal-text me-2"></i>Audit logs</a></li>
                    <li class="mb-2"><a href="{{ route('admin.system-health.index') }}"><i class="bi bi-heart-pulse me-2"></i>System health</a></li>
                </ul>
            </div>
        </div>
    </div>
    @endcan

    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-p3 p-4 text-center">
                <i class="bi bi-chat-right-text" style="font-size:2rem;color:#00A868"></i>
                <div class="mt-3">
                    <h5 class="fw-bold">Still need help?</h5>
                    <p class="text-muted">Contact your system administrator or use Lightning AI from the dashboard.</h5>
                    <a href="{{ route('chat.index') }}" class="btn btn-outline-success mt-2"><i class="bi bi-lightning-charge-fill me-1"></i>Ask Lightning AI</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
