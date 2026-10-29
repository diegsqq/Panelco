<?php

namespace App\Jobs;

use App\Mail\DocumentStatusNotification;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\Ai\AiProvider;
use App\Services\CategoryRecommender;
use App\Services\DocumentChunker;
use App\Services\DocumentExtractionService;
use App\Services\ExtractionFailedException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $documentId) {}

    public function handle(DocumentExtractionService $extractor, DocumentChunker $chunker, CategoryRecommender $recommender, AiProvider $ai): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->processing_status === 'completed') {
            return;
        }

        $document->update(['processing_status' => 'processing', 'processing_error' => null]);

        try {
            $result = $extractor->extract($document);
        } catch (ExtractionFailedException $e) {
            $document->update(['processing_status' => 'failed', 'processing_error' => $e->getMessage()]);
            $this->audit($document, 'document.processing_failed', ['error' => $e->getMessage()]);
            $this->notifyUploader($document, true);

            return;
        } catch (\Throwable $e) {
            $document->update([
                'processing_status' => 'failed',
                'processing_error' => 'Unexpected processing error. Please retry or re-upload the document.',
            ]);
            $this->audit($document, 'document.processing_failed', []);
            $this->notifyUploader($document, true);

            return;
        }

        $chunker->chunk($document, $result['text'], $result['pages']);

        // Rule-based suggestion always runs (no AI needed). AI refines it
        // and adds summary/keywords only when a provider is configured.
        $suggestion = $recommender->suggest($result['text']);
        $updates = [
            'extracted_text' => $result['text'],
            'document_type' => $document->document_type ?? $this->guessType($document),
            'suggested_category_id' => $suggestion['category_id'] ?? null,
            'processing_status' => 'completed',
            'processing_error' => null,
        ];

        // Fall back to the uploader's department when none was recorded.
        if ($document->department_id === null && $document->uploaded_by !== null) {
            $document->loadMissing('uploader');
            if ($document->uploader?->department_id !== null) {
                $updates['department_id'] = $document->uploader->department_id;
            }
        }

        // Fall back to the processing date when no document date was detected.
        if ($document->document_date === null) {
            $updates['document_date'] = ($document->created_at ?? now())->toDateString();
        }

        if ($ai->available()) {
            try {
                $analysis = $ai->analyze($result['text']);
                if ($analysis !== null) {
                    $updates['summary'] = $analysis['summary'];
                    $updates['keywords'] = implode(', ', $analysis['keywords']);
                }

                $names = DocumentCategory::pluck('name')->all();
                $classified = $ai->classify($result['text'], $names);
                if ($classified !== null && $classified['category'] !== null) {
                    $match = DocumentCategory::where('name', $classified['category'])->first();
                    if ($match) {
                        $updates['suggested_category_id'] = $match->id;
                        $updates['classification_confidence'] = $classified['confidence'];
                    }
                }
            } catch (\Throwable $e) {
                // AI failure must not fail extraction: keep rule suggestion.
            }
        }

        // Rule-based keyword fallback so documents still get useful keywords
        // when no AI provider is configured.
        if (! isset($updates['keywords']) || $updates['keywords'] === null || trim((string) $updates['keywords']) === '') {
            $updates['keywords'] = $this->fallbackKeywords($result['text']);
        }

        $document->update($updates);
        $this->audit($document, 'document.processed', ['chunks' => $document->chunks()->count()]);
        $this->notifyUploader($document, false);
    }

    /**
     * Emails the uploader when processing finishes, if their
     * notification preference is on. Never breaks the job.
     */
    private function notifyUploader(Document $document, bool $failed): void
    {
        try {
            if ($document->uploaded_by === null) {
                return;
            }
            $uploader = $document->uploader;
            if (! $uploader) {
                return;
            }

            $prefEnabled = $failed ? $uploader->notify_doc_failed : $uploader->notify_doc_done;
            if (! $prefEnabled) {
                return;
            }

            Mail::to($uploader->email)->send(new DocumentStatusNotification($document, $failed));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function guessType(Document $document): ?string
    {
        return match (strtolower(pathinfo($document->original_filename, PATHINFO_EXTENSION))) {
            'pdf' => 'PDF',
            'docx' => 'Word Document',
            'txt' => 'Text File',
            'png', 'jpg', 'jpeg' => 'Image',
            default => null,
        };
    }

    private function fallbackKeywords(string $text): ?string
    {
        $stop = ['the', 'and', 'for', 'with', 'that', 'this', 'from', 'have', 'will', 'your', 'about', 'into', 'their', 'there', 'which', 'would', 'could', 'should', 'other', 'than', 'then', 'them', 'these', 'those', 'such', 'only', 'also', 'more', 'most', 'some', 'each', 'every', 'very', 'just', 'over', 'under', 'between', 'through', 'during', 'before', 'after', 'above', 'below', 'again', 'further', 'once', 'here', 'when', 'where', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'can', 'will', 'just', 'don', 'should', 'now'];

        preg_match_all('/\b[\p{L}]{4,}\b/u', mb_strtolower($text), $m);
        $counts = array_count_values($m[0] ?? []);
        foreach ($stop as $w) {
            unset($counts[$w]);
        }
        arsort($counts);

        $top = array_slice(array_keys($counts), 0, 6);

        return empty($top) ? null : implode(', ', $top);
    }

    private function audit(Document $document, string $action, array $metadata): void
    {
        AuditLog::create([
            'user_id' => null,
            'action' => $action,
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'metadata' => $metadata,
        ]);
    }
}
