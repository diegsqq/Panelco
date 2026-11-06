<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocument;
use App\Mail\DocumentApprovalNotification;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentVersion;
use App\Services\DuplicateDocumentDetector;
use App\Services\UploadSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;
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

        if ($user->role === \App\Models\User::ROLE_EMPLOYEE) {
            $query->where('uploaded_by', $user->id);
        } elseif (! $user->isAdmin()) {
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
        $validated = $this->validatedLibraryFilters($request);
        $query = $this->filteredLibraryQuery($request, $validated);
        $search = trim((string) ($validated['q'] ?? ''));
        $visibleIds = (clone $this->visibleToUser($request))->reorder()->select('documents.id');
        $categoryIds = Document::query()->whereIn('id', $visibleIds)
            ->where(function ($q) {
                $q->whereNotNull('category_id')->orWhereNotNull('suggested_category_id');
            })
            ->selectRaw('COALESCE(category_id, suggested_category_id) as category_id')
            ->distinct()->pluck('category_id');
        $departmentIds = Document::query()->whereIn('id', $visibleIds)
            ->where(function ($q) {
                $q->whereNotNull('department_id')->orWhereNotNull('suggested_department_id');
            })
            ->selectRaw('COALESCE(department_id, suggested_department_id) as department_id')
            ->distinct()->pluck('department_id');
        $uploaderIds = Document::query()->whereIn('id', $visibleIds)
            ->whereNotNull('uploaded_by')->distinct()->pluck('uploaded_by');
        $categories = DocumentCategory::whereIn('id', $categoryIds)->orderBy('name')->get();
        $departments = Department::whereIn('id', $departmentIds)->orderBy('name')->get();
        $uploaders = \App\Models\User::whereIn('id', $uploaderIds)->orderBy('name')->get();

        $sort = $validated['sort'] ?? 'newest';
        $documents = $query->paginate(15)->withQueryString();

        return view('documents.index', compact(
            'documents', 'search', 'categories', 'departments', 'uploaders', 'validated', 'sort'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $this->validatedLibraryFilters($request);
        $query = $this->filteredLibraryQuery($request, $validated);

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Could not open the CSV output stream.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Title',
                'Original filename',
                'Department',
                'Category',
                'Processing status',
                'Document date',
                'Uploaded by',
                'File size (bytes)',
            ]);

            $query->chunk(500, function ($documents) use ($output): void {
                foreach ($documents as $document) {
                    fputcsv($output, array_map($this->spreadsheetSafeValue(...), [
                        $document->title ?: $document->original_filename,
                        $document->original_filename,
                        $document->department?->name ?? $document->suggestedDepartment?->name,
                        $document->category?->name ?? $document->suggestedCategory?->name,
                        $document->processing_status,
                        $document->document_date?->format('Y-m-d'),
                        $document->uploader?->name,
                        $document->file_size,
                    ]));
                }
            });

            fclose($output);
        }, 'smartdocs-documents-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLibraryFilters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'processing', 'completed', 'failed'])],
            'category_id' => ['nullable', 'integer', 'exists:document_categories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'uploader_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'alphabetical'])],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function filteredLibraryQuery(Request $request, array $validated): Builder
    {
        $query = $this->visibleToUser($request)->with(['suggestedCategory', 'suggestedDepartment']);
        $search = trim((string) ($validated['q'] ?? ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('original_filename', 'like', $like)
                    ->orWhere('keywords', 'like', $like)
                    ->orWhere('extracted_text', 'like', $like)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('suggestedCategory', fn ($c) => $c->where('name', 'like', $like));
            });
        }

        if (! empty($validated['category_id'])) {
            $query->whereRaw('COALESCE(documents.category_id, documents.suggested_category_id) = ?', [
                (int) $validated['category_id'],
            ]);
        }

        if (! empty($validated['department_id'])) {
            $query->whereRaw('COALESCE(documents.department_id, documents.suggested_department_id) = ?', [
                (int) $validated['department_id'],
            ]);
        }

        if (! empty($validated['uploader_id'])) {
            $query->where('uploaded_by', $validated['uploader_id']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('document_date', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('document_date', '<=', $validated['date_to']);
        }

        if (! empty($validated['status'])) {
            $query->where('processing_status', $validated['status']);
        }

        $sort = $validated['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'alphabetical' => $query->orderByRaw("COALESCE(NULLIF(title, ''), original_filename) asc")->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query;
    }

    private function spreadsheetSafeValue(mixed $value): string
    {
        $value = (string) ($value ?? '');
        if (preg_match('/^[\t\r\n ]*[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    public function create(): View
    {
        $categories = DocumentCategory::orderBy('name')->get();

        return view('documents.create', compact('categories'));
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        $document->load(['category', 'department']);
        $categories = DocumentCategory::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('documents.edit', compact('document', 'categories', 'departments'));
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'file' => ['sometimes', 'required', 'file', 'mimes:' . implode(',', config('smartdocs.upload.allowed_extensions')), 'max:' . config('smartdocs.upload.max_kb', self::MAX_KB)],
            'title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:document_categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'document_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'sender' => ['nullable', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
        ]);

        // Department drives who can see the document — only managers/admins
        // may move it to another department.
        $payload = [
            'title' => trim((string) ($data['title'] ?? '')) !== '' ? $data['title'] : null,
            'category_id' => $data['category_id'] ?? null,
            'document_date' => $data['document_date'] ?? null,
            'expiration_date' => $data['expiration_date'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'sender' => $data['sender'] ?? null,
            'recipient' => $data['recipient'] ?? null,
        ];
        if ($request->user()->isManager()) {
            $payload['department_id'] = $data['department_id'] ?? null;
        }

        $file = $request->file('file');
        if ($file !== null) {
            $duplicateDetector = app(DuplicateDocumentDetector::class);
            $fileHash = $duplicateDetector->hash($file);
            $isCurrentFile = $document->file_hash !== null && hash_equals($document->file_hash, $fileHash);

            if (! $isCurrentFile && $duplicateDetector->exists($fileHash, $file->getSize())) {
                $currentDocument = $document->fresh();
                $isCurrentFile = $currentDocument?->file_hash !== null
                    && hash_equals($currentDocument->file_hash, $fileHash);

                if (! $isCurrentFile) {
                    return back()->withErrors(['file' => 'This exact file is already stored in the library.'])->withInput();
                }
            }
            if ($isCurrentFile) {
                $file = null;
            }
        }

        if ($file !== null) {
            $newPath = $file->store('documents/' . now()->format('Y/m'), 'private');
            if (! is_string($newPath)) {
                abort(500, 'The replacement file could not be saved to private storage.');
            }

            try {
                DB::transaction(function () use ($document, $payload, $file, $fileHash, $newPath, $request) {
                    DocumentVersion::create([
                        'document_id' => $document->id,
                        'version_number' => $document->version_number,
                        'uploaded_by' => $document->current_version_uploaded_by ?? $document->uploaded_by,
                        'original_filename' => $document->original_filename,
                        'storage_path' => $document->storage_path,
                        'file_hash' => $document->file_hash,
                        'mime_type' => $document->mime_type,
                        'file_size' => $document->file_size,
                    ]);

                    $document->chunks()->delete();
                    $document->update(array_merge($payload, [
                        'original_filename' => $file->getClientOriginalName(),
                        'storage_path' => $newPath,
                        'file_hash' => $fileHash,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'version_number' => $document->version_number + 1,
                        'current_version_uploaded_by' => $request->user()->id,
                        'document_type' => null,
                        'summary' => null,
                        'keywords' => null,
                        'extracted_text' => null,
                        'processing_status' => 'pending',
                        'processing_error' => null,
                        'classification_confidence' => null,
                        'suggested_category_id' => null,
                        'suggested_category_reason' => null,
                        'suggested_department_id' => null,
                        'suggested_department_reason' => null,
                        'department_classification_confidence' => null,
                        'classification_review_status' => 'pending',
                        'classification_reviewed_at' => null,
                        'classification_reviewed_by' => null,
                        'classification_review_note' => null,
                        'approved_at' => null,
                        'approved_by' => null,
                        'approval_status' => 'pending',
                        'approval_reviewed_at' => null,
                        'approval_reviewed_by' => null,
                        'approval_review_note' => null,
                    ]));
                });
            } catch (\Throwable $exception) {
                Storage::disk('private')->delete($newPath);
                throw $exception;
            }
        } else {
            $document->update($payload);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'document.updated',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => ['fields' => array_keys($data)],
        ]);

        if ($file !== null) {
            ProcessDocument::dispatch($document->id, $document->version_number);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'document.version_uploaded',
                'entity_type' => Document::class,
                'entity_id' => $document->id,
                'metadata' => [
                    'version_number' => $document->version_number,
                    'previous_version_number' => $document->version_number - 1,
                    'filename' => $document->original_filename,
                    'size' => $document->file_size,
                ],
            ]);
        }

        return redirect()->route('documents.show', $document)->with('status', 'Document updated.');
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
        $duplicates = [];
        $duplicateDetector = app(DuplicateDocumentDetector::class);
        foreach ($files as $file) {
            $fileHash = $duplicateDetector->hash($file);
            if ($duplicateDetector->exists($fileHash, $file->getSize())) {
                $duplicates[] = $file->getClientOriginalName();

                continue;
            }

            $path = $file->store('documents/' . now()->format('Y/m'), 'private');

            $docTitle = $single && $title !== '' ? $title : null;

            $document = Document::create([
                'uploaded_by' => $request->user()->id,
                'title' => $docTitle,
                'original_filename' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'file_hash' => $fileHash,
                'current_version_uploaded_by' => $request->user()->id,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'department_id' => $request->user()->department_id,
                'category_id' => $categoryId,
                'document_date' => $documentDate
                    ?? ($single ? app(UploadSuggester::class)->creationDate($file, $fileLastModified) : null),
                'processing_status' => 'pending',
            ]);

            ProcessDocument::dispatch($document->id, $document->version_number);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'document.uploaded',
                'entity_type' => Document::class,
                'entity_id' => $document->id,
                'metadata' => ['filename' => $file->getClientOriginalName(), 'size' => $file->getSize()],
            ]);

            $count++;
        }

        $response = redirect()->route('documents.library');
        if ($count > 0) {
            $response->with('status', $count === 1
                ? 'Document uploaded and queued for processing.'
                : $count . ' documents uploaded and queued for processing.');
        }
        if ($duplicates !== []) {
            $response->with('warning', ($count === 0 ? 'No new documents were uploaded. ' : '')
                . 'Skipped identical file(s): ' . implode(', ', $duplicates) . '.');
        }

        return $response;
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);
        $document->load(['category', 'suggestedCategory', 'department', 'suggestedDepartment', 'uploader', 'currentVersionUploader', 'approver', 'approvalReviewer', 'classificationReviewer', 'chunks', 'versions.uploader']);
        $similarCandidates = $this->visibleToUser(request())
            ->where('processing_status', 'completed')
            ->whereNotNull('extracted_text')
            ->where('id', '!=', $document->id)
            ->orderByDesc('created_at')
            ->limit(250)
            ->get(['id', 'title', 'original_filename', 'document_date', 'extracted_text']);
        $similarDocuments = app(\App\Services\SimilarDocumentDetector::class)
            ->findSimilar((string) $document->extracted_text, $similarCandidates, (int) $document->id);
        $categories = DocumentCategory::orderBy('name')->get();
        $departmentQuery = Department::orderBy('name');
        if (! auth()->user()->isAdmin()) {
            $allowedDepartmentIds = auth()->user()->departments()->pluck('departments.id')
                ->push(auth()->user()->department_id)->filter()->unique()->values();
            $departmentQuery->whereIn('id', $allowedDepartmentIds);
        }
        $departments = $departmentQuery->get();

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

        $detailExtractor = app(\App\Services\DetailExtractor::class);
        $details = $document->extracted_text ? $detailExtractor->extract($document->extracted_text) : [];
        $correctiveActions = $document->extracted_text ? $detailExtractor->extractCorrectiveActions($document->extracted_text) : [];
        $details = array_values(array_diff($details, $correctiveActions));

        $tables = app(\App\Services\StructuredTableExtractor::class)->extract($document->extracted_text);

        $summaryCheck = null;
        if ($document->summary !== null && $document->extracted_text) {
            $summaryCheck = app(\App\Services\SummaryVerifier::class)
                ->verify($document->summary, $document->extracted_text);
        }

        return view('documents.show', compact('document', 'categories', 'departments', 'highlightedHtml', 'details', 'correctiveActions', 'tables', 'summaryCheck', 'similarDocuments'));
    }

    public function downloadVersion(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->authorize('download', $document);
        abort_unless((int) $version->document_id === (int) $document->id, 404);

        if (! Storage::disk('private')->exists($version->storage_path)) {
            abort(404, 'Version file not found in private storage.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'document.version_downloaded',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => [
                'version_number' => $version->version_number,
                'filename' => $version->original_filename,
            ],
        ]);

        return Storage::disk('private')->download($version->storage_path, $version->original_filename);
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

    public function toPdf(Document $document): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('download', $document);

        if (! \Illuminate\Support\Facades\Storage::disk('private')->exists($document->storage_path)) {
            abort(404, 'File not found in private storage.');
        }

        $mime = $document->mime_type ?? '';
        $content = \Illuminate\Support\Facades\Storage::disk('private')->get($document->storage_path);

        if ($mime === 'application/pdf') {
            return \Illuminate\Support\Facades\Storage::disk('private')->download($document->storage_path, $document->original_filename);
        }

        $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;} pre{white-space:pre-wrap;word-wrap:break-word;} img{max-width:100%;}</style></head><body>';

        if (str_starts_with($mime, 'image/')) {
            $html .= '<img src="data:' . $mime . ';base64,' . base64_encode($content) . '" alt="Document">';
        } elseif ($mime === 'text/plain') {
            $html .= '<pre>' . e($content) . '</pre>';
        } elseif ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            $tmp = tempnam(sys_get_temp_dir(), 'docx');
            file_put_contents($tmp, $content);
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($tmp);
                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
                ob_start();
                $writer->save('php://output');
                $docHtml = ob_get_clean();
                // Strip outer html/head/body wrappers if present, keep inner content.
                $html .= $docHtml;
            } finally {
                @unlink($tmp);
            }
        } else {
            abort(415, 'Unsupported file type for PDF conversion.');
        }

        $html .= '</body></html>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = pathinfo($document->original_filename, PATHINFO_FILENAME) . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function thumb(Document $document): StreamedResponse
    {
        // Lightweight image preview for lists/detail pages: same access
        // rules as view(), but no audit log (thumbnails fire on every page
        // load) and short browser cache.
        $this->authorize('download', $document);

        if (! Storage::disk('private')->exists($document->storage_path)) {
            abort(404, 'File not found in private storage.');
        }

        return Storage::disk('private')->response(
            $document->storage_path,
            $document->original_filename,
            ['Cache-Control' => 'private, max-age=3600'],
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
        ProcessDocument::dispatch($document->id, $document->version_number);

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
            'category_id' => ['nullable', 'required_without:department_id', 'exists:document_categories,id'],
            'department_id' => ['nullable', 'required_without:category_id', 'exists:departments,id'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $request->user()->isAdmin() && ! empty($data['department_id'])
            && ! $request->user()->canAccessDepartment((int) $data['department_id'])) {
            return back()->withErrors([
                'department_id' => 'You cannot assign this document to a department you cannot access.',
            ])->withInput();
        }

        $reviewedAt = now();
        $updates = [
            'classification_review_status' => 'approved',
            'classification_reviewed_at' => $reviewedAt,
            'classification_reviewed_by' => $request->user()->id,
            'classification_review_note' => $data['review_note'] ?? null,
        ];
        if (array_key_exists('category_id', $data)) {
            $updates['category_id'] = $data['category_id'];
        }
        if (array_key_exists('department_id', $data)) {
            $updates['department_id'] = $data['department_id'];
        }
        $document->update($updates);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'document.classification_approved',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => [
                'suggested_category_id' => $document->suggested_category_id,
                'suggested_department_id' => $document->suggested_department_id,
                'suggested_category_reason' => $document->suggested_category_reason,
                'suggested_department_reason' => $document->suggested_department_reason,
                'category_confidence' => $document->classification_confidence,
                'department_confidence' => $document->department_classification_confidence,
                'category_id' => $document->category_id,
                'department_id' => $document->department_id,
                'review_note' => $data['review_note'] ?? null,
            ],
        ]);

        return back()->with('status', 'Classification review saved.');
    }

    public function rejectClassification(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);

        if ($document->suggested_category_id === null && $document->suggested_department_id === null) {
            return back()->withErrors(['classification' => 'There are no suggestions to reject.']);
        }

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviewedAt = now();
        $document->update([
            'classification_review_status' => 'rejected',
            'classification_reviewed_at' => $reviewedAt,
            'classification_reviewed_by' => $request->user()->id,
            'classification_review_note' => $data['review_note'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'document.classification_rejected',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => [
                'suggested_category_id' => $document->suggested_category_id,
                'suggested_department_id' => $document->suggested_department_id,
                'category_confidence' => $document->classification_confidence,
                'department_confidence' => $document->department_classification_confidence,
                'suggested_category_reason' => $document->suggested_category_reason,
                'suggested_department_reason' => $document->suggested_department_reason,
                'review_note' => $data['review_note'] ?? null,
            ],
        ]);

        return back()->with('status', 'Classification suggestions rejected.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $docId = $document->id;
        $document->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'document.deleted',
            'entity_type' => Document::class,
            'entity_id' => $docId,
            'metadata' => [],
        ]);

        return redirect()->route('documents.library')->with('status', 'Document moved to the recycle bin.');
    }

    public function trash(Request $request): View
    {
        abort_unless($request->user()->isManager(), 403);

        $query = Document::onlyTrashed()
            ->with(['department', 'uploader'])
            ->latest('deleted_at');
        $user = $request->user();
        if (! $user->isAdmin()) {
            $allowedDeptIds = $user->departments()->pluck('departments.id')
                ->push($user->department_id)->filter()->unique()->values();
            $query->where(function ($q) use ($user, $allowedDeptIds) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereIn('department_id', $allowedDeptIds)
                    ->orWhereNull('department_id')
                    ->orWhereIn('id', $user->authorizedDocuments()->withTrashed()->pluck('documents.id'));
            });
        }

        $documents = $query->paginate(20);

        return view('documents.trash', compact('documents'));
    }

    public function restore(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('restore', $document);
        abort_unless($document->trashed(), 404);

        $storagePaths = $document->versions()->pluck('storage_path')
            ->prepend($document->storage_path);
        foreach ($storagePaths as $storagePath) {
            if (! Storage::disk('private')->exists($storagePath)) {
                return back()->withErrors([
                    'document' => 'This document cannot be restored because one or more stored files are missing.',
                ]);
            }
        }

        $document->restore();
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'document.restored',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => [],
        ]);

        return redirect()->route('documents.trash')->with('status', 'Document restored.');
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

    public function approvals(Request $request): View
    {
        abort_unless($request->user()->isManager(), 403);

        $documents = $this->visibleToUser($request)
            ->where('processing_status', 'completed')
            ->where('approval_status', 'pending')
            ->with(['uploader', 'department'])
            ->orderBy('created_at')
            ->paginate(15);

        return view('documents.approvals', compact('documents'));
    }

    public function classificationReviews(Request $request): View
    {
        abort_unless($request->user()->isManager(), 403);

        $documents = $this->visibleToUser($request)
            ->where('processing_status', 'completed')
            ->where('classification_review_status', 'pending')
            ->where(function ($query) {
                $query->whereNotNull('suggested_category_id')
                    ->orWhereNotNull('suggested_department_id');
            })
            ->with(['uploader', 'department', 'suggestedCategory', 'suggestedDepartment'])
            ->orderBy('created_at')
            ->paginate(15);

        return view('documents.classification-reviews', compact('documents'));
    }

    public function approveDocument(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);

        if ($document->processing_status !== 'completed') {
            return back()->withErrors(['approval' => 'Only processed documents can be approved.']);
        }

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $reviewedAt = now();
        $document->update([
            'approval_status' => 'approved',
            'approved_at' => $reviewedAt,
            'approved_by' => $request->user()->id,
            'approval_reviewed_at' => $reviewedAt,
            'approval_reviewed_by' => $request->user()->id,
            'approval_review_note' => $data['review_note'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'document.approved',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => ['review_note' => $data['review_note'] ?? null],
        ]);
        $this->notifyUploaderOfApproval($document, $request, true, $data['review_note'] ?? null);

        return back()->with('status', 'Document approved.');
    }

    public function rejectDocument(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);

        if ($document->processing_status !== 'completed') {
            return back()->withErrors(['approval' => 'Only processed documents can be rejected.']);
        }

        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);
        $reviewedAt = now();
        $document->update([
            'approval_status' => 'rejected',
            'approved_at' => null,
            'approved_by' => null,
            'approval_reviewed_at' => $reviewedAt,
            'approval_reviewed_by' => $request->user()->id,
            'approval_review_note' => $data['review_note'],
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'document.rejected',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => ['review_note' => $data['review_note']],
        ]);
        $this->notifyUploaderOfApproval($document, $request, false, $data['review_note']);

        return back()->with('status', 'Document rejected.');
    }

    private function notifyUploaderOfApproval(Document $document, Request $request, bool $approved, ?string $reviewNote): void
    {
        $uploader = $document->uploader;
        if (! $uploader || ! $uploader->notify_doc_approval) {
            return;
        }

        try {
            Mail::to($uploader->email)->send(new DocumentApprovalNotification(
                $document,
                $request->user()->name,
                $approved,
                $reviewNote
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
