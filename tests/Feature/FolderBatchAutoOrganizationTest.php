<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\FolderBatch;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FolderBatchAutoOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_folder_upload_auto_applies_suggestions_and_notifies_once(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        Storage::fake('private');

        $file = UploadedFile::fake()->createWithContent('maintenance-report.txt', "Maintenance report for transformer inspection. Department: ASD.");

        $this->actingAs($emp)->post(route('documents.store'), [
            'file' => [$file],
            'mode' => 'folder',
            'access_level' => 'private',
        ])->assertRedirect(route('documents.library'));

        $document = Document::where('uploaded_by', $emp->id)->firstOrFail();
        $this->assertNotNull($document->upload_batch_id);

        $batch = FolderBatch::findOrFail($document->upload_batch_id);
        $this->assertSame(1, $batch->total_files);

        // Processing runs synchronously in tests; refresh and verify.
        $document->refresh();
        $this->assertSame('completed', $document->processing_status);
        $this->assertSame('approved', $document->classification_review_status);
        if ($document->suggested_category_id !== null) {
            $this->assertSame($document->suggested_category_id, $document->category_id);
        }
        if ($document->suggested_department_id !== null) {
            $this->assertSame($document->suggested_department_id, $document->department_id);
        }

        $this->assertDatabaseHas('notifications', [
            'user_id' => $emp->id,
            'type' => 'folder_organization_completed',
        ]);

        $batch->refresh();
        $this->assertNotNull($batch->notified_at);
    }
}
