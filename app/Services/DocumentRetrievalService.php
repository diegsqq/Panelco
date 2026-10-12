<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Permission-first document retrieval.
 *
 * The backend (never the AI model) decides which documents the user may
 * access: only chunks of completed documents visible to the user are
 * searched, and only the top passages are returned for answering.
 */
class DocumentRetrievalService
{
    public const STATUS_OK = 'ok';
    public const STATUS_NO_DOCUMENTS = 'no_documents';
    public const STATUS_NO_MATCH = 'no_match';
    public const STATUS_DB_ERROR = 'db_error';

    private const STOPWORDS = [
        'the', 'and', 'for', 'with', 'from', 'that', 'this', 'what', 'which',
        'about', 'into', 'have', 'has', 'were', 'are', 'was', 'who', 'whom',
        'find', 'latest', 'show', 'tell', 'give',
    ];

    /**
     * @return array{status: string, chunks: list<array{chunk: DocumentChunk, document: Document, score: int, excerpt: string}>}
     */
    public function retrieve(User $user, string $query, ?int $limit = null): array
    {
        try {
            $visible = $this->visibleDocuments($user);

            if ((clone $visible)->where('processing_status', 'completed')->count() === 0) {
                if ((clone $visible)->count() === 0) {
                    return ['status' => self::STATUS_NO_DOCUMENTS, 'chunks' => []];
                }

                return ['status' => self::STATUS_NO_MATCH, 'chunks' => []];
            }

            $terms = $this->terms($query);
            if (empty($terms)) {
                return ['status' => self::STATUS_NO_MATCH, 'chunks' => []];
            }

            $candidates = DocumentChunk::with('document')
                ->whereHas('document', function ($q) use ($user) {
                    $this->applyVisibility($q, $user);
                    $q->where('processing_status', 'completed');
                })
                ->get();

            $scored = [];
            foreach ($candidates as $chunk) {
                $score = $this->score($chunk, $terms);
                if ($score >= (int) config('smartdocs.retrieval.min_score', 2)) {
                    $scored[] = [
                        'chunk' => $chunk,
                        'document' => $chunk->document,
                        'score' => $score,
                        'excerpt' => $this->excerpt($chunk->content, $terms),
                    ];
                }
            }

            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
            $scored = array_slice($scored, 0, $limit ?? (int) config('smartdocs.retrieval.limit', 5));

            if (empty($scored)) {
                return ['status' => self::STATUS_NO_MATCH, 'chunks' => []];
            }

            return ['status' => self::STATUS_OK, 'chunks' => $scored];
        } catch (\Throwable $e) {
            Log::error('Document retrieval failed', ['error' => $e->getMessage()]);

            return ['status' => self::STATUS_DB_ERROR, 'chunks' => []];
        }
    }

    private function visibleDocuments(User $user)
    {
        $query = Document::query();

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

    private function applyVisibility($query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }
        $allowedDeptIds = $user->departments()->pluck('departments.id')
            ->push($user->department_id)->filter()->unique()->values();
        $query->where(function ($q) use ($user, $allowedDeptIds) {
            $q->where('uploaded_by', $user->id)
                ->orWhereIn('department_id', $allowedDeptIds)
                ->orWhereNull('department_id')
                ->orWhereIn('documents.id', $user->authorizedDocuments()->pluck('documents.id'));
        });
    }

    /** @return list<string> */
    public function terms(string $query): array
    {
        $words = preg_split('/[^a-z0-9]+/i', mb_strtolower($query));
        $terms = [];
        foreach ((array) $words as $w) {
            if (strlen($w) >= 3 && ! in_array($w, self::STOPWORDS, true)) {
                $terms[] = $w;
            }
        }

        return array_values(array_unique($terms));
    }

    private function score(DocumentChunk $chunk, array $terms): int
    {
        $haystack = mb_strtolower($chunk->content);
        $score = 0;
        foreach ($terms as $term) {
            if (str_contains($haystack, $term)) {
                $score += 2 + min(substr_count($haystack, $term), 3);
            }
        }

        $title = mb_strtolower(($chunk->document->title ?? '') . ' ' . ($chunk->document->original_filename ?? ''));
        foreach ($terms as $term) {
            if (str_contains($title, $term)) {
                $score += 3;
            }
        }

        return $score;
    }

    private function excerpt(string $content, array $terms): string
    {
        $len = (int) config('smartdocs.retrieval.excerpt_chars', 300);
        $lower = mb_strtolower($content);
        $pos = null;
        foreach ($terms as $term) {
            $p = mb_strpos($lower, $term);
            if ($p !== false && ($pos === null || $p < $pos)) {
                $pos = $p;
            }
        }

        if ($pos === null) {
            return mb_substr($content, 0, $len);
        }

        $start = max(0, $pos - 80);

        return ($start > 0 ? '…' : '') . mb_substr($content, $start, $len) . (mb_strlen($content) > $start + $len ? '…' : '');
    }

    public static function statusMessage(string $status): string
    {
        return match ($status) {
            self::STATUS_NO_DOCUMENTS => 'No documents have been uploaded yet. Please upload a document before asking questions about stored files.',
            self::STATUS_NO_MATCH => "I couldn't find relevant information in the documents you are authorized to access. Try different keywords or upload the relevant document.",
            self::STATUS_DB_ERROR => "We couldn't search the document library because of a system error. Please try again later.",
            default => '',
        };
    }
}
