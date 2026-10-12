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
     * @return array{category_id: ?int, score: int}|null
     */
    public function suggest(string $text): ?array
    {
        $lower = mb_strtolower($text);
        $best = null;

        foreach (DocumentCategory::all() as $category) {
            $keywords = array_filter(array_map('trim', explode(',', (string) $category->keywords)));
            if (empty($keywords)) {
                continue;
            }

            $score = 0;
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains($lower, mb_strtolower($kw))) {
                    $score += mb_strlen($kw);
                }
            }

            if ($score > 0 && ($best === null || $score > $best['score'])) {
                $best = ['category_id' => $category->id, 'score' => $score];
            }
        }

        if ($best === null) {
            $other = DocumentCategory::where('name', 'Other Documents')->first();
            if ($other) {
                return ['category_id' => $other->id, 'score' => 0];
            }
        }

        return $best;
    }
}
