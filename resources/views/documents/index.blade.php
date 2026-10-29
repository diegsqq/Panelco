@extends('layouts.app')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
<div><h3 class="fw-bold mb-0">Document Library</h3></div>
<div class="d-flex flex-wrap gap-2 align-items-center">
<form method="GET" action="{{ route('documents.library') }}" class="d-flex align-items-center gap-2" role="search">
    <div class="input-group" style="min-width:16rem">
        <span class="input-group-text" style="background:#f2f7f3;border-color:#d5e8db;color:#00A868"><i class="bi bi-search"></i></span>
        <input type="search" name="q" value="{{ $search ?? request('q') }}" class="form-control" placeholder="Search title, file, category…" aria-label="Search documents" style="border-color:#d5e8db">
    </div>
    <button type="submit" class="btn btn-sm" style="background:#00A868;border-color:#00A868;color:#fff;font-weight:600">Search</button>
</form>
</div>
</div>

<div id="libListWrap" style="min-height:calc(100vh - 130px);display:flex;flex-direction:column">
<div class="card card-p3 p-0 overflow-hidden" style="flex-grow:1">
@forelse ($documents as $doc)
@php
$mime = $doc->mime_type ?? '';
$icon = str_contains($mime, 'pdf') ? ['bi-file-earmark-pdf-fill', '#dc3545'] : (str_contains($mime, 'word') ? ['bi-file-earmark-word-fill', '#0d6efd'] : (str_starts_with($mime, 'image/') ? ['bi-file-earmark-image-fill', '#00A868'] : (str_contains($mime, 'text') ? ['bi-file-earmark-text-fill', '#6c757d'] : ['bi-file-earmark-fill', '#00A868'])));
@endphp
<div class="d-flex justify-content-between align-items-center border-bottom p-3 src-card position-relative">
<div class="d-flex gap-3">
@can('download', $doc)
@if(str_starts_with($mime, 'image/'))
<img src="{{ route('documents.thumb', $doc) }}" alt="{{ $doc->original_filename }}" loading="lazy"
    style="width:56px;height:56px;object-fit:cover;border-radius:12px;border:1px solid #d5e8db;background:#fff">
@else
<span class="stat-icon" style="background:#f2f7f3;color:{{ $icon[1] }}"><i class="bi {{ $icon[0] }}"></i></span>
@endif
@else
<span class="stat-icon" style="background:#f2f7f3;color:{{ $icon[1] }}"><i class="bi {{ $icon[0] }}"></i></span>
@endcan
<div>
<a href="{{ route('documents.show', $doc) }}" class="fw-semibold text-decoration-none stretched-link" style="color:#00743F">{{ $doc->title ?? $doc->original_filename }}</a>
<div><small class="text-muted">@if($doc->title && $doc->title !== pathinfo($doc->original_filename, PATHINFO_FILENAME)){{ $doc->original_filename }} · @endif{{ number_format($doc->file_size / 1024, 1) }} KB · {{ $doc->category?->name ?? $doc->suggestedCategory?->name ?? 'Uncategorized' }} · {{ $doc->created_at->diffForHumans() }}</small></div>
</div>
</div>
<div class="d-flex align-items-center gap-2 position-relative" style="z-index:2">
@can('delete', $doc)
<button type="button" class="btn btn-sm btn-outline-danger" title="Delete document" data-delete-url="{{ route('documents.destroy', $doc) }}" onclick="window.__libDeleteUrl=this.getAttribute('data-delete-url'); event.stopPropagation(); bootstrap.Modal.getOrCreateInstance(document.getElementById('libDeleteModal')).show();">
<i class="bi bi-trash me-1"></i>
</button>
@endcan
</div>
</div>
@empty
<div class="p-5 text-center"><i class="bi bi-inbox fs-2 text-muted"></i><p class="fw-semibold mt-2 mb-2">No documents found.</p><a href="{{ route('documents.upload') }}" class="btn btn-sm" style="background:#00A868;color:#fff">Upload a document</a></div>
@endforelse
</div>
<div id="libPagination" class="d-flex flex-wrap justify-content-center align-items-center gap-3" style="margin-top:.75rem;position:sticky;bottom:0;z-index:20;display:{{ $documents->hasPages() ? 'flex' : 'none' }}">
@if ($documents->hasPages())
<small class="text-muted">{{ $documents->firstItem() }}&ndash;{{ $documents->lastItem() }} of {{ $documents->total() }}</small>
<div class="d-flex align-items-center gap-2">
@if ($documents->onFirstPage())
<span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
@else
<a class="btn btn-sm btn-outline-success" href="{{ $documents->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
@endif
<span class="px-1 fw-semibold" style="color:#00743F">{{ $documents->currentPage() }}/{{ $documents->lastPage() }}</span>
@if ($documents->hasMorePages())
<a class="btn btn-sm btn-outline-success" href="{{ $documents->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
@else
<span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-right"></i></span>
@endif
</div>
@endif
</div>
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
<button type="button" class="btn btn-outline-danger px-4 flex-fill" id="libConfirmDeleteBtn"><i class="bi bi-trash me-1"></i></button>
</div>
</div>
</div>
</div>
<form id="libDeleteForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
<script>
(function () {
    var form = document.querySelector('form[role="search"]');
    if (!form) return;
    var input = form.querySelector('input[name="q"]');
    var list = document.querySelector('.card.card-p3.p-0.overflow-hidden');
    var pagination = document.getElementById('libPagination');
    if (!input || !list) return;
    var timer = null;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var q = input.value.trim();
            var url = new URL(form.action);
            if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
            fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var newList = doc.querySelector('.card.card-p3.p-0.overflow-hidden');
                    if (newList) list.innerHTML = newList.innerHTML;
                    if (pagination) {
                        var newPag = doc.getElementById('libPagination');
                        if (newPag && newPag.innerHTML.trim()) {
                            pagination.innerHTML = newPag.innerHTML;
                            pagination.style.display = 'flex';
                        } else {
                            pagination.style.display = 'none';
                        }
                    }
                    try { history.replaceState(null, '', url.toString()); } catch (e) {}
                });
        }, 300);
    });
})();
</script>
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
        var t = e.target && e.target.closest ? e.target.closest('[data-delete-url]') : null;
        if (t) { window.__libDeleteUrl = t.getAttribute('data-delete-url'); pendingUrl = window.__libDeleteUrl; }
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
@endsection
