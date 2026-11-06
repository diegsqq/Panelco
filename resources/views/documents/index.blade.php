@extends('layouts.app')

@section('content')
<style>
    body:has(#libPage) main { padding: .75rem 1rem .25rem !important; }
    .lib-page { min-height: 0; display: flex; flex-direction: column; }
    .lib-shell { min-height: 0; flex: 1 1 auto; display: flex; flex-direction: column; padding: .75rem; background: #fff; border: 1px solid #e5e5e5; border-radius: 1rem; }
    .lib-breadcrumb { color: #858585; font-size: .78rem; }
    .lib-breadcrumb .bi-chevron-right { font-size: .6rem; }
    .lib-toolbar { background: #f7f7f7; }
    .lib-table-wrap { min-height: 0; flex: 1 1 auto; overflow: hidden; border: 1px solid #dedede; border-radius: .65rem; display: flex; flex-direction: column; }
    .lib-table { width: 100%; margin: 0; font-size: .79rem; table-layout: auto; height: 100%; flex: 1 1 auto; }
    .lib-table > :not(caption) > * > * { padding: .35rem .45rem; border-color: #e4e4e4; vertical-align: middle; }
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
            <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold mb-0">{{ auth()->user()->role === \App\Models\User::ROLE_EMPLOYEE ? 'Your documents' : 'Documents' }}</h3>
                <span class="badge text-capitalize" style="background:#e6f3ea;color:#00743F;font-weight:500">{{ number_format($documents->total()) }} {{ \Illuminate\Support\Str::plural('document', $documents->total()) }}</span>
            </div>
            @if(auth()->user()->isManager())
                <a href="{{ route('documents.trash') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash3 me-1"></i>Recycle bin</a>
            @endif
            <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
            <form method="GET" action="{{ route('documents.library') }}" class="d-flex flex-wrap align-items-center gap-2" role="search">
                <div class="input-group input-group-sm" style="width:min(18rem, 55vw)">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Title, keywords, or content" aria-label="Search title, keywords, or content">
                </div>
                <select name="status" class="form-select form-select-sm" aria-label="Filter by processing status" style="width:auto" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach(['completed' => 'Complete', 'pending' => 'Pending', 'processing' => 'In Progress', 'failed' => 'Failed'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="category_id" class="form-select form-select-sm" aria-label="Filter by category" style="width:auto" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="department_id" class="form-select form-select-sm" aria-label="Filter by department" style="width:auto" onchange="this.form.submit()">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @if(auth()->user()->isManager())
                    <select name="uploader_id" class="form-select form-select-sm" aria-label="Filter by uploader" style="width:auto" onchange="this.form.submit()">
                        <option value="">All uploaders</option>
                        @foreach($uploaders as $uploader)
                            <option value="{{ $uploader->id }}" @selected((string) request('uploader_id') === (string) $uploader->id)>{{ $uploader->name }}</option>
                        @endforeach
                    </select>
                @endif
                <input type="date" name="date_from" value="{{ $validated['date_from'] ?? '' }}" class="form-control form-control-sm" aria-label="Document date from" title="Document date from" style="width:auto" onchange="this.form.submit()">
                <input type="date" name="date_to" value="{{ $validated['date_to'] ?? '' }}" class="form-control form-control-sm" aria-label="Document date to" title="Document date to" style="width:auto" onchange="this.form.submit()">
                <select name="sort" class="form-select form-select-sm" aria-label="Sort documents" style="width:auto" onchange="this.form.submit()">
                    <option value="newest" @selected($sort === 'newest')>Newest</option>
                    <option value="oldest" @selected($sort === 'oldest')>Oldest</option>
                    <option value="alphabetical" @selected($sort === 'alphabetical')>A–Z</option>
                </select>
                @if(collect($validated)->filter(fn ($value) => $value !== null && $value !== '' && $value !== 'newest')->isNotEmpty())
                    <a href="{{ route('documents.library') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
            <a href="{{ route('documents.export', request()->query()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Export CSV
            </a>
            </div>
        </div>

        <div class="lib-table-wrap">
            <table class="table table-sm lib-table">
                <caption class="visually-hidden">Document library results</caption>
                <thead>
                    <tr>
                        <th scope="col" class="text-center">No.</th>
                        <th scope="col">Document</th>
                        <th scope="col">Type</th>
                        <th scope="col">Department</th>
                        <th scope="col">Category</th>

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
                            <td>{{ $doc->department?->name ?? $doc->suggestedDepartment?->name ?? '—' }}</td>
                            <td><span class="lib-category">{{ $doc->category?->name ?? $doc->suggestedCategory?->name ?? 'Uncategorized' }}</span></td>

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
                                @canany(['update', 'delete'], $doc)
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm text-muted p-1" title="Document actions" aria-label="Actions for {{ $doc->title ?? $doc->original_filename }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('update', $doc)
                                                <li><a class="dropdown-item" href="{{ route('documents.edit', $doc) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                            @endcan
                                            @can('delete', $doc)
                                                <li><button type="button" class="dropdown-item text-danger" data-delete-url="{{ route('documents.destroy', $doc) }}" onclick="window.__libDeleteUrl=this.getAttribute('data-delete-url'); bootstrap.Modal.getOrCreateInstance(document.getElementById('libDeleteModal')).show();"><i class="bi bi-trash me-2"></i>Delete</button></li>
                                            @endcan
                                        </ul>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-inbox fs-2 text-muted"></i>
                                <p class="fw-semibold mt-2 mb-2">No documents found.</p>
                                <a href="{{ route('documents.upload') }}" class="btn btn-sm btn-outline-success">Upload a document</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <nav id="libPagination" class="lib-pagination d-flex flex-wrap justify-content-center align-items-center gap-3 pt-3" aria-label="Document result pages">
                <small class="text-muted">{{ $documents->firstItem() }}&ndash;{{ $documents->lastItem() }} of {{ $documents->total() }}</small>
                <div class="d-flex align-items-center gap-2">
                    @if ($documents->onFirstPage())
                        <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
                    @else
                        <a class="btn btn-sm btn-outline-success" href="{{ $documents->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
                    @endif
                    <span class="px-1 fw-semibold" aria-label="Page {{ $documents->currentPage() }} of {{ $documents->lastPage() }}" aria-current="page">{{ $documents->currentPage() }}/{{ $documents->lastPage() }}</span>
                    @if ($documents->hasMorePages())
                        <a class="btn btn-sm btn-outline-success" href="{{ $documents->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
                    @else
                        <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-right"></i></span>
                    @endif
                </nav>
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
        el.style.height = Math.max(320, window.innerHeight - top - 8) + 'px';
    }
    fit();
    window.addEventListener('resize', fit);
    window.addEventListener('load', fit);
    setTimeout(fit, 400);
})();
</script>
@endsection
