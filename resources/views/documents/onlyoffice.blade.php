@extends('layouts.app')

@section('body-class', 'p3-no-scroll')
@section('full-screen', true)
@section('full-screen-col', 'col-12')

@section('content')
<div style="display:flex;flex-direction:column;height:100vh;overflow:hidden;background:#f5f5f5">
    <div style="position:relative;flex:1 1 auto;min-height:0;width:100%">
        <div id="onlyofficeEditor"></div>

        <button type="button" id="chat-options" class="p3-ask-toggle" aria-controls="chat-header" aria-expanded="false">
            <i class="bi bi-lightning-charge-fill me-1"></i>Ask Lightning AI
        </button>

        <aside id="chat-header" class="p3-ask-panel" aria-hidden="true" aria-label="Ask Lightning AI">
            <div class="p3-ask-head">
                <span><i class="bi bi-lightning-charge-fill me-1"></i>Ask Lightning AI</span>
                <button type="button" id="chat-header-close" class="p3-ask-close" aria-label="Close Ask Lightning AI"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="p3AskMessages" class="p3-ask-messages" aria-live="polite">
                <div class="p3-ask-intro">Ask about this document. Answers use authorized P-ARCHIVE records.</div>
            </div>
            <div id="p3AskSources" class="p3-ask-sources"></div>
            <form id="p3AskForm" class="p3-ask-form">
                <textarea id="p3AskInput" rows="2" maxlength="2000" placeholder="Ask Lightning AI…" aria-label="Ask Lightning AI question"></textarea>
                <button type="submit" id="p3AskSend"><i class="bi bi-send-fill"></i></button>
            </form>
        </aside>
    </div>
</div>

