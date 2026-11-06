@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
    <div>
        <h3 class="fw-bold mb-1">Document expiration review</h3>
        <p class="text-muted mb-0">Review expired documents and documents due to expire within {{ $upcomingDays }} days. This page does not archive or delete documents.</p>
    </div>
</div>

<div class="card card-p3 p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Document</th>
                    <th>Department</th>
                    <th>Uploaded by</th>
                    <th>Expiration</th>
                    <th>Status</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    @php $isExpired = $document->expiration_date->lte(today()); @endphp
                    <tr>
                        <td class="fw-semibold">{{ $document->title }}</td>
                        <td>{{ $document->department?->name ?? '—' }}</td>
                        <td>{{ $document->uploader?->name ?? 'Unknown' }}</td>
                        <td class="text-nowrap">{{ $document->expiration_date->format('M d, Y') }}</td>
                        <td>
                            <span class="badge {{ $isExpired ? 'text-bg-danger' : 'text-bg-warning' }}">
                                {{ $isExpired ? 'Expired' : 'Expires soon' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-success" href="{{ route('documents.show', $document) }}">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted p-4">No expired or soon-to-expire documents.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $documents->links() }}</div>
@endsection
