<?php

namespace App\Services;

/**
 * Best-effort recovery of tabular structure from flattened extracted text.
 *
 * PDF/DOCX extraction collapses columns into one long line, so tables arrive
 * as:  <Category> <Ratio> <Formula> <Result> <Interpretation> <Category> ...
 * This sniffer re-splits rows on the "number followed by a full sentence"
 * boundary, detects formula cells by math operators, and strips caption/header
 * words from the first row. Returns [] whenever confidence is low, so callers
 * can fall back to plain text.
 */
class StructuredTableExtractor
{
    private const MIN_ROWS = 3;
    private const MIN_INTERPRETATION_LENGTH = 15;
    // Caps reject noise from spreadsheets, where extraction glues entire
    // statements into a single "cell" — such rows are not real table rows.
    private const MAX_INTERPRETATION_LENGTH = 400;
    private const MAX_PREFIX_LENGTH = 500;
    // Forward-scan limits: an interpretation may quote a few numbers before
    // the next row starts, but never dozens — capping keeps number-heavy
    // spreadsheets (thousands of numeric tokens) from quadratic blowup.
    private const RESULT_SCAN_AHEAD = 6;
    private const MAX_SPAN_LENGTH = 1500;

    /** Words that commonly name columns in printed tables. */
    private const GENERIC_HEADERS = [
        'category', 'ratio', 'formula', 'result', 'interpretation',
        'item', 'description', 'details', 'value', 'amount', 'no', 'total',
        'name', 'remarks', 'particulars', 'percent', 'rate', 'year', 'indicator',
    ];

    private const CAPTION_KEYWORDS = ['table', 'figure', 'tab', 'chart'];

    /** Measure nouns that typically close a ratio/metric name. */
    private const RATIO_NAME_TAILS = [
        'ratio', 'margin', 'turnover', 'share', 'period', 'return',
        'rate', 'yield', 'percentage', 'capital', 'coverage', 'velocity',
    ];

    /**
     * @return array<int, array{caption: ?string, headers: array<int, string>, rows: array<int, array<int, string>>}>
     */
    public function extract(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $rows = $this->findRows($text);
        if (count($rows) < self::MIN_ROWS) {
            return [];
        }

        [$caption, $headers, $rows] = $this->stripCaptionAndHeaders($rows);

        // Consecutive rows with a different cell count belong to a different table.
        $groups = [];
        foreach ($rows as $row) {
            $cells = $this->splitRow($row['prefix'], $row['result'], $row['interpretation']);
            $shape = count($cells);
            $last = array_key_last($groups);
            if ($last !== null && $groups[$last]['shape'] === $shape) {
                $groups[$last]['rows'][] = $cells;
            } else {
                $groups[] = ['shape' => $shape, 'rows' => [$cells]];
            }
        }

        $tables = [];
        foreach ($groups as $i => $group) {
            if (count($group['rows']) < self::MIN_ROWS) {
                continue;
            }
            $groupHeaders = $i === 0 && count($headers) === $group['shape']
                ? $headers
                : $this->fallbackHeaders($group['shape']);

            $tables[] = [
                'caption' => $i === 0 ? $caption : null,
                'headers' => $groupHeaders,
                'rows' => $group['rows'],
            ];
        }

        return $tables;
    }

