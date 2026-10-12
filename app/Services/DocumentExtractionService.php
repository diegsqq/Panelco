<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentExtractionService
{
    /**
     * @return array{text: string, pages: array<int, string>}
     *
     * @throws ExtractionFailedException
     */
    public function extract(Document $document): array
    {
        $disk = Storage::disk('private');

        if (! $disk->exists($document->storage_path)) {
            throw new ExtractionFailedException('The stored file could not be found. Please re-upload the document.');
        }

        $ext = strtolower(pathinfo($document->original_filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => $this->fromPdf($disk->path($document->storage_path)),
            'docx' => $this->fromDocx($disk->path($document->storage_path)),
            'txt' => ['text' => $this->fromText($disk->get($document->storage_path)), 'pages' => []],
            'png', 'jpg', 'jpeg' => $this->fromImage($disk->path($document->storage_path)),
            default => throw new ExtractionFailedException('Unsupported file type for text extraction.'),
        };
    }

    private function fromPdf(string $path): array
    {
        try {
            $pdf = (new PdfParser())->parseFile($path);
        } catch (\Throwable $e) {
            throw new ExtractionFailedException('The PDF could not be read. It may be corrupt — please upload a readable copy.');
        }

        $pages = [];
        foreach ($pdf->getPages() as $i => $page) {
            $text = trim(preg_replace('/\s+/', ' ', (string) $page->getText()));
            if ($text !== '') {
                $pages[$i + 1] = $text;
            }
        }

        $full = trim(implode("\n\n", $pages));

        if ($full === '') {
            throw new ExtractionFailedException(
                'The document was found, but its contents could not be extracted. It may be a scanned image — ' .
                ($this->ocrAvailable()
                    ? 'OCR will be attempted by the processing pipeline.'
                    : 'please upload a readable copy or scan it again.')
            );
        }

        return ['text' => $full, 'pages' => $pages];
    }

    private function fromDocx(string $path): array
    {
        try {
            $phpWord = IOFactory::load($path, 'Word2007');
        } catch (\Throwable $e) {
            throw new ExtractionFailedException('The DOCX file could not be read. It may be corrupt — please upload a readable copy.');
        }

        $parts = [];
        foreach ($phpWord->getSections() as $section) {
            $parts[] = $this->containerText($section);
        }

        $full = trim(preg_replace('/[ \t]+/', ' ', implode("\n", array_filter($parts))));

        if ($full === '') {
            throw new ExtractionFailedException('The document was found, but its contents could not be extracted. Please upload a readable copy.');
        }

        return ['text' => $full, 'pages' => []];
    }

    private function containerText(AbstractContainer $container): string
    {
        $out = [];
        foreach ($container->getElements() as $el) {
            if ($el instanceof Text) {
                $out[] = $el->getText();
            } elseif ($el instanceof TextRun) {
                foreach ($el->getElements() as $inner) {
                    if ($inner instanceof Text) {
                        $out[] = $inner->getText();
                    }
                }
            } elseif ($el instanceof Table) {
                foreach ($el->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $out[] = $this->containerText($cell);
                    }
                }
            } elseif ($el instanceof AbstractContainer) {
                $out[] = $this->containerText($el);
            }
        }

        return implode(' ', array_filter($out));
    }

    private function fromText(?string $contents): string
    {
        $text = trim((string) $contents);
        if ($text === '') {
            throw new ExtractionFailedException('The text file is empty. Please upload a document with content.');
        }

        return $text;
    }

    private function fromImage(string $path): array
    {
        $binary = $this->ocrBinary();
        if ($binary === null) {
            throw new ExtractionFailedException('The document was found, but its contents could not be extracted. Image OCR is not installed — please upload a readable copy or scan it again.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ocr') . '.txt';
        $cmd = escapeshellarg($binary) . ' ' . escapeshellarg($path) . ' ' . escapeshellarg(substr($tmp, 0, -4)) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        $text = '';
        if (is_file($tmp)) {
            $text = trim((string) file_get_contents($tmp));
            @unlink($tmp);
        }

        if ($code !== 0 || $text === '') {
            throw new ExtractionFailedException('The document was found, but its contents could not be extracted. Please upload a readable copy or scan it again.');
        }

        return ['text' => $text, 'pages' => [1 => $text]];
    }

    public function ocrAvailable(): bool
    {
        return $this->ocrBinary() !== null;
    }

    private function ocrBinary(): ?string
    {
        $configured = config('smartdocs.ocr.binary');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $probe = [];
        $code = 0;
        @exec('tesseract --version 2>&1', $probe, $code);

        return $code === 0 ? 'tesseract' : null;
    }
}
