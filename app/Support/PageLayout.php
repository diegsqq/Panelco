<?php

namespace App\Support;

/**
 * Page setup vocabulary shared by the editor, print CSS and the
 * DOCX/PDF exporters. Millimetres are the source of truth; conversions
 * to CSS units and OOXML twips happen here so every surface agrees.
 */
final class PageLayout
{
    /** @var array<string, array{label: string, width_mm: float, height_mm: float}> */
    public const PAPERS = [
        'a4' => ['label' => 'A4', 'width_mm' => 210.0, 'height_mm' => 297.0],
        'letter' => ['label' => 'Letter', 'width_mm' => 215.9, 'height_mm' => 279.4],
        'legal' => ['label' => 'Legal', 'width_mm' => 215.9, 'height_mm' => 355.6],
    ];

    /** @var array<string, string> */
    public const ORIENTATIONS = [
        'portrait' => 'Portrait',
        'landscape' => 'Landscape',
    ];

    /** @var array<string, array{label: string, mm: float}> */
    public const MARGINS = [
        'narrow' => ['label' => 'Narrow (0.5")', 'mm' => 12.7],
        'normal' => ['label' => 'Normal (1")', 'mm' => 25.4],
        'wide' => ['label' => 'Wide (2")', 'mm' => 50.8],
    ];

    /** Fallbacks used when a stored value is missing or unknown. */
    public const DEFAULTS = ['paper' => 'a4', 'orientation' => 'portrait', 'margins' => 'normal'];

    /**
     * Oriented page dimensions in millimetres.
     *
     * @return array{width_mm: float, height_mm: float}
     */
    public static function dimensions(string $paper, string $orientation): array
    {
        $spec = self::PAPERS[$paper] ?? self::PAPERS[self::DEFAULTS['paper']];
        $landscape = $orientation === 'landscape';

        return [
            'width_mm' => $landscape ? $spec['height_mm'] : $spec['width_mm'],
            'height_mm' => $landscape ? $spec['width_mm'] : $spec['height_mm'],
        ];
    }

    public static function marginMm(string $margins): float
    {
        return self::MARGINS[$margins]['mm'] ?? self::MARGINS[self::DEFAULTS['margins']]['mm'];
    }

    /** @page rule value for CSS and dompdf ("A4", "Letter", "Legal"). */
    public static function cssPaper(string $paper): string
    {
        $label = self::PAPERS[$paper]['label'] ?? self::PAPERS[self::DEFAULTS['paper']]['label'];

        return $label;
    }

    /** OOXML section style array (twips) for PhpWord. */
    public static function sectionStyle(string $paper, string $orientation, string $margins): array
    {
        $dim = self::dimensions($paper, $orientation);
        $margin = self::twips(self::marginMm($margins));

        return [
            'paperSize' => self::cssPaper($paper),
            'orientation' => $orientation === 'landscape' ? 'landscape' : 'portrait',
            'marginLeft' => $margin,
            'marginRight' => $margin,
            'marginTop' => $margin,
            'marginBottom' => $margin,
            'pageSizeW' => self::twips($dim['width_mm']),
            'pageSizeH' => self::twips($dim['height_mm']),
        ];
    }

    public static function twips(float $mm): int
    {
        return (int) round($mm * 566.929133858);
    }

    /**
     * Page number tokens accepted in headers/footers.
     *
     * @return array<string, string> token => OOXML field name
     */
    public static function tokens(): array
    {
        return ['{page}' => 'PAGE', '{pages}' => 'NUMPAGES'];
    }
}