<style>
    .p3-ask-toggle {
        position: absolute;
        top: .75rem;
        right: 1rem;
        z-index: 50;
        border: 1px solid #00A868;
        border-radius: 2rem;
        background: #fff;
        color: #00743F;
        font-size: .8rem;
        font-weight: 600;
        padding: .45rem .9rem;
        box-shadow: 0 2px 8px rgba(0,0,0,.16);
    }
    .p3-ask-panel {
        position: absolute;
        top: .75rem;
        right: 1rem;
        z-index: 60;
        display: none;
        flex-direction: column;
        width: 360px;
        max-width: calc(100vw - 2rem);
        height: min(70vh, 620px);
        border: 1px solid #d5e8db;
        border-radius: .85rem;
        background: #fff;
        box-shadow: 0 12px 32px rgba(0,0,0,.2);
        overflow: hidden;
    }
    .p3-ask-panel.open { display: flex; }
    .p3-ask-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .7rem .9rem;
        background: #00A868;
        color: #fff;
        font-size: .85rem;
        font-weight: 600;
    }
    .p3-ask-close {
        border: 0;
        background: transparent;
        color: #fff;
        font-size: .8rem;
    }
    .p3-ask-messages {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: .8rem;
        font-size: .82rem;
        line-height: 1.45;
    }
    .p3-ask-intro { color: #6b7a73; margin-bottom: .75rem; }
    .p3-ask-msg { margin-bottom: .75rem; padding: .6rem .7rem; border-radius: .65rem; }
    .p3-ask-msg.user { background: #e9f7ef; color: #174c34; }
    .p3-ask-msg.answer { background: #f6f8f7; color: #24322b; }
    .p3-ask-msg.answer p { margin: 0 0 .5rem; }
    .p3-ask-msg.answer p:last-child { margin-bottom: 0; }
    .p3-ask-sources {
        max-height: 120px;
        overflow-y: auto;
        padding: .5rem .8rem;
        border-top: 1px solid #e5e5e5;
        font-size: .7rem;
        color: #5f6f66;
    }
    .p3-ask-source { margin-bottom: .35rem; }
    .p3-ask-form {
        display: flex;
        gap: .5rem;
        padding: .7rem;
        border-top: 1px solid #e5e5e5;
        background: #fafbfa;
    }
    .p3-ask-form textarea {
        flex: 1 1 auto;
        resize: none;
        border: 1px solid #cfded4;
        border-radius: .5rem;
        padding: .45rem .55rem;
        font-size: .8rem;
    }
    .p3-ask-form button {
        flex: 0 0 auto;
        border: 0;
        border-radius: .5rem;
        background: #00A868;
        color: #fff;
        padding: 0 .8rem;
    }
    [data-theme="dark"] .p3-ask-toggle { background: #173026; color: #d6f6e3; }
    [data-theme="dark"] .p3-ask-panel { background: #14211b; border-color: #26362e; }
    [data-theme="dark"] .p3-ask-messages { color: #dce8e1; }
    [data-theme="dark"] .p3-ask-intro { color: #91a89a; }
    [data-theme="dark"] .p3-ask-msg.user { background: #1e3b2c; color: #d6f6e3; }
    [data-theme="dark"] .p3-ask-msg.answer { background: #1b2a22; color: #dce8e1; }
    [data-theme="dark"] .p3-ask-sources { border-color: #26362e; color: #9db1a5; }
    [data-theme="dark"] .p3-ask-form { background: #111c16; border-color: #26362e; }
    [data-theme="dark"] .p3-ask-form textarea { background: #0e1512; border-color: #2c3d34; color: #dce8e1; }
</style>

<script src="{{ $editorScript }}"></script>
<script>
(function () {
    var target = document.getElementById('onlyofficeEditor');
    if (typeof DocsAPI === 'undefined') {
        target.innerHTML = '<div style="padding:2rem;text-align:center;color:#b00">OnlyOffice Document Server is not reachable. Start it with <code>docker compose -f docker-compose.onlyoffice.yml up -d</code>.</div>';
        return;
    }
    var config = @json($config);
    config.token = @json($configToken);
    new DocsAPI.DocEditor('onlyofficeEditor', config);
})();

(function () {
    var toggle = document.getElementById('chat-options');
    var panel = document.getElementById('chat-header');
    var close = document.getElementById('chat-header-close');
    var form = document.getElementById('p3AskForm');
    var input = document.getElementById('p3AskInput');
    var send = document.getElementById('p3AskSend');
    var messages = document.getElementById('p3AskMessages');
    var sources = document.getElementById('p3AskSources');
    var endpoint = @json(route('documents.ask', $document), JSON_UNESCAPED_SLASHES);
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function setOpen(open) {
        panel.classList.toggle('open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) input.focus();
    }

    toggle.addEventListener('click', function () { setOpen(!panel.classList.contains('open')); });
    close.addEventListener('click', function () { setOpen(false); });

    function addMessage(className, text, html) {
        var row = document.createElement('div');
        row.className = 'p3-ask-msg ' + className;
        if (html) row.innerHTML = html; else row.textContent = text;
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
    }

    function renderSources(items) {
        sources.innerHTML = '';
        (items || []).forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'p3-ask-source';
            row.textContent = (item.title || 'Document') + (item.page ? ' · p.' + item.page : '') + ' — ' + (item.excerpt || '');
            sources.appendChild(row);
        });
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        var question = input.value.trim();
        if (!question) return;

        addMessage('user', question);
        input.value = '';
        send.disabled = true;
        var loading = document.createElement('div');
        loading.className = 'p3-ask-msg answer';
        loading.textContent = 'Lightning AI is reading…';
        messages.appendChild(loading);
        messages.scrollTop = messages.scrollHeight;

        try {
            var response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ question: question })
            });
            var data = await response.json();
            loading.remove();
            if (!response.ok) {
                addMessage('answer', data.message || 'Lightning AI could not answer that.');
                return;
            }
            addMessage('answer', '', data.answer || 'No answer returned.');
            renderSources(data.sources);
        } catch (error) {
            loading.remove();
            addMessage('answer', 'Lightning AI is unavailable right now.');
        } finally {
            send.disabled = false;
            input.focus();
        }
    });
})();
</script>
@endsection
