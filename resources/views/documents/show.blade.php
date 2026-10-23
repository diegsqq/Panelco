@extends('layouts.app')
@section('content')
<div class="row g-3 mt-1">
<div class="col-lg-8">
<div class="card card-p3 p-4">
<div class="d-flex justify-content-between align-items-start">
<div><h4 class="fw-bold mb-1">{{ $document->title ?? $document->original_filename }}</h4><small class="text-muted">{{ $document->original_filename }} · {{ $document->mime_type }} · {{ number_format($document->file_size / 1024, 1) }} KB</small></div>
@unless($document->processing_status === 'completed')<span class="badge status-{{ $document->processing_status }}">{{ $document->processing_status }}</span>@endunless
</div>
<hr>
<dl class="row small mb-0">
<dt class="col-sm-4"><i class="bi bi-file-earmark me-1" style="color:#00A868"></i>Type</dt><dd class="col-sm-8">{{ $document->document_type ?? '—' }}</dd>
<dt class="col-sm-4"><i class="bi bi-tags me-1" style="color:#00A868"></i>Category</dt><dd class="col-sm-8">{{ $document->category?->name ?? $document->suggestedCategory?->name ?? '—' }}@if(!$document->category && $document->suggestedCategory) <span class="text-muted">suggested{{ $document->classification_confidence !== null ? ' · ' . round($document->classification_confidence * 100) . '%' : '' }}</span>@endif</dd>
<dt class="col-sm-4"><i class="bi bi-building me-1" style="color:#00A868"></i>Department</dt><dd class="col-sm-8">{{ $document->department?->name ?? '—' }}</dd>
<dt class="col-sm-4"><i class="bi bi-calendar me-1" style="color:#00A868"></i>Document date</dt><dd class="col-sm-8">{{ $document->document_date?->format('Y-m-d') ?? '—' }}</dd>
<dt class="col-sm-4"><i class="bi bi-cloud-upload me-1" style="color:#00A868"></i>Uploaded</dt><dd class="col-sm-8">{{ $document->created_at->format('M d, Y') }} by {{ $document->uploader?->name ?? '—' }}</dd>
<dt class="col-sm-4"><i class="bi bi-check-circle me-1" style="color:#00A868"></i>Approved</dt><dd class="col-sm-8">{{ $document->approved_at ? $document->approved_at->format('M d, Y') . ' by ' . ($document->approver?->name ?? '—') : '—' }}</dd>
<dt class="col-sm-4"><i class="bi bi-key me-1" style="color:#00A868"></i>Keywords</dt><dd class="col-sm-8">@if($document->keywords)@foreach(array_filter(array_map('trim', explode(',', $document->keywords))) as $kw)<span class="badge me-1 mb-1" style="background:#e6f3ea;color:#00743F">{{ $kw }}</span>@endforeach@endif</dd>
@if ($document->processing_error)<dt class="col-sm-4"><i class="bi bi-exclamation-triangle me-1" style="color:#dc3545"></i>Error</dt><dd class="col-sm-8 text-danger">{{ $document->processing_error }}</dd>@endif
</dl>
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
@if(count($tables))
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

