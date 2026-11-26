<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function document(User $user, array $attributes = []): Document
    {
        return Document::create(array_merge([
            'uploaded_by' => $user->id,
            'title' => 'Preview test',
            'original_filename' => 'report.pdf',
            'storage_path' => 'documents/report.pdf',
            'file_hash' => hash('sha256', 'preview contents'),
            'current_version_uploaded_by' => $user->id,
            'mime_type' => 'application/pdf',
            'file_size' => 16,
            'processing_status' => 'completed',
            'access_level' => 'all_employees',
        ], $attributes));
    }

    public function test_pdf_preview_renders_the_original_file_from_private_storage(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $document = $this->document($user);
        Storage::disk('private')->put($document->storage_path, '%PDF-1.4 preview contents');

        $this->actingAs($user)->get(route('documents.preview', $document))
            ->assertOk()
            ->assertSee('PDF preview of', false)
            ->assertSee(route('documents.view', $document), false);

        $response = $this->actingAs($user)->get(route('documents.view', $document));
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF-1.4 preview contents', $response->streamedContent());
    }

    public function test_word_preview_uses_read_only_onlyoffice_when_enabled(): void
    {
        config()->set('onlyoffice.enabled', true);
        Storage::fake('private');
        $user = User::factory()->create();
        $document = $this->document($user, [
            'title' => 'Word preview test',
            'original_filename' => 'report.docx',
            'storage_path' => 'documents/report.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
        Storage::disk('private')->put($document->storage_path, 'docx contents');

        $this->actingAs($user)->get(route('documents.preview', $document))
            ->assertOk()
            ->assertSee('Document preview of', false)
            ->assertSee(route('documents.edit-content', $document) . '?view=1', false);
    }

    public function test_text_preview_shows_extracted_or_original_text(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $document = $this->document($user, [
            'original_filename' => 'notes.txt',
            'storage_path' => 'documents/notes.txt',
            'mime_type' => 'text/plain',
            'extracted_text' => 'Extracted paragraph from the uploaded file.',
        ]);
        Storage::disk('private')->put($document->storage_path, 'Original text from the uploaded file.');

        $this->actingAs($user)->get(route('documents.preview', $document))
            ->assertOk()
            ->assertSee('Original text from the uploaded file.', false)
            ->assertDontSee('Preview unavailable', false);
    }

    public function test_missing_stored_file_shows_a_clear_preview_error(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $document = $this->document($user);

        $this->actingAs($user)->get(route('documents.preview', $document))
            ->assertOk()
            ->assertSee('Preview unavailable', false)
            ->assertSee('not available in centralized storage', false);
    }
}
