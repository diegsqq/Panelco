@extends('layouts.chat')
@section('content')
<style>
    /* ── Shell ── */
    #lgPage { display: flex; height: 100vh; overflow: hidden; background: #212121; color: #ececec; }

    /* ── Sidebar ── */
    .lg-sidebar { width: 260px; flex: 0 0 260px; background: #171717; display: flex; flex-direction: column; padding: 0; overflow: hidden; }
    .lg-sidebar-top { padding: .85rem .75rem .5rem; }
    .lg-brand { display: flex; align-items: center; gap: .6rem; padding: .35rem .6rem .7rem; }
    .lg-brand-ico { width: 28px; height: 28px; border-radius: 8px; background: #2a2a2a; display: flex; align-items: center; justify-content: center; color: #00A868; font-size: .95rem; }
    .lg-brand-name { font-weight: 700; font-size: .95rem; color: #ececec; }
    .lg-new-btn { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .55rem .75rem; border-radius: .65rem; border: none; background: #2a2a2a; color: #ececec; font-size: .85rem; font-weight: 600; cursor: pointer; transition: background .15s ease; text-decoration: none; }
    .lg-new-btn:hover { background: #333; color: #fff; }
    .lg-new-btn i { font-size: 1rem; }
    .lg-sidebar-section { font-size: .68rem; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .55px; padding: .8rem .85rem .3rem; }
    .lg-sidebar-body { flex: 1 1 auto; overflow-y: auto; padding: 0 .5rem .75rem; }
    .lg-conv-item { display: flex; align-items: center; gap: .4rem; padding: .48rem .65rem; border-radius: .55rem; text-decoration: none; color: #c0c0c0; font-size: .83rem; transition: background .12s ease; white-space: nowrap; overflow: hidden; }
    .lg-conv-item:hover { background: #2a2a2a; color: #fff; }
    .lg-conv-item span { overflow: hidden; text-overflow: ellipsis; flex: 1 1 auto; }
    .lg-sidebar-foot { padding: .75rem; border-top: 1px solid #2a2a2a; }
    .lg-user-row { display: flex; align-items: center; gap: .6rem; padding: .45rem .6rem; border-radius: .65rem; transition: background .12s ease; cursor: pointer; }
    .lg-user-row:hover { background: #2a2a2a; }
    .lg-user-avatar { width: 28px; height: 28px; border-radius: 50%; background: #00A868; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .72rem; font-weight: 700; flex: 0 0 28px; overflow: hidden; }
    .lg-user-name { font-size: .82rem; color: #ececec; font-weight: 600; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lg-user-sub  { font-size: .68rem; color: #555; }

    /* ── Main area ── */
    .lg-main { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }

    /* ── Landing center ── */
    .lg-center { flex: 1 1 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem 1rem; gap: 1.5rem; }
    .lg-welcome { font-size: 1.85rem; font-weight: 700; color: #ececec; text-align: center; }

    /* ── Input bar ── */
    .lg-composer-wrap { flex: 0 0 auto; padding: .75rem 1.5rem 1.25rem; display: flex; flex-direction: column; align-items: center; gap: .85rem; width: 100%; }
    .lg-composer { width: 100%; max-width: 680px; background: #2f2f2f; border-radius: 1.1rem; border: 1px solid #3a3a3a; overflow: hidden; transition: border-color .15s ease; }
    .lg-composer:focus-within { border-color: #00A868; }
    .lg-composer-top { display: flex; align-items: flex-end; gap: .5rem; padding: .7rem 1rem .7rem .85rem; }
    .lg-composer input { flex: 1 1 auto; background: transparent; border: none; outline: none; color: #ececec; font-size: .9rem; resize: none; min-width: 0; }
    .lg-composer input::placeholder { color: #666; }
    .lg-composer-actions { display: flex; align-items: center; gap: .4rem; padding: .45rem .7rem .55rem; border-top: 1px solid #3a3a3a; }
    .lg-doc-select { flex: 1 1 auto; background: transparent; border: 1px solid #3a3a3a; border-radius: .5rem; color: #aaa; font-size: .75rem; padding: .28rem .55rem; }
    .lg-doc-select option { background: #2f2f2f; }
    .lg-send-btn { width: 34px; height: 34px; border-radius: 50%; background: #00A868; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s ease; flex: 0 0 34px; color: #fff; font-size: .9rem; }
    .lg-send-btn:hover { background: #00743F; }
    .lg-send-btn:disabled { background: #333; color: #555; cursor: not-allowed; }

    /* ── Suggestion chips ── */
    .lg-chips { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; max-width: 680px; width: 100%; }
    .lg-chip { background: #2f2f2f; border: 1px solid #3a3a3a; border-radius: 2rem; color: #aaa; font-size: .75rem; padding: .35rem .85rem; cursor: pointer; transition: all .15s ease; display: flex; align-items: center; gap: .4rem; white-space: nowrap; }
    .lg-chip:hover { background: #3a3a3a; border-color: #00A868; color: #fff; }
    .lg-chip i { color: #00A868; font-size: .8rem; }

    /* ── Footer note ── */
    .lg-footnote { font-size: .72rem; color: #555; text-align: center; padding-bottom: .5rem; }
</style>

<div id="lgPage">

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
            @if($conversations->count())
            <div class="lg-sidebar-section">Recents</div>
            @foreach($conversations as $c)
            <a href="{{ route('chat.show', $c) }}" class="lg-conv-item">
                <span>{{ \Illuminate\Support\Str::limit($c->title, 36, '…') }}</span>
            </a>
            @endforeach
            @if($conversations->hasMorePages())
            <a href="{{ route('chat.index', ['page' => $conversations->currentPage() + 1]) }}" class="lg-conv-item" style="color:#555;font-size:.75rem">
                <i class="bi bi-chevron-down" style="font-size:.7rem"></i> <span>Show more</span>
            </a>
            @endif
            @endif
        </div>

        <div class="lg-sidebar-foot">
            <a href="{{ route('dashboard') }}" class="lg-user-row text-decoration-none">
                <div class="lg-user-avatar">
                    @if(auth()->user()->avatar_path)
                        <img src="{{ route('profile.avatar') }}?v={{ auth()->user()->updated_at?->timestamp }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
                    @else
                        {{ auth()->user()->initials() }}
                    @endif
                </div>
                <div>
                    <div class="lg-user-name">{{ auth()->user()->name }}</div>
                    <div class="lg-user-sub">{{ ucfirst(auth()->user()->role ?? 'Employee') }}</div>
                </div>
            </a>
        </div>
    </aside>

    {{-- ── MAIN ── --}}
    <main class="lg-main" style="background:#212121">
        <form method="POST" action="{{ route('chat.store') }}" id="lgForm">
            @csrf
            @if($document)<input type="hidden" name="document_id" value="{{ $document->id }}">@endif

            {{-- Center content --}}
            <div class="lg-center">
                @if($document)
                <div style="background:#2a2a2a;border:1px solid #3a3a3a;border-radius:.75rem;padding:.55rem 1rem;font-size:.8rem;color:#aaa;max-width:680px;width:100%">
                    <i class="bi bi-file-earmark-lock me-1" style="color:#00A868"></i>
                    Asking about: <strong style="color:#ececec">{{ $document->title ?? $document->original_filename }}</strong>
                    <a href="{{ route('documents.show', $document) }}" class="ms-2" style="color:#00A868;font-size:.75rem">View</a>
                </div>
                @endif
                <div class="lg-welcome">Where should we begin?</div>
            </div>

            {{-- Composer --}}
            <div class="lg-composer-wrap">
                <div class="lg-composer" style="max-width:680px;width:100%">
                    <div class="lg-composer-top">
                        <input name="question" id="lgQuestion" maxlength="2000" required
                               placeholder="Ask Lightning AI about your documents…"
                               value="{{ request('q') }}" autocomplete="off"
                               aria-label="Ask Lightning AI">
                        <button type="submit" class="lg-send-btn" id="lgSendBtn" aria-label="Send">
                            <i class="bi bi-arrow-up-short" style="font-size:1.2rem"></i>
                        </button>
                    </div>
                    @unless($document)
                    <div class="lg-composer-actions">
                        <select name="document_id" class="lg-doc-select" aria-label="Focus on a document (optional)">
                            <option value="">All accessible documents</option>
                            @foreach($availableDocuments as $item)
                            <option value="{{ $item->id }}" @selected((string)old('document_id') === (string)$item->id)>{{ \Illuminate\Support\Str::limit($item->title ?? $item->original_filename, 55) }}</option>
                            @endforeach
                        </select>
                        <select name="comparison_document_id" class="lg-doc-select" aria-label="Compare with (optional)">
                            <option value="">No comparison</option>
                            @foreach($availableDocuments as $item)
                            <option value="{{ $item->id }}" @selected((string)old('comparison_document_id') === (string)$item->id)>{{ \Illuminate\Support\Str::limit($item->title ?? $item->original_filename, 55) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endunless
                </div>

                {{-- Suggestion chips --}}
                <div class="lg-chips">
                    @foreach([
                        ['ico'=>'bi-file-text',    'txt'=>'Summarize the latest report'],
                        ['ico'=>'bi-search',        'txt'=>'Find transformer maintenance docs'],
                        ['ico'=>'bi-list-check',    'txt'=>'Extract action items and deadlines'],
                        ['ico'=>'bi-arrow-left-right','txt'=>'Compare two documents'],
                        ['ico'=>'bi-translate',     'txt'=>'Translate report to Filipino'],
                    ] as $chip)
                    <button type="button" class="lg-chip" data-q="{{ $chip['txt'] }}">
                        <i class="bi {{ $chip['ico'] }}"></i>{{ $chip['txt'] }}
                    </button>
                    @endforeach
                </div>

                <div class="lg-footnote">Lightning AI answers strictly from your authorized documents with cited sources.</div>
            </div>
        </form>
    </main>
</div>

<script>
(function () {
    var form    = document.getElementById('lgForm');
    var input   = document.getElementById('lgQuestion');
    var sendBtn = document.getElementById('lgSendBtn');

    /* Chips fill the input and submit */
    document.querySelectorAll('.lg-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            input.value = chip.dataset.q;
            input.focus();
        });
    });

    /* Enter submits */
    if (input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
        });
    }

    /* Loading state */
    if (form) {
        form.addEventListener('submit', function() {
            if (sendBtn) {
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:14px;height:14px" role="status" aria-hidden="true"></span>';
            }
        });
    }
})();
</script>
@endsection
