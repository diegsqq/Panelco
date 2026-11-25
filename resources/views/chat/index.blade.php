@extends('layouts.chat')

@section('content')
<style>
    /* ── Theme tokens: light default, dark override ── */
    #lgPage {
        --lg-bg: #f4faf5;
        --lg-side: #f0f9f3;
        --lg-surface: #ffffff;
        --lg-soft: #e6f3ea;
        --lg-border: #d5e8db;
        --lg-text: #173528;
        --lg-muted: #5f6f66;
        --lg-faint: #8aaa95;
        --lg-shadow: 0 4px 16px rgba(0,100,60,.08);
        --lg-menu-shadow: 0 8px 24px rgba(0,100,60,.12);
    }
    html[data-theme="dark"] #lgPage {
        --lg-bg: #212121;
        --lg-side: #171717;
        --lg-surface: #2f2f2f;
        --lg-soft: #2a2a2a;
        --lg-border: #3a3a3a;
        --lg-text: #ececec;
        --lg-muted: #aaa;
        --lg-faint: #555;
        --lg-shadow: none;
        --lg-menu-shadow: 0 8px 24px rgba(0,0,0,.5);
    }

    /* ── Shell ── */
    #lgPage { display: flex; height: 100vh; overflow: hidden; background: var(--lg-bg); color: var(--lg-text); }

    /* ── Sidebar ── */
    .lg-sidebar { width: 260px; flex: 0 0 260px; background: var(--lg-side); border-right: 1px solid var(--lg-border); display: flex; flex-direction: column; padding: 0; overflow: hidden; }
    .lg-sidebar-top { padding: .85rem .75rem .5rem; }
    .lg-brand { display: flex; align-items: center; gap: .6rem; padding: .35rem .6rem .7rem; }
    .lg-brand-ico { width: 28px; height: 28px; border-radius: 8px; background: var(--lg-soft); display: flex; align-items: center; justify-content: center; color: #00A868; font-size: .95rem; }
    .lg-brand-name { font-weight: 700; font-size: .95rem; color: var(--lg-text); }
    .lg-new-btn { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .55rem .75rem; border-radius: .65rem; border: none; background: var(--lg-surface); color: var(--lg-text); font-size: .85rem; font-weight: 600; cursor: pointer; transition: background .15s ease; text-decoration: none; box-shadow: var(--lg-shadow); }
    .lg-new-btn:hover { background: var(--lg-soft); color: #00743F; }
    .lg-new-btn i { font-size: 1rem; }
    .lg-back-btn { color: var(--lg-muted); font-weight: 500; }
    .lg-sidebar-section { font-size: .68rem; font-weight: 700; color: var(--lg-faint); text-transform: uppercase; letter-spacing: .55px; padding: .8rem .85rem .3rem; }
    .lg-sidebar-body { flex: 1 1 auto; overflow-y: auto; padding: 0 .5rem .75rem; }
    .lg-conv-item { display: flex; align-items: center; gap: .4rem; padding: .48rem .65rem; border-radius: .55rem; text-decoration: none; color: var(--lg-text); font-size: .83rem; transition: background .12s ease; white-space: nowrap; overflow: hidden; }
    .lg-conv-item:hover { background: var(--lg-soft); color: #00743F; }
    .lg-conv-item span { overflow: hidden; text-overflow: ellipsis; flex: 1 1 auto; }
    .lg-show-more { color: var(--lg-muted); font-size: .75rem; }
    .lg-sidebar-foot { padding: .75rem; border-top: 1px solid var(--lg-border); }
    .lg-user-row { display: flex; align-items: center; gap: .6rem; padding: .45rem .6rem; border-radius: .65rem; transition: background .12s ease; cursor: pointer; }
    .lg-user-row:hover { background: var(--lg-surface); }
    .lg-user-avatar { width: 28px; height: 28px; border-radius: 50%; background: #00A868; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .72rem; font-weight: 700; flex: 0 0 28px; overflow: hidden; }
    .lg-user-name { font-size: .82rem; color: var(--lg-text); font-weight: 600; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lg-user-sub  { font-size: .68rem; color: var(--lg-muted); }

    /* ── Main area ── */
    .lg-main { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; background: var(--lg-bg); }
    .lg-main form { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }

    /* ── Landing center ── */
    .lg-center { flex: 1 1 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1.5rem; gap: 1.5rem; }
    .lg-welcome { font-size: 1.85rem; font-weight: 700; color: var(--lg-text); text-align: center; }
    .lg-doc-banner { background: var(--lg-soft); border: 1px solid var(--lg-border); border-radius: .75rem; padding: .55rem 1rem; font-size: .8rem; color: var(--lg-muted); max-width: 680px; width: 100%; }
    .lg-doc-banner strong { color: var(--lg-text); }
    .lg-doc-banner a { color: #00A868; font-size: .75rem; }

    /* ── Input bar ── */
    .lg-composer-wrap { display: flex; flex-direction: column; align-items: center; gap: .75rem; width: 100%; max-width: 720px; }
    .lg-composer { width: 100%; max-width: 680px; background: var(--lg-surface); border-radius: 1.1rem; border: 1px solid var(--lg-border); overflow: hidden; transition: border-color .15s ease; box-shadow: var(--lg-shadow); }
    .lg-composer:focus-within { border-color: #00A868; }
    .lg-composer-top { display: flex; align-items: center; gap: .5rem; padding: .7rem 1rem .7rem .85rem; }
    .lg-plus-btn { width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 1px solid var(--lg-border); color: var(--lg-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s ease; flex: 0 0 28px; font-size: .85rem; }
    .lg-plus-btn:hover { background: var(--lg-soft); border-color: #00A868; color: #00743F; }
    /* ── Plus popup menu ── */
    .lg-plus-menu { position: absolute; bottom: calc(100% + .5rem); left: 0; background: var(--lg-surface); border: 1px solid var(--lg-border); border-radius: .85rem; padding: .4rem; min-width: 200px; box-shadow: var(--lg-menu-shadow); z-index: 100; display: none; flex-direction: column; gap: .15rem; }
    .lg-plus-menu.open { display: flex; }
    .lg-plus-menu-item { display: flex; align-items: center; gap: .65rem; padding: .55rem .75rem; border-radius: .6rem; font-size: .83rem; color: var(--lg-text); cursor: pointer; border: none; background: transparent; width: 100%; text-align: left; transition: background .12s ease, color .12s ease; text-decoration: none; }
    .lg-plus-menu-item:hover { background: var(--lg-soft); color: #00743F; }
    .lg-plus-menu-item i { font-size: 1rem; color: #00A868; width: 18px; text-align: center; flex: 0 0 18px; }
    .lg-plus-menu-sep { border: 0; border-top: 1px solid var(--lg-border); margin: .25rem 0; }
    .lg-composer-wrap-inner { position: relative; width: 100%; max-width: 680px; }
    .lg-composer input { flex: 1 1 auto; background: transparent; border: none; outline: none; color: var(--lg-text); font-size: .9rem; resize: none; min-width: 0; }
    .lg-composer input::placeholder { color: var(--lg-muted); }
    .lg-composer-actions { display: flex; align-items: center; gap: .4rem; padding: .45rem .7rem .55rem; border-top: 1px solid var(--lg-border); }
    .lg-doc-select { flex: 1 1 auto; background: transparent; border: 1px solid var(--lg-border); border-radius: .5rem; color: var(--lg-muted); font-size: .75rem; padding: .28rem .55rem; }
    .lg-doc-select option { background: var(--lg-surface); color: var(--lg-text); }
    .lg-send-btn { width: 34px; height: 34px; border-radius: 50%; background: #00A868; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s ease; flex: 0 0 34px; color: #fff; font-size: .9rem; }
    .lg-send-btn:hover { background: #00743F; }
    .lg-send-btn:disabled { background: var(--lg-border); color: var(--lg-muted); cursor: not-allowed; }

    /* ── Suggestion chips ── */
    .lg-chips { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; max-width: 680px; width: 100%; }
    .lg-chip { background: var(--lg-surface); border: 1px solid var(--lg-border); border-radius: 2rem; color: var(--lg-muted); font-size: .75rem; padding: .35rem .85rem; cursor: pointer; transition: all .15s ease; display: flex; align-items: center; gap: .4rem; white-space: nowrap; }
    .lg-chip:hover { background: var(--lg-soft); border-color: #00A868; color: #00743F; }
    .lg-chip i { color: #00A868; font-size: .8rem; }

    /* ── Footer note ── */
    .lg-footnote { font-size: .72rem; color: var(--lg-faint); text-align: center; padding-bottom: .5rem; }
</style>

<div id="lgPage">

    {{-- ── SIDEBAR ── --}}
    <aside class="lg-sidebar">
        <div class="lg-sidebar-top">
            <div class="lg-brand">
                <div class="lg-brand-ico"><i class="bi bi-lightning-charge-fill"></i></div>
                <span class="lg-brand-name">Ask Lightning AI</span>
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
            <a href="{{ route('chat.index', ['page' => $conversations->currentPage() + 1]) }}" class="lg-conv-item lg-show-more">
                <i class="bi bi-chevron-down" style="font-size:.7rem"></i> <span>Show more</span>
            </a>
            @endif
            @endif
        </div>

        <div class="lg-sidebar-foot">
            <a href="{{ route('dashboard') }}" class="lg-new-btn lg-back-btn" style="font-size:.83rem">
                <i class="bi bi-arrow-left"></i> Back to P-ARCHIVE
            </a>
        </div>
    </aside>

    {{-- ── MAIN ── --}}
    <main class="lg-main">
        <form method="POST" action="{{ route('chat.store') }}" id="lgForm">
            @csrf
            @if($document)<input type="hidden" name="document_id" value="{{ $document->id }}">@endif

            {{-- Center content + composer as one centered block --}}
            <div class="lg-center">
                @if($document)
                <div class="lg-doc-banner">
                    <i class="bi bi-file-earmark-lock me-1"></i>
                    Asking about: <strong>{{ $document->display_title }}</strong>
                    <a href="{{ route('documents.show', $document) }}" class="ms-2">View</a>
                </div>
                @endif
                <div class="lg-welcome">Where should we begin?</div>

                {{-- Composer --}}
                <div class="lg-composer-wrap">
                    <div class="lg-composer-wrap-inner">
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
                        <div class="lg-composer" style="width:100%">
                        <div class="lg-composer-top">
                            <button type="button" class="lg-plus-btn" aria-label="Attach" title="Attach">
                                <i class="bi bi-plus-lg"></i>
                            </button>
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
                                <option value="{{ $item->id }}" @selected((string)old('document_id') === (string)$item->id)>{{ \Illuminate\Support\Str::limit($item->display_title, 55) }}</option>
                                @endforeach
                            </select>
                            <select name="comparison_document_id" class="lg-doc-select" aria-label="Compare with (optional)">
                                <option value="">No comparison</option>
                                @foreach($availableDocuments as $item)
                                <option value="{{ $item->id }}" @selected((string)old('comparison_document_id') === (string)$item->id)>{{ \Illuminate\Support\Str::limit($item->display_title, 55) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endunless
                        </div>{{-- /lg-composer --}}
                    </div>{{-- /lg-composer-wrap-inner --}}

                    {{-- Suggestion chips --}}
                    <div class="lg-chips">
                        @foreach([
                            ['ico'=>'bi-file-text',      'txt'=>'Summarize the latest report'],
                            ['ico'=>'bi-search',          'txt'=>'Find transformer maintenance docs'],
                            ['ico'=>'bi-list-check',      'txt'=>'Extract action items and deadlines'],
                            ['ico'=>'bi-arrow-left-right','txt'=>'Compare two documents'],
                            ['ico'=>'bi-translate',       'txt'=>'Translate report to Filipino'],
                        ] as $chip)
                        <button type="button" class="lg-chip" data-q="{{ $chip['txt'] }}">
                            <i class="bi {{ $chip['ico'] }}"></i>{{ $chip['txt'] }}
                        </button>
                        @endforeach
                    </div>

                    <div class="lg-footnote">Lightning AI answers strictly from your authorized documents with cited sources.</div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
(function () {
    var form    = document.getElementById('lgForm');
    var input   = document.getElementById('lgQuestion');
    var sendBtn = document.getElementById('lgSendBtn');
    var plusBtn = document.getElementById('lgPlusBtn') || document.querySelector('.lg-plus-btn');
    var plusMenu = document.getElementById('lgPlusMenu');

    /* ── Plus menu toggle ── */
    if (plusBtn && plusMenu) {
        plusBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            plusMenu.classList.toggle('open');
        });
        document.addEventListener('click', function() {
            plusMenu.classList.remove('open');
        });
        plusMenu.addEventListener('click', function(e) { e.stopPropagation(); });
    }

    /* ── Choose from library: fills the document select ── */
    var chooseDoc = document.getElementById('lgChooseDoc');
    var docSelect = document.getElementById('document_id');
    if (chooseDoc && docSelect) {
        chooseDoc.addEventListener('click', function() {
            plusMenu.classList.remove('open');
            docSelect.closest('.lg-composer-actions').style.display = 'flex';
            docSelect.focus();
        });
    }

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
