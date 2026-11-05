<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentVersionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_replacing_a_document_preserves_the_old_file_and_queues_the_new_version(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $oldContents = 'The previous document version.';
        $newContents = 'The replacement document version.';
        Storage::disk('private')->put('documents/old.txt', $oldContents);
        $document = Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Versioned report',
            'original_filename' => 'report-v1.txt',
            'storage_path' => 'documents/old.txt',
            'file_hash' => hash('sha256', $oldContents),
            'mime_type' => 'text/plain',
            'file_size' => strlen($oldContents),
            'processing_status' => 'completed',
            'extracted_text' => 'Old extracted text',
            'summary' => 'Old summary',
            'keywords' => 'old, content',
            'classification_review_status' => 'approved',
        ]);
        DocumentChunk::create([
            'document_id' => $document->id,
            'chunk_index' => 0,
            'content' => 'Old indexed content',
        ]);

        $this->actingAs($employee)->put(route('documents.update', $document), [
            'file' => UploadedFile::fake()->createWithContent('report-v2.txt', $newContents),
            'title' => 'Versioned report',
        ])->assertRedirect(route('documents.show', $document));

        $document->refresh();
        $version = DocumentVersion::where('document_id', $document->id)->firstOrFail();

        $this->assertSame(1, $version->version_number);
        $this->assertSame($employee->id, $version->uploaded_by);
        $this->assertSame('report-v1.txt', $version->original_filename);
        $this->assertSame('documents/old.txt', $version->storage_path);
        $this->assertSame(2, $document->version_number);
        $this->assertSame($employee->id, $document->current_version_uploaded_by);
        $this->assertSame('report-v2.txt', $document->original_filename);
        $this->assertSame(hash('sha256', $newContents), $document->file_hash);
        $this->assertSame('pending', $document->processing_status);
        $this->assertNull($document->extracted_text);
        $this->assertNull($document->summary);
        $this->assertSame('pending', $document->classification_review_status);
        $this->assertSame(0, $document->chunks()->count());
        Storage::disk('private')->assertExists('documents/old.txt');
        Storage::disk('private')->assertExists($document->storage_path);
        Queue::assertPushed(ProcessDocument::class, 1);
        $this->assertTrue(AuditLog::where('action', 'document.version_uploaded')
            ->where('entity_id', $document->id)->exists());

        $this->get(route('documents.show', $document))
            ->assertOk()
            ->assertSee('Version history')
            ->assertSee('Version 1')
            ->assertSee('report-v1.txt');
    }

    public function test_identical_current_file_is_not_added_as_a_new_version(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $contents = 'Do not version identical content.';
        Storage::disk('private')->put('documents/current.txt', $contents);
        $document = Document::create([
            'uploaded_by' => $employee->id,
            'original_filename' => 'current.txt',
            'storage_path' => 'documents/current.txt',
            'file_hash' => hash('sha256', $contents),
            'mime_type' => 'text/plain',
            'file_size' => strlen($contents),
            'processing_status' => 'completed',
        ]);

        $this->actingAs($employee)->put(route('documents.update', $document), [
            'file' => UploadedFile::fake()->createWithContent('copy.txt', $contents),
        ])->assertRedirect()
            ->assertSessionHas('status', 'Document updated.');

        $this->assertSame(1, $document->fresh()->version_number);
        $this->assertSame(0, DocumentVersion::count());
        Queue::assertNothingPushed();
    }

    public function test_an_existing_historical_file_cannot_be_added_as_a_duplicate_version(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $oldContents = 'Already stored as an earlier version.';
        $currentContents = 'Current version contents.';
        Storage::disk('private')->put('documents/history.txt', $oldContents);
        Storage::disk('private')->put('documents/current.txt', $currentContents);
        $document = Document::create([
            'uploaded_by' => $employee->id,
            'original_filename' => 'current.txt',
            'storage_path' => 'documents/current.txt',
            'file_hash' => hash('sha256', $currentContents),
            'mime_type' => 'text/plain',
            'file_size' => strlen($currentContents),
            'processing_status' => 'completed',
            'version_number' => 2,
        ]);
        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'uploaded_by' => $employee->id,
            'original_filename' => 'history.txt',
            'storage_path' => 'documents/history.txt',
            'file_hash' => hash('sha256', $oldContents),
            'mime_type' => 'text/plain',
            'file_size' => strlen($oldContents),
        ]);

        $this->actingAs($employee)->put(route('documents.update', $document), [
            'file' => UploadedFile::fake()->createWithContent('duplicate.txt', $oldContents),
        ])->assertSessionHasErrors('file');

        $this->assertSame(2, $document->fresh()->version_number);
        $this->assertSame(1, DocumentVersion::where('document_id', $document->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_deleting_a_document_keeps_its_stored_version_files_for_recovery(): void
    {
        $this->seed();
        Storage::fake('private');
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $document = Document::create([
            'uploaded_by' => $admin->id,
            'original_filename' => 'current.txt',
            'storage_path' => 'documents/current.txt',
            'mime_type' => 'text/plain',
            'file_size' => 7,
            'processing_status' => 'completed',
        ]);
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'uploaded_by' => $admin->id,
            'original_filename' => 'previous.txt',
            'storage_path' => 'documents/previous.txt',
            'mime_type' => 'text/plain',
            'file_size' => 8,
        ]);
        Storage::disk('private')->put($document->storage_path, 'current');
        Storage::disk('private')->put($version->storage_path, 'previous');

        $this->actingAs($admin)->delete(route('documents.destroy', $document))
            ->assertRedirect(route('documents.library'));

        Storage::disk('private')->assertExists('documents/current.txt');
        Storage::disk('private')->assertExists('documents/previous.txt');
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_a_stale_processing_job_does_not_change_the_current_version(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = Document::create([
            'uploaded_by' => $employee->id,
            'original_filename' => 'current.txt',
            'storage_path' => 'documents/current.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'pending',
            'version_number' => 2,
            'extracted_text' => 'Current version text.',
        ]);

        app()->call([new ProcessDocument($document->id, 1), 'handle']);

        $this->assertSame('pending', $document->fresh()->processing_status);
        $this->assertSame('Current version text.', $document->fresh()->extracted_text);
        $this->assertSame(0, $document->chunks()->count());
    }

    public function test_version_download_uses_parent_document_permissions(): void
    {
        $this->seed();
        Storage::fake('private');
        $owner = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $other = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        Storage::disk('private')->put('documents/history.txt', 'Previous version contents');
        $document = Document::create([
            'uploaded_by' => $owner->id,
            'original_filename' => 'current.txt',
            'storage_path' => 'documents/current.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'completed',
        ]);
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'uploaded_by' => $owner->id,
            'original_filename' => 'previous.txt',
            'storage_path' => 'documents/history.txt',
            'mime_type' => 'text/plain',
            'file_size' => 25,
        ]);

        $this->actingAs($other)
            ->get(route('documents.versions.download', [$document, $version]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('documents.versions.download', [$document, $version]))
            ->assertDownload('previous.txt');

        $this->assertTrue(AuditLog::where('action', 'document.version_downloaded')
            ->where('entity_id', $document->id)->exists());
    }
}
