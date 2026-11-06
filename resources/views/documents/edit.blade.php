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
        <form method="POST" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold" for="title">Title</label>
                <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $document->title) }}" maxlength="255" placeholder="Leave blank to use the filename" aria-describedby="titleHelp{{ $errors->has('title') ? ' titleError' : '' }}" @error('title') aria-invalid="true" @enderror>
                @error('title')<div id="titleError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div id="titleHelp" class="form-text">Shown in the library and search results instead of <code>{{ $document->original_filename }}</code>.</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" @error('category_id') aria-describedby="categoryError" aria-invalid="true" @enderror>
                        <option value="">— None —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected((string) old('category_id', $document->category_id) === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div id="categoryError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                @if (auth()->user()->isManager())
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" @error('department_id') aria-describedby="departmentError" aria-invalid="true" @enderror>
                        <option value="">— None —</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected((string) old('department_id', $document->department_id) === (string) $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div id="departmentError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text">Controls which departments can find this document.</div>
                </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="document_date">Document date</label>
                    <input id="document_date" name="document_date" type="date" class="form-control @error('document_date') is-invalid @enderror" value="{{ old('document_date', $document->document_date?->format('Y-m-d')) }}" @error('document_date') aria-describedby="documentDateError" aria-invalid="true" @enderror>
                    @error('document_date')<div id="documentDateError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="expiration_date">Expiration date <span class="text-muted fw-normal">(optional)</span></label>
                    <input id="expiration_date" name="expiration_date" type="date" class="form-control @error('expiration_date') is-invalid @enderror" value="{{ old('expiration_date', $document->expiration_date?->format('Y-m-d')) }}" @error('expiration_date') aria-describedby="expirationDateError" aria-invalid="true" @enderror>
                    @error('expiration_date')<div id="expirationDateError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text">Set a reminder date; SmartDocs will not automatically archive or delete the document.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="reference_number">Reference number</label>
                    <input id="reference_number" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number', $document->reference_number) }}" maxlength="255" @error('reference_number') aria-describedby="referenceNumberError" aria-invalid="true" @enderror>
                    @error('reference_number')<div id="referenceNumberError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="sender">Sender</label>
                    <input id="sender" name="sender" class="form-control @error('sender') is-invalid @enderror" value="{{ old('sender', $document->sender) }}" maxlength="255" @error('sender') aria-describedby="senderError" aria-invalid="true" @enderror>
                    @error('sender')<div id="senderError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="recipient">Recipient</label>
                    <input id="recipient" name="recipient" class="form-control @error('recipient') is-invalid @enderror" value="{{ old('recipient', $document->recipient) }}" maxlength="255" @error('recipient') aria-describedby="recipientError" aria-invalid="true" @enderror>
                    @error('recipient')<div id="recipientError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="form-label fw-semibold" for="file">Replace file <span class="text-muted fw-normal">(optional)</span></label>
                <input id="file" name="file" type="file" class="form-control @error('file') is-invalid @enderror" accept=".{{ implode(',.', config('smartdocs.upload.allowed_extensions')) }}" @error('file') aria-describedby="fileError" aria-invalid="true" @enderror>
                @error('file')<div id="fileError" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">The current file will be retained in version history. Replacing it resets extracted results and queues the new version for processing.</div>
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
            <dt class="col-sm-4 text-muted">Version</dt><dd class="col-sm-8">{{ $document->version_number }}</dd>
            <dt class="col-sm-4 text-muted">Filename</dt><dd class="col-sm-8">{{ $document->original_filename }}</dd>
            <dt class="col-sm-4 text-muted">Type</dt><dd class="col-sm-8">{{ $document->mime_type ?? '—' }}</dd>
            <dt class="col-sm-4 text-muted">Size</dt><dd class="col-sm-8">{{ number_format($document->file_size / 1024, 1) }} KB</dd>
            <dt class="col-sm-4 text-muted">Status</dt><dd class="col-sm-8">{{ ucfirst($document->processing_status ?? 'pending') }}</dd>
        </dl>
        <div class="form-text mt-2">Previous files remain available from the document's version history.</div>
    </div>

</div>
</div>
@endsection
