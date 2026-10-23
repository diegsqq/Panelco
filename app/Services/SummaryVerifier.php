<?php

namespace App\Services;

/**
 * Deterministic summary fact-check (no AI involved). Pulls every numeric
 * claim out of an AI-written summary and checks it against the document's
 * extracted text. A summary is "verified" only when each number in it
 * appears in the source, allowing for formatting variants:
 *
 *   - thousands separators ("3,000,000" vs "3000000" from spreadsheet text)
 *   - percent spacing ("12%" vs "12 %")
 *   - word magnitudes ("₱3 million" vs "₱3,000,000" vs "3000000")
 *
 * Word-form numbers without a magnitude ("four years") are not checked.
 */
class SummaryVerifier
{
    private const MAGNITUDES = [
        'million' => 1_000_000,
        'billion' => 1_000_000_000,
        'trillion' => 1_000_000_000_000,
    ];

    /**
     * @return array{verified: bool, checked: list<string>, unsupported: list<string>}
     */
    public function verify(string $summary, string $text): array
    {
        $claims = $this->claims($summary);
        if ($claims === []) {
            return ['verified' => false, 'checked' => [], 'unsupported' => []];
        }

        $haystack = $this->normalize($text);
        $unsupported = [];

        foreach ($claims as $claim) {
            if (! $this->found($claim, $haystack)) {
                $unsupported[] = $claim;
            }
        }

        return [
            'verified' => $unsupported === [],
            'checked' => $claims,
            'unsupported' => $unsupported,
        ];
    }

    /**
     * Numeric claims: digits with optional separators/decimals, plus an
     * optional trailing % or million/billion/trillion word. Never ends
     * in a comma.
     *
     * @return list<string>
     */
    private function claims(string $summary): array
    {
        preg_match_all(
            '/\d(?:[\d,]*\d)?(?:\.\d+)?\s?(?:%|million|billion|trillion)?/iu',
            $this->normalize($summary),
            $m
        );

        return array_values(array_unique(array_map('trim', $m[0])));
    }

    private function found(string $claim, string $haystack): bool
    {
        foreach ($this->candidates($claim) as $variant) {
            if (preg_match($this->pattern($variant), $haystack)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Equivalent written forms of one claim. Any variant found in the
     * document counts — same figure, different formatting.
     *
     * @return list<string>
     */
    private function candidates(string $claim): array
    {
        $variants = [$claim];

        // "3 million" / "1.3 million" → full digit forms too.
        if (preg_match('/^([\d,]+(?:\.\d+)?)\s(million|billion|trillion)$/iu', $claim, $m)) {
            $factor = self::MAGNITUDES[strtolower($m[2])];
            $full = (float) str_replace(',', '', $m[1]) * $factor;
            $formatted = number_format($full, 0, '.', ',');
            $variants[] = $formatted;
            $variants[] = str_replace(',', '', $formatted);

            return array_values(array_unique($variants));
        }

        if (str_contains($claim, ',')) {
            // Spreadsheet extraction drops separators: "3,000,000" → "3000000".
            $variants[] = str_replace(',', '', $claim);
        } elseif (preg_match('/^\d{4,}$/', $claim)) {
            // Bare 4+ digit figure may appear grouped in the document.
            $variants[] = number_format((int) $claim);
        }

        return array_values(array_unique($variants));
    }

    private function pattern(string $variant): string
    {
        // Digit-boundary guards so "19" never matches inside "0.19",
        // "0.19" never inside "10.19", and "5.98" never inside "5.980".
        return '/(?<!\d)(?<!\.)' . preg_quote($variant, '/') . '(?!\d)(?!\.\d)(?!,\d)/iu';
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        // "5.98 %" → "5.98%"
        $value = preg_replace('/\s*%\s*/u', '%', $value) ?? $value;
        // "₱ 3,000,000" → "₱3,000,000"
        $value = preg_replace('/(₱|PHP)\s+(?=\d)/u', '$1', $value) ?? $value;

        return $value;
    }
}
