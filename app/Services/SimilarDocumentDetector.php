<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;

class SimilarDocumentDetector
{
    private const MIN_WORDS = 30;

    private const MIN_JACCARD_SCORE = 0.65;

    private const MAX_CANDIDATES = 250;

    private const MAX_WORDS_PER_DOCUMENT = 10000;

    /**
     * Find likely content matches among documents the caller is allowed to see.
     *
     * @return Collection<int, array{document: Document, similarity: int}>
     */
    public function findSimilar(string $text, Collection $candidates, ?int $excludeDocumentId = null): Collection
    {
        $source = $this->shingles($text);
        if ($source === null) {
            return collect();
        }

        return $candidates
            ->take(self::MAX_CANDIDATES)
            ->reject(fn (Document $candidate) => $candidate->id === $excludeDocumentId)
            ->map(function (Document $candidate) use ($source) {
                $candidateShingles = $this->shingles((string) $candidate->extracted_text);
                if ($candidateShingles === null) {
                    return null;
                }

                $intersection = count(array_intersect_key($source, $candidateShingles));
                $union = count($source) + count($candidateShingles) - $intersection;
                $score = $union > 0 ? $intersection / $union : 0;

                if ($score < self::MIN_JACCARD_SCORE) {
                    return null;
                }

                return [
                    'document' => $candidate,
                    'similarity' => (int) round($score * 100),
                ];
            })
            ->filter()
            ->sortByDesc('similarity')
            ->take(5)
            ->values();
    }

    /**
     * @return array<string, true>|null
     */
    private function shingles(string $text): ?array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($text), $matches);
        $words = array_slice($matches[0] ?? [], 0, self::MAX_WORDS_PER_DOCUMENT);
        if (count($words) < self::MIN_WORDS) {
            return null;
        }

        $shingles = [];
        for ($index = 0, $limit = count($words) - 2; $index < $limit; $index++) {
            $shingles[hash('xxh3', $words[$index].' '.$words[$index + 1].' '.$words[$index + 2])] = true;
        }

        return $shingles;
    }
}
