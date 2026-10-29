@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card card-p3 p-4 mb-3" style="background:linear-gradient(135deg,#00A868,#00743F)">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.2);color:#fff;font-size:1.2rem"><i class="bi bi-lightning-charge-fill"></i></span>
<h4 class="fw-bold text-white mb-0">Lightning</h4>
</div>
<form method="POST" action="{{ route('chat.store') }}" id="newChatForm">
@csrf
<div class="input-group">
<input name="question" class="form-control" maxlength="2000" required placeholder="Ask about your documents…" value="{{ request('q') }}" aria-label="Ask about your documents">
<button class="btn" style="background:#fff;color:#00743F;font-weight:700">Send</button>
</div>
</form>
<div class="d-flex flex-wrap gap-2 mt-3">
@foreach(['Summarize the latest report', 'What corrective actions were recorded?', 'Find documents mentioning a transformer', 'What are the important details?'] as $s)
<button type="button" class="btn btn-sm btn-outline-light chip-q">{{ $s }}</button>
@endforeach
</div>
<script>
(function () {
    var form = document.getElementById('newChatForm');
    var box = document.querySelector('#newChatForm input[name=question]');
    if (!form || !box) return;
    document.querySelectorAll('.chip-q').forEach(function (c) {
        c.addEventListener('click', function () {
            box.value = c.textContent;
            c.disabled = true;
            c.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            form.submit();
        });
    });
})();
</script>
</div>
<div id="convListWrap" style="min-height:calc(100vh - 130px);display:flex;flex-direction:column">
<div class="card card-p3 p-0 overflow-hidden" style="flex-grow:1">
<div class="p-3 border-bottom fw-bold">Your conversations</div>
@forelse ($conversations as $c)
<div class="d-flex justify-content-between align-items-center border-bottom p-3">
<a href="{{ route('chat.show', $c) }}" class="fw-semibold text-decoration-none" style="color:#00743F">{{ $c->title }}</a>
<small class="text-muted">{{ $c->created_at->diffForHumans() }}</small>
</div>
@empty
<div class="p-4 text-center text-muted">No conversations yet — ask your first question above.</div>
@endforelse
</div>
<div id="chatPagination" class="d-flex flex-wrap justify-content-center align-items-center gap-3" style="margin-top:.75rem;position:sticky;bottom:0;z-index:20">
@if ($conversations->hasPages())
<small class="text-muted">{{ $conversations->firstItem() }}&ndash;{{ $conversations->lastItem() }} of {{ $conversations->total() }}</small>
<div class="d-flex align-items-center gap-2">
@if ($conversations->onFirstPage())
<span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
@else
<a class="btn btn-sm btn-outline-success" href="{{ $conversations->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
@endif
<span class="px-1 fw-semibold" style="color:#00743F">{{ $conversations->currentPage() }}/{{ $conversations->lastPage() }}</span>
@if ($conversations->hasMorePages())
<a class="btn btn-sm btn-outline-success" href="{{ $conversations->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
@else
<span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-right"></i></span>
@endif
</div>
@endif
</div>
</div>
</div>
</div>
@endsection
