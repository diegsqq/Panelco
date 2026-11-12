@extends('layouts.app')

@section('content')
<style>
    .admin-review-page {
        padding: 2rem 1.5rem;
        max-width: 720px;
        margin: 0 auto;
    }
    .admin-review-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 1rem;
        box-shadow: 0 4px 14px rgba(10,31,92,.06);
        padding: 1.5rem;
    }
    .admin-review-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #163a2b;
        margin-bottom: .25rem;
    }
    .admin-review-subtitle {
        color: #718078;
        font-size: .9rem;
        margin-bottom: 1.5rem;
    }
    .admin-review-row {
        display: flex;
        gap: 1rem;
        padding: .75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .admin-review-row:last-child { border-bottom: 0; }
    .admin-review-label {
        font-weight: 600;
        color: #294438;
        font-size: .85rem;
        min-width: 120px;
    }
    .admin-review-value {
        color: #1a1a2e;
        font-size: .9rem;
        flex: 1;
    }
    .admin-review-status {
        display: inline-block;
        padding: .2rem .6rem;
        border-radius: .4rem;
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .admin-review-status.pending { background: #fff3cd; color: #856404; }
    .admin-review-status.approved { background: #d4edda; color: #155724; }
    .admin-review-status.declined { background: #f8d7da; color: #842029; }
    .admin-review-actions {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
    }
    .admin-review-actions .btn {
        min-height: 42px;
        border-radius: .65rem;
        font-weight: 600;
        font-size: .9rem;
    }
    .btn-approve {
        background: #087a4b;
        color: #fff;
        border: 0;
    }
    .btn-approve:hover {
        background: #06623d;
        color: #fff;
    }
    .btn-decline {
        background: #dc3545;
        color: #fff;
        border: 0;
    }
    .btn-decline:hover {
        background: #c82333;
        color: #fff;
    }
    .admin-note-input {
        width: 100%;
        border: 1px solid #dce6df;
        border-radius: .65rem;
        padding: .65rem .85rem;
        font-size: .9rem;
        min-height: 80px;
        resize: vertical;
    }
    .admin-note-input:focus {
        border-color: #159461;
        box-shadow: 0 0 0 .22rem rgba(21,148,97,.13);
        outline: none;
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
    .alert-warning {
        background: #fff3cd;
        border: 1px solid #ffe69c;
        color: #856404;
        border-radius: .7rem;
        padding: .75rem 1rem;
        margin-bottom: 1rem;
        font-size: .88rem;
    }
    .admin-review-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: #087a4b;
        text-decoration: none;
        font-size: .85rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
    }
    .admin-review-back:hover {
        color: #064b34;
        text-decoration: underline;
    }
</style>

<div class="admin-review-page">
    <a href="{{ route('admin.users.index') }}" class="admin-review-back"><i class="bi bi-arrow-left me-1"></i>Back to users</a>

    <h1 class="admin-review-title">Password Reset Request</h1>
    <p class="admin-review-subtitle">Review the employee's password reset request and approve or decline it.</p>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('status') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('warning') }}
        </div>
    @endif

    <div class="admin-review-card">
        <div class="admin-review-row">
            <div class="admin-review-label">Employee ID</div>
            <div class="admin-review-value">{{ $request->employee_id }}</div>
        </div>
        <div class="admin-review-row">
            <div class="admin-review-label">Employee Name</div>
            <div class="admin-review-value">{{ $request->user?->name ?? '—' }}</div>
        </div>
        <div class="admin-review-row">
            <div class="admin-review-label">Email</div>
            <div class="admin-review-value">{{ $request->user?->email ?? '—' }}</div>
        </div>
        <div class="admin-review-row">
            <div class="admin-review-label">Reason</div>
            <div class="admin-review-value">{{ $request->reason }}</div>
        </div>
        <div class="admin-review-row">
            <div class="admin-review-label">Status</div>
            <div class="admin-review-value">
                <span class="admin-review-status {{ $request->status }}">{{ $request->status }}</span>
            </div>
        </div>
        <div class="admin-review-row">
            <div class="admin-review-label">Requested At</div>
            <div class="admin-review-value">{{ $request->created_at?->format('M d, Y h:i A') ?? '—' }}</div>
        </div>
        @if($request->reviewed_at)
        <div class="admin-review-row">
            <div class="admin-review-label">Reviewed At</div>
            <div class="admin-review-value">{{ $request->reviewed_at?->format('M d, Y h:i A') ?? '—' }}</div>
        </div>
        <div class="admin-review-row">
            <div class="admin-review-label">Reviewer</div>
            <div class="admin-review-value">{{ $request->reviewer?->name ?? '—' }}</div>
        </div>
        @endif
        @if($request->admin_note)
        <div class="admin-review-row">
            <div class="admin-review-label">Admin Note</div>
            <div class="admin-review-value">{{ $request->admin_note }}</div>
        </div>
        @endif

        @if($request->isPending())
        <div class="mt-3">
            <label for="admin_note" class="form-label fw-semibold" style="font-size:.85rem;color:#294438">Admin note (optional)</label>
            <textarea id="admin_note" name="admin_note" class="admin-note-input" placeholder="Add a note for the employee..."></textarea>
        </div>

        <div class="admin-review-actions">
            <form method="POST" action="{{ route('admin.password-reset-requests.approve', $request) }}" class="m-0 flex-fill">
                @csrf
                <input type="hidden" name="admin_note" id="approve_note">
                <button type="submit" class="btn btn-approve w-100" onclick="document.getElementById('approve_note').value = document.getElementById('admin_note').value;">
                    <i class="bi bi-check-lg me-1"></i>Approve
                </button>
            </form>
            <form method="POST" action="{{ route('admin.password-reset-requests.decline', $request) }}" class="m-0 flex-fill">
                @csrf
                <input type="hidden" name="admin_note" id="decline_note">
                <button type="submit" class="btn btn-decline w-100" onclick="document.getElementById('decline_note').value = document.getElementById('admin_note').value;">
                    <i class="bi bi-x-lg me-1"></i>Decline
                </button>
            </form>
        </div>
        @else
        <div class="mt-3 text-center">
            <span class="admin-review-status {{ $request->status }}">{{ $request->status }}</span>
            <p class="text-muted small mt-2">This request has been {{ $request->status }}.</p>
        </div>
        @endif
    </div>
</div>
@endsection