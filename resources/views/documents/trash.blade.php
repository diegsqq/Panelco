@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
    <div>
        <h3 class="fw-bold mb-0">Recycle bin</h3>
        <small class="text-muted">Deleted documents are kept with their versions and can be restored by an administrator or a manager with access.</small>
    </div>
    <a href="{{ route('documents.library') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to documents</a>
</div>

<div class="card card-p3 p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">Document</th>
                    <th scope="col">Department</th>
                    <th scope="col">Deleted</th>
                    <th scope="col">Uploaded by</th>
                    <th scope="col" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $document->title ?? $document->original_filename }}</div>
                            <small class="text-muted">{{ $document->original_filename }}</small>
                        </td>
                        <td>{{ $document->department?->name ?? '—' }}</td>
                        <td>{{ $document->deleted_at->format('M d, Y H:i') }}</td>
                        <td>{{ $document->uploader?->name ?? 'Former user' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('documents.restore', $document) }}" onsubmit="return confirm('Restore this document to the library?')">
                                @csrf
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted p-4">The recycle bin is empty.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $documents->links() }}</div>
@endsection
