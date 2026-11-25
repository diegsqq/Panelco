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
    public function retrieve(User $user, string $query, ?int $limit = null, ?int $documentId = null): array
    {
        try {
            $visible = $this->visibleDocuments($user, $documentId);

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
                ->whereHas('document', function ($q) use ($user, $documentId) {
                    $this->applyVisibility($q, $user, $documentId);
                    $q->where('processing_status', 'completed');
                })
                ->get();

            if ($documentId !== null) {
                $candidates = $candidates->sortBy('chunk_index')
                    ->take($limit ?? (int) config('smartdocs.retrieval.limit', 5))
                    ->values();
                if ($candidates->isEmpty()) {
                    return ['status' => self::STATUS_NO_MATCH, 'chunks' => []];
                }

                return [
                    'status' => self::STATUS_OK,
                    'chunks' => $candidates->map(fn (DocumentChunk $chunk) => [
                        'chunk' => $chunk,
                        'document' => $chunk->document,
                        'score' => 1,
                        'excerpt' => $chunk->content,
                    ])->all(),
                ];
            }

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

    /**
     * Full current text for a document-scoped question. Unlike retrieve(),
     * this does not keyword-filter or take only the first few chunks: the AI
     * sees the whole visible document, bounded by the configured char budget.
     *
     * @return array{status: string, chunks: list<array{chunk: DocumentChunk, document: Document, score: int, excerpt: string}>}
     */
    public function fullDocument(User $user, Document $document): array
    {
        try {
            $visible = $this->visibleDocuments($user, $document->id)->whereKey($document->id);

            if (! $visible->exists()) {
                return ['status' => self::STATUS_NO_DOCUMENTS, 'chunks' => []];
            }

            $text = $this->fullDocumentText($document);
            if ($text === '') {
                return ['status' => self::STATUS_NO_MATCH, 'chunks' => []];
            }

            $limit = max(1000, (int) config('smartdocs.retrieval.full_document_chars', 24000));
            if (mb_strlen($text) > $limit) {
                $text = mb_substr($text, 0, $limit)
                    . "\n\n[Document continues beyond the AI context budget.]";
            }

            // Synthetic chunk keeps the existing passage/citation shape without
            // writing a generated row into the search index.
            $chunk = new DocumentChunk(['chunk_index' => 0, 'page_number' => null]);
            $chunk->setRelation('document', $document);

            return [
                'status' => self::STATUS_OK,
                'chunks' => [[
                    'chunk' => $chunk,
                    'document' => $document,
                    'score' => 1,
                    'excerpt' => $text,
                ]],
            ];
        } catch (\Throwable $e) {
            Log::error('Full document retrieval failed', ['error' => $e->getMessage()]);

            return ['status' => self::STATUS_DB_ERROR, 'chunks' => []];
        }
    }

    public function accessibleDocuments(User $user)
    {
        return $this->visibleDocuments($user);
    }

    private function fullDocumentText(Document $document): string
    {
        // Edited content wins: OnlyOffice and the in-app editor both persist
        // the current document here, while chunks can lag behind a save.
        $content = trim((string) $document->content);
        if ($content !== '') {
            return $this->htmlToText($content);
        }

        $extracted = trim((string) $document->extracted_text);
        if ($extracted !== '') {
            return $extracted;
        }

        return $document->chunks()
            ->orderBy('chunk_index')
            ->pluck('content')
            ->implode("\n\n");
    }

    private function htmlToText(string $html): string
    {
        $withBreaks = preg_replace(
            '/<\s*br\s*\/?>|<\/\s*(?:p|div|li|tr|h[1-6]|blockquote|pre)\s*>/i',
            "\n",
            $html
        ) ?? $html;

        $text = html_entity_decode(strip_tags($withBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n[ \t]+/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function visibleDocuments(User $user, ?int $documentId = null)
    {
        $query = Document::query();
        if ($documentId !== null) {
            $query->whereKey($documentId);
        }

        if ($user->role === User::ROLE_EMPLOYEE) {
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

    private function applyVisibility($query, User $user, ?int $documentId = null): void
    {
        if ($documentId !== null) {
            $query->where('documents.id', $documentId);
        }

        if ($user->isAdmin()) {
            return;
        }
        if ($user->role === User::ROLE_EMPLOYEE) {
            $query->where('documents.uploaded_by', $user->id);

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

    /**
     * Short excerpts around the question terms. Used when the model reports
     * that the document has no direct answer, so the user still sees the
     * closest supporting text instead of a bare refusal.
     *
     * @return list<string>
     */
    public function snippets(string $text, string $query, int $max = 3, int $chars = 260): array
    {
        $terms = $this->terms($query);
        $lower = mb_strtolower($text);
        $snippets = [];

        foreach ($terms as $term) {
            $pos = mb_strpos($lower, $term);
            if ($pos === false) {
                continue;
            }

            $start = max(0, $pos - 80);
            $snippet = ($start > 0 ? '…' : '')
                . mb_substr($text, $start, $chars)
                . (mb_strlen($text) > $start + $chars ? '…' : '');

            if (! in_array($snippet, $snippets, true)) {
                $snippets[] = $snippet;
            }
            if (count($snippets) >= $max) {
                break;
            }
        }

        if ($snippets === [] && trim($text) !== '') {
            $snippets[] = mb_substr(trim($text), 0, $chars)
                . (mb_strlen(trim($text)) > $chars ? '…' : '');
        }

        return $snippets;
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
