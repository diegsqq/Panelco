<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Pre-upload metadata suggester. Reads the selected file, then proposes an
 * editable title, category, and document date. Suggestions only — nothing is
 * saved and every field stays editable before the user uploads.
 */
class UploadSuggester
{
    private const MONTHS = [
        'january' => 1, 'jan' => 1,
        'february' => 2, 'feb' => 2,
        'march' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4,
        'may' => 5,
        'june' => 6, 'jun' => 6,
        'july' => 7, 'jul' => 7,
        'august' => 8, 'aug' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'october' => 10, 'oct' => 10,
        'november' => 11, 'nov' => 11,
        'december' => 12, 'dec' => 12,
    ];

    public function __construct(
        private DocumentExtractionService $extractor,
        private CategoryRecommender $recommender,
    ) {}

    /**
     * @return array{title: string, category_id: ?int, document_date: ?string, text_found: bool}
     *
     * @param  ?int  $clientTimestamp  file.lastModified from the browser (ms).
     */
    public function suggest(UploadedFile $file, ?int $clientTimestamp = null): array
    {
        $result = [
            'title' => $this->titleFromFilename($file->getClientOriginalName()),
            'category_id' => null,
            'document_date' => $this->creationDate($file, $clientTimestamp),
            'text_found' => false,
        ];

        try {
            $text = trim($this->extractor->extractFromUploadedFile($file));
        } catch (\Throwable $e) {
            // Corrupt file, unreadable scan, or missing OCR — fall back to
            // the filename title so the user can still upload manually.
            return $result;
        }

        if ($text === '') {
            return $result;
        }

        $result['text_found'] = true;
        $result['title'] = $this->titleFromText($text) ?? $result['title'];
        $result['category_id'] = $this->recommender->suggest($text)['category_id'] ?? null;
        $result['document_date'] = $result['document_date'] ?? $this->detectDate($text);

        return $result;
    }

    /**
     * Date the file itself was created: embedded metadata first
     * (DOCX core.xml, PDF CreationDate), then the browser's
     * file.lastModified. Y-m-d or null.
     */
    public function creationDate(UploadedFile $file, ?int $clientTimestamp = null): ?string
    {
        $path = (string) $file->getRealPath();
        $ext = strtolower((string) $file->getClientOriginalExtension());

        $fromMeta = match ($ext) {
            'docx' => $this->docxCreatedAt($path),
            'pdf' => $this->pdfCreatedAt($path),
            default => null,
        };

        if ($fromMeta !== null
            && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $fromMeta, $m)
            && $this->valid((int) $m[1], (int) $m[2], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if ($clientTimestamp !== null && $clientTimestamp > 0) {
            // Browsers report milliseconds; accept seconds too.
            $ts = $clientTimestamp > 10_000_000_000 ? intdiv($clientTimestamp, 1000) : $clientTimestamp;
            if ($ts > 0) {
                $year = (int) date('Y', $ts);
                $month = (int) date('n', $ts);
                $day = (int) date('j', $ts);
                if ($this->valid($year, $month, $day)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
        }

        return null;
    }

    private function docxCreatedAt(string $path): ?string
    {
        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }
        $xml = $zip->getFromName('docProps/core.xml');
        $zip->close();

        if (! is_string($xml)) {
            return null;
        }

        return preg_match('/<dcterms:created[^>]*>\s*([^<\s]+)/', $xml, $m) ? trim($m[1]) : null;
    }

    private function pdfCreatedAt(string $path): ?string
    {
        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        if (preg_match('/\/CreationDate\s*\(D:(\d{4})(\d{2})(\d{2})/', $raw, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        if (preg_match('/<xmp:CreateDate>\s*(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
            return $m[1];
        }

        return null;
    }

    private function titleFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $clean = trim(preg_replace('/[\-_]+/', ' ', $base) ?? $base);

        return mb_substr($clean !== '' ? ucwords($clean) : $base, 0, 255);
    }

    /**
     * Uses the first non-empty line when it reads like a heading,
     * otherwise null so the filename title stands.
     */
    private function titleFromText(string $text): ?string
    {
        foreach (preg_split('/\R/', mb_substr($text, 0, 2000)) as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');

            if ($line === '') {
                continue;
            }

            $words = str_word_count($line);
            $looksLikeHeading = mb_strlen($line) >= 4 && mb_strlen($line) <= 90
                && $words >= 2 && $words <= 14
                && preg_match('/\p{L}/u', $line)
                && ! preg_match('/^\d[\d\/\.\-]*$/', $line);

            return $looksLikeHeading ? mb_substr($line, 0, 255) : null;
        }

        return null;
    }

    /**
     * Finds the first plausible date in the opening pages of the text.
     * Returns Y-m-d or null. Ambiguous numeric dates (03/04/2024) are read
     * month-first, matching common PH document convention.
     */
    private function detectDate(string $text): ?string
    {
        $window = mb_substr($text, 0, 8000);
        $names = implode('|', array_map('ucfirst', array_unique(array_keys(self::MONTHS))));

        // "15 January 2024" / "15-Jan-2024" / "3rd of March 2024"
        if (preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?[\s\-\/](?:of\s+)?(' . $names . ')[\s\-\.,]+(\d{4})\b/iu', $window, $m)) {
            $month = self::MONTHS[mb_strtolower($m[2])];
            if ($this->valid((int) $m[3], $month, (int) $m[1])) {
                return sprintf('%04d-%02d-%02d', (int) $m[3], $month, (int) $m[1]);
            }
        }

        // "January 15, 2024" / "Jan 15 2024"
        if (preg_match('/\b(' . $names . ')\.?\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+(\d{4})\b/iu', $window, $m)) {
            $month = self::MONTHS[mb_strtolower($m[1])];
            if ($this->valid((int) $m[3], $month, (int) $m[2])) {
                return sprintf('%04d-%02d-%02d', (int) $m[3], $month, (int) $m[2]);
            }
        }

        // "2024-01-15" (ISO, year first)
        if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $window, $m)) {
            if ($this->valid((int) $m[1], (int) $m[2], (int) $m[3])) {
                return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
            }
        }

        // "01/15/2024", "15.01.2024", "15-01-2024"
        if (preg_match('/\b(\d{1,2})[\/\.\-](\d{1,2})[\/\.\-](\d{2,4})\b/', $window, $m)) {
            $year = (int) $m[3];
            $year += $year < 100 ? 2000 : 0;

            [$a, $b] = [(int) $m[1], (int) $m[2]];
            if ($a > 12) {           // clearly day-first
                [$day, $month] = [$a, $b];
            } elseif ($b > 12) {     // clearly month-first
                [$month, $day] = [$a, $b];
            } else {                 // ambiguous → month-first
                [$month, $day] = [$a, $b];
            }

            if ($this->valid($year, $month, $day)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    private function valid(int $year, int $month, int $day): bool
    {
        if ($year < 1990 || $year > (int) now()->addYear()->format('Y') || $month < 1 || $month > 12) {
            return false;
        }

        return checkdate($month, $day, $year);
    }
}
