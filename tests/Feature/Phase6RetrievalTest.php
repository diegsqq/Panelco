<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6RetrievalTest extends TestCase
{
    use RefreshDatabase;

    private function completedDoc(User $uploader, string $title, string $text, ?int $dept = null): Document
    {
        $doc = Document::create([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'original_filename' => $title . '.txt',
            'storage_path' => 'documents/x.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($text),
            'department_id' => $dept,
            'processing_status' => 'completed',
            'extracted_text' => $text,
        ]);
        $doc->chunks()->create(['chunk_index' => 0, 'content' => $text, 'page_number' => 2]);

        return $doc;
    }

    public function test_finds_authorized_chunk_with_citation(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $this->completedDoc($emp, 'Transformer Report', 'The damaged transformer was replaced by crew', $emp->department_id);

        $result = app(DocumentRetrievalService::class)->retrieve($emp, 'damaged transformer');

        $this->assertEquals(DocumentRetrievalService::STATUS_OK, $result['status']);
        $this->assertCount(1, $result['chunks']);
        $hit = $result['chunks'][0];
        $this->assertEquals(2, $hit['chunk']->page_number);
        $this->assertStringContainsString('transformer', strtolower($hit['excerpt']));
        $this->assertEquals('Transformer Report', $hit['document']->title);
    }

    public function test_excludes_restricted_department_document(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $otherDept = Department::create(['name' => 'Secret Ops']);
        $this->completedDoc($admin, 'Secret Plans', 'classified transformer vault codes', $otherDept->id);

        $result = app(DocumentRetrievalService::class)->retrieve($emp, 'transformer vault');

        // Must never disclose the restricted doc: no OK status, no chunks,
        // and no message revealing that a matching restricted file exists.
        $this->assertNotEquals(DocumentRetrievalService::STATUS_OK, $result['status']);
        $this->assertEmpty($result['chunks']);
        $this->assertStringNotContainsString('Secret', DocumentRetrievalService::statusMessage($result['status']));
    }

    public function test_empty_library_message(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $result = app(DocumentRetrievalService::class)->retrieve($emp, 'transformer');

        $this->assertEquals(DocumentRetrievalService::STATUS_NO_DOCUMENTS, $result['status']);
        $this->assertEquals(
            'No documents have been uploaded yet. Please upload a document before asking questions about stored files.',
            DocumentRetrievalService::statusMessage($result['status'])
        );
    }

    public function test_pending_documents_not_searched(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        Document::create([
            'uploaded_by' => $emp->id,
            'title' => 'Pending transformer notes',
            'original_filename' => 'p.txt',
            'storage_path' => 'documents/p.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'pending',
        ]);

        $result = app(DocumentRetrievalService::class)->retrieve($emp, 'transformer');

        $this->assertEquals(DocumentRetrievalService::STATUS_NO_MATCH, $result['status']);
    }

    public function test_threshold_filters_weak_matches(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $this->completedDoc($emp, 'General Memo', 'Please be informed about the holiday schedule', $emp->department_id);

        config(['smartdocs.retrieval.min_score' => 999]);
        $result = app(DocumentRetrievalService::class)->retrieve($emp, 'holiday schedule');

        $this->assertEquals(DocumentRetrievalService::STATUS_NO_MATCH, $result['status']);
    }
}
