<?php

namespace App\Services;

use App\Models\Department;

class DepartmentRecommender
{
    /**
     * @return array{department_id: int, confidence: float}|null
     */
    public function suggest(string $text): ?array
    {
        $normalizedText = ' '.$this->normalize($text).' ';
        $scores = [];

        foreach (Department::query()->whereNotNull('keywords')->get() as $department) {
            $score = 0;
            $matched = [];

            foreach (explode(',', (string) $department->keywords) as $keyword) {
                $normalizedKeyword = $this->normalize($keyword);
                if ($normalizedKeyword !== '' && ! isset($matched[$normalizedKeyword])
                    && str_contains($normalizedText, ' '.$normalizedKeyword.' ')) {
                    $matched[$normalizedKeyword] = true;
                    $score += mb_strlen($normalizedKeyword);
                }
            }

            if ($score > 0) {
                $scores[$department->id] = $score;
            }
        }

        if ($scores === []) {
            return null;
        }

        arsort($scores);
        $departmentId = (int) array_key_first($scores);
        $bestScore = reset($scores);
        $secondScore = count($scores) > 1 ? array_values($scores)[1] : 0;

        if ($bestScore === $secondScore) {
            return null;
        }

        $confidence = min(0.95, ($bestScore / ($bestScore + 20)) * (($bestScore - $secondScore) / $bestScore));

        return [
            'department_id' => $departmentId,
            'confidence' => round($confidence, 4),
        ];
    }

    private function normalize(string $value): string
    {
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($value)) ?? '';

        return trim($normalized);
    }
}
