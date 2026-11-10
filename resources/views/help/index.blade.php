@extends('layouts.app')

@section('content')

{{-- ── Hero header ─────────────────────────────────────────────────────── --}}
<div class="p3-help-hero">
    <div class="p3-help-hero-inner">
        <div class="p3-help-hero-icon" aria-hidden="true">
            <i class="bi bi-book-half"></i>
        </div>
        <div>
            <h1 class="p3-help-title">SmartDocs AI — User Guide</h1>
            <p class="p3-help-subtitle">Everything you need to upload, find, edit, and ask questions about your documents.</p>
        </div>
    </div>
</div>

{{-- ── Main grid ────────────────────────────────────────────────────────── --}}
<div class="p3-help-grid">

    {{-- Card: Upload --}}
    <section class="p3-hcard" aria-labelledby="help-upload">
        <div class="p3-hcard-icon-wrap" style="background:#e8f5ef">
            <i class="bi bi-cloud-arrow-up-fill" style="color:#00A868"></i>
        </div>
        <div class="p3-hcard-body">
            <h2 class="p3-hcard-title" id="help-upload">Upload a document</h2>
            <ol class="p3-hcard-steps">
                <li>Open <a href="{{ route('documents.upload') }}">Upload</a> and choose or drop one or more supported files.</li>
                <li>Review the suggested title, category, and date — suggestions are editable.</li>
                <li>Select <strong>Upload &amp; Queue</strong>. Processing may take time; check status in the Document Library.</li>
            </ol>
            <p class="p3-hcard-note">Only upload records you are authorised to store. The upload page shows supported file types and the current size limit.</p>
        </div>
        <a href="{{ route('documents.upload') }}" class="p3-hcard-cta">Go to Upload <i class="bi bi-arrow-right ms-1"></i></a>
    </section>

    {{-- Card: Find --}}
    <section class="p3-hcard" aria-labelledby="help-find">
        <div class="p3-hcard-icon-wrap" style="background:#e8f0fe">
            <i class="bi bi-search" style="color:#1a73e8"></i>
        </div>
        <div class="p3-hcard-body">
            <h2 class="p3-hcard-title" id="help-find">Find and review documents</h2>
            <ol class="p3-hcard-steps">
                <li>Open the <a href="{{ route('documents.library') }}">Document Library</a>.</li>
                <li>Search by title, keywords, or content; use filters to narrow results.</li>
                <li>Open a document to review details, processing status, and available actions.</li>
                <li>Use <strong>Export CSV</strong> to download metadata for matching documents.</li>
            </ol>
            <p class="p3-hcard-note">Your results are limited to documents your account is allowed to access. Contact your administrator if a document is missing.</p>
        </div>
        <a href="{{ route('documents.library') }}" class="p3-hcard-cta">Open Library <i class="bi bi-arrow-right ms-1"></i></a>
    </section>

    {{-- Card: Edit --}}
    <section class="p3-hcard" aria-labelledby="help-edit">
        <div class="p3-hcard-icon-wrap" style="background:#fef3e2">
            <i class="bi bi-pencil-square" style="color:#e37400"></i>
        </div>
        <div class="p3-hcard-body">
            <h2 class="p3-hcard-title" id="help-edit">Edit document content</h2>
            <ol class="p3-hcard-steps">
                <li>Open a document you are allowed to update, then choose <strong>Edit content</strong>.</li>
                <li>Format text with fonts, sizes, colors, bold, italic, underline, headings, and lists.</li>
                <li>Insert tables, images, links, page numbers, headers, and footers.</li>
                <li>Export to DOCX or PDF when ready.</li>
                <li>Press <kbd>Ctrl+S</kbd> or simply leave — changes save automatically.</li>
            </ol>
            <p class="p3-hcard-note">Editing changes the SmartDocs copy. The original uploaded file is preserved in version history.</p>
        </div>
        <a href="{{ route('documents.library') }}" class="p3-hcard-cta">Browse documents <i class="bi bi-arrow-right ms-1"></i></a>
    </section>

    {{-- Card: Account --}}
    <section class="p3-hcard" aria-labelledby="help-account">
        <div class="p3-hcard-icon-wrap" style="background:#fce8f3">
            <i class="bi bi-person-circle" style="color:#c2185b"></i>
        </div>
        <div class="p3-hcard-body">
            <h2 class="p3-hcard-title" id="help-account">Account and security</h2>
            <ul class="p3-hcard-steps">
                <li>Update your profile and account details in <a href="{{ route('settings.index') }}">Settings</a>.</li>
                <li>Review password and two-factor options in <a href="{{ route('settings.security') }}">Security settings</a>.</li>
                <li>Adjust appearance and language in <a href="{{ route('settings.preferences') }}">Preferences</a>.</li>
            </ul>
            <p class="p3-hcard-note">For access changes, missing features, or account help, contact your SmartDocs administrator.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="p3-hcard-cta">Open Settings <i class="bi bi-arrow-right ms-1"></i></a>
    </section>

