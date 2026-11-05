<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimilarDocumentDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_page_shows_similar_accessible_documents_but_hides_inaccessible_matches(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $commonText = implode(' ', [
            'transformer maintenance inspection schedule electrical distribution equipment safety procedures',
            'technicians examine cooling systems protective relays voltage readings and connection integrity',
            'maintenance teams document findings report unusual vibration monitor temperature and verify operating conditions',
            'scheduled inspections reduce equipment failure improve reliability and support safe service continuity',
        ]);
        $current = $this->createDocument($employee, 'Current maintenance report', $commonText.' current version details');
        $match = $this->createDocument($employee, 'Related maintenance report', $commonText.' related version details');
        $this->createDocument($employee, 'Unrelated meeting notes', 'The cafeteria menu changes every Friday with seasonal meals and employee announcements.');
        $this->createDocument($otherEmployee, 'Private matching report', $commonText.' another employee confidential document');

        $this->actingAs($employee)->get(route('documents.show', $current))
            ->assertOk()
            ->assertSee('Potentially similar documents')
            ->assertSee('Related maintenance report')
            ->assertSee('similarity')
            ->assertDontSee('Private matching report')
            ->assertDontSee('Unrelated meeting notes');

        $this->assertNotSame($current->id, $match->id);
    }

    public function test_short_or_unrelated_content_does_not_produce_similarity_matches(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $short = $this->createDocument($employee, 'Short document', 'A few words only.');
        $this->createDocument($employee, 'Short match', 'A few words only.');

        $this->actingAs($employee)->get(route('documents.show', $short))
            ->assertOk()
            ->assertDontSee('Potentially similar documents');
    }

    private function createDocument(User $uploader, string $title, string $text): Document
    {
        return Document::create([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'original_filename' => str_replace(' ', '-', strtolower($title)).'.txt',
            'storage_path' => 'documents/'.str_replace(' ', '-', strtolower($title)).'.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($text),
            'processing_status' => 'completed',
            'extracted_text' => $text,
        ]);
    }
}
