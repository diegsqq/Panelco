<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummaryVerifyTest extends TestCase
{
    use RefreshDatabase;

    private function makeDoc(User $uploader, ?string $summary, string $text): Document
    {
        return Document::create([
            'uploaded_by' => $uploader->id,
            'title' => 'Ratios',
            'original_filename' => 'ratios.docx',
            'storage_path' => 'documents/ratios.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 100,
            'department_id' => null,
            'processing_status' => 'completed',
            'summary' => $summary,
            'extracted_text' => $text,
        ]);
    }

    public function test_editor_page_loads_for_document_with_matching_summary(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc(
            $emp,
            'Current ratio of 7.68 and ROE of 83.05% confirm strong liquidity.',
            'Financial Ratios: Current Ratio 7.68. ROE 83.05%. Interpretation follows.'
        );

        $this->actingAs($emp)->get(route('documents.edit-content', $doc))->assertOk();
        $this->assertSame(
            'Current ratio of 7.68 and ROE of 83.05% confirm strong liquidity.',
            $doc->fresh()->summary
        );
    }

    public function test_editor_page_loads_for_document_with_unverified_summary(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc(
            $emp,
            'Current ratio of 9.99 shows very strong liquidity.',
            'Financial Ratios: Current Ratio 7.68. Interpretation follows.'
        );

        $this->actingAs($emp)->get(route('documents.edit-content', $doc))->assertOk();
        $this->assertSame('Current ratio of 9.99 shows very strong liquidity.', $doc->fresh()->summary);
    }

    public function test_editor_page_loads_for_document_without_summary(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc($emp, null, 'Some extracted text without any summary yet.');

        $this->actingAs($emp)->get(route('documents.edit-content', $doc))->assertOk();
        $this->assertNull($doc->fresh()->summary);
    }
}