</div>

{{-- ── Lightning AI spotlight ───────────────────────────────────────────── --}}
<div class="p3-help-ai-wrap">
    <div class="p3-help-ai-card">
        <div class="p3-help-ai-left">
            <div class="p3-help-ai-icon" aria-hidden="true">
                <i class="bi bi-stars"></i>
            </div>
            <div>
                <div class="p3-help-ai-title">Ask Lightning AI</div>
                <div class="p3-help-ai-sub">Your AI-powered assistant for all your documents</div>
            </div>
        </div>
        <div class="p3-help-ai-steps">
            <div class="p3-help-ai-step">
                <span class="p3-help-ai-num">1</span>
                <span>Open <a href="{{ route('chat.index') }}" class="p3-help-ai-link">Lightning AI</a> and ask a clear question about your authorised documents.</span>
            </div>
            <div class="p3-help-ai-step">
                <span class="p3-help-ai-num">2</span>
                <span>Optionally focus on one document or compare two documents side by side.</span>
            </div>
            <div class="p3-help-ai-step">
                <span class="p3-help-ai-num">3</span>
                <span>Review the cited source documents and passages before relying on an answer.</span>
            </div>
        </div>
        <a href="{{ route('chat.index') }}" class="p3-help-ai-btn">
            <i class="bi bi-lightning-charge-fill me-2"></i>Open Lightning AI
        </a>
    </div>
    <p class="p3-help-ai-disclaimer">Generated answers can be incomplete or mistaken. Always verify important details against the cited record.</p>
</div>

<style>
/* ── Hero ──────────────────────────────────────────────────────────── */
.p3-help-hero {
    background: linear-gradient(135deg, #00A868 0%, #00743F 100%);
    border-radius: 1.1rem;
    margin: .25rem 0 1.5rem;
    padding: 2rem 2rem;
    color: #fff;
}
.p3-help-hero-inner {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}
.p3-help-hero-icon {
    width: 60px; height: 60px;
    border-radius: 16px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
}
.p3-help-title {
    font-size: 1.45rem;
    font-weight: 800;
    margin: 0 0 .3rem;
    line-height: 1.2;
}
.p3-help-subtitle {
    margin: 0;
    font-size: .93rem;
    opacity: .88;
    line-height: 1.5;
}

/* ── Grid ──────────────────────────────────────────────────────────── */
.p3-help-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}
@media (max-width: 767px) {
    .p3-help-grid { grid-template-columns: 1fr; }
}