    /**
     * A row = <result token> <interpretation sentence(s)>. Rows arrive
     * concatenated, so each row's span runs from its result token to the next
     * row's result token. Within that span the interpretation ends at the LAST
     * sentence boundary whose tail still looks like a row prefix (short, and
     * containing a math operator). Numbers mentioned inside an interpretation
     * (e.g. "₱ 3,000,000") are false candidates and are skipped by scanning
     * forward to the next result token.
     *
     * @return array<int, array{prefix: string, result: string, interpretation: string}>
     */
    private function findRows(string $text): array
    {
        $pattern = '/(?<![\d.,])(\d+\s*(?:years?|months?|weeks?|days?)|\d+(?:[.,]\d+)?\s?%?)\s+/u';

        if (! preg_match_all($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $candidates = [];
        foreach ($m[0] as $i => $full) {
            // A bare number right after "=" is a formula constant ("NPV = 0"),
            // not a result.
            $before = rtrim(substr($text, max(0, $full[1] - 8), 8));
            if (preg_match('/[=÷×+]\s*$/u', $before)) {
                continue;
            }
            $candidates[] = [
                'start' => $full[1],
                'end' => $full[1] + strlen($full[0]),
                'value' => trim(preg_replace('/\s+%/u', '%', $m[1][$i][0])),
            ];
        }

        $rows = [];
        $count = count($candidates);
        $prefixStart = 0;
        $i = 0;
        while ($i < $count) {
            $interpStart = $candidates[$i]['end'];

            // Try each following result candidate as "the next row's result";
            // the ones in between are numbers quoted inside the interpretation.
            $split = null;
            $j = $i + 1;
            $last = min($count, $i + self::RESULT_SCAN_AHEAD);
            for (; $j <= $last; $j++) {
                $spanEnd = $j < $count ? $candidates[$j]['start'] : strlen($text);
                if ($spanEnd - $interpStart > self::MAX_SPAN_LENGTH) {
                    break;
                }
                $span = substr($text, $interpStart, $spanEnd - $interpStart);
                $split = $this->splitInterpretation($span, $j === $count);
                if ($split !== null) {
                    break;
                }
            }

            if ($split === null) {
                $i++;
                continue;
            }

            // This row's own prefix sits between the previous row's split and
            // this result token — NOT the tail the split just returned, which
            // belongs to the NEXT row.
            $prefix = trim(substr($text, $prefixStart, $candidates[$i]['start'] - $prefixStart));
            if ($prefix !== '') {
                $rows[] = [
                    'prefix' => $prefix,
                    'result' => $candidates[$i]['value'],
                    'interpretation' => $split['interpretation'],
                ];
            }
            $prefixStart = $interpStart + $split['relPos'] + 1;
            $i = max($j, $i + 1);
        }

        return $rows;
    }

    /**
     * Split "interpretation... <next row's prefix>" at the last sentence
     * boundary whose tail is a plausible row prefix. $isFinal marks the span
     * after the document's last result token, where the interpretation simply
     * ends at the last period. relPos = byte offset of the boundary within the
     * span, so the caller can locate the following row's prefix absolutely.
     *
     * @return array{interpretation: string, prefix: string, relPos: int}|null
     */
    private function splitInterpretation(string $span, bool $isFinal): ?array
    {
        // Sentence boundaries: ". Capital", ". " at end of span, or blank line.
        $boundaries = [];
        if (preg_match_all('/\.(?=\s+[A-Z]|\s*$)|\n\s*\n/', $span, $bm, PREG_OFFSET_CAPTURE)) {
            foreach ($bm[0] as $b) {
                $boundaries[] = $b[1] + (str_starts_with($b[0], '.') ? 0 : strlen($b[0]) - 1);
            }
        }

        foreach (array_reverse($boundaries) as $pos) {
            $interpretation = trim(substr($span, 0, $pos + 1));
            $prefix = trim(substr($span, $pos + 1));

            if ($isFinal && $prefix === '') {
                $len = mb_strlen($interpretation);
                return $len >= self::MIN_INTERPRETATION_LENGTH && $len <= self::MAX_INTERPRETATION_LENGTH
                    ? ['interpretation' => $interpretation, 'prefix' => $prefix, 'relPos' => $pos]
                    : null;
            }
            if ($this->validSplit($interpretation, $prefix)) {
                return ['interpretation' => $interpretation, 'prefix' => $prefix, 'relPos' => $pos];
            }
        }

        return null;
    }

    private function validSplit(string $interpretation, string $prefix): bool
    {
        if (mb_strlen($interpretation) < self::MIN_INTERPRETATION_LENGTH
            || mb_strlen($interpretation) > self::MAX_INTERPRETATION_LENGTH
            || mb_strlen($prefix) > self::MAX_PREFIX_LENGTH) {
            return false;
        }

        // Spreadsheet extraction dumps cell runs with "|" separators and
        // line breaks; flattened document text never contains either.
        if (str_contains($prefix, '|') || str_contains($prefix, "\n")
            || str_contains($interpretation, '|')) {
            return false;
        }

        $tokens = $this->tokens($prefix);
        if (count($tokens) < 2) {
            return false;
        }

        // A row prefix names an item and its formula, so it must contain a
        // math operator (or the word "where", as in "… where NPV = 0").
        return $this->formulaStart($tokens) !== null;
    }

    /**
     * The first row's prefix also holds the caption ("Table Financial Ratios
     * Overview") and the printed column names. Cut everything before the first
     * token that also starts a later row, then split that cut into caption vs.
     * header words.
     *
     * @param  array<int, array{prefix: string, result: string, interpretation: string}>  $rows
     * @return array{0: ?string, 1: array<int, string>, 2: array<int, array{prefix: string, result: string, interpretation: string}>}
     */
    private function stripCaptionAndHeaders(array $rows): array
    {
        $starters = [];
        foreach (array_slice($rows, 1) as $row) {
            $tokens = $this->tokens($row['prefix']);
            if ($tokens !== []) {
                $starters[$tokens[0]] = true;
            }
        }

        $first = $this->tokens($rows[0]['prefix']);
        $cut = null;
        foreach ($first as $i => $token) {
            if ($i > 0 && isset($starters[$token])) {
                $cut = $i;
                break;
            }
        }

        if ($cut === null) {
            return [null, [], $rows];
        }

        $lead = array_slice($first, 0, $cut);
        $rows[0]['prefix'] = implode(' ', array_slice($first, $cut));

        $headers = [];
        $captionWords = [];
        foreach ($lead as $word) {
            if (in_array(mb_strtolower($word), self::GENERIC_HEADERS, true)) {
                $headers[] = ucfirst(mb_strtolower($word));
            } else {
                $captionWords[] = $word;
            }
        }
        if ($captionWords !== [] && in_array(mb_strtolower($captionWords[0]), self::CAPTION_KEYWORDS, true)) {
            array_shift($captionWords);
        }
        $caption = $captionWords === [] ? null : implode(' ', $captionWords);

        return [$caption, $headers, $rows];
    }

    /**
     * Split "<Category> <Ratio name> <Formula...>" into three cells.
     *
     * The formula starts after the ratio name. Ratio names in financial docs
     * almost always end in a measure noun ("Ratio", "Margin", "Turnover", ...),
     * so the formula begins right after the LAST such word before the math
     * operator. "where" (as in "Discount Rate where NPV = 0") marks the
     * formula directly and takes priority.
     *
     * @return array<int, string>
     */
    private function splitRow(string $prefix, string $result, string $interpretation): array
    {
        $tokens = $this->tokens($prefix);
        $category = array_shift($tokens);

        $formulaStart = $this->formulaStart($tokens);

        if ($formulaStart !== null) {
            // Keep at least one word of ratio and one of formula.
            $formulaStart = max(1, min($formulaStart, count($tokens) - 1));
            $ratio = implode(' ', array_slice($tokens, 0, $formulaStart));
            $formula = implode(' ', array_slice($tokens, $formulaStart));
        } else {
            $ratio = implode(' ', $tokens);
            $formula = '';
        }

        $cells = [$category, $ratio !== '' ? $ratio : '—'];
        if ($formula !== '') {
            $cells[] = $formula;
        }
        $cells[] = $result;
        $cells[] = $interpretation;

        return $cells;
    }

    /**
     * Index (within the category-stripped token list) where the formula cell
     * begins, or null when no math operator is present.
     */
    private function formulaStart(array $tokens): ?int
    {
        // "Discount Rate where NPV = 0" — the word "where" opens the formula,
        // which then continues two words later... i.e. two words BEFORE it.
        foreach ($tokens as $i => $token) {
            if (mb_strtolower($token) === 'where') {
                return $i - 2;
            }
        }

        // First math operator. "÷ × =" never appear inside words; "/" and "+"
        // only count when not glued to letters ("R&D/Q2", "C/O" are not math).
        $operator = null;
        foreach ($tokens as $i => $token) {
            if (preg_match('/[÷×=]/u', $token) || preg_match('/(?<!\pL)[\/+](?!\pL)/u', $token)) {
                $operator = $i;
                break;
            }
        }
        if ($operator === null) {
            return null;
        }

        // Walk back to the last measure-noun closing the ratio name. A token
        // ending in ")" also closes it ("Return on Assets (ROA)").
        foreach (array_keys(array_slice($tokens, 0, $operator)) as $i) {
            $rev = $operator - 1 - $i;
            if (in_array(mb_strtolower($tokens[$rev]), self::RATIO_NAME_TAILS, true)
                || str_ends_with($tokens[$rev], ')')) {
                return $rev + 1;
            }
        }

        // No measure-noun found: assume the operand before the operator is
        // two words long ("Net Income ÷ Sales").
        return $operator - 2;
    }

    /**
     * @return array<int, string>
     */
    private function fallbackHeaders(int $shape): array
    {
        return match ($shape) {
            5 => ['Category', 'Item', 'Formula', 'Result', 'Interpretation'],
            4 => ['Category', 'Item', 'Result', 'Interpretation'],
            default => array_map(fn (int $i): string => 'Column ' . ($i + 1), range(0, $shape - 1)),
        };
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $text): array
    {
        return preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
