<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase3UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_pdf_upload_creates_pending_record(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $response = $this->actingAs($emp)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
            'title' => 'Test Report',
        ]);

        $response->assertRedirect(route('documents.library'));
        $doc = Document::where('original_filename', 'report.pdf')->first();
        $this->assertNotNull($doc);
        $this->assertEquals('pending', $doc->processing_status);
        $this->assertEquals($emp->id, $doc->uploaded_by);
        Storage::disk('private')->assertExists($doc->storage_path);
        Queue::assertPushed(ProcessDocument::class);
    }

    public function test_unsupported_file_rejected(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->create('evil.exe', 100, 'application/x-msdownload'),
        ])->assertSessionHasErrors('file');

        $this->assertEquals(0, Document::count());
    }

    public function test_guest_cannot_upload(): void
    {
        $this->get(route('documents.upload'))->assertRedirect('/login');
    }

    public function test_user_cannot_download_restricted_document(): void
    {
        $this->seed();
        Storage::fake('private');
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $other = User::factory()->create(['role' => 'employee', 'department_id' => null]);

        $path = 'documents/test.pdf';
        Storage::disk('private')->put($path, 'content');
        $doc = Document::create([
            'uploaded_by' => $admin->id,
            'original_filename' => 'secret.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 7,
            'department_id' => $admin->department_id,
            'processing_status' => 'completed',
        ]);

        $this->actingAs($other)->get(route('documents.download', $doc))->assertForbidden();
        $this->actingAs($admin)->get(route('documents.download', $doc))->assertOk();
    }

    public function test_retry_failed_without_duplicates(): void
    {
        $this->seed();
        Storage::fake('private');
        $emp = User::where('email', 'employee@panelco3.local')->first();
        Storage::disk('private')->put('documents/good.txt', 'Retry content here for processing');
        $doc = Document::create([
            'uploaded_by' => $emp->id,
            'original_filename' => 'good.txt',
            'storage_path' => 'documents/good.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'failed',
            'processing_error' => 'Extraction failed',
        ]);

        $this->actingAs($emp)->post(route('documents.retry', $doc))->assertRedirect();
        $this->assertEquals(1, Document::count());
        // Sync queue in tests runs the job inline: failed → completed.
        $this->assertEquals('completed', $doc->fresh()->processing_status);
        $this->assertNull($doc->fresh()->processing_error);
    }

    public function test_library_requires_auth(): void
    {
        $this->get(route('documents.library'))->assertRedirect('/login');
    }
}
