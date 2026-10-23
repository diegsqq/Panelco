@extends('layouts.app')
@section('content')
<a href="{{ route('documents.library') }}" class="small text-decoration-none" style="color:#00A868">&larr; Back to library</a>
<div class="row g-3 mt-1">
<div class="col-lg-8">
<div class="card card-p3 p-4">
<div class="d-flex justify-content-between align-items-start">
<div><h4 class="fw-bold mb-1">{{ $document->title ?? $document->original_filename }}</h4><small class="text-muted">{{ $document->original_filename }} · {{ $document->mime_type }} · {{ number_format($document->file_size / 1024, 1) }} KB</small></div>
@unless($document->processing_status === 'completed')<span class="badge status-{{ $document->processing_status }}">{{ $document->processing_status }}</span>@endunless
</div>
<hr>
<dl class="row small mb-0">
<dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ $document->document_type ?? '—' }}</dd>
<dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $document->category?->name ?? $document->suggestedCategory?->name ?? '—' }}@if(!$document->category && $document->suggestedCategory) <span class="text-muted">suggested{{ $document->classification_confidence !== null ? ' · ' . round($document->classification_confidence * 100) . '%' : '' }}</span>@endif</dd>
<dt class="col-sm-4">Department</dt><dd class="col-sm-8">{{ $document->department?->name ?? '—' }}</dd>
<dt class="col-sm-4">Document date</dt><dd class="col-sm-8">{{ $document->document_date?->format('Y-m-d') ?? '—' }}</dd>
<dt class="col-sm-4">Uploaded</dt><dd class="col-sm-8">{{ $document->created_at->format('M d, Y') }} by {{ $document->uploader?->name ?? '—' }}</dd>
<dt class="col-sm-4">Approved</dt><dd class="col-sm-8">{{ $document->approved_at ? $document->approved_at->format('M d, Y') . ' by ' . ($document->approver?->name ?? '—') : '—' }}</dd>
<dt class="col-sm-4">Keywords</dt><dd class="col-sm-8">@if($document->keywords)@foreach(array_filter(array_map('trim', explode(',', $document->keywords))) as $kw)<span class="badge me-1 mb-1" style="background:#e6f3ea;color:#00743F">{{ $kw }}</span>@endforeach@else<span class="text-muted">—</span>@endif</dd>
@if ($document->processing_error)<dt class="col-sm-4">Error</dt><dd class="col-sm-8 text-danger">{{ $document->processing_error }}</dd>@endif
</dl>
@can('approve', $document)
<hr>
<form method="POST" action="{{ route('documents.approve', $document) }}" class="row g-2 align-items-end">
@csrf
<div class="col-md-7"><label class="form-label small fw-semibold" for="category_id">Approve classification</label><select id="category_id" name="category_id" class="form-select form-select-sm">@foreach($categories as $c)<option value="{{ $c->id }}" {{ (int) old('category_id', $document->suggested_category_id ?? $document->category_id) === (int) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-5"><button class="btn btn-sm w-100" style="background:#00A868;color:#fff">Approve classification</button></div>
</form>
@endcan
</div>
<div class="card card-p3 p-4 mt-3 p3-summary @if($summaryCheck !== null && $summaryCheck['verified']) is-verified @elseif($summaryCheck !== null && $summaryCheck['unsupported']) is-warn @endif">
<div class="d-flex justify-content-between align-items-center mb-2">
<h6 class="fw-bold mb-0"><i class="bi bi-journal-text me-1" style="color:#00A868"></i>Summary</h6>
@if($summaryCheck !== null && $summaryCheck['verified'])
<span class="badge" id="sum-verified" style="background:#d4edda;color:#155724"><i class="bi bi-patch-check-fill me-1"></i>verified</span>
@elseif($summaryCheck !== null && count($summaryCheck['unsupported']) > 0)
<span class="badge" id="sum-unverified" style="background:#fff3cd;color:#856404"><i class="bi bi-exclamation-triangle-fill me-1"></i>unverified</span>
@elseif($summaryCheck !== null)
<span class="badge" id="sum-nofigures" style="background:#e9ecef;color:#6c757d">no figures to check</span>
@endif
</div>
@if($document->summary)
@php
    // Highlight monetary amounts, percentages, and decimal figures so the
    // numbers pop out of the prose.
    $summaryHtml = preg_replace(
        '/(₱\s?[\d,]+(?:\.\d+)?|\b\d[\d,]*(?:\.\d+)?\s?%|\b\d{1,3}(?:,\d{3})+(?:\.\d+)?|\b\d+\.\d+\b)/u',
        '<span class="p3-fig">$1</span>',
        e($document->summary)
    );
@endphp
<p class="mb-0 p3-summary-text">{!! $summaryHtml !!}</p>
@if($summaryCheck !== null && ! $summaryCheck['verified'] && $summaryCheck['unsupported'])
<div class="small mt-3 p-2 rounded" style="background:#fff3cd;color:#856404"><i class="bi bi-exclamation-triangle-fill me-1"></i>Not found in the document text: <strong>{{ implode(', ', $summaryCheck['unsupported']) }}</strong></div>
@endif
@elseif($document->processing_status === 'completed')
<p class="mb-0 text-muted">No summary available.</p>
@else
<p class="mb-0 text-muted">Still processing.</p>
@endif
</div>
@if($highlightedHtml || count($tables))
<div class="card card-p3 p-4 mt-3" id="cited-text">
<h6 class="fw-bold">Document text</h6>
@if(count($tables))
@foreach($tables as $table)
@if($table['caption'])<p class="small fw-semibold mb-2" style="color:#00743F">{{ $table['caption'] }}</p>@endif
<div class="table-responsive p3-table-wrap">
<table class="table table-p3 align-middle mb-0">
<thead><tr>@foreach($table['headers'] as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
<tbody>
@foreach($table['rows'] as $row)
<tr>
@foreach($row as $j => $cell)
@if($j === 0)<td><span class="p3-cat-pill p3-cat-{{ Str::slug($cell) }}">{{ $cell }}</span></td>
@elseif($j === count($row) - 1)<td class="p3-cell-interp">{{ $cell }}</td>
@elseif(is_numeric(str_replace(['%', ' ', '₱'], '', $cell)))<td><span class="p3-result">{{ $cell }}</span></td>
@elseif(str_contains($cell, '÷') || str_contains($cell, '×') || str_contains($cell, '=') || str_contains($cell, '+') || str_contains($cell, '/'))<td><code class="p3-formula">{{ $cell }}</code></td>
@else<td class="fw-semibold">{{ $cell }}</td>
@endif
@endforeach
</tr>
@endforeach
</tbody>
</table>
</div>
@endforeach
<details class="mt-3">
<summary class="small" style="cursor:pointer;color:#00743F">View raw extracted text</summary>
<p class="small mt-2 mb-0" style="white-space:pre-wrap">{!! $highlightedHtml !!}</p>
</details>
@else
<p class="small" style="white-space:pre-wrap">{!! $highlightedHtml !!}</p>
@endif
</div>
@endif
@if(!empty($details))
<div class="card card-p3 p-4 mt-3">
<h6 class="fw-bold">Important details</h6>
<ul class="small mb-0 ps-4">@foreach($details as $d)<li class="mb-1">{{ $d }}</li>@endforeach</ul>
</div>
@endif
</div>
<div class="col-lg-4 d-flex flex-column gap-3">
<div class="card card-p3 p-3">
<h6 class="fw-bold">Actions</h6>
<div class="d-grid gap-2">
@can('download', $document)<button type="button" class="btn btn-sm w-100" style="background:#00A868;color:#fff" data-bs-toggle="modal" data-bs-target="#viewerModal"><i class="bi bi-eye-fill me-1"></i>View document</button>@endcan
@can('download', $document)<a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Download</a>@endcan
@if ($document->processing_status === 'failed')
@can('update', $document)<form method="POST" action="{{ route('documents.retry', $document) }}">@csrf<button class="btn btn-sm w-100" style="background:#00A868;color:#fff">Retry processing</button></form>@endcan
@endif
@can('delete', $document)<form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Delete this document?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm w-100">Delete</button></form>@endcan
</div>
</div>
<div class="card card-p3 p-3">
<h6 class="fw-bold">Processing</h6>
@unless($document->processing_status === 'completed')<p class="small mb-1">Status: <strong>{{ $document->processing_status }}</strong></p>@endunless
<p class="small text-muted mb-0">{{ $document->chunks->count() }} indexed passages.</p>
</div>
<div class="card card-p3 p-3">
<h6 class="fw-bold"><i class="bi bi-lightning-charge-fill me-1" style="color:#00A868"></i>Ask Lightning about this document</h6>
<div class="d-flex flex-wrap gap-2">
<a class="btn btn-sm btn-outline-success" href="{{ route('chat.index', ['q' => 'Summarize ' . ($document->title ?? $document->original_filename)]) }}">Summarize it</a>
<a class="btn btn-sm btn-outline-success" href="{{ route('chat.index', ['q' => 'What are the important details in ' . ($document->title ?? $document->original_filename) . '?']) }}">Important details</a>
<a class="btn btn-sm btn-outline-success" href="{{ route('chat.index', ['q' => 'What corrective actions were recorded in ' . ($document->title ?? $document->original_filename) . '?']) }}">Corrective actions</a>
</div>
</div>
</div>
</div>
{{-- View-only document modal: the file is fetched as an ephemeral blob (no
     shareable file URL), rendered without browser toolbars, and the object
     URL is revoked on close. --}}
<div class="modal fade" id="viewerModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-centered">
<div class="modal-content p3-modal d-flex flex-column" style="height:88vh">
<div class="d-flex justify-content-between align-items-center px-3 py-2" style="border-bottom:1px solid var(--p3-card-border)">
<span class="fw-bold text-truncate" style="max-width:80%"><i class="bi bi-eye-fill me-1" style="color:#00A868"></i>{{ $document->original_filename }}</span>
<span class="badge" style="background:#e6f3ea;color:#1F7A2B">View only</span>
</div>
<div id="viewerBody" class="flex-grow-1" style="overflow:auto;background:#f1f5f3;min-height:0;text-align:center">
<span class="text-muted small"><i class="bi bi-hour2"></i> Loading…</span>
</div>
</div>
</div>
</div>
<script>
(function () {
    var modal = document.getElementById('viewerModal');
    if (!modal) return;
    var body = document.getElementById('viewerBody');
    var objectUrl = null;

    // Best-effort view-only hardening: no right-click menu, no text
    // selection, no dragging inside the viewer.
    body.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    body.addEventListener('dragstart', function (e) { e.preventDefault(); });
    body.addEventListener('copy', function (e) { e.preventDefault(); });
    document.addEventListener('selectstart', function (e) {
        if (modal.classList.contains('show') && body.contains(e.target)) e.preventDefault();
    });

    function clear() {
        body.innerHTML = '<span class="text-muted small"><i class="bi bi-hour2"></i> Loading…</span>';
        if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
    }

    var DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    // mammoth.js converts DOCX to HTML in the browser; loaded lazily the
    // first time a .docx is opened so other documents never pay for it.
    function loadMammoth() {
        if (window.mammoth) return Promise.resolve();
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.8.0/mammoth.browser.min.js';
            s.onload = resolve;
            s.onerror = function () { reject(new Error('mammoth failed to load')); };
            document.head.appendChild(s);
        });
    }

    function renderDocx(blob) {
        body.innerHTML = '<span class="text-muted small"><i class="bi bi-hour2"></i> Rendering document…</span>';
        blob.arrayBuffer()
            .then(function (buffer) { return loadMammoth().then(function () { return buffer; }); })
            .then(function (buffer) { return mammoth.convertToHtml({ arrayBuffer: buffer }); })
            .then(function (result) {
                body.innerHTML = '<div class="p3-docx" style="user-select:none;text-align:left">' + result.value + '</div>';
            })
            .catch(function () {
                body.innerHTML = '<div class="text-center small text-muted p-4">'
                    + '<i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>'
                    + 'Could not render this DOCX in the browser. '
                    + 'Use <strong>Download</strong> to open it in Word.</div>';
            });
    }

    function render(blob) {
        var mime = (blob.type || '').toLowerCase();
        var name = (@json($document->original_filename) || '').toLowerCase();
        var isDocx = mime === DOCX_MIME || name.slice(-5) === '.docx';

        if (isDocx) { renderDocx(blob); return; }

        objectUrl = URL.createObjectURL(blob);
        var html;
        if (mime === 'application/pdf') {
            // toolbar=0 hides the PDF viewer's own download/print controls
            // where the browser honors it.
            html = '<iframe src="' + objectUrl + '#toolbar=0&navpanes=0&view=FitH" '
                + 'style="width:100%;height:100%;border:0;background:#fff" '
                + 'oncontextmenu="return false"></iframe>';
        } else if (mime.indexOf('image/') === 0) {
            html = '<img src="' + objectUrl + '" alt="Document preview" draggable="false" '
                + 'style="max-width:100%;max-height:100%;-webkit-user-drag:none;user-select:none">';
        } else {
            html = '<pre class="small mb-0 p-3" style="white-space:pre-wrap;user-select:none"></pre>';
        }
        body.innerHTML = html;
        if (html.indexOf('<pre') === 0) {
            blob.text().then(function (t) { body.querySelector('pre').textContent = t; });
        }
    }

    modal.addEventListener('shown.bs.modal', function () {
        clear();
        fetch(@json(route('documents.view', $document)), { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.blob();
            })
            .then(render)
            .catch(function () {
                body.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Could not load the document.</span>';
            });
    });
    modal.addEventListener('hidden.bs.modal', clear);
})();
</script>
@endsection
