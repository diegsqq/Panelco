@extends('layouts.chat')
@section('content')
<style>
    /* ── Shell ── */
    #lgThreadPage { display: flex; height: calc(100vh); overflow: hidden; background: #212121; color: #ececec; }

    /* ── Sidebar ── */
    .lg-sidebar { width: 260px; flex: 0 0 260px; background: #171717; display: flex; flex-direction: column; overflow: hidden; }
    .lg-sidebar-top { padding: .85rem .75rem .5rem; }
    .lg-brand { display: flex; align-items: center; gap: .6rem; padding: .35rem .6rem .7rem; }
    .lg-brand-ico { width: 28px; height: 28px; border-radius: 8px; background: #2a2a2a; display: flex; align-items: center; justify-content: center; color: #00A868; font-size: .95rem; }
    .lg-brand-name { font-weight: 700; font-size: .95rem; color: #ececec; }
    .lg-new-btn { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .55rem .75rem; border-radius: .65rem; border: none; background: #2a2a2a; color: #ececec; font-size: .85rem; font-weight: 600; cursor: pointer; transition: background .15s ease; text-decoration: none; }
    .lg-new-btn:hover { background: #333; color: #fff; }
    .lg-sidebar-section { font-size: .68rem; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .55px; padding: .8rem .85rem .3rem; }
    .lg-sidebar-body { flex: 1 1 auto; overflow-y: auto; padding: 0 .5rem .75rem; }
    .lg-conv-item { display: flex; align-items: center; gap: .4rem; padding: .48rem .65rem; border-radius: .55rem; text-decoration: none; color: #c0c0c0; font-size: .83rem; transition: background .12s ease; white-space: nowrap; overflow: hidden; }
    .lg-conv-item:hover { background: #2a2a2a; color: #fff; }
    .lg-conv-item.active { background: #2f2f2f; color: #fff; }
    .lg-conv-item span { overflow: hidden; text-overflow: ellipsis; flex: 1 1 auto; }
    .lg-conv-delete { opacity: 0; background: none; border: none; color: #666; font-size: .75rem; padding: .1rem .25rem; border-radius: .3rem; flex: 0 0 auto; transition: opacity .12s ease; cursor: pointer; }
    .lg-conv-item:hover .lg-conv-delete { opacity: 1; }
    .lg-conv-delete:hover { color: #f87171; }
    .lg-sidebar-foot { padding: .75rem; border-top: 1px solid #2a2a2a; }
    .lg-user-row { display: flex; align-items: center; gap: .6rem; padding: .45rem .6rem; border-radius: .65rem; transition: background .12s ease; cursor: pointer; }
    .lg-user-row:hover { background: #2a2a2a; }
    .lg-user-avatar { width: 28px; height: 28px; border-radius: 50%; background: #00A868; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .72rem; font-weight: 700; flex: 0 0 28px; overflow: hidden; }
    .lg-user-name { font-size: .82rem; color: #ececec; font-weight: 600; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lg-user-sub  { font-size: .68rem; color: #555; }

    /* ── Thread main ── */
    .lg-thread { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }
    .lg-thread-header { flex: 0 0 auto; padding: .65rem 1.5rem; border-bottom: 1px solid #2a2a2a; display: flex; align-items: center; gap: .75rem; }
    .lg-thread-title { font-weight: 600; font-size: .9rem; color: #ececec; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lg-back { color: #555; text-decoration: none; font-size: .82rem; display: flex; align-items: center; gap: .3rem; transition: color .12s; flex: 0 0 auto; }
    .lg-back:hover { color: #00A868; }

    /* ── Messages ── */
    .lg-messages { flex: 1 1 auto; overflow-y: auto; padding: 1.5rem 0; display: flex; flex-direction: column; gap: .5rem; }
    .lg-msg-wrap { max-width: 720px; width: 100%; margin: 0 auto; padding: 0 1.5rem; }

    /* User bubble */
    .lg-msg-user { display: flex; justify-content: flex-end; margin-bottom: .25rem; }
    .lg-msg-user-bubble { background: #2f2f2f; border: 1px solid #3a3a3a; border-radius: 1.1rem 1.1rem .25rem 1.1rem; padding: .65rem 1rem; max-width: 75%; color: #ececec; font-size: .88rem; line-height: 1.6; }
    .lg-msg-time { font-size: .68rem; color: #555; margin-top: .3rem; }

    /* AI bubble */
    .lg-msg-ai { display: flex; gap: .75rem; align-items: flex-start; margin-bottom: .25rem; }
    .lg-ai-ico { width: 28px; height: 28px; border-radius: 50%; background: #00A868; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .8rem; flex: 0 0 28px; margin-top: .1rem; }
    .lg-msg-ai-body { flex: 1 1 auto; min-width: 0; color: #ececec; font-size: .88rem; line-height: 1.75; }
    .lg-msg-ai-body p { margin-bottom: .5rem; }
    .lg-msg-ai-body ul, .lg-msg-ai-body ol { padding-left: 1.25rem; margin-bottom: .5rem; }
    .lg-msg-ai-body li { margin-bottom: .2rem; }
    .lg-msg-ai-body strong { color: #fff; }
    .lg-msg-ai-body code { background: #2a2a2a; border-radius: .3rem; padding: .1em .35em; font-size: .82em; color: #7fe0ac; }
    .lg-msg-ai-body pre { background: #1a1a1a; border-radius: .55rem; padding: .85rem 1rem; overflow-x: auto; margin-bottom: .5rem; }

    /* Sources */
    .lg-sources { margin-top: .6rem; padding-top: .6rem; border-top: 1px solid #2a2a2a; }
    .lg-sources-label { font-size: .7rem; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .4px; margin-bottom: .4rem; }
    .lg-source-card { display: block; text-decoration: none; background: #2a2a2a; border: 1px solid #333; border-radius: .55rem; padding: .5rem .75rem; margin-bottom: .35rem; transition: border-color .12s ease; }
    .lg-source-card:hover { border-color: #00A868; }
    .lg-source-title { font-size: .78rem; font-weight: 600; color: #00A868; display: flex; align-items: center; gap: .3rem; }
    .lg-source-excerpt { font-size: .72rem; color: #777; margin-top: .15rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

    /* Document context banner */
    .lg-doc-banner { background: #1e2e22; border: 1px solid #2c3d2c; border-radius: .65rem; padding: .45rem .9rem; font-size: .78rem; color: #8fa59a; display: flex; align-items: center; gap: .5rem; }
    .lg-doc-banner a { color: #00A868; text-decoration: none; }
    .lg-doc-banner a:hover { text-decoration: underline; }

    /* ── Composer ── */
    .lg-composer-area { flex: 0 0 auto; padding: .75rem 1.5rem 1.1rem; position: relative; }
    .lg-composer { max-width: 720px; margin: 0 auto; background: #2f2f2f; border-radius: 1.1rem; border: 1px solid #3a3a3a; transition: border-color .15s ease; }
    .lg-composer:focus-within { border-color: #00A868; }
    .lg-composer-inner { display: flex; align-items: center; gap: .5rem; padding: .65rem .9rem; }
    .lg-plus-btn { width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 1px solid #4a4a4a; color: #888; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s ease; flex: 0 0 28px; font-size: .85rem; }
    .lg-plus-btn:hover { background: #3a3a3a; border-color: #666; color: #ececec; }
    /* ── Plus popup menu ── */
    .lg-plus-menu { position: absolute; bottom: calc(100% + .4rem); left: 1.5rem; background: #2a2a2a; border: 1px solid #3a3a3a; border-radius: .85rem; padding: .4rem; min-width: 200px; box-shadow: 0 8px 24px rgba(0,0,0,.5); z-index: 100; display: none; flex-direction: column; gap: .15rem; }
    .lg-plus-menu.open { display: flex; }
    .lg-plus-menu-item { display: flex; align-items: center; gap: .65rem; padding: .55rem .75rem; border-radius: .6rem; font-size: .83rem; color: #c0c0c0; cursor: pointer; border: none; background: transparent; width: 100%; text-align: left; transition: background .12s ease, color .12s ease; text-decoration: none; }
    .lg-plus-menu-item:hover { background: #383838; color: #fff; }
    .lg-plus-menu-item i { font-size: 1rem; color: #00A868; width: 18px; text-align: center; flex: 0 0 18px; }
    .lg-plus-menu-sep { border: 0; border-top: 1px solid #3a3a3a; margin: .25rem 0; }
    .lg-composer textarea { flex: 1 1 auto; background: transparent; border: none; outline: none; color: #ececec; font-size: .88rem; resize: none; overflow: hidden; min-height: 24px; max-height: 180px; }
    .lg-composer textarea::placeholder { color: #666; }
    .lg-send-btn { width: 34px; height: 34px; border-radius: 50%; background: #00A868; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s ease; flex: 0 0 34px; color: #fff; font-size: .9rem; }
    .lg-send-btn:hover { background: #00743F; }
    .lg-send-btn:disabled { background: #333; color: #555; cursor: not-allowed; }
    .lg-footnote { font-size: .7rem; color: #3a3a3a; text-align: center; margin-top: .5rem; }
</style>

<div id="lgThreadPage">

    {{-- ── SIDEBAR ── --}}
    <aside class="lg-sidebar">
        <div class="lg-sidebar-top">
            <div class="lg-brand">
                <div class="lg-brand-ico"><i class="bi bi-lightning-charge-fill"></i></div>
                <span class="lg-brand-name">Lightning AI</span>
            </div>
            <a href="{{ route('chat.index') }}" class="lg-new-btn">
                <i class="bi bi-plus-lg"></i> New chat
            </a>
        </div>

        <div class="lg-sidebar-body">
            @php
                $allConvs = \App\Models\Conversation::where('user_id', auth()->id())->latest()->limit(30)->get();
            @endphp
            @if($allConvs->count())
            <div class="lg-sidebar-section">Recents</div>
            @foreach($allConvs as $c)
            <div class="lg-conv-item {{ $c->id === $conversation->id ? 'active' : '' }}" style="display:flex;align-items:center">
                <a href="{{ route('chat.show', $c) }}" class="text-decoration-none flex-fill overflow-hidden" style="color:inherit;display:flex;align-items:center;gap:.4rem;overflow:hidden">
                    <span>{{ \Illuminate\Support\Str::limit($c->title, 32, '…') }}</span>
                </a>
                <form method="POST" action="{{ route('chat.destroy', $c) }}" style="margin:0">
                    @csrf @method('DELETE')
                    <button type="submit" class="lg-conv-delete" title="Delete" onclick="return confirm('Delete this conversation?')">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>
            @endforeach
            @endif
        </div>

        <div class="lg-sidebar-foot">
            <a href="{{ route('dashboard') }}" class="lg-new-btn" style="color:#aaa;font-size:.83rem">
                <i class="bi bi-arrow-left"></i> Back to SmartDocs
            </a>
        </div>
    </aside>

    {{-- ── THREAD ── --}}
    <div class="lg-thread">

        {{-- Header --}}
        <div class="lg-thread-header">
            <a href="{{ route('chat.index') }}" class="lg-back"><i class="bi bi-arrow-left"></i></a>
            <div class="lg-thread-title">{{ $conversation->title }}</div>
            <form method="POST" action="{{ route('chat.destroy', $conversation) }}" class="ms-auto" id="deleteConvForm">
                @csrf @method('DELETE')
                <button type="button" class="lg-back" title="Delete conversation" onclick="if(confirm('Delete this conversation?')) document.getElementById('deleteConvForm').submit()">
                    <i class="bi bi-trash3"></i>
                </button>
            </form>
        </div>

        {{-- Messages --}}
        <div class="lg-messages" id="lgMessages" aria-live="polite">
            <div class="lg-msg-wrap">

                {{-- Document context banner --}}
                @if($conversation->document)
                <div class="lg-doc-banner mb-3">
                    <i class="bi bi-file-earmark-lock" style="color:#00A868;flex:0 0 auto"></i>
                    <span>Scoped to <strong style="color:#ececec">{{ $conversation->document->title ?? $conversation->document->original_filename }}</strong>
                    @if($conversation->comparisonDocument) vs <strong style="color:#ececec">{{ $conversation->comparisonDocument->title ?? $conversation->comparisonDocument->original_filename }}</strong>@endif
                    </span>
                    <a href="{{ route('documents.show', $conversation->document) }}" class="ms-auto">View</a>
                </div>
                @endif

                {{-- Messages --}}
                @foreach($conversation->messages as $msg)
                @if($msg->role === 'user')
                <div class="lg-msg-user">
                    <div>
                        <div class="lg-msg-user-bubble">{{ $msg->content }}</div>
                        <div class="lg-msg-time text-end">{{ $msg->created_at->format('g:i A') }}</div>
                    </div>
                </div>
                @else
                <div class="lg-msg-ai mb-2">
                    <div class="lg-ai-ico"><i class="bi bi-lightning-charge-fill"></i></div>
                    <div>
                        <div class="lg-msg-ai-body">
                            {!! \App\Support\MarkdownLite::render($msg->content) !!}
                            <div class="lg-msg-time">{{ $msg->created_at->format('g:i A') }}</div>
                        </div>
                        @if($msg->sources->isNotEmpty())
                        <div class="lg-sources">
                            <div class="lg-sources-label"><i class="bi bi-link-45deg me-1"></i>Sources</div>
                            @foreach($msg->sources as $src)
                                @can('view', $src->document)
                                <a href="{{ route('documents.show', $src->document) }}?excerpt={{ urlencode(mb_substr($src->supporting_excerpt ?? '', 0, 120)) }}" class="lg-source-card">
                                    <div class="lg-source-title">
                                        <i class="bi bi-file-earmark-text"></i>
                                        {{ $src->document->title ?? $src->document->original_filename }}
                                        @if($src->page_number) · p.{{ $src->page_number }}@endif
                                        <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:.65rem;color:#555"></i>
                                    </div>
                                    @if($src->supporting_excerpt)
                                    <div class="lg-source-excerpt">"{{ $src->supporting_excerpt }}"</div>
                                    @endif
                                </a>
                                @else
                                <div class="lg-source-card" style="cursor:default">
                                    <div class="lg-source-title" style="color:#555"><i class="bi bi-lock"></i> Document no longer accessible</div>
                                </div>
                                @endcan
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                @endforeach

            </div>
        </div>

        {{-- Composer --}}
        <div class="lg-composer-area">
            {{-- Plus popup menu --}}
            <div class="lg-plus-menu" id="lgPlusMenu">
                <button type="button" class="lg-plus-menu-item" id="lgChooseDoc">
                    <i class="bi bi-folder2-open"></i> Choose from library
                </button>
                <hr class="lg-plus-menu-sep">
                <a href="{{ route('documents.upload') }}" class="lg-plus-menu-item">
                    <i class="bi bi-cloud-upload"></i> Upload a new document
                </a>
            </div>
            <form method="POST" action="{{ route('chat.ask', $conversation) }}" id="lgAskForm">
                @csrf
                <div class="lg-composer">
                    <div class="lg-composer-inner">
                        <button type="button" class="lg-plus-btn" aria-label="Attach" title="Attach">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                        <textarea name="question" id="lgInput" rows="1" maxlength="2000" required
                                  placeholder="Ask a follow-up…"
                                  autocomplete="off" aria-label="Ask a follow-up"></textarea>
                        <button type="submit" class="lg-send-btn" id="lgSendBtn" aria-label="Send">
                            <i class="bi bi-arrow-up-short" style="font-size:1.2rem"></i>
                        </button>
                    </div>
                </div>
            </form>
            <div class="lg-footnote">Lightning AI answers strictly from your authorized documents.</div>
        </div>

    </div>{{-- /lg-thread --}}
</div>{{-- /lgThreadPage --}}

<script>
(function () {
    /* Scroll to bottom */
    var msgs = document.getElementById('lgMessages');
    if (msgs) msgs.scrollTop = msgs.scrollHeight;

    /* Auto-grow textarea */
    var input = document.getElementById('lgInput');
    if (input) {
        input.addEventListener('input', function() {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 180) + 'px';
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('lgAskForm').requestSubmit();
            }
        });
    }

    /* Loading state */
    var form = document.getElementById('lgAskForm');
    var btn  = document.getElementById('lgSendBtn');
    if (form) {
        form.addEventListener('submit', function() {
            if (!input || !input.value.trim()) return;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:13px;height:13px" role="status" aria-hidden="true"></span>';
            }
            /* Append a typing indicator */
            var wrap = document.querySelector('.lg-msg-wrap');
            if (wrap) {
                var typing = document.createElement('div');
                typing.className = 'lg-msg-ai mb-2';
                typing.id = 'lgTyping';
                typing.innerHTML = '<div class="lg-ai-ico"><i class="bi bi-lightning-charge-fill"></i></div>'
                    + '<div class="lg-msg-ai-body" style="color:#555;display:flex;align-items:center;gap:.5rem">'
                    + '<span class="spinner-border spinner-border-sm" style="width:13px;height:13px" role="status" aria-hidden="true"></span>'
                    + 'Searching your documents…</div>';
                wrap.appendChild(typing);
                if (msgs) msgs.scrollTop = msgs.scrollHeight;
            }
        });
    }
})();
</script>
@endsection
