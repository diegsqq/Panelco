@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <header class="mb-4">
        <h1 class="h3 fw-bold mb-2">SmartDocs user guide</h1>
        <p class="text-muted mb-0">Quick steps for uploading, finding, and asking questions about documents you are authorized to access.</p>
    </header>

    <div class="row g-3">
        <section class="col-12 col-lg-6" aria-labelledby="help-upload">
            <div class="card card-p3 p-4 h-100">
                <h2 class="h5 fw-semibold" id="help-upload">Upload a document</h2>
                <ol class="mb-3">
                    <li>Open <a href="{{ route('documents.upload') }}">Upload</a> and choose or drop one or more supported files.</li>
                    <li>Review the suggested title, category, and date. Suggestions are editable; confirm details before uploading.</li>
                    <li>Select <strong>Upload &amp; Queue</strong>. Processing may take time; check the status in the document library.</li>
                </ol>
                <p class="small text-muted mb-0">Only upload records you are authorized to store. The upload page shows supported file types and the current size limit.</p>
            </div>
        </section>

        <section class="col-12 col-lg-6" aria-labelledby="help-find">
            <div class="card card-p3 p-4 h-100">
                <h2 class="h5 fw-semibold" id="help-find">Find and review documents</h2>
                <ol class="mb-3">
                    <li>Open the <a href="{{ route('documents.library') }}">document library</a>.</li>
                    <li>Search by title, keywords, or content; use the available filters to narrow results.</li>
                    <li>Open a document to review its details, processing status, and available actions.</li>
                    <li>Use <strong>Export CSV</strong> to download metadata for matching documents you can access.</li>
                </ol>
                <p class="small text-muted mb-0">Exports include document metadata, not document contents. Your results are limited to documents your account is allowed to access. If a document is missing or processing has failed, contact your administrator.</p>
            </div>
        </section>

        <section class="col-12 col-lg-6" aria-labelledby="help-edit">
            <div class="card card-p3 p-4 h-100">
                <h2 class="h5 fw-semibold" id="help-edit">Edit document content</h2>
                <ol class="mb-3">
                    <li>Open a document you are allowed to update, then choose <strong>Edit content</strong>.</li>
                    <li>Format text with fonts, sizes, colors, alignment, bold, italic, underline, headings, and lists from the toolbar.</li>
                    <li>Insert tables, images, links, page numbers, headers, and footers.</li>
                    <li>Choose a template, set paper size, orientation, and margins, then export to DOCX or PDF.</li>
                    <li>Leave the page or press <strong>Ctrl+S</strong> — changes save automatically while you type.</li>
                </ol>
                <p class="small text-muted mb-0">Editing changes the SmartDocs copy of the document; the original uploaded file stays in version history. Editing needs update permission (uploader, document manager, or administrator).</p>
            </div>
        </section>

        <section class="col-12 col-lg-6" aria-labelledby="help-ask">
            <div class="card card-p3 p-4 h-100">
                <h2 class="h5 fw-semibold" id="help-ask">Ask Lightning AI</h2>
                <ol class="mb-3">
                    <li>Open <a href="{{ route('chat.index') }}">Lightning AI</a> and ask a clear question about your authorized documents.</li>
                    <li>Optionally choose a document to focus the search, or choose two documents to compare them.</li>
                    <li>Review the cited source documents and passages before relying on an answer.</li>
                </ol>
                <p class="small text-muted mb-0">Generated answers can be incomplete or mistaken. Verify important details against the cited record.</p>
            </div>
        </section>

        <section class="col-12 col-lg-6" aria-labelledby="help-account">
            <div class="card card-p3 p-4 h-100">
                <h2 class="h5 fw-semibold" id="help-account">Account and security</h2>
                <ul class="mb-3">
                    <li>Update your profile and account details in <a href="{{ route('settings.index') }}">Settings</a>.</li>
                    <li>Review password and two-factor options in <a href="{{ route('settings.security') }}">Security settings</a>.</li>
                    <li>Adjust appearance and language in <a href="{{ route('settings.preferences') }}">Preferences</a>.</li>
                </ul>
                <p class="small text-muted mb-0">For access changes, missing features, or account help, contact your SmartDocs administrator.</p>
            </div>
        </section>
    </div>
</div>
@endsection



