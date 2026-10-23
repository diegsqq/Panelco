@extends('layouts.app')
@section('content')
<div class="card card-p3 p-5 text-center">
<i class="bi bi-gear fs-1" style="color:#1DBF7D"></i>
<h4 class="fw-bold mt-2">Settings</h4>
<p class="text-muted">Categories, departments, AI provider, and retention settings land in Phase 9. Requires PANELCO III approval for production AI/data changes.</p>
<a href="{{ route('dashboard') }}" class="btn btn-p3">Back to Dashboard</a>
</div>
@endsection
