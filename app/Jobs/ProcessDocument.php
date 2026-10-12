<?php

namespace App\Jobs;

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

            return;
        } catch (\Throwable $e) {
            $document->update([
                'processing_status' => 'failed',
                'processing_error' => 'Unexpected processing error. Please retry or re-upload the document.',
            ]);
            $this->audit($document, 'document.processing_failed', []);

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

        $document->update($updates);
        $this->audit($document, 'document.processed', ['chunks' => $document->chunks()->count()]);
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
