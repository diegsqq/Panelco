<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentChunker;
use App\Services\DocumentExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class Phase4ExtractionTest extends TestCase
{
    use RefreshDatabase;

    private function makeDoc(User $uploader, string $filename, string $contents, string $mime): Document
    {
        Storage::disk('private')->put('documents/' . $filename, $contents);

        return Document::create([
            'uploaded_by' => $uploader->id,
            'original_filename' => $filename,
            'storage_path' => 'documents/' . $filename,
            'mime_type' => $mime,
            'file_size' => strlen($contents),
            'processing_status' => 'pending',
        ]);
    }

    private function runJob(Document $doc): void
    {
        app()->call([new ProcessDocument($doc->id), 'handle']);
    }

    private function minimalPdf(string $text): string
    {
        $esc = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $content = "BT /F1 24 Tf 100 700 Td ({$esc}) Tj ET";
        $objs = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            4 => "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $n => $body) {
            $offsets[$n] = strlen($pdf);
            $pdf .= "{$n} 0 obj\n{$body}\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= 'xref' . "\n" . '0 ' . (count($objs) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($objs as $n => $_) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$n]);
        }
        $pdf .= 'trailer' . "\n" . '<< /Size ' . (count($objs) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= 'startxref' . "\n" . $xrefPos . "\n" . '%%EOF';

        return $pdf;
    }

    public function test_txt_extracts_and_chunks(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc($emp, 'notes.txt', 'Power interruption reported in Zone 4. Crew dispatched.', 'text/plain');

        $this->runJob($doc);

        $doc->refresh();
        $this->assertEquals('completed', $doc->processing_status);
        $this->assertStringContainsString('Zone 4', $doc->extracted_text);
        $this->assertEquals('Text File', $doc->document_type);
        $this->assertGreaterThanOrEqual(1, $doc->chunks()->count());
    }

    public function test_pdf_extracts_with_page_numbers(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc($emp, 'memo.pdf', $this->minimalPdf('Maintenance report transformer inspected'), 'application/pdf');

        $this->runJob($doc);

        $doc->refresh();
        $this->assertEquals('completed', $doc->processing_status);
        $this->assertStringContainsString('transformer', strtolower($doc->extracted_text));
        $this->assertEquals(1, $doc->chunks()->first()->page_number);
    }

    public function test_docx_extracts(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $phpWord = new PhpWord();
        $phpWord->addSection()->addText('Network incident report connectivity restored');
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $phpWord->save($tmp, 'Word2007');
        $contents = file_get_contents($tmp);

        $doc = $this->makeDoc($emp, 'report.docx', $contents, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->runJob($doc);

        $doc->refresh();
        $this->assertEquals('completed', $doc->processing_status);
        $this->assertStringContainsString('connectivity restored', $doc->extracted_text);
    }

    public function test_corrupt_file_marked_failed(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc($emp, 'broken.pdf', "\x00\x01garbage-not-a-pdf", 'application/pdf');

        $this->runJob($doc);

        $doc->refresh();
        $this->assertEquals('failed', $doc->processing_status);
        $this->assertNotEmpty($doc->processing_error);
        $this->assertNull($doc->summary);
        $this->assertEquals(0, $doc->chunks()->count());
    }

    public function test_reprocessing_does_not_duplicate_chunks(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $long = trim(str_repeat('word ', 1200));
        $doc = $this->makeDoc($emp, 'long.txt', $long, 'text/plain');

        $this->runJob($doc);
        $first = $doc->refresh()->chunks()->count();
        $this->assertGreaterThan(1, $first);

        // Simulate retry: failed → pending → process again.
        $doc->update(['processing_status' => 'failed', 'processing_error' => 'x']);
        $doc->update(['processing_status' => 'pending', 'processing_error' => null]);
        $this->runJob($doc);

        $this->assertEquals(1, Document::count());
        $this->assertEquals($first, $doc->refresh()->chunks()->count());
    }

    public function test_image_without_ocr_fails_gracefully(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        // 1x1 PNG. OCR engine not installed in CI/dev → graceful failure expected.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $doc = $this->makeDoc($emp, 'scan.png', $png, 'image/png');

        $this->runJob($doc);

        $doc->refresh();
        if (! app(DocumentExtractionService::class)->ocrAvailable()) {
            $this->assertEquals('failed', $doc->processing_status);
            $this->assertNotEmpty($doc->processing_error);
        } else {
            $this->assertContains($doc->processing_status, ['completed', 'failed']);
        }
    }
}
