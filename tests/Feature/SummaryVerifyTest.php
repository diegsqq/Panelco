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

    public function test_summary_matching_text_shows_verified_badge(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc(
            $emp,
            'Current ratio of 7.68 and ROE of 83.05% confirm strong liquidity.',
            'Financial Ratios: Current Ratio 7.68. ROE 83.05%. Interpretation follows.'
        );

        $this->actingAs($emp)->get(route('documents.show', $doc))
            ->assertOk()
            ->assertSee('sum-verified', false)
            ->assertDontSee('sum-unverified', false);
    }

    public function test_summary_with_invented_figure_shows_unverified_and_lists_it(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc(
            $emp,
            'Current ratio of 9.99 shows very strong liquidity.',
            'Financial Ratios: Current Ratio 7.68. Interpretation follows.'
        );

        $this->actingAs($emp)->get(route('documents.show', $doc))
            ->assertOk()
            ->assertSee('sum-unverified', false)
            ->assertDontSee('sum-verified', false)
            ->assertSee('Not found in the document text')
            ->assertSee('9.99');
    }

    public function test_doc_without_summary_shows_no_verification_badge(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeDoc($emp, null, 'Some extracted text without any summary yet.');

        $this->actingAs($emp)->get(route('documents.show', $doc))
            ->assertOk()
            ->assertDontSee('sum-verified', false)
            ->assertDontSee('sum-unverified', false);
    }
}
