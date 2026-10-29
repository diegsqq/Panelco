@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
<div class="col-12 p3-chat-page" id="chatPage">
<div class="card card-p3 p-3 mb-2" style="background:linear-gradient(135deg,#00A868,#00743F);flex:0 0 auto">
<div class="d-flex align-items-center gap-2 mb-2">
<span class="d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;border-radius:9px;background:rgba(255,255,255,.2);color:#fff;font-size:1rem"><i class="bi bi-lightning-charge-fill"></i></span>
<h5 class="fw-bold text-white mb-0" style="font-size:1.05rem">Lightning</h5>
</div>
<form method="POST" action="{{ route('chat.store') }}" id="newChatForm">
@csrf
<div class="input-group input-group-sm">
<input name="question" class="form-control" maxlength="2000" required placeholder="Ask about your documents…" value="{{ request('q') }}" aria-label="Ask about your documents">
<button class="btn" style="background:#fff;color:#00743F;font-weight:700">Send</button>
</div>
</form>
<div class="d-flex flex-wrap gap-2 mt-2">
@foreach(['Summarize the latest report', 'What corrective actions were recorded?', 'Find documents mentioning a transformer', 'What are the important details?'] as $s)
<button type="button" class="btn btn-sm btn-outline-light chip-q" style="font-size:.72rem;padding:.2rem .6rem">{{ $s }}</button>
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
<div id="convListWrap" class="p3-conv-wrap">
<div class="card card-p3 p-0 p3-conv-card">
<div class="px-3 border-bottom fw-bold p3-conv-head" style="font-size:.8rem">Your conversations</div>
@forelse ($conversations as $c)
<div class="d-flex justify-content-between align-items-center border-bottom px-3 p3-conv-row">
<a href="{{ route('chat.show', $c) }}" class="fw-semibold text-decoration-none text-truncate me-3 p3-conv-title" style="color:#00743F;font-size:.85rem;line-height:1.35">{{ \Illuminate\Support\Str::limit($c->title, 40, '…') }}</a>
<small class="text-muted text-nowrap p3-conv-time" style="font-size:.68rem">{{ $c->created_at->diffForHumans() }}</small>
</div>
@empty
<div class="p-4 text-center text-muted">No conversations yet — ask your first question above.</div>
@endforelse
<div id="chatPagination" class="p3-conv-foot d-flex flex-wrap justify-content-center align-items-center gap-3">
@if ($conversations->hasPages())
<small class="text-muted" style="font-size:.68rem">{{ $conversations->firstItem() }}&ndash;{{ $conversations->lastItem() }} of {{ $conversations->total() }}</small>
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
<style>
/* Lightning list page: page locked, conversation card scrolls inside. */
.p3-chat-page { display: flex; flex-direction: column; min-height: 0; }
.p3-conv-wrap { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
.p3-conv-card { flex: 1 1 auto; min-height: 0; overflow-y: auto; display: flex; flex-direction: column; }
.p3-conv-card > div { flex-shrink: 0; }
.p3-conv-head { padding-top: .4rem; padding-bottom: .4rem; flex: 0 0 auto; }
/* Rows stretch to fill leftover card space (12-row page fills with no dead
   white), but max-height keeps a lone page-2 row at normal size. */
.p3-conv-row { padding-top: .6rem; padding-bottom: .6rem; flex: 1 1 auto; min-height: 40px; max-height: 62px; transition: background-color .12s ease; }
/* min-width:0 lets the flex item shrink so text-truncate actually ellipsizes
   instead of pushing the timestamp onto a second line. */
.p3-conv-title { min-width: 0; flex: 0 1 auto; }
.p3-conv-time { flex: 0 0 auto; }
.p3-conv-row:hover { background: #f0f9f3; }
[data-theme="dark"] .p3-conv-row:hover { background: #1d2b24; }
/* Footer: no visible container — blends into the card, floats above the
   fold only while the list scrolls; pushed to the bottom when short. */
.p3-conv-foot { position: sticky; bottom: 0; z-index: 5; margin-top: auto; padding: .45rem 0 .35rem; background: inherit; }
.p3-conv-card > div:nth-last-child(2) { border-bottom: none; }
</style>
<script>
(function () {
    function fit() {
        var el = document.getElementById('chatPage');
        if (!el) return;
        var top = el.getBoundingClientRect().top;
        el.style.height = Math.max(320, window.innerHeight - top) + 'px';
    }
    fit();
    window.addEventListener('resize', fit);
    window.addEventListener('load', fit);
    setTimeout(fit, 400); // re-fit after main's entrance animation settles
})();
</script>
@endsection
