<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_restore_accessible_soft_deleted_document_and_its_files(): void
    {
        Storage::fake('private');
        $department = Department::create(['name' => 'Operations']);
        $otherDepartment = Department::create(['name' => 'Finance']);
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'department_id' => $department->id,
        ]);
        $uploader = User::factory()->create(['department_id' => $department->id]);
        $document = $this->createDocument($uploader, 'Recoverable report.txt', $department->id);
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'uploaded_by' => $uploader->id,
            'original_filename' => 'Previous report.txt',
            'storage_path' => 'documents/previous-report.txt',
            'mime_type' => 'text/plain',
            'file_size' => 8,
        ]);
        $hiddenDocument = $this->createDocument($uploader, 'Private finance report.txt', $otherDepartment->id);
        $sharedDocument = $this->createDocument($uploader, 'Shared finance report.txt', $otherDepartment->id);
        $manager->authorizedDocuments()->attach($sharedDocument->id);

        Storage::disk('private')->put($document->storage_path, 'current');
        Storage::disk('private')->put($version->storage_path, 'previous');
        $document->delete();
        $hiddenDocument->delete();
        $sharedDocument->delete();

        Storage::disk('private')->assertExists($document->storage_path);
        Storage::disk('private')->assertExists($version->storage_path);

        $this->actingAs($manager)
            ->get(route('documents.trash'))
            ->assertOk()
            ->assertSee('Recoverable report.txt')
            ->assertSee('Shared finance report.txt')
            ->assertDontSee('Private finance report.txt');

        $this->post(route('documents.restore', $document))
            ->assertRedirect(route('documents.trash'))
            ->assertSessionHas('status', 'Document restored.');

        $this->assertNotSoftDeleted('documents', ['id' => $document->id]);
        $this->assertSoftDeleted('documents', ['id' => $hiddenDocument->id]);
        Storage::disk('private')->assertExists($document->storage_path);
        Storage::disk('private')->assertExists($version->storage_path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.restored',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
        ]);
    }

    public function test_employees_cannot_open_recycle_bin_or_restore_documents(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $document = $this->createDocument($employee, 'Deleted employee report.txt', null);
        $document->delete();

        $this->actingAs($employee)
            ->get(route('documents.trash'))
            ->assertForbidden();

        $this->post(route('documents.restore', $document))
            ->assertForbidden();

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        $this->assertFalse(AuditLog::where('action', 'document.restored')->exists());
    }

    public function test_document_with_missing_storage_cannot_be_restored_as_if_healthy(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $document = $this->createDocument($admin, 'Missing file.txt', null);
        $document->delete();

        $this->actingAs($admin)
            ->post(route('documents.restore', $document))
            ->assertSessionHasErrors('document');

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        $this->assertFalse(AuditLog::where('action', 'document.restored')->exists());
    }

    private function createDocument(User $uploader, string $filename, ?int $departmentId): Document
    {
        return Document::create([
            'uploaded_by' => $uploader->id,
            'title' => pathinfo($filename, PATHINFO_FILENAME),
            'original_filename' => $filename,
            'storage_path' => 'documents/'.str_replace(' ', '-', strtolower($filename)),
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'department_id' => $departmentId,
            'processing_status' => 'completed',
        ]);
    }
}
