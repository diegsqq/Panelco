<?php

namespace App\Services;

use App\Models\DocumentCategory;

/**
 * Rule-based destination recommender. Scores configured categories by
 * keyword matches in the extracted text. Runs without any AI provider.
 * Results are suggestions only — never auto-approved.
 */
class CategoryRecommender
{
    /**
     * @return array{category_id: ?int, score: int, matched_keywords: list<string>}|null
     */
    public function suggest(string $text): ?array
    {
        $normalizedText = ' '.$this->normalize($text).' ';
        $best = null;

        foreach (DocumentCategory::all() as $category) {
            $keywords = array_filter(array_map('trim', explode(',', (string) $category->keywords)));
            if (empty($keywords)) {
                continue;
            }

            $score = 0;
            $matched = [];
            foreach ($keywords as $kw) {
                $normalizedKeyword = $this->normalize($kw);
                if ($normalizedKeyword !== '' && ! isset($matched[$normalizedKeyword])
                    && str_contains($normalizedText, ' '.$normalizedKeyword.' ')) {
                    $matched[$normalizedKeyword] = trim($kw);
                    $score += mb_strlen($normalizedKeyword);
                }
            }

            if ($score > 0 && ($best === null || $score > $best['score'])) {
                $best = [
                    'category_id' => $category->id,
                    'score' => $score,
                    'matched_keywords' => array_values($matched),
                ];
            }
        }

        if ($best === null) {
            $other = DocumentCategory::where('name', 'Other Documents')->first();
            if ($other) {
                return ['category_id' => $other->id, 'score' => 0, 'matched_keywords' => []];
            }
        }

        return $best;
    }

    private function normalize(string $value): string
    {
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($value)) ?? '';

        return trim($normalized);
    }
}
