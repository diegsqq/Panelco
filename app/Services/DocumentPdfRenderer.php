<?php

namespace App\Services;

use App\Models\Document;
use App\Support\PageLayout;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Renders the editor's sanitized HTML (body, page header, page footer)
 * to a printable PDF that honours the document's page setup.
 *
 * {page}/{pages} tokens cannot change per page in a static flow, so they
 * are stripped from the header/footer markup and stamped on every page
 * with dompdf's page_text callback, which substitutes {PAGE_NUM} and
 * {PAGE_COUNT} at output time.
 */
class DocumentPdfRenderer
{
    public function __construct(private readonly DocumentContentSanitizer $sanitizer)
    {
    }

    public function render(
        Document $document,
        ?string $body = null,
        ?string $header = null,
        ?string $footer = null,
        ?string $paper = null,
        ?string $orientation = null,
        ?string $margins = null
    ): string {
        $paper = $paper ?: ($document->page_paper ?? PageLayout::DEFAULTS['paper']);
        $orientation = $orientation ?: ($document->page_orientation ?? PageLayout::DEFAULTS['orientation']);
        $margins = $margins ?: ($document->page_margins ?? PageLayout::DEFAULTS['margins']);

        $header = $header === null
            ? $this->sanitizer->sanitize($document->content_header)
            : $this->sanitizer->sanitize($header);
        $footer = $footer === null
            ? $this->sanitizer->sanitize($document->content_footer)
            : $this->sanitizer->sanitize($footer);
        $body = $body === null
            ? $this->sanitizer->sanitize($document->content)
            : $this->sanitizer->sanitize($body);

        $this->extractTokens($header, $stampHeader);
        $this->extractTokens($footer, $stampFooter);

        // The tokens are stamped per page, so they must not also print as
        // literal text in the flowing header/footer markup.
        $tokens = array_keys(PageLayout::tokens());
        $header = str_replace($tokens, '', $header);
        $footer = str_replace($tokens, '', $footer);

        $hasHeader = trim(strip_tags($header)) !== '';
        $hasFooter = trim(strip_tags($footer)) !== '';

        $html = $this->buildHtml(
            $paper,
            $orientation,
            $margins,
            $this->rewriteImages($header),
            $this->rewriteImages($body),
            $this->rewriteImages($footer),
            $hasHeader,
            $hasFooter,
        );

        $options = new Options();
        // Content images live under storage/app/private/documents; the chroot
        // keeps the renderer from reading anything outside that tree.
        $options->set('chroot', storage_path('app/private'));
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'dejavuserif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper(PageLayout::cssPaper($paper), $orientation === 'landscape' ? 'landscape' : 'portrait');
        $dompdf->render();

        $this->stampPages($dompdf, $stampHeader, $stampFooter, $paper, $orientation, $margins);

        $output = $dompdf->output();
        if ($output === null || $output === '') {
            throw new \RuntimeException('The PDF file could not be generated.');
        }

        return $output;
    }

    private function buildHtml(
        string $paper,
        string $orientation,
        string $margins,
        string $header,
        string $body,
        string $footer,
        bool $hasHeader,
        bool $hasFooter
    ): string {
        $marginMm = PageLayout::marginMm($margins);

        $pageCss = sprintf(
            '@page { size: %s %s; margin: %s; }',
            PageLayout::cssPaper($paper),
            $orientation === 'landscape' ? 'landscape' : 'portrait',
            $marginMm . 'mm'
        );

        // Fixed header/footer sit inside the page area, so the flowing body
        // needs matching offsets to keep text from running underneath them.
        $bodyShift = ($hasHeader ? 'padding-top: 14mm; ' : '')
            . ($hasFooter ? 'padding-bottom: 14mm;' : '');

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            . $pageCss
            . 'body { font-family: "DejaVu Serif", serif; font-size: 12pt; color: #111; }'
            . 'p { margin: 0 0 6pt; }'
            . 'table { border-collapse: collapse; width: 100%; margin: 0 0 6pt; }'
            . 'td, th { border: 1px solid #999; padding: 3pt 5pt; vertical-align: top; }'
            . 'th { background-color: #f2f2f2; font-weight: bold; }'
            . 'img { max-width: 100%; }'
            . 'h1, h2, h3, h4, h5, h6 { margin: 0 0 6pt; }'
            . 'ul, ol { margin: 0 0 6pt; padding-left: 18pt; }'
            . '.sd-header { position: fixed; top: 0; left: 0; width: 100%; font-size: 10pt; }'
            . '.sd-footer { position: fixed; bottom: 0; left: 0; width: 100%; font-size: 10pt; }'
            . '</style></head><body>'
            . ($hasHeader ? '<div class="sd-header">' . $header . '</div>' : '')
            . '<div style="' . $bodyShift . '">' . $body . '</div>'
            . ($hasFooter ? '<div class="sd-footer">' . $footer . '</div>' : '')
            . '</body></html>';
    }

    /**
     * Remove page tokens from the static header/footer flow and build the
     * stamped replacement text ({page} → {PAGE_NUM}, {pages} → {PAGE_COUNT}).
     * Returns '' when the fragment carries no token.
     */
    private function extractTokens(string $html, ?string &$stamp): string
    {
        $stamp = '';
        if ($html === '') {
            return '';
        }

        $text = trim(preg_replace('/<[^>]+>/', ' ', $html) ?? '');
        $found = '';
        foreach (PageLayout::tokens() as $token => $field) {
            if (str_contains($text, $token)) {
                $found .= ($found === '' ? '' : ' ') . $token;
            }
        }

        if ($found === '') {
            return '';
        }

        $stamp = str_replace(
            array_keys(PageLayout::tokens()),
            ['{PAGE_NUM}', '{PAGE_COUNT}'],
            $found
        );

        return $found;
    }

    private function stampPages(
        Dompdf $dompdf,
        string $headerStamp,
        string $footerStamp,
        string $paper,
        string $orientation,
        string $margins
    ): void {
        if ($headerStamp === '' && $footerStamp === '') {
            return;
        }

        $dim = PageLayout::dimensions($paper, $orientation);
        $margin = PageLayout::marginMm($margins);

        $font = $dompdf->getFontMetrics()->getFont('times');
        if ($font === null) {
            return;
        }

        $canvas = $dompdf->getCanvas();
        $pointsPerMm = 72 / 25.4;

        // Right-align the stamp just inside the right margin.
        $x = ($dim['width_mm'] - $margin - 5) * $pointsPerMm;

        if ($headerStamp !== '') {
            $y = max(6.0, ($margin / 2) * $pointsPerMm);
            $canvas->page_text($x, $y, $headerStamp, $font, 9, [0, 0, 0]);
        }

        if ($footerStamp !== '') {
            $y = ($dim['height_mm'] - $margin / 2 - 2) * $pointsPerMm;
            $canvas->page_text($x, $y, $footerStamp, $font, 9, [0, 0, 0]);
        }
    }

    /**
     * Point content images at their local private-storage path so dompdf
     * (chrooted to storage/app/private) can read them from disk.
     */
    private function rewriteImages(string $html): string
    {
        if ($html === '' || ! str_contains($html, '/content/images/')) {
            return $html;
        }

        return preg_replace_callback(
            '#src="/library/(\d+)/content/images/([A-Za-z0-9._-]+)"#',
            function (array $matches): string {
                $local = storage_path('app/private/documents/content-images/' . $matches[1] . '/' . $matches[2]);

                return is_file($local)
                    ? 'src="' . htmlspecialchars($local, ENT_QUOTES) . '"'
                    : $matches[0];
            },
            $html
        ) ?? $html;
    }
}
