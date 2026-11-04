@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">

    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <span class="stat-icon" style="background:#e6f3ea;color:#00A868;width:52px;height:52px;font-size:1.4rem"><i class="bi bi-pencil-square"></i></span>
            <div>
                <h4 class="fw-bold mb-0">Edit document</h4>
                <small class="text-muted text-truncate d-inline-block" style="max-width:22rem" title="{{ $document->original_filename }}">{{ $document->original_filename }}</small>
            </div>
        </div>
        <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-outline-secondary text-nowrap"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <div class="card card-p3 p-4">
        <form method="POST" action="{{ route('documents.update', $document) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold" for="title">Title</label>
                <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $document->title) }}" maxlength="255" placeholder="Leave blank to use the filename">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Shown in the library and search results instead of <code>{{ $document->original_filename }}</code>.</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">— None —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected((string) old('category_id', $document->category_id) === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if (auth()->user()->isManager())
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">— None —</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected((string) old('department_id', $document->department_id) === (string) $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Controls which departments can find this document.</div>
                </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="document_date">Document date</label>
                    <input id="document_date" name="document_date" type="date" class="form-control @error('document_date') is-invalid @enderror" value="{{ old('document_date', $document->document_date?->format('Y-m-d')) }}">
                    @error('document_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button class="btn px-4" style="background:#ffffff;color:#00A868;border:1px solid #00A868;font-weight:700"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                <a href="{{ route('documents.show', $document) }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>

    <div class="card card-p3 p-4 mt-3">
        <h6 class="fw-bold mb-3">File</h6>
        <dl class="row small mb-0">
            <dt class="col-sm-4 text-muted">Filename</dt><dd class="col-sm-8">{{ $document->original_filename }}</dd>
            <dt class="col-sm-4 text-muted">Type</dt><dd class="col-sm-8">{{ $document->mime_type ?? '—' }}</dd>
            <dt class="col-sm-4 text-muted">Size</dt><dd class="col-sm-8">{{ number_format($document->file_size / 1024, 1) }} KB</dd>
            <dt class="col-sm-4 text-muted">Status</dt><dd class="col-sm-8">{{ ucfirst($document->processing_status ?? 'pending') }}</dd>
        </dl>
        <div class="form-text mt-2">The uploaded file itself cannot be replaced here — upload a new document to swap the file.</div>
    </div>

</div>
</div>
@endsection
