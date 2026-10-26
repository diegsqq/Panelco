<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocument;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\UploadSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public const ALLOWED_MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'image/png',
        'image/jpeg',
    ];

    public const MAX_KB = 20480;

    private function uploadRules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:' . implode(',', config('smartdocs.upload.allowed_extensions')), 'max:' . config('smartdocs.upload.max_kb', self::MAX_KB)],
            'title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:document_categories,id'],
            'document_date' => ['nullable', 'date'],
            'file_last_modified' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function visibleToUser(Request $request)
    {
        $user = $request->user();
        $query = Document::with(['category', 'department', 'uploader'])->latest();

        if (! $user->isAdmin()) {
            $allowedDeptIds = $user->departments()->pluck('departments.id')
                ->push($user->department_id)->filter()->unique()->values();
            $query->where(function ($q) use ($user, $allowedDeptIds) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereIn('department_id', $allowedDeptIds)
                    ->orWhereNull('department_id')
                    ->orWhereIn('id', $user->authorizedDocuments()->pluck('documents.id'));
            });
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $query = $this->visibleToUser($request);

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('original_filename', 'like', $like)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('suggestedCategory', fn ($c) => $c->where('name', 'like', $like));
            });
        }

        $documents = $query->paginate(12)->withQueryString();

        return view('documents.index', compact('documents', 'search'));
    }

    public function create(): View
    {
        $categories = DocumentCategory::orderBy('name')->get();

        return view('documents.create', compact('categories'));
    }

    /**
     * AJAX: reads the selected file and proposes editable title, category,
     * and document date before the user actually uploads.
     */
    public function suggest(Request $request, UploadSuggester $suggester): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:' . implode(',', config('smartdocs.upload.allowed_extensions')), 'max:' . config('smartdocs.upload.max_kb', self::MAX_KB)],
            'file_last_modified' => ['nullable', 'integer', 'min:1'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        if (! in_array($file->getMimeType(), config('smartdocs.upload.allowed_mimes', self::ALLOWED_MIMES), true)) {
            return response()->json(['message' => 'Unsupported file type.'], 422);
        }

        $clientTs = $request->filled('file_last_modified') ? (int) $request->input('file_last_modified') : null;

        return response()->json($suggester->suggest($file, $clientTs));
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedMimes = config('smartdocs.upload.allowed_mimes', self::ALLOWED_MIMES);
        $maxKb = (int) config('smartdocs.upload.max_kb', self::MAX_KB);

        $uploaded = $request->file('file') ?? $request->file('files');
        if ($uploaded === null) {
            return back()->withErrors(['file' => 'Please choose at least one file.'])->withInput();
        }
        $files = is_array($uploaded) ? $uploaded : [$uploaded];

        $title = trim((string) $request->input('title', ''));
        $categoryId = $request->input('category_id') ?: null;
        $documentDate = $request->input('document_date') ?: null;
        $fileLastModified = $request->input('file_last_modified');

        $single = count($files) === 1;

        foreach ($files as $file) {
            if (! in_array($file->getMimeType(), $allowedMimes, true)) {
                return back()->withErrors(['file' => 'Unsupported file type for ' . $file->getClientOriginalName() . '. Allowed: PDF, DOCX, TXT, PNG, JPEG.'])->withInput();
            }
            if ($file->getSize() > $maxKb * 1024) {
                return back()->withErrors(['file' => $file->getClientOriginalName() . ' exceeds the ' . number_format($maxKb / 1024, 0) . ' MB limit.'])->withInput();
            }
        }

        $count = 0;
        foreach ($files as $file) {
            $path = $file->store('documents/' . now()->format('Y/m'), 'private');

            $docTitle = $single && $title !== ''
                ? $title
                : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            $document = Document::create([
                'uploaded_by' => $request->user()->id,
                'title' => $docTitle,
                'original_filename' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'department_id' => $request->user()->department_id,
                'category_id' => $categoryId,
                'document_date' => $documentDate
                    ?? ($single ? app(UploadSuggester::class)->creationDate($file, $fileLastModified) : null),
                'processing_status' => 'pending',
            ]);

            ProcessDocument::dispatch($document->id);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'document.uploaded',
                'entity_type' => Document::class,
                'entity_id' => $document->id,
                'metadata' => ['filename' => $file->getClientOriginalName(), 'size' => $file->getSize()],
            ]);

            $count++;
        }

        return redirect()->route('documents.library')
            ->with('status', $count === 1
                ? 'Document uploaded and queued for processing.'
                : $count . ' documents uploaded and queued for processing.');
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);
        $document->load(['category', 'suggestedCategory', 'department', 'suggestedDepartment', 'uploader', 'approver', 'chunks']);
        $categories = DocumentCategory::orderBy('name')->get();

        $highlightedHtml = null;
        if ($document->extracted_text) {
            $visible = mb_substr($document->extracted_text, 0, 6000);
            $safe = e($visible);
            $snippet = trim((string) request('excerpt', ''));
            if ($snippet !== '') {
                $needle = e(mb_substr($snippet, 0, 120));
                if ($needle !== '' && str_contains($safe, $needle)) {
                    $safe = str_replace($needle, '<mark>' . $needle . '</mark>', $safe);
                }
            }
            $highlightedHtml = nl2br($safe);
        }

        $details = $document->extracted_text
            ? app(\App\Services\DetailExtractor::class)->extract($document->extracted_text)
            : [];

        $tables = app(\App\Services\StructuredTableExtractor::class)->extract($document->extracted_text);

        $summaryCheck = null;
        if ($document->summary !== null && $document->extracted_text) {
            $summaryCheck = app(\App\Services\SummaryVerifier::class)
                ->verify($document->summary, $document->extracted_text);
        }

        return view('documents.show', compact('document', 'categories', 'highlightedHtml', 'details', 'tables', 'summaryCheck'));
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        if (! Storage::disk('private')->exists($document->storage_path)) {
            abort(404, 'File not found in private storage.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'document.downloaded',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => ['filename' => $document->original_filename],
        ]);

        return Storage::disk('private')->download($document->storage_path, $document->original_filename);
    }

    public function view(Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        if (! Storage::disk('private')->exists($document->storage_path)) {
            abort(404, 'File not found in private storage.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'document.viewed',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => ['filename' => $document->original_filename],
        ]);

        // Inline disposition: PDFs and images render in the browser tab.
        return Storage::disk('private')->response(
            $document->storage_path,
            $document->original_filename,
            [],
            'inline'
        );
    }

    public function retry(Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        if ($document->processing_status !== 'failed') {
            return back()->withErrors(['document' => 'Only failed documents can be retried.']);
        }

        // No duplicate records or chunks: reuse the same row.
        $document->update(['processing_status' => 'pending', 'processing_error' => null]);
        ProcessDocument::dispatch($document->id);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'document.retry',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => [],
        ]);

        return back()->with('status', 'Document re-queued for processing.');
    }

    public function approve(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);

        $data = $request->validate([
            'category_id' => ['required', 'exists:document_categories,id'],
        ]);

        $document->update([
            'category_id' => $data['category_id'],
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'document.classification_approved',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => ['category_id' => $data['category_id']],
        ]);

        return back()->with('status', 'Classification approved.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk('private')->delete($document->storage_path);
        $docId = $document->id;
        $document->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'document.deleted',
            'entity_type' => Document::class,
            'entity_id' => $docId,
            'metadata' => [],
        ]);

        return redirect()->route('documents.library')->with('status', 'Document deleted.');
    }

    public function queue(Request $request): View
    {
        $base = $this->visibleToUser($request);
        $stats = [
            'pending' => (clone $base)->where('processing_status', 'pending')->count(),
            'processing' => (clone $base)->where('processing_status', 'processing')->count(),
            'completed' => (clone $base)->where('processing_status', 'completed')->count(),
            'failed' => (clone $base)->where('processing_status', 'failed')->count(),
        ];
        $items = (clone $base)->whereIn('processing_status', ['pending', 'processing', 'failed'])
            ->paginate(15)->withQueryString();

        return view('queue.index', compact('stats', 'items'));
    }
}
