@extends('layouts.app')

@section('body-class', 'p3-no-scroll')
@section('full-screen', true)
@section('full-screen-col', 'col-12')

@section('content')
<div class="p3-preview-shell d-flex flex-column h-100">
    <div class="p3-preview-bar d-flex align-items-center gap-2 px-3 py-2 flex-wrap">
        <a href="{{ route('documents.library') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i>Back to library
        </a>
        <div class="min-w-0 flex-grow-1">
            <div class="fw-bold text-truncate" style="color:#00743F">{{ $document->display_title }}</div>
            <div class="small text-muted text-truncate">{{ $document->original_filename }}</div>
        </div>
        <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-success">
            <i class="bi bi-download me-1"></i>Download
        </a>
    </div>

    <div class="p3-preview-stage flex-grow-1 position-relative">
        @if($previewError !== null)
            <div class="d-flex align-items-center justify-content-center h-100 p-4">
                <div class="alert alert-warning border-0 shadow-sm text-center" style="max-width:520px;border-radius:1rem">
                    <i class="bi bi-exclamation-triangle-fill d-block mb-2" style="font-size:2rem;color:#b54708"></i>
                    <h5 class="fw-bold mb-2">Preview unavailable</h5>
                    <p class="mb-3">{{ $previewError }}</p>
                    <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download me-1"></i>Download file
                    </a>
                </div>
            </div>
        @elseif($previewType === 'pdf')
            <iframe src="{{ $previewSource }}" title="PDF preview of {{ $document->display_title }}" class="p3-preview-frame"></iframe>
        @elseif($previewType === 'image')
            <div class="h-100 w-100 d-flex align-items-center justify-content-center p-3 overflow-auto">
                <img src="{{ $previewSource }}" alt="Preview of {{ $document->display_title }}" class="img-fluid" style="max-height:100%">
            </div>
        @elseif($previewType === 'onlyoffice')
            <iframe src="{{ $previewSource }}" title="Document preview of {{ $document->display_title }}" class="p3-preview-frame"></iframe>
        @elseif($previewType === 'text')
            <div class="h-100 overflow-auto p-4">
                <div class="p3-preview-text" style="max-width:950px;margin:0 auto">{{ $previewText }}</div>
            </div>
        @endif
    </div>
</div>

<style>
    .p3-preview-shell { background:#f5f8f6; }
    .p3-preview-bar { background:#fff; border-bottom:1px solid #d5e8db; flex:0 0 auto; }
    .p3-preview-stage { min-height:0; background:#eef2ef; }
    .p3-preview-frame { width:100%; height:100%; border:0; display:block; background:#fff; }
    .p3-preview-text {
        white-space:pre-wrap;
        word-break:break-word;
        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
        font-size:.9rem;
        line-height:1.65;
        background:#fff;
        border:1px solid #d5e8db;
        border-radius:.9rem;
        padding:2rem;
        box-shadow:0 .35rem 1.5rem rgba(0,90,45,.06);
    }
    @media (prefers-color-scheme: dark) {
        .p3-preview-shell { background:#111714; }
        .p3-preview-bar { background:#18211c; border-color:#274033; }
        .p3-preview-stage { background:#0d120f; }
        .p3-preview-text { background:#18211c; border-color:#274033; color:#e8f3ec; }
    }
</style>
@endsection
