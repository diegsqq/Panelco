<?php

namespace App\Services;

/**
 * Rule-based important-detail extractor. Pulls action-oriented sentences
 * (repairs, resolutions, dispatches, dated commitments, measured findings)
 * from extracted text. Labeled as rule-extracted, never AI-claimed.
 *
 * @return list<string>
 */
class DetailExtractor
{
    private const ACTION_WORDS = [
        'replaced', 'repaired', 'restored', 'dispatched', 'inspected', 'resolved',
        'completed', 'installed', 'tested', 'verified', 'corrected', 'fixed',
        'conducted', 'performed', 'checked', 'found', 'recorded', 'reported',
        'approved', 'damaged', 'interrupted', 'outage',
    ];

    public function extract(string $text, int $max = 8): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $scored = [];

        foreach ((array) $sentences as $s) {
            $s = trim($s);
            $words = str_word_count($s);
            if ($words < 4 || $words > 60) {
                continue;
            }
            $lower = mb_strtolower($s);
            $score = 0;
            foreach (self::ACTION_WORDS as $w) {
                if (str_contains($lower, $w)) {
                    $score += 2;
                }
            }
            if (preg_match('/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b|\b\d+\s?(kv|kva|mw|amps?|volts?|hours?|days?)\b/i', $s)) {
                $score += 2;
            }
            if (preg_match('/\b(must|shall|will|required|deadline|immediately|asap)\b/i', $s)) {
                $score += 1;
            }
            if ($score >= 2) {
                $scored[$s] = $score;
            }
        }

        arsort($scored);

        return array_slice(array_keys($scored), 0, $max);
    }
}
