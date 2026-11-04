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

    public function test_library_renders_documents_and_filters_by_status(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();

        Document::create([
            'uploaded_by' => $admin->id,
            'title' => 'Approved plan',
            'original_filename' => 'approved.pdf',
            'storage_path' => 'documents/approved.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'processing_status' => 'completed',
        ]);
        Document::create([
            'uploaded_by' => $admin->id,
            'title' => 'Pending plan',
            'original_filename' => 'pending.pdf',
            'storage_path' => 'documents/pending.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'processing_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('documents.library', ['status' => 'completed']))
            ->assertOk()
            ->assertSeeText('Documents')
            ->assertSeeText('Approved plan')
            ->assertSeeText('Complete')
            ->assertDontSeeText('Pending plan');
    }

    public function test_employee_library_and_document_access_are_owner_only(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->first();
        $otherEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'department_id' => $employee->department_id,
        ]);
        $own = Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'My private report',
            'original_filename' => 'mine.txt',
            'storage_path' => 'documents/mine.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'department_id' => $employee->department_id,
            'processing_status' => 'completed',
        ]);
        $shared = Document::create([
            'uploaded_by' => $otherEmployee->id,
            'title' => 'Department shared report',
            'original_filename' => 'shared.txt',
            'storage_path' => 'documents/shared.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'department_id' => $employee->department_id,
            'processing_status' => 'completed',
        ]);

        $this->actingAs($employee)->get(route('documents.library'))
            ->assertOk()
            ->assertSeeText('My private report')
            ->assertDontSeeText('Department shared report');
        $this->get(route('documents.show', $own))->assertOk();
        $this->get(route('documents.show', $shared))->assertForbidden();
    }

    public function test_document_page_shows_vertical_summary_details_and_actions(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->first();
        $doc = Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Transformer Repair Report',
            'original_filename' => 'repair.txt',
            'storage_path' => 'documents/repair.txt',
            'mime_type' => 'text/plain',
            'file_size' => 80,
            'processing_status' => 'completed',
            'summary' => 'The crew repaired the damaged transformer.',
            'extracted_text' => 'The crew repaired the damaged transformer. A work order was filed.',
        ]);

        $this->actingAs($employee)->get(route('documents.show', $doc))
            ->assertOk()
            ->assertSeeText('Document Summary')
            ->assertSeeText('Important Details')
            ->assertSeeText('Corrective Actions')
            ->assertSeeText('repaired the damaged transformer')
            ->assertSee('document_id=' . $doc->id, false);
    }
}
