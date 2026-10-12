<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Minimal safe markdown for assistant messages: bold, lists, paragraphs.
 * Input is escaped first, so raw HTML (including XSS) can never pass through.
 */
class MarkdownLite
{
    public static function render(string $text): HtmlString
    {
        $lines = preg_split('/\r?\n/', $text);
        $html = '';
        $list = null; // 'ul' | 'ol' | null

        $closeList = function () use (&$html, &$list) {
            if ($list !== null) {
                $html .= $list === 'ul' ? '</ul>' : '</ol>';
                $list = null;
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $closeList();
                continue;
            }
            if (str_starts_with($trimmed, '- ')) {
                if ($list !== 'ul') {
                    $closeList();
                    $html .= '<ul class="mb-2 ps-4">';
                    $list = 'ul';
                }
                $html .= '<li>' . self::inline(mb_substr($trimmed, 2)) . '</li>';
            } elseif (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $m)) {
                if ($list !== 'ol') {
                    $closeList();
                    $html .= '<ol class="mb-2 ps-4">';
                    $list = 'ol';
                }
                $html .= '<li>' . self::inline($m[1]) . '</li>';
            } else {
                $closeList();
                $html .= '<p class="mb-2">' . self::inline($trimmed) . '</p>';
            }
        }
        $closeList();

        return new HtmlString($html === '' ? '<p class="mb-0 text-muted">—</p>' : $html);
    }

    private static function inline(string $text): string
    {
        $e = e($text);
        $e = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $e);

        return preg_replace('/`(.+?)`/', '<code>$1</code>', $e);
    }
}
