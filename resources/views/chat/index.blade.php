@extends('layouts.chat')
@section('content')
<style>
    /* ── Shell ── */
    #lgPage { display: flex; height: 100vh; overflow: hidden; background: #f7f7f7; color: #111; }

    /* ── Sidebar ── */
    .lg-sidebar { width: 260px; flex: 0 0 260px; background: #f0f0f0; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; padding: 0; overflow: hidden; }
    .lg-sidebar-top { padding: .85rem .75rem .5rem; }
    .lg-brand { display: flex; align-items: center; gap: .6rem; padding: .35rem .6rem .7rem; }
    .lg-brand-ico { width: 28px; height: 28px; border-radius: 8px; background: #e6f3ea; display: flex; align-items: center; justify-content: center; color: #00A868; font-size: .95rem; }
    .lg-brand-name { font-weight: 700; font-size: .95rem; color: #111; }
    .lg-new-btn { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .55rem .75rem; border-radius: .65rem; border: none; background: #fff; color: #111; font-size: .85rem; font-weight: 600; cursor: pointer; transition: background .15s ease; text-decoration: none; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .lg-new-btn:hover { background: #e8e8e8; color: #111; }
    .lg-new-btn i { font-size: 1rem; }
    .lg-sidebar-section { font-size: .68rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: .55px; padding: .8rem .85rem .3rem; }
    .lg-sidebar-body { flex: 1 1 auto; overflow-y: auto; padding: 0 .5rem .75rem; }
    .lg-conv-item { display: flex; align-items: center; gap: .4rem; padding: .48rem .65rem; border-radius: .55rem; text-decoration: none; color: #333; font-size: .83rem; transition: background .12s ease; white-space: nowrap; overflow: hidden; }
    .lg-conv-item:hover { background: #e8e8e8; color: #111; }
    .lg-conv-item span { overflow: hidden; text-overflow: ellipsis; flex: 1 1 auto; }
    .lg-sidebar-foot { padding: .75rem; border-top: 1px solid #e0e0e0; }

    /* ── Main area ── */
    .lg-main { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; background: #f7f7f7; }
    .lg-main form { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }

    /* ── Landing center ── */
    .lg-center { flex: 1 1 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1.5rem; gap: 1.5rem; }
    .lg-welcome { font-size: 1.85rem; font-weight: 700; color: #111; text-align: center; }

    /* ── Input bar ── */
    .lg-composer-wrap { display: flex; flex-direction: column; align-items: center; gap: .75rem; width: 100%; max-width: 720px; }
    .lg-composer-wrap-inner { position: relative; width: 100%; max-width: 680px; }
    .lg-composer { width: 100%; background: #fff; border-radius: 1.1rem; border: 1px solid #ddd; overflow: hidden; transition: border-color .15s ease; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .lg-composer:focus-within { border-color: #00A868; box-shadow: 0 2px 12px rgba(0,168,104,.12); }
    .lg-composer-top { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem .75rem .9rem; }
    .lg-composer input { flex: 1 1 auto; background: transparent; border: none; outline: none; color: #111; font-size: .9rem; min-width: 0; }
    .lg-composer input::placeholder { color: #aaa; }
    .lg-composer-actions { display: flex; align-items: center; gap: .4rem; padding: .45rem .7rem .55rem; border-top: 1px solid #eee; }
    .lg-doc-select { flex: 1 1 auto; background: #f7f7f7; border: 1px solid #ddd; border-radius: .5rem; color: #555; font-size: .75rem; padding: .28rem .55rem; }
    .lg-doc-select option { background: #fff; }
    .lg-plus-btn { width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 1px solid #ddd; color: #888; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s ease; flex: 0 0 28px; font-size: .85rem; }
    .lg-plus-btn:hover { background: #f0f0f0; border-color: #aaa; color: #333; }
    .lg-send-btn { width: 34px; height: 34px; border-radius: 50%; background: #00A868; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s ease; flex: 0 0 34px; color: #fff; font-size: .9rem; }
    .lg-send-btn:hover { background: #00743F; }
    .lg-send-btn:disabled { background: #ddd; color: #aaa; cursor: not-allowed; }

    /* ── Plus popup menu ── */
    .lg-plus-menu { position: absolute; bottom: calc(100% + .5rem); left: 0; background: #fff; border: 1px solid #e0e0e0; border-radius: .85rem; padding: .4rem; min-width: 200px; box-shadow: 0 8px 24px rgba(0,0,0,.12); z-index: 100; display: none; flex-direction: column; gap: .15rem; }
    .lg-plus-menu.open { display: flex; }
    .lg-plus-menu-item { display: flex; align-items: center; gap: .65rem; padding: .55rem .75rem; border-radius: .6rem; font-size: .83rem; color: #333; cursor: pointer; border: none; background: transparent; width: 100%; text-align: left; transition: background .12s ease; }
    .lg-plus-menu-item:hover { background: #f0f0f0; color: #111; }
    .lg-plus-menu-item i { font-size: 1rem; color: #00A868; width: 18px; text-align: center; flex: 0 0 18px; }
    .lg-plus-menu-sep { border: 0; border-top: 1px solid #eee; margin: .25rem 0; }

    /* ── Suggestion chips ── */
    .lg-chips { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; max-width: 680px; width: 100%; }
    .lg-chip { background: #fff; border: 1px solid #ddd; border-radius: 2rem; color: #333; font-size: .75rem; padding: .35rem .85rem; cursor: pointer; transition: all .15s ease; display: flex; align-items: center; gap: .4rem; white-space: nowrap; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
    .lg-chip:hover { background: #f0f0f0; border-color: #aaa; color: #111; }
    .lg-chip i { color: #00A868; font-size: .8rem; }

    /* ── Footer note ── */
    .lg-footnote { font-size: .72rem; color: #aaa; text-align: center; padding-bottom: .5rem; }

    /* ── Dark mode ── */
    [data-theme="dark"] #lgPage { background: #212121; color: #ececec; }
    [data-theme="dark"] .lg-main { background: #212121; }
    [data-theme="dark"] .lg-sidebar { background: #171717; border-color: #2a2a2a; }
    [data-theme="dark"] .lg-brand-ico { background: #2a2a2a; }
    [data-theme="dark"] .lg-brand-name { color: #ececec; }
    [data-theme="dark"] .lg-new-btn { background: #2a2a2a; color: #ececec; box-shadow: none; }
    [data-theme="dark"] .lg-new-btn:hover { background: #333; color: #fff; }
    [data-theme="dark"] .lg-sidebar-section { color: #555; }
    [data-theme="dark"] .lg-conv-item { color: #c0c0c0; }
    [data-theme="dark"] .lg-conv-item:hover { background: #2a2a2a; color: #fff; }
    [data-theme="dark"] .lg-sidebar-foot { border-color: #2a2a2a; }
    [data-theme="dark"] .lg-welcome { color: #ececec; }
    [data-theme="dark"] .lg-composer { background: #2f2f2f; border-color: #3a3a3a; box-shadow: none; }
    [data-theme="dark"] .lg-composer:focus-within { border-color: #00A868; box-shadow: none; }
    [data-theme="dark"] .lg-composer input { color: #ececec; }
    [data-theme="dark"] .lg-composer input::placeholder { color: #555; }
    [data-theme="dark"] .lg-composer-actions { border-color: #3a3a3a; }
    [data-theme="dark"] .lg-doc-select { background: #2a2a2a; border-color: #3a3a3a; color: #aaa; }
    [data-theme="dark"] .lg-doc-select option { background: #2f2f2f; }
    [data-theme="dark"] .lg-plus-btn { border-color: #4a4a4a; color: #888; }
    [data-theme="dark"] .lg-plus-btn:hover { background: #3a3a3a; border-color: #666; color: #ececec; }
    [data-theme="dark"] .lg-plus-menu { background: #2a2a2a; border-color: #3a3a3a; box-shadow: 0 8px 24px rgba(0,0,0,.5); }
    [data-theme="dark"] .lg-plus-menu-item { color: #c0c0c0; }
    [data-theme="dark"] .lg-plus-menu-item:hover { background: #383838; color: #fff; }
    [data-theme="dark"] .lg-plus-menu-sep { border-color: #3a3a3a; }
    [data-theme="dark"] .lg-chip { background: #2f2f2f; border-color: #3a3a3a; color: #aaa; box-shadow: none; }
    [data-theme="dark"] .lg-chip:hover { background: #3a3a3a; border-color: #555; color: #fff; }
    [data-theme="dark"] .lg-send-btn:disabled { background: #333; color: #555; }
    [data-theme="dark"] .lg-footnote { color: #555; }
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
            <a href="{{ route('chat.index', ['page' => $conversations->currentPage() + 1]) }}" class="lg-conv-item" style="color:#5f6f66;font-size:.75rem">
                <i class="bi bi-chevron-down" style="font-size:.7rem"></i> <span>Show more</span>
            </a>
            @endif
            @endif
        </div>

        <div class="lg-sidebar-foot">
            <a href="{{ route('dashboard') }}" class="lg-new-btn" style="color:#5f6f66;font-size:.83rem">
                <i class="bi bi-arrow-left"></i> Back to SmartDocs
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
                <div style="background:#ffffff;border:1px solid #d5e8db;border-radius:.75rem;padding:.55rem 1rem;font-size:.8rem;color:#5f6f66;max-width:680px;width:100%">
                    <i class="bi bi-file-earmark-lock me-1" style="color:#00A868"></i>
                    Asking about: <strong style="color:#173528">{{ $document->title ?? $document->original_filename }}</strong>
                    <a href="{{ route('documents.show', $document) }}" class="ms-2" style="color:#00A868;font-size:.75rem">View</a>
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



