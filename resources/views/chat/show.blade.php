@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
<div class="col-lg-9">
<div class="d-flex justify-content-between align-items-center">
<a href="{{ route('chat.index') }}" class="small text-decoration-none" style="color:#00CC33">&larr; All conversations</a>
</div>
<h4 class="fw-bold mt-1 mb-3">{{ $conversation->title }}</h4>
<div class="d-flex flex-column gap-3 chat-scroll pe-1" id="messages" aria-live="polite">
@foreach ($conversation->messages as $msg)
@if ($msg->role === 'user')
<div class="d-flex justify-content-end gap-2 msg-in">
<div class="p-3 text-white" style="background:#00CC33;border-radius:1rem 1rem .25rem 1rem;max-width:85%">{{ $msg->content }}<div class="mt-1 opacity-75" style="font-size:.72rem">{{ $msg->created_at->format('g:i A') }}</div></div>
<span class="avatar avatar-user" title="{{ auth()->user()->name }}">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
</div>
@else
<div class="d-flex gap-2 msg-in">
<span class="avatar avatar-ai"><i class="bi bi-lightning-charge-fill"></i></span>
<div class="card card-p3 p-3 flex-grow-1" style="border:none">
{!! \App\Support\MarkdownLite::render($msg->content) !!}
<div style="font-size:.72rem" class="text-muted mt-1">{{ $msg->created_at->format('g:i A') }}</div>
@if ($msg->sources->isNotEmpty())
<hr class="my-2">
<div class="small fw-bold text-muted mb-2"><i class="bi bi-link-45deg me-1"></i>SOURCES · click to verify</div>
@foreach ($msg->sources as $src)
@can('view', $src->document)
<a href="{{ route('documents.show', $src->document) }}?excerpt={{ urlencode(mb_substr($src->supporting_excerpt ?? '', 0, 120)) }}" class="src-card d-block text-decoration-none border rounded p-2 mb-2" style="border-color:#d5e8db">
<div class="fw-semibold small" style="color:#007A24"><i class="bi bi-file-earmark-text me-1"></i>{{ $src->document->title ?? $src->document->original_filename }}@if($src->page_number) · Page {{ $src->page_number }}@endif<i class="bi bi-box-arrow-up-right ms-1"></i></div>
<div class="small text-muted">“{{ $src->supporting_excerpt }}”</div>
</a>
@else
<div class="small text-muted border rounded p-2 mb-2">A cited document is no longer accessible to you.</div>
@endcan
@endforeach
@endif
</div>
</div>
@endif
@endforeach
</div>
<form method="POST" action="{{ route('chat.ask', $conversation) }}" class="mt-3 sticky-bottom pb-1" id="askForm" style="background:var(--p3-bg)">
@csrf
<div class="input-group">
<textarea name="question" id="questionInput" class="form-control" rows="1" maxlength="2000" required placeholder="Ask a follow-up… (Enter to send)" autocomplete="off" aria-label="Ask a follow-up" style="resize:none"></textarea>
<button class="btn" style="background:#00CC33;border-color:#00CC33;color:#fff;font-weight:600" id="sendBtn">Send</button>
</div>
</form>
</div>
</div>
<script>
(function () {
    var box = document.getElementById('messages');
    if (box) box.scrollTop = box.scrollHeight;
    var form = document.getElementById('askForm');
    var input = document.getElementById('questionInput');
    if (!form || !input) return;
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });
    form.addEventListener('submit', function () {
        var btn = document.getElementById('sendBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        var typing = document.createElement('div');
        typing.className = 'd-flex gap-2 msg-in';
        typing.innerHTML = '<span class="avatar avatar-ai"><i class="bi bi-lightning-charge-fill"></i></span><div class="card card-p3 p-3 text-muted small"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Searching your documents…</div>';
        box.appendChild(typing);
        box.scrollTop = box.scrollHeight;
    });
})();
</script>
@endsection
