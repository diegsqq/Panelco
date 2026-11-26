<?php

namespace App\Services;

/**
 * Turns a short chat instruction such as "change X to Y" into an exact
 * find-and-replace pair. No AI key is needed: the phrase is matched against
 * the document's own text, so the editor receives the exact casing and
 * spacing that appears in the document.
 */
class DocumentEditService
{
    private const VERBS = 'change|replace|set|edit|make';

    /**
     * @return array{searchString: string, replaceString: string, matchCase: bool, occurrences: int}|null
     */
    public function plan(string $instruction, string $documentText): ?array
    {
        $question = trim($instruction);

        if (! preg_match(
            '/^(?:please\s+|kindly\s+)?(?:' . self::VERBS . ')\s+(.+?)\s+(?:to|with|into|as)\s+(.+?)\s*[.!]?$/iu',
            $question,
            $matches
        )) {
            return null;
        }

        $target = $this->clean($matches[1]);
        $replacement = $this->clean($matches[2]);

        if ($target === '' || $replacement === '' || mb_strlen($target) > 150 || mb_strlen($replacement) > 500) {
            return null;
        }

        $actual = $this->findInDocument($target, $documentText);
        if ($actual === null) {
            return null;
        }

        // "change practicum weekly report to 1" means change the report's
        // number/semester qualifier, not the title itself. When a short
        // numeric value follows a phrase like "1st Semester 2026- 2027",
        // replace that qualifier instead of the phrase.
        $search = $actual;
        if (preg_match('/^\d{1,3}$/u', $replacement)) {
            $position = mb_stripos($documentText, $actual);
            if ($position !== false) {
                $tail = mb_substr($documentText, $position + mb_strlen($actual));
                if (preg_match('/^\s+(\d{1,3}(?:st|nd|rd|th)?\s+\p{L}+\s+\d{4}\s*-?\s*\d{4})/u', $tail, $qualifier) === 1) {
                    $search = $qualifier[1];
                }
            }
        }

        preg_match_all('/' . preg_quote($search, '/') . '/iu', $documentText, $found);

        return [
            'searchString' => $search,
            'replaceString' => $replacement,
            'matchCase' => true,
            'occurrences' => count($found[0] ?? []),
        ];
    }

    private function clean(string $value): string
    {
        return trim($value, " \t\n\r\0\x0B\"'“”‘’");
    }

    /**
     * Return the text exactly as written in the document so OnlyOffice can
     * find it even when the instruction used different casing.
     */
    private function findInDocument(string $target, string $text): ?string
    {
        $position = mb_stripos($text, $target);
        if ($position !== false) {
            return mb_substr($text, $position, mb_strlen($target));
        }

        $words = preg_split('/\s+/u', trim($target)) ?: [];
        if (count($words) < 2) {
            return null;
        }

        $pattern = implode('\\s+', array_map(static fn (string $word): string => preg_quote($word, '/'), $words));
        if (preg_match('/' . $pattern . '/iu', $text, $found, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return (string) $found[0][0];
    }
}
