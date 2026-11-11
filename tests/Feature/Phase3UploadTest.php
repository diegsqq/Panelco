<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
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

    public function test_exact_duplicate_upload_is_skipped_with_a_clear_warning(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $contents = "Identical document contents for duplicate detection.\n";

        $this->actingAs($employee)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('original.txt', $contents),
        ])->assertRedirect(route('documents.library'));

        $original = Document::where('original_filename', 'original.txt')->firstOrFail();
        $this->assertSame(hash('sha256', $contents), $original->file_hash);

        $this->actingAs($employee)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('renamed-copy.txt', $contents),
        ])->assertRedirect(route('documents.library'))
            ->assertSessionHas('warning', 'No new documents were uploaded. Skipped identical file(s): renamed-copy.txt.');

        $this->assertSame(1, Document::count());
        Queue::assertPushed(ProcessDocument::class, 1);
    }

    public function test_duplicate_files_in_a_batch_only_create_one_document(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $contents = 'Same document selected twice in one batch.';

        $this->actingAs($employee)->post(route('documents.store'), [
            'file' => [
                UploadedFile::fake()->createWithContent('first.txt', $contents),
                UploadedFile::fake()->createWithContent('second.txt', $contents),
            ],
        ])->assertRedirect(route('documents.library'))
            ->assertSessionHas('status', 'Document uploaded and queued for processing.')
            ->assertSessionHas('warning', 'Skipped identical file(s): second.txt.');

        $this->assertSame(1, Document::count());
        Queue::assertPushed(ProcessDocument::class, 1);
    }

    public function test_duplicate_detection_backfills_hash_for_legacy_document(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $contents = 'Existing stored document with no hash yet.';
        Storage::disk('private')->put('documents/legacy.txt', $contents);
        $legacy = Document::create([
            'uploaded_by' => $employee->id,
            'original_filename' => 'legacy.txt',
            'storage_path' => 'documents/legacy.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($contents),
            'processing_status' => 'completed',
        ]);

        $this->actingAs($employee)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('legacy-copy.txt', $contents),
        ])->assertRedirect(route('documents.library'))
            ->assertSessionHas('warning');

        $this->assertSame(hash('sha256', $contents), $legacy->fresh()->file_hash);
        $this->assertSame(1, Document::count());
    }

    public function test_duplicate_hidden_from_user_does_not_block_upload(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $contents = "Private record only the admin can see.\n";

        $this->actingAs($admin)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('Daily Time Record.txt', $contents),
            'access_level' => 'private',
        ])->assertRedirect(route('documents.library'));

        $this->assertSame(1, Document::count());

        // Employee cannot see the admin's private copy, so it must not block them.
        $this->actingAs($employee)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('Daily Time Record.txt', $contents),
        ])->assertRedirect(route('documents.library'))
            ->assertSessionHas('status', 'Document uploaded and queued for processing.')
            ->assertSessionMissing('warning');

        $this->assertSame(2, Document::count());
        Queue::assertPushed(ProcessDocument::class, 2);
    }

    public function test_duplicate_visible_to_user_is_still_skipped(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $contents = "Shared record everyone can see.\n";

        $this->actingAs($admin)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('Daily Time Record.txt', $contents),
            'access_level' => 'all_employees',
        ])->assertRedirect(route('documents.library'));

        $this->actingAs($employee)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('Daily Time Record.txt', $contents),
        ])->assertRedirect(route('documents.library'))
            ->assertSessionHas('warning', 'No new documents were uploaded. Skipped identical file(s): Daily Time Record.txt.');

        $this->assertSame(1, Document::count());
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

    public function test_upload_picker_is_keyboard_accessible_and_announces_file_status(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();

        $this->actingAs($employee)
            ->get(route('documents.upload'))
            ->assertOk()
            ->assertSee('role="button" tabindex="0" aria-label="Choose files to upload"', false)
            ->assertSee('aria-describedby="uploadInstructions" aria-controls="file"', false)
            ->assertSee('id="fileMeta" role="status" aria-live="polite"', false)
            ->assertSee('id="suggestNote" role="status" aria-live="polite"', false)
            ->assertSee('input.files[0]', false)
            ->assertDontSee('input.files = (function ()', false);
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

    public function test_library_csv_export_respects_access_filters_and_escapes_spreadsheet_formulas(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        Document::create([
            'uploaded_by' => $employee->id,
            'title' => '=HYPERLINK("https://example.invalid","Open")',
            'original_filename' => 'report.txt',
            'storage_path' => 'documents/report.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'processing_status' => 'completed',
            'extracted_text' => 'private document contents are excluded',
        ]);
        Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Unmatched own document',
            'original_filename' => 'other.txt',
            'storage_path' => 'documents/other.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'processing_status' => 'completed',
        ]);
        Document::create([
            'uploaded_by' => $otherEmployee->id,
            'title' => '=HYPERLINK("https://example.invalid","Private")',
            'original_filename' => 'private.txt',
            'storage_path' => 'documents/private.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'processing_status' => 'completed',
        ]);

        $response = $this->actingAs($employee)->get(route('documents.export', ['q' => 'HYPERLINK']));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('cache-control', 'no-store, private');
        $this->assertStringContainsString('.csv', (string) $response->headers->get('content-disposition'));
        $csv = $response->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Unmatched own document', $csv);
        $this->assertStringNotContainsString('Private', $csv);
        $this->assertStringNotContainsString('private document contents are excluded', $csv);
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
            ->assertSee('<caption class="visually-hidden">Document library results</caption>', false)
            ->assertSee('aria-label="Document result pages"', false)
            ->assertSee('aria-label="Page 1 of 1" aria-current="page"', false)
            ->assertSeeText('Approved plan')
            ->assertSeeText('Complete')
            ->assertDontSeeText('Pending plan');
    }

    public function test_library_searches_title_keywords_and_extracted_content(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();

        foreach ([
            ['title' => 'Transformer Maintenance Plan', 'keywords' => 'inspection,oil testing', 'extracted_text' => 'Routine checks completed.'],
            ['title' => 'Asset Report', 'keywords' => 'equipment', 'extracted_text' => 'Transformer maintenance is scheduled for next week.'],
            ['title' => 'Unrelated Memo', 'keywords' => 'staff meeting', 'extracted_text' => 'The meeting starts at nine.'],
        ] as $index => $attributes) {
            Document::create(array_merge([
                'uploaded_by' => $admin->id,
                'original_filename' => 'search-'.$index.'.txt',
                'storage_path' => 'documents/search-'.$index.'.txt',
                'mime_type' => 'text/plain',
                'file_size' => 100,
                'processing_status' => 'completed',
            ], $attributes));
        }

        $this->actingAs($admin)->get(route('documents.library', ['q' => 'transformer maintenance']))
            ->assertOk()
            ->assertSeeText('Transformer Maintenance Plan')
            ->assertSeeText('Asset Report')
            ->assertDontSeeText('Unrelated Memo');
    }

    public function test_library_filters_category_department_uploader_and_document_date(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $category = DocumentCategory::where('name', 'Maintenance Reports')->firstOrFail();
        $department = Department::where('name', 'IT Services')->firstOrFail();

        $matching = Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Maintenance Inspection',
            'original_filename' => 'inspection.txt',
            'storage_path' => 'documents/inspection.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'category_id' => $category->id,
            'department_id' => $department->id,
            'document_date' => '2025-03-15',
            'processing_status' => 'completed',
        ]);
        Document::create([
            'uploaded_by' => $employee->id,
            'title' => 'Older maintenance inspection',
            'original_filename' => 'old-inspection.txt',
            'storage_path' => 'documents/old-inspection.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'category_id' => $category->id,
            'department_id' => $department->id,
            'document_date' => '2024-03-15',
            'processing_status' => 'completed',
        ]);
        Document::create([
            'uploaded_by' => $otherEmployee->id,
            'title' => 'Other uploader inspection',
            'original_filename' => 'other-inspection.txt',
            'storage_path' => 'documents/other-inspection.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'category_id' => $category->id,
            'department_id' => $department->id,
            'document_date' => '2025-03-15',
            'processing_status' => 'completed',
        ]);

        $library = $this->actingAs($admin);
        $categoryResponse = $library->get(route('documents.library', ['category_id' => $category->id]));
        $categoryResponse->assertOk()->assertSeeText('Maintenance Inspection');
        $library->get(route('documents.library', ['department_id' => $department->id]))
            ->assertOk()->assertSeeText('Maintenance Inspection');
        $library->get(route('documents.library', ['uploader_id' => $employee->id]))
            ->assertOk()->assertSeeText('Maintenance Inspection');
        $library->get(route('documents.library', [
            'date_from' => '2025-03-01',
            'date_to' => '2025-03-31',
        ]))->assertOk()->assertSeeText('Maintenance Inspection');

        $library->get(route('documents.library', [
            'category_id' => $category->id,
            'department_id' => $department->id,
            'uploader_id' => $employee->id,
            'date_from' => '2025-03-01',
            'date_to' => '2025-03-31',
            'sort' => 'alphabetical',
        ]))->assertOk()
            ->assertSeeText('Maintenance Inspection')
            ->assertDontSeeText('Older maintenance inspection')
            ->assertDontSeeText('Other uploader inspection');

        $this->assertNotNull($matching->id);
    }

    public function test_employee_filter_options_and_results_are_limited_to_own_documents(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        foreach ([
            [$employee, 'Employee Owned Document'],
            [$otherEmployee, 'Another Employee Document'],
        ] as [$uploader, $title]) {
            Document::create([
                'uploaded_by' => $uploader->id,
                'title' => $title,
                'original_filename' => $title.'.txt',
                'storage_path' => 'documents/'.str_replace(' ', '-', strtolower($title)).'.txt',
                'mime_type' => 'text/plain',
                'file_size' => 100,
                'processing_status' => 'completed',
            ]);
        }

        $this->actingAs($employee)->get(route('documents.library'))
            ->assertOk()
            ->assertSeeText('Employee Owned Document')
            ->assertDontSeeText('Another Employee Document')
            ->assertDontSeeText($otherEmployee->name)
            ->assertDontSee('name="uploader_id"', false);
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
            ->assertSee(str_replace('/', '\\/', route('documents.ask', $doc)), false);
    }
}
