<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Whitelist-based HTML sanitizer for the in-app document editor.
 *
 * Every tag and attribute that survives must appear in the allowlists
 * below, so unknown markup (scripts, event handlers, embeds, forms, ...)
 * is dropped before it ever reaches the database or the page.
 */
class DocumentContentSanitizer
{
    /**
     * Tags whose entire subtree is removed. They can carry executable
     * content or change page behaviour, so nothing inside them is kept.
     */
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed',
        'applet', 'noscript', 'template', 'svg', 'math', 'form', 'input',
        'button', 'select', 'textarea', 'link', 'meta', 'base', 'head',
        'title', 'html', 'body', 'audio', 'video', 'canvas', 'map', 'area',
        'marquee', 'dialog', 'slot',
    ];

    /**
     * Tags that may appear in editor output. Unknown tags are unwrapped
     * (their children are kept) unless they are in DROP_WITH_CONTENT.
     */
    private const ALLOWED_TAGS = [
        'p', 'div', 'span', 'br', 'hr', 'blockquote', 'pre', 'code',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins',
        'sub', 'sup', 'small', 'mark', 'abbr', 'cite', 'q',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'col', 'colgroup', 'caption',
        'a', 'img', 'figure', 'figcaption',
    ];

    /**
     * Per-tag attributes on top of the shared ones below.
     */
    private const TAG_ATTRIBUTES = [
        'a' => ['href'],
        'img' => ['src', 'alt', 'width', 'height'],
        'ol' => ['start'],
        'td' => ['colspan', 'rowspan', 'scope'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'col' => ['span'],
        'colgroup' => ['span'],
    ];

    /** Attributes allowed on every permitted tag. */
    private const SHARED_ATTRIBUTES = ['style', 'dir', 'lang', 'class'];

    /**
     * The only class names the editor may persist. Anything else found in
     * a class attribute is discarded (the attribute itself is kept if at
     * least one allowed name survives).
     */
    private const ALLOWED_CLASSES = ['sd-page-token', 'sd-template'];

    /**
     * CSS properties that may appear in inline styles. Anything not
     * listed here is discarded along with its value.
     */
    private const ALLOWED_CSS_PROPERTIES = [
        'color', 'background-color',
        'text-align', 'text-decoration', 'text-indent', 'text-transform',
        'font-size', 'font-weight', 'font-style', 'font-family',
        'width', 'height', 'max-width', 'min-width',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
        'border-color', 'border-style', 'border-width', 'border-collapse', 'border-spacing',
        'vertical-align', 'line-height', 'letter-spacing',
        'list-style-type', 'white-space', 'opacity',
    ];

    /** Value patterns that can smuggle code or external fetches into CSS. */
    private const DANGEROUS_CSS = '/url\s*\(|expression\s*\(|javascript:|behaviour:|@import|\\\\/i';

    /**
     * Sanitize a rich-text fragment (document body, header or footer).
     * Returns markup built exclusively from the allowlists above.
     */
    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = $this->loadFragment($html);
        $root = $document?->getElementById('sd-root');
        if (! $root instanceof DOMElement) {
            return '';
        }

        $this->cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    /**
     * Flatten a fragment to plain text (used for validation and tests).
     */
    public function toPlainText(?string $html): string
    {
        $safe = $this->sanitize($html);

        if ($safe === '') {
            return '';
        }

        $root = $this->loadFragment($safe)?->getElementById('sd-root');

        return trim(preg_replace('/\s+/u', ' ', $root?->textContent ?? '') ?? '');
    }

    /**
     * Parse a fragment inside a well-formed UTF-8 document so that
     * multibyte characters survive the round trip through DOMDocument.
     */
    private function loadFragment(string $fragment): ?DOMDocument
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

        return $loaded ? $document : null;
    }

    private function cleanChildren(DOMNode $parent): void
    {
        $node = $parent->firstChild;

        while ($node !== null) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $next = $this->nextNode($node, $parent);
                $parent->removeChild($node);
                $node = $next;

                continue;
            }

            if ($node->nodeType !== XML_ELEMENT_NODE) {
                if ($node->nodeType === XML_TEXT_NODE) {
                    $node->nodeValue = $this->cleanText($node->nodeValue ?? '');
                }
                $node = $this->nextNode($node, $parent);

                continue;
            }

            /** @var DOMElement $node */
            $tag = strtolower($node->nodeName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $next = $this->nextNode($node, $parent);
                $parent->removeChild($node);
                $node = $next;

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown but harmless container: promote the children and
                // continue the walk from the first promoted node.
                $first = $node->firstChild;
                $next = $this->nextNode($node, $parent);
                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                $node = $first ?? $next;

                continue;
            }

            $this->cleanAttributes($node, $tag);

            if ($node->firstChild !== null) {
                $node = $node->firstChild;

                continue;
            }

            $node = $this->nextNode($node, $parent);
        }
    }

    /**
     * In-order successor of $node within $parent, or null when the walk
     * has covered the whole subtree.
     */
    private function nextNode(DOMNode $node, DOMNode $parent): ?DOMNode
    {
        if ($node->nextSibling !== null) {
            return $node->nextSibling;
        }

        while ($node !== $parent && $node->nextSibling === null) {
            $node = $node->parentNode;
        }

        if ($node === $parent || $node->parentNode === null) {
            return null;
        }

        return $node->nextSibling;
    }

    private function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(self::SHARED_ATTRIBUTES, self::TAG_ATTRIBUTES[$tag] ?? []);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'style') {
                $style = $this->sanitizeStyle($value);
                if ($style === '') {
                    $element->removeAttribute($attribute->name);
                } else {
                    $element->setAttribute('style', $style);
                }

                continue;
            }

            if ($name === 'class') {
                $kept = array_values(array_intersect(
                    preg_split('/\s+/', $value) ?: [],
                    self::ALLOWED_CLASSES
                ));
                if ($kept === []) {
                    $element->removeAttribute($attribute->name);
                } else {
                    $element->setAttribute('class', implode(' ', $kept));
                }

                continue;
            }

            if ($name === 'href' && ! $this->isSafeUrl($value, ['http', 'https', 'mailto', 'tel'])) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'src' && ! $this->isSafeUrl($value, ['http', 'https', 'data'])) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['width', 'height'], true) && ! preg_match('/^\d{1,4}%?$/', $value)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['colspan', 'rowspan', 'span', 'start'], true) && ! preg_match('/^\d{1,3}$/', $value)) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    /**
     * Keep only allowlisted declarations whose values carry no code
     * execution or external resource loading primitives.
     */
    private function sanitizeStyle(string $style): string
    {
        $kept = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $value = trim($value, " \t\n\r\0\x0B'\"");

            if (! in_array($property, self::ALLOWED_CSS_PROPERTIES, true)) {
                continue;
            }

            if ($value === '' || preg_match(self::DANGEROUS_CSS, $value)) {
                continue;
            }

            $kept[] = $property . ': ' . $value;
        }

        return implode('; ', $kept);
    }

    /**
     * @param  list<string>  $schemes
     */
    private function isSafeUrl(string $url, array $schemes): bool
    {
        if ($url === '') {
            return false;
        }

        // In-document and relative links are harmless.
        if (str_starts_with($url, '/') || str_starts_with($url, '#') || str_starts_with($url, '?')) {
            return ! str_starts_with($url, '//');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme === 'data') {
            return (bool) preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=\s]+$#i', $url);
        }

        return in_array($scheme, $schemes, true);
    }

    private function cleanText(string $text): string
    {
        // Drop control characters that would corrupt rendering or storage.
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    }
}
