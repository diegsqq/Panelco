<?php

namespace App\Services;

use App\Models\Document;
use App\Support\PageLayout;
use DOMDocument;
use DOMElement;
use DOMNode;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Text as SharedText;
use PhpOffice\PhpWord\Style\ListItem as ListItemStyle;

/**
 * Converts the editor's sanitized HTML (body, page header, page footer)
 * into a real editable .docx using PhpWord, honouring the document's
 * page setup and turning {page}/{pages} tokens into Word fields.
 */
class DocumentDocxExporter
{
    /** @var list<string> temporary image files to clean up */
    private array $tempFiles = [];

    private const HEADING_SIZES = [
        'h1' => 32, // half-points (16 pt)
        'h2' => 28,
        'h3' => 24,
        'h4' => 22,
        'h5' => 20,
        'h6' => 18,
    ];

    private const BLOCK_TAGS = [
        'p', 'div', 'blockquote', 'figure', 'figcaption', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'ul', 'ol', 'table', 'hr', 'pre',
    ];

    public function __construct(private readonly DocumentContentSanitizer $sanitizer)
    {
    }

    public function export(Document $document): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection(PageLayout::sectionStyle(
            $document->page_paper ?? PageLayout::DEFAULTS['paper'],
            $document->page_orientation ?? PageLayout::DEFAULTS['orientation'],
            $document->page_margins ?? PageLayout::DEFAULTS['margins'],
        ));

        $headerHtml = $this->sanitizer->sanitize($document->content_header);
        if ($headerHtml !== '') {
            $this->renderFragment($section->addHeader(), $headerHtml, true);
        }

        $this->renderFragment($section, $this->sanitizer->sanitize($document->content), false);

        $footerHtml = $this->sanitizer->sanitize($document->content_footer);
        if ($footerHtml !== '') {
            $this->renderFragment($section->addFooter(), $footerHtml, true);
        }

        $path = tempnam(sys_get_temp_dir(), 'sd-docx');

        try {
            IOFactory::createWriter($phpWord, 'Word2007')->save($path);
            $binary = file_get_contents($path);
        } finally {
            @unlink($path);
            foreach ($this->tempFiles as $temp) {
                @unlink($temp);
            }
            $this->tempFiles = [];
        }

        if ($binary === false) {
            throw new \RuntimeException('The DOCX file could not be generated.');
        }

