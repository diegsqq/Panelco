@extends('layouts.app')

@section('content')
<style>
    body:has(#libPage) main { padding: .75rem 1rem 1rem !important; }
    .lib-page { min-height: 0; display: flex; flex-direction: column; }
    .lib-shell { min-height: 0; flex: 1 1 auto; display: flex; flex-direction: column; padding: 1.15rem; background: #fff; border: 1px solid #e5e5e5; border-radius: 1rem; }
    .lib-breadcrumb { color: #858585; font-size: .78rem; }
    .lib-breadcrumb .bi-chevron-right { font-size: .6rem; }
    .lib-toolbar { background: #f7f7f7; }
    .lib-table-wrap { min-height: 0; flex: 1 1 auto; overflow: auto; border: 1px solid #dedede; border-radius: .65rem; }
    .lib-table { min-width: 930px; margin: 0; font-size: .79rem; }
    .lib-table > :not(caption) > * > * { padding: .52rem .55rem; border-color: #e4e4e4; vertical-align: middle; }
    .lib-table thead th { position: sticky; top: 0; z-index: 1; background: #f7f7f7; color: #777; font-weight: 500; white-space: nowrap; }
    .lib-table tbody tr:nth-child(odd) { background: #fcfcfc; }
    .lib-table tbody tr:hover { background: #f0f7f3; }
    .lib-doc-name { max-width: 245px; color: #252525; }
    .lib-file-icon { font-size: 1rem; }
    .lib-avatar { width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 22px; border-radius: 50%; background: #e9f1ed; color: #41624f; font-size: .58rem; font-weight: 700; }
    .lib-status, .lib-category { display: inline-block; padding: .16rem .4rem; border-radius: .3rem; font-size: .68rem; line-height: 1.25; white-space: nowrap; }
    .lib-status-completed { background: #d9f3e5; color: #18764a; }
    .lib-status-pending { background: #fff0dc; color: #a45b12; }
    .lib-status-processing { background: #dff3f5; color: #13727d; }
    .lib-status-failed { background: #fbe2e2; color: #a83232; }
    .lib-category { background: #edf2ef; color: #59665f; }
    .lib-pagination { flex: 0 0 auto; }
    [data-theme="dark"] .lib-shell { background: #121b16; border-color: #26362e; }
    [data-theme="dark"] .lib-toolbar { background: #16211c; border-color: #26362e !important; color: #d7e5dc; }
    [data-theme="dark"] .lib-table-wrap { border-color: #2c3d34; }
    [data-theme="dark"] .lib-table { --bs-table-bg: transparent; --bs-table-color: #d7e5dc; --bs-table-border-color: #26362e; --bs-table-striped-bg: #131d18; --bs-table-hover-bg: #1d2b24; }
    [data-theme="dark"] .lib-table thead th { background: #16211c; color: #9db2a7; }
    [data-theme="dark"] .lib-table tbody tr:nth-child(odd) { background: #131d18; }
    [data-theme="dark"] .lib-table tbody tr:hover { background: #1d2b24; }
    [data-theme="dark"] .lib-doc-name { color: #e5eee8; }
    [data-theme="dark"] .lib-avatar { background: #1d2b24; color: #a9c4b6; }
    [data-theme="dark"] .lib-category { background: #1d2b24; color: #c3d0c8; }
    [data-theme="dark"] .lib-status-completed { background: #12291d; color: #8fe0b3; }
    [data-theme="dark"] .lib-status-pending { background: #3a3010; color: #ffe08a; }
    [data-theme="dark"] .lib-status-processing { background: #10304d; color: #8fc6ff; }
    [data-theme="dark"] .lib-status-failed { background: #3a1616; color: #f1a8a8; }
    @media (max-width: 767.98px) {
        body:has(#libPage) main { padding: .5rem !important; }
        .lib-shell { padding: .8rem; }
    }
</style>

<div class="lib-page p3-chat-page" id="libPage">
    <section class="lib-shell">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h3 class="fw-bold mb-0">Documents</h3>
                <small class="text-muted">{{ number_format($documents->total()) }} {{ \Illuminate\Support\Str::plural('document', $documents->total()) }}</small>
            </div>
            <a href="{{ route('documents.upload') }}" class="btn btn-p3 btn-sm px-3">
                <i class="bi bi-plus-lg me-1"></i>Add New
            </a>
        </div>

        <div class="lib-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 py-2 border rounded-top">
            <span class="fw-semibold small">All Documents</span>
            <form method="GET" action="{{ route('documents.library') }}" class="d-flex flex-wrap align-items-center gap-2" role="search">
                <div class="input-group input-group-sm" style="width:min(16rem, 55vw)">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search documents" aria-label="Search documents">
                </div>
                <select name="status" class="form-select form-select-sm" aria-label="Filter by processing status" style="width:auto" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach(['completed' => 'Complete', 'pending' => 'Pending', 'processing' => 'In Progress', 'failed' => 'Failed'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Search"><i class="bi bi-search"></i></button>
                @if($search !== '' || request()->filled('status'))
                    <a href="{{ route('documents.library') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>

        <div class="lib-table-wrap">
            <table class="table table-sm lib-table">
                <thead>
                    <tr>
                        <th scope="col" class="text-center">No.</th>
                        <th scope="col">Document</th>
                        <th scope="col">Type</th>
                        <th scope="col">Department</th>
                        <th scope="col">Category</th>
                        <th scope="col">Status</th>
                        <th scope="col">Uploaded by</th>
                        <th scope="col">Date added</th>
                        <th scope="col" class="text-end">Size</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        @php
                            $mime = $doc->mime_type ?? '';
                            $fileType = str_contains($mime, 'pdf') ? ['PDF', 'bi-file-earmark-pdf-fill', '#dc3545']
                                : (str_contains($mime, 'word') ? ['Word', 'bi-file-earmark-word-fill', '#0d6efd']
                                : (str_starts_with($mime, 'image/') ? ['Image', 'bi-file-earmark-image-fill', '#00A868']
                                : (str_contains($mime, 'text') ? ['Text', 'bi-file-earmark-text-fill', '#6c757d']
                                : ['File', 'bi-file-earmark-fill', '#6c757d'])));
                            $status = $doc->processing_status ?? 'pending';
                            $statusLabel = ['completed' => 'Complete', 'pending' => 'Pending', 'processing' => 'In Progress', 'failed' => 'Failed'][$status] ?? ucfirst($status);
                            $size = $doc->file_size >= 1048576 ? number_format($doc->file_size / 1048576, 1) . ' MB' : number_format($doc->file_size / 1024, 0) . ' KB';
                        @endphp
                        <tr>
                            <td class="text-center text-muted">{{ $documents->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="lib-file-icon {{ $fileType[1] }}" style="color:{{ $fileType[2] }}" aria-hidden="true"></i>
                                    <a href="{{ route('documents.show', $doc) }}" class="lib-doc-name text-decoration-none text-truncate fw-semibold" title="{{ $doc->title ?? $doc->original_filename }}">
                                        {{ $doc->title ?? $doc->original_filename }}
                                    </a>
                                </div>
                            </td>
                            <td>{{ $fileType[0] }}</td>
                            <td>{{ $doc->department?->name ?? '—' }}</td>
                            <td><span class="lib-category">{{ $doc->category?->name ?? $doc->suggestedCategory?->name ?? 'Uncategorized' }}</span></td>
                            <td><span class="lib-status lib-status-{{ $status }}">{{ $statusLabel }}</span></td>
                            <td>
                                @if($doc->uploader)
                                    <span class="d-inline-flex align-items-center gap-2 text-nowrap"><span class="lib-avatar">{{ $doc->uploader->initials() }}</span>{{ $doc->uploader->name }}</span>
                                @else
                                    <span class="text-muted">Unknown</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $doc->created_at?->format('m/d/Y') ?? '—' }}</td>
                            <td class="text-end text-nowrap">{{ $size }}</td>
                            <td class="text-end">
                                @can('delete', $doc)
                                    <button type="button" class="btn btn-sm text-muted p-1" title="Delete document" aria-label="Delete {{ $doc->title ?? $doc->original_filename }}" data-delete-url="{{ route('documents.destroy', $doc) }}" onclick="window.__libDeleteUrl=this.getAttribute('data-delete-url'); event.stopPropagation(); bootstrap.Modal.getOrCreateInstance(document.getElementById('libDeleteModal')).show();">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="bi bi-inbox fs-2 text-muted"></i>
                                <p class="fw-semibold mt-2 mb-2">No documents found.</p>
                                <a href="{{ route('documents.upload') }}" class="btn btn-sm btn-p3">Upload a document</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="libPagination" class="lib-pagination d-flex flex-wrap justify-content-center align-items-center gap-3 pt-3">
            @if ($documents->hasPages())
                <small class="text-muted">{{ $documents->firstItem() }}&ndash;{{ $documents->lastItem() }} of {{ $documents->total() }}</small>
                <div class="d-flex align-items-center gap-2">
                    @if ($documents->onFirstPage())
                        <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
                    @else
                        <a class="btn btn-sm btn-outline-success" href="{{ $documents->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
                    @endif
                    <span class="px-1 fw-semibold">{{ $documents->currentPage() }}/{{ $documents->lastPage() }}</span>
                    @if ($documents->hasMorePages())
                        <a class="btn btn-sm btn-outline-success" href="{{ $documents->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
                    @else
                        <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-right"></i></span>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>

<div class="modal fade" id="libDeleteModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;width:calc(100% - 2rem)">
        <div class="modal-content p3-modal">
            <div class="text-center pt-4 px-4">
                <div class="p3-meow"><img src="{{ asset('images/meow.gif') }}" alt="meow" style="width:90px;height:90px;object-fit:contain"></div>
                <h5 class="fw-bold mt-3 mb-0">Delete this document?</h5>
                <p class="text-muted mb-0 mt-2" style="font-size:.85rem">This permanently removes the file and its indexed passages. This action cannot be undone.</p>
            </div>
            <div class="p3-modal-actions">
                <button type="button" class="btn btn-outline-secondary px-4 flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-danger px-4 flex-fill" id="libConfirmDeleteBtn"><i class="bi bi-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>
<form id="libDeleteForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
<script>
(function () {
    var modal = document.getElementById('libDeleteModal');
    var form = document.getElementById('libDeleteForm');
    var btn = document.getElementById('libConfirmDeleteBtn');
    var pendingUrl = null;
    if (modal) {
        modal.addEventListener('show.bs.modal', function (e) {
            var trigger = e.relatedTarget;
            if (trigger && trigger.getAttribute('data-delete-url')) {
                pendingUrl = trigger.getAttribute('data-delete-url');
            }
            if (!pendingUrl && window.__libDeleteUrl) {
                pendingUrl = window.__libDeleteUrl;
            }
        });
    }
    document.addEventListener('click', function (e) {
        var target = e.target && e.target.closest ? e.target.closest('[data-delete-url]') : null;
        if (target) { window.__libDeleteUrl = target.getAttribute('data-delete-url'); pendingUrl = window.__libDeleteUrl; }
    }, true);
    if (btn) {
        btn.addEventListener('click', function () {
            var url = pendingUrl || window.__libDeleteUrl;
            if (url && form) {
                form.action = url;
                form.submit();
            }
        });
    }
})();
</script>
<script>
(function () {
    function fit() {
        var el = document.getElementById('libPage');
        if (!el) return;
        var top = el.getBoundingClientRect().top;
        el.style.height = Math.max(320, window.innerHeight - top) + 'px';
    }
    fit();
    window.addEventListener('resize', fit);
    window.addEventListener('load', fit);
    setTimeout(fit, 400);
})();
</script>
@endsection
