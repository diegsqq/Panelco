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
     * Extracts straight from a not-yet-stored upload (pre-save suggestions).
     *
     * @throws ExtractionFailedException
     */
    public function extractFromUploadedFile(\Illuminate\Http\UploadedFile $file): string
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $path = (string) $file->getRealPath();

        return match ($ext) {
            'pdf' => $this->fromPdf($path)['text'],
            'docx' => $this->fromDocx($path)['text'],
            'txt' => $this->fromText((string) file_get_contents($path)),
            'png', 'jpg', 'jpeg' => $this->fromImage($path)['text'],
            default => throw new ExtractionFailedException('Unsupported file type for text extraction.'),
        };
    }

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
                'please upload a readable (text-based) copy or scan it again.'
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
            // The DOCX may contain only scanned images (no text runs).
            // Try OCR on every embedded image before giving up.
            $ocrParts = [];
            foreach ($this->docxImagePaths($path) as $imgPath) {
                $t = $this->ocrFile($imgPath);
                @unlink($imgPath);
                if ($t !== '') {
                    $ocrParts[] = $t;
                }
            }
            $full = trim(implode("\n\n", $ocrParts));
        }

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
        $text = $this->ocrFile($path);

        if ($text === '') {
            throw new ExtractionFailedException('The document was found, but its contents could not be extracted. Image OCR found no readable text — please upload a clearer scan.');
        }

        return ['text' => $text, 'pages' => [1 => $text]];
    }

    public function ocrAvailable(): bool
    {
        return $this->ocrBinary() !== null;
    }

    private function ocrFile(string $path): string
    {
        $binary = $this->ocrBinary();
        if ($binary === null) {
            return '';
        }

        // Tesseract does not accept every image container (e.g. JFIF);
        // normalise to PNG via GD when the extension is not directly
        // supported, so embedded DOCX images (image1.jfif) still OCR.
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $input = $path;
        $tmpPng = null;
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'tif', 'tiff', 'bmp'], true)) {
            if (extension_loaded('gd')) {
                $im = @imagecreatefromstring((string) @file_get_contents($path));
                if ($im !== false) {
                    $tmpPng = tempnam(sys_get_temp_dir(), 'ocrimg') . '.png';
                    imagepng($im, $tmpPng);
                    imagedestroy($im);
                    $input = $tmpPng;
                }
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ocr') . '.txt';
        $best = '';
        $bestScore = 0;
        foreach ([6, 4, 3, 11] as $psm) {
            $runTmp = tempnam(sys_get_temp_dir(), 'ocr') . '.txt';
            $cmd = escapeshellarg($binary) . ' ' . escapeshellarg($input)
                . ' ' . escapeshellarg(substr($runTmp, 0, -4))
                . ' --psm ' . $psm . ' -l eng 2>&1';
            $output = [];
            $code = 0;
            exec($cmd, $output, $code);

            $text = '';
            if (is_file($runTmp)) {
                $text = trim((string) file_get_contents($runTmp));
                @unlink($runTmp);
            }
            if ($code !== 0 || $text === '') {
                continue;
            }
            // Score by alphanumeric content; prefer the pass that yields
            // the most real (letter/digit) characters.
            $score = preg_match_all('/[\p{L}\p{N}]/u', $text);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $text;
            }
        }
        @unlink($tmp);

        $text = $best;

        if ($tmpPng !== null) {
            @unlink($tmpPng);
        }

        return $text;
    }

    /**
     * Extract embedded image files from a DOCX archive to temp files.
     *
     * @return string[]
     */
    private function docxImagePaths(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $paths = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! preg_match('#^word/media/.+#i', (string) $name)) {
                continue;
            }
            $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (! in_array($ext, ['png', 'jpg', 'jpeg', 'jfif', 'gif', 'bmp', 'tif', 'tiff', 'webp'], true)) {
                continue;
            }
            $bin = $zip->getFromIndex($i);
            if ($bin === false) {
                continue;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'docximg');
            file_put_contents($tmp, $bin);
            $paths[] = $tmp;
        }
        $zip->close();

        return $paths;
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