        return $binary;
    }

    private function renderFragment(AbstractContainer $container, string $html, bool $pageMatter): void
    {
        if ($html === '') {
            return;
        }

        $root = $this->parse($html);
        if ($root === null) {
            return;
        }

        foreach (iterator_to_array($root->childNodes) as $child) {
            $this->renderNode($container, $child, $pageMatter);
        }
    }

    private function parse(string $fragment): ?DOMElement
    {
        $document = new DOMDocument();
        $useInternal = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'
            . '<div id="sd-root">' . $fragment . '</div>'
            . '</body></html>'
        );
        libxml_clear_errors();
        libxml_use_internal_errors($useInternal);

        return $loaded ? $document->getElementById('sd-root') : null;
    }

    private function renderNode(AbstractContainer $container, DOMNode $node, bool $pageMatter): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = (string) $node->nodeValue;
            if (trim($text) === '') {
                return;
            }
            if ($pageMatter && $this->hasToken($text)) {
                $container->addPreserveText(SharedText::toUTF8($this->mapTokens(trim($text))));

                return;
            }
            $container->addText(SharedText::toUTF8($text));

            return;
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var DOMElement $node */
        $tag = strtolower($node->nodeName);

        if ($tag === 'br' || $tag === 'hr') {
            $container->addTextBreak();

            return;
        }

        if (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
            $font = ['size' => self::HEADING_SIZES[$tag], 'bold' => true];
            $font = $this->mergeStyle($font, $node->getAttribute('style'));

            $this->renderParagraph($container, $node, $pageMatter, $font);

            return;
        }

        if (in_array($tag, ['ul', 'ol'], true)) {
            $this->renderList($container, $node, 0, $tag === 'ol', $pageMatter);

            return;
        }

        if ($tag === 'table') {
            $this->renderTable($container, $node, $pageMatter);

            return;
        }

        if ($tag === 'img') {
            $this->addImage($container, $node);

            return;
        }

        if (in_array($tag, self::BLOCK_TAGS, true)) {
            if ($this->isEmptyBlock($node)) {
                $container->addTextBreak();

                return;
            }
            $this->renderParagraph($container, $node, $pageMatter);

            return;
        }

        // Unknown inline container: render its children in place.
        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->renderNode($container, $child, $pageMatter);
        }
    }

    private function renderParagraph(
        AbstractContainer $container,
        DOMElement $element,
        bool $pageMatter,
        ?array $fontOverride = null
    ): void {
        if ($pageMatter && $this->hasToken($element->textContent)) {
            $text = trim(preg_replace('/\s+/u', ' ', $element->textContent) ?? '');
            if ($text !== '') {
                $container->addPreserveText(SharedText::toUTF8($this->mapTokens($text)));
            }

            return;
        }

        $run = $container->addTextRun($this->paragraphStyle($element));
        $this->fillRun($run, $element, $fontOverride ?? []);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paragraphStyle(DOMElement $element): ?array
    {
        $align = $this->cssValue($element->getAttribute('style'), 'text-align');
        if ($align === null || ! in_array($align, ['left', 'center', 'right', 'justify'], true)) {
            return null;
        }

        return ['alignment' => $align];
    }

    private function fillRun(TextRun $run, DOMNode $parent, array $font): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = (string) $child->nodeValue;
                if ($text !== '') {
                    $run->addText(SharedText::toUTF8($text), $this->fontStyle($font));
                }

                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            /** @var DOMElement $child */
            $tag = strtolower($child->nodeName);

            switch ($tag) {
                case 'br':
                    $run->addTextBreak();
                    break;

                case 'strong':
                case 'b':
                    $this->fillRun($run, $child, $font + ['bold' => true]);
                    break;

                case 'em':
                case 'i':
                    $this->fillRun($run, $child, $font + ['italic' => true]);
                    break;

                case 'u':
                    $this->fillRun($run, $child, $font + ['underline' => 'single']);
                    break;

                case 's':
                case 'strike':
                case 'del':
                    $this->fillRun($run, $child, $font + ['strikethrough' => true]);
                    break;

                case 'sub':
                    $this->fillRun($run, $child, $font + ['subScript' => true]);
                    break;

                case 'sup':
                    $this->fillRun($run, $child, $font + ['superScript' => true]);
                    break;

                case 'a':
                    $href = $child->getAttribute('href');
                    $text = trim(preg_replace('/\s+/u', ' ', $child->textContent) ?? '');
                    if ($href !== '' && $text !== '') {
                        $run->addLink($href, SharedText::toUTF8($text), $this->fontStyle($font + [
                            'color' => '0563C1',
                            'underline' => 'single',
                        ]));
                    }
                    break;

                case 'img':
                    $this->addImage($run, $child);
                    break;

                case 'font':
                    $font = $this->mergeFontAttributes($font, $child);
                    $this->fillRun($run, $child, $font);
                    break;

                default:
                    $font = $this->mergeStyle($font, $child->getAttribute('style'));
                    $this->fillRun($run, $child, $font);
                    break;
            }
        }
    }

    private function renderList(
        AbstractContainer $container,
        DOMElement $list,
        int $depth,
        bool $ordered,
        bool $pageMatter
    ): void {
        $listStyle = $ordered ? ['listType' => ListItemStyle::TYPE_NUMBER] : null;

        foreach (iterator_to_array($list->childNodes) as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            /** @var DOMElement $child */
            $tag = strtolower($child->nodeName);

            if ($tag === 'li') {
                $text = trim(preg_replace('/\s+/u', ' ', $child->textContent) ?? '');
                if ($pageMatter) {
                    $text = $this->mapTokens($text);
                }
                if ($text !== '') {
                    $container->addListItem(SharedText::toUTF8($text), $depth, null, $listStyle, null);
                }

                foreach (iterator_to_array($child->childNodes) as $nested) {
                    if ($nested->nodeType === XML_ELEMENT_NODE
                        && in_array(strtolower($nested->nodeName), ['ul', 'ol'], true)) {
                        $this->renderList($container, $nested, $depth + 1, strtolower($nested->nodeName) === 'ol', $pageMatter);
                    }
                }

                continue;
            }

            if (in_array($tag, ['ul', 'ol'], true)) {
                $this->renderList($container, $child, $depth, $tag === 'ol', $pageMatter);
            }
        }
    }

    private function renderTable(AbstractContainer $container, DOMElement $table, bool $pageMatter): void
    {
        $element = $container->addTable([
            'borderSize' => 6,
            'borderColor' => '9AA5A0',
        ]);

        foreach ($this->childElements($table, ['tbody', 'thead', 'tfoot']) as $sectionTag) {
            $rows = $sectionTag === $table
                ? $this->childElements($table, ['tr'])
                : $this->childElements($sectionTag, ['tr']);

            foreach ($rows as $tr) {
                $row = $element->addRow();
                foreach ($this->childElements($tr, ['td', 'th']) as $cell) {
                    $newCell = $row->addCell();
                    $colspan = (int) $cell->getAttribute('colspan');
                    $rowspan = (int) $cell->getAttribute('rowspan');
                    if ($colspan > 1 && method_exists($newCell, 'setColSpan')) {
                        $newCell->setColSpan($colspan);
                    }
                    if ($rowspan > 1 && method_exists($newCell, 'setRowSpan')) {
                        $newCell->setRowSpan($rowspan);
                    }

                    $isHeader = strtolower($cell->nodeName) === 'th';
                    $hasBlock = false;
                    foreach ($this->childElements($cell, self::BLOCK_TAGS) as $_) {
                        $hasBlock = true;
                        break;
                    }

                    if ($hasBlock) {
                        foreach (iterator_to_array($cell->childNodes) as $child) {
                            $this->renderNode($newCell, $child, $pageMatter);
                        }
                    } else {
                        $run = $newCell->addTextRun(['alignment' => $isHeader ? 'center' : 'left']);
                        $this->fillRun($run, $cell, $isHeader ? ['bold' => true] : []);
                        if ($run->getElements() === []) {
                            $newCell->addText('');
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  list<string>  $tags
     * @return list<DOMElement>
     */
    private function childElements(DOMElement $parent, array $tags): array
    {
        $found = [];
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && in_array(strtolower($child->nodeName), $tags, true)) {
                $found[] = $child;
            }
        }

        return $found;
    }

    private function addImage(AbstractContainer $container, DOMElement $image): bool
    {
        $path = $this->resolveImage($image->getAttribute('src'));
        if ($path === null) {
            return false;
        }

        $width = $image->getAttribute('width');
        $height = $image->getAttribute('height');
        $style = null;
        if (preg_match('/^\d{1,4}$/', $width) === 1 && preg_match('/^\d{1,4}$/', $height) === 1) {
            $style = ['width' => (int) $width, 'height' => (int) $height];
        }

        $container->addImage($path, $style);

        return true;
    }

    private function resolveImage(string $src): ?string
    {
        if ($src === '') {
            return null;
        }

        if (str_starts_with($src, 'data:image/')) {
            $comma = strpos($src, ',');
            if ($comma === false) {
                return null;
            }
            $binary = base64_decode(substr($src, $comma + 1), true);
            if ($binary === false) {
                return null;
            }
            $meta = substr($src, 5, strpos($src, ';') - 5);
            $extension = strtolower(strtok($meta, '/') ?: 'png');
            $temp = tempnam(sys_get_temp_dir(), 'sd-img') . '.' . $extension;
            file_put_contents($temp, $binary);
            $this->tempFiles[] = $temp;

            return $temp;
        }

        $path = parse_url($src, PHP_URL_PATH);
        if (! is_string($path)
            || preg_match('#^/library/(\d+)/content/images/([A-Za-z0-9._-]+)$#', $path, $matches) !== 1) {
            return null; // External images are not inlined into the export.
        }

        $local = storage_path('app/private/documents/content-images/' . $matches[1] . '/' . $matches[2]);

        return is_file($local) ? $local : null;
    }

    /** @return array<string, mixed> */
    private function mergeStyle(array $font, string $style): array
    {
        if ($style === '') {
            return $font;
        }

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }
            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $value = trim($value, " \t\n\r\0\x0B'\"");
            if ($value === '') {
                continue;
            }

            switch (strtolower($property)) {
                case 'font-family':
                    $family = trim((string) strtok($value, ','));
                    $family = trim($family, " \t\"'");
                    if ($family !== '') {
                        $font['name'] = SharedText::toUTF8($family);
                    }
                    break;

                case 'font-size':
                    if (preg_match('/^([\d.]+)\s*(px|pt|em|rem)?$/i', $value, $match)) {
                        $number = (float) $match[1];
                        $unit = strtolower($match[2] ?? 'px');
                        $points = match ($unit) {
                            'px' => $number * 0.75,
                            'pt' => $number,
                            'em', 'rem' => $number * 12,
                            default => $number,
                        };
                        $font['size'] = (int) round($points * 2); // OOXML half-points
                    }
                    break;

                case 'color':
                    $hex = $this->toHex($value);
                    if ($hex !== null) {
                        $font['color'] = $hex;
                    }
                    break;

                case 'font-weight':
                    if (in_array(strtolower($value), ['bold', 'bolder', '600', '700', '800', '900'], true)) {
                        $font['bold'] = true;
                    } elseif (in_array(strtolower($value), ['normal', '400'], true)) {
                        $font['bold'] = false;
                    }
                    break;

                case 'font-style':
                    $font['italic'] = strtolower($value) === 'italic';
                    break;

                case 'text-decoration':
                    if (str_contains(strtolower($value), 'underline')) {
                        $font['underline'] = 'single';
                    }
                    if (str_contains(strtolower($value), 'line-through')) {
                        $font['strikethrough'] = true;
                    }
                    break;
            }
        }

        return $font;
    }

    /** @return array<string, mixed> */
    private function mergeFontAttributes(array $font, DOMElement $element): array
    {
        $face = trim($element->getAttribute('face'));
        if ($face !== '') {
            $font['name'] = SharedText::toUTF8(trim((string) strtok($face, ',')));
        }

        $color = $this->toHex($element->getAttribute('color'));
        if ($color !== null) {
            $font['color'] = $color;
        }

        $size = $element->getAttribute('size');
        if (preg_match('/^[1-7]$/', $size) === 1) {
            $map = [1 => 20, 2 => 24, 3 => 30, 4 => 36, 5 => 48, 6 => 64, 7 => 96]; // half-points
            $font['size'] = $map[(int) $size];
        }

        return $font;
    }

    /** @return array<string, mixed>|null */
    private function fontStyle(array $font): ?array
    {
        return $font === [] ? null : $font;
    }

    private function toHex(string $value): ?string
    {
        $value = strtolower(trim($value));

        if (preg_match('/^#?([0-9a-f]{6})$/', $value, $match) === 1) {
            return strtoupper($match[1]);
        }

        if (preg_match('/^#?([0-9a-f]{3})$/', $value, $match) === 1) {
            $short = $match[1];

            return strtoupper($short[0] . $short[0] . $short[1] . $short[1] . $short[2] . $short[2]);
        }

        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $value, $match) === 1) {
            return strtoupper(sprintf('%02X%02X%02X', (int) $match[1], (int) $match[2], (int) $match[3]));
        }

        return null;
    }

    private function cssValue(string $style, string $property): ?string
    {
        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode(':', $declaration, 2));
            if (strtolower($name) === $property) {
                return $value;
            }
        }

        return null;
    }

    private function isEmptyBlock(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE && trim((string) $child->nodeValue) !== '') {
                return false;
            }
            if ($child->nodeType === XML_ELEMENT_NODE
                && ! in_array(strtolower($child->nodeName), ['br'], true)) {
                return false;
            }
        }

        return true;
    }

    private function hasToken(string $text): bool
    {
        foreach (array_keys(PageLayout::tokens()) as $token) {
            if (str_contains($text, $token)) {
                return true;
            }
        }

        return false;
    }

    private function mapTokens(string $text): string
    {
        $search = array_keys(PageLayout::tokens());
        $replace = array_map(
            fn (string $field): string => '{' . $field . '}',
            array_values(PageLayout::tokens())
        );

        return str_replace($search, $replace, $text);
    }
}