@else
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
@can('download', $document)<button type="button" class="btn btn-sm w-100 btn-outline-success" data-bs-toggle="modal" data-bs-target="#viewerModal"><i class="bi bi-eye-fill me-1"></i>View document</button>@endcan
@can('download', $document)<a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Download</a>@endcan
@can('download', $document)<button type="button" class="btn btn-outline-success btn-sm w-100" id="printDocBtn"><i class="bi bi-printer me-1"></i>Print document</button>@endcan
@if ($document->processing_status === 'failed')
@can('update', $document)<form method="POST" action="{{ route('documents.retry', $document) }}">@csrf<button class="btn btn-sm w-100" style="background:#00A868;color:#fff">Retry processing</button></form>@endcan
@endif
@can('delete', $document)<form method="POST" action="{{ route('documents.destroy', $document) }}" id="deleteDocForm">@csrf @method('DELETE')<button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#deleteDocModal"><i class="bi bi-trash me-1"></i>Delete</button></form>@endcan
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
<span class="fw-bold text-truncate" style="max-width:70%"><i class="bi bi-eye-fill me-1" style="color:#00A868"></i>{{ $document->original_filename }}</span>
<div class="d-flex align-items-center gap-2">
<button type="button" class="btn btn-sm btn-outline-success" id="viewerPrintBtn"><i class="bi bi-printer me-1"></i>Print</button>
<span class="badge" style="background:#e6f3ea;color:#1F7A2B">View only</span>
</div>
</div>
<div id="viewerBody" class="flex-grow-1" style="overflow:auto;background:#f1f5f3;min-height:0;text-align:center">
<span class="text-muted small"><i class="bi bi-hour2"></i> Loading…</span>
</div>
</div>
</div>
</div>
<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
<div class="modal-dialog modal-dialog-centered" style="max-width:420px;width:calc(100% - 2rem)">
<div class="modal-content p3-modal">
<div class="text-center pt-4 px-4">
<div class="p3-meow"><img src="{{ asset('images/meow.gif') }}" alt="meow" style="width:90px;height:90px;object-fit:contain"></div>
<h5 class="fw-bold mt-3 mb-0">Delete this document?</h5>
<p class="text-muted mb-0 mt-2" style="font-size:.85rem">This permanently removes the file and its indexed passages. This action cannot be undone.</p>
</div>
<div class="p3-modal-actions">
<button type="button" class="btn btn-outline-secondary px-4 flex-fill" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-outline-danger px-4 flex-fill" id="confirmDeleteDocBtn"><i class="bi bi-trash me-1"></i>Delete</button>
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
    var pendingPrint = false;

    function printHtml(html) {
        var iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
        var doc = iframe.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(function () { if (iframe.parentNode) iframe.parentNode.removeChild(iframe); }, 1000);
    }
    function printNow() {
        var iframe = body.querySelector('iframe');
        if (iframe) {
            try {
                if (iframe.contentDocument) iframe.contentDocument.title = docTitle;
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                pendingPrint = false;
                return;
            } catch (e) { /* fall through */ }
        }
        var img = body.querySelector('img');
        if (img) {
            printHtml('<html><head><title>' + docTitle + '</title><style>@page{margin:0}body{margin:1.5cm;font-family:system-ui,sans-serif;text-align:center}</style></head><body><img src="' + img.src + '" style="max-width:100%"></body></html>');
            pendingPrint = false;
            return;
        }
        var docx = body.querySelector('.p3-docx');
        if (docx) {
            printHtml('<html><head><title>' + docTitle + '</title><style>@page{margin:0}body{margin:1.5cm;font-family:system-ui,sans-serif;line-height:1.5}</style></head><body>' + docx.innerHTML + '</body></html>');
            pendingPrint = false;
            return;
        }
        var pre = body.querySelector('pre');
        if (pre) {
            printHtml('<html><head><title>' + docTitle + '</title><style>@page{margin:0}body{margin:1.5cm;font-family:monospace;white-space:pre-wrap}</style></head><body><pre>' + pre.textContent.replace(/</g, '&lt;') + '</pre></body></html>');
            pendingPrint = false;
            return;
        }
        window.print();
        pendingPrint = false;
    }

    var printBtn = document.getElementById('printDocBtn');
    var viewerPrintBtn = document.getElementById('viewerPrintBtn');
    var docTitle = @json($document->original_filename);
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            if (modal.classList.contains('show')) {
                printNow();
            } else {
                pendingPrint = true;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    }
    if (viewerPrintBtn) {
        viewerPrintBtn.addEventListener('click', function () { printNow(); });
    }
    var confirmDeleteBtn = document.getElementById('confirmDeleteDocBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            var f = document.getElementById('deleteDocForm');
            if (f) f.submit();
        });
    }

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
                if (pendingPrint) setTimeout(printNow, 600);
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
        var iframe = body.querySelector('iframe');
        if (iframe && pendingPrint) {
            iframe.onload = function () { setTimeout(printNow, 400); };
        }
        if (pendingPrint) setTimeout(printNow, 1200);
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