/* ── Cards ─────────────────────────────────────────────────────────── */
.p3-hcard {
    background: #fff;
    border: 1px solid var(--p3-card-border);
    border-radius: 1rem;
    padding: 1.4rem 1.4rem 1rem;
    display: flex;
    flex-direction: column;
    gap: .75rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    transition: box-shadow .18s ease, transform .18s ease;
}
.p3-hcard:hover {
    box-shadow: 0 6px 20px rgba(0,100,60,.10);
    transform: translateY(-2px);
}
.p3-hcard-icon-wrap {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.p3-hcard-body { flex: 1 1 auto; }
.p3-hcard-title {
    font-size: 1rem;
    font-weight: 700;
    color: #111;
    margin: 0 0 .65rem;
}
.p3-hcard-steps {
    padding-left: 1.25rem;
    margin: 0 0 .6rem;
    color: #374151;
    font-size: .88rem;
    line-height: 1.65;
}
.p3-hcard-steps li { margin-bottom: .3rem; }
.p3-hcard-steps a { color: var(--p3-navy); font-weight: 500; text-decoration: none; }
.p3-hcard-steps a:hover { text-decoration: underline; }
.p3-hcard-note {
    font-size: .78rem;
    color: #6b7280;
    margin: 0;
    padding: .6rem .75rem;
    background: #f9fafb;
    border-left: 3px solid var(--p3-card-border);
    border-radius: 0 .4rem .4rem 0;
    line-height: 1.55;
}
.p3-hcard-cta {
    display: inline-flex;
    align-items: center;
    font-size: .82rem;
    font-weight: 600;
    color: var(--p3-navy);
    text-decoration: none;
    padding: .4rem 0;
    border-top: 1px solid var(--p3-card-border);
    margin-top: auto;
    transition: gap .15s ease, color .15s ease;
    gap: .15rem;
}
.p3-hcard-cta:hover { color: var(--p3-navy-2); gap: .4rem; }

kbd {
    display: inline-block;
    padding: .1rem .4rem;
    font-size: .78em;
    color: #444;
    background: #f1f1f1;
    border: 1px solid #d0d0d0;
    border-radius: .3rem;
    font-family: monospace;
    box-shadow: 0 1px 0 #d0d0d0;
}

/* ── Lightning AI spotlight ────────────────────────────────────────── */
.p3-help-ai-wrap { margin-bottom: 1.5rem; }
.p3-help-ai-card {
    background: linear-gradient(135deg, #00A868 0%, #007a4a 100%);
    border-radius: 1rem;
    padding: 1.5rem 1.75rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    box-shadow: 0 4px 20px rgba(0,100,60,.18);
    color: #fff;
}
.p3-help-ai-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
    min-width: 190px;
}
.p3-help-ai-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.p3-help-ai-title { font-size: 1.1rem; font-weight: 800; line-height: 1.2; }
.p3-help-ai-sub { font-size: .8rem; opacity: .82; margin-top: .2rem; }
.p3-help-ai-steps {
    flex: 1 1 240px;
    display: flex;
    flex-direction: column;
    gap: .5rem;
}
.p3-help-ai-step {
    display: flex;
    align-items: flex-start;
    gap: .6rem;
    font-size: .85rem;
    line-height: 1.5;
    opacity: .92;
}
.p3-help-ai-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px; height: 20px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    font-size: .72rem;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: .15rem;
}
.p3-help-ai-link { color: #fff; font-weight: 600; text-decoration: underline; }
.p3-help-ai-btn {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    background: #fff;
    color: var(--p3-navy-2);
    font-weight: 700;
    font-size: .88rem;
    padding: .6rem 1.2rem;
    border-radius: .65rem;
    text-decoration: none;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
    transition: transform .15s ease, box-shadow .15s ease;
}
.p3-help-ai-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.18); color: var(--p3-navy-2); }
.p3-help-ai-disclaimer {
    font-size: .77rem;
    color: #9ca3af;
    margin: .6rem 0 0;
    padding: 0 .25rem;
}

/* ── Dark mode ─────────────────────────────────────────────────────── */
[data-theme="dark"] .p3-hcard {
    background: #16211c;
    border-color: #26362e;
    box-shadow: none;
}
[data-theme="dark"] .p3-hcard:hover { box-shadow: 0 6px 20px rgba(0,0,0,.35); }
[data-theme="dark"] .p3-hcard-title { color: #fff; }
[data-theme="dark"] .p3-hcard-steps { color: #c3d0c8; }
[data-theme="dark"] .p3-hcard-steps a { color: #7fe0ac; }
[data-theme="dark"] .p3-hcard-note { background: #0f1512; border-color: #2c3d34; color: #8fa59a; }
[data-theme="dark"] .p3-hcard-cta { border-color: #26362e; color: #7fe0ac; }
[data-theme="dark"] .p3-hcard-cta:hover { color: #4fd39a; }
[data-theme="dark"] kbd { background: #1d2b24; border-color: #2c3d34; color: #c3d0c8; box-shadow: 0 1px 0 #2c3d34; }
[data-theme="dark"] .p3-help-ai-disclaimer { color: #6b8c7a; }
[data-theme="dark"] .p3-help-hero { background: linear-gradient(135deg, #00743F 0%, #004d2b 100%); }
</style>

@endsection
