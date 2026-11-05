<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Department;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Ai\AiProvider;
use App\Services\Ai\NullAiProvider;
use App\Services\DepartmentRecommender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FakeAiProvider implements AiProvider
{
    public function available(): bool { return true; }
    public function name(): string { return 'fake'; }
    public function analyze(string $text): ?array
    {
        return ['summary' => 'Fake summary of report.', 'keywords' => ['power', 'zone 4']];
    }
    public function classify(string $text, array $categoryNames): ?array
    {
        $pick = in_array('Maintenance Reports', $categoryNames, true) ? 'Maintenance Reports' : null;

        return ['category' => $pick, 'confidence' => 0.82];
    }

    public function answer(string $question, array $passages): ?string
    {
        return 'Fake generated answer.';
    }
}

class Phase5AiTest extends TestCase
{
    use RefreshDatabase;

    private function makeTxt(User $uploader, string $text): Document
    {
        Storage::fake('private');
        Storage::disk('private')->put('documents/r.txt', $text);

        return Document::create([
            'uploaded_by' => $uploader->id,
            'original_filename' => 'r.txt',
            'storage_path' => 'documents/r.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($text),
            'processing_status' => 'pending',
        ]);
    }

    public function test_rule_suggestion_without_ai(): void
    {
        $this->seed();
        app()->instance(AiProvider::class, new NullAiProvider());
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeTxt($emp, 'Preventive maintenance schedule transformer inspection crew assigned');

        app()->call([new ProcessDocument($doc->id), 'handle']);

        $doc->refresh();
        $this->assertEquals('completed', $doc->processing_status);
        $this->assertNull($doc->summary);
        $this->assertNull($doc->classification_confidence);
        $this->assertEquals('Maintenance Reports', $doc->suggestedCategory->name);
        $this->assertStringContainsString('maintenance', strtolower($doc->suggested_category_reason));
        $this->assertNull($doc->category_id);
    }

    public function test_rule_suggestion_identifies_department_and_returns_confidence(): void
    {
        $this->seed();
        $department = \App\Models\Department::where('name', 'IT Services')->firstOrFail();
        $department->update(['keywords' => 'network operations,server maintenance']);
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeTxt($employee, 'Network operations completed server maintenance for the records system.');

        app()->call([new ProcessDocument($document->id), 'handle']);

        $document->refresh();
        $this->assertSame($department->id, $document->suggested_department_id);
        $this->assertGreaterThan(0, (float) $document->department_classification_confidence);
        $this->assertLessThanOrEqual(1, (float) $document->department_classification_confidence);
        $this->assertStringContainsString('network operations', strtolower($document->suggested_department_reason));
        $this->assertSame($employee->department_id, $document->department_id);
    }

    public function test_department_recommender_omits_tied_suggestions(): void
    {
        $this->seed();
        \App\Models\Department::where('name', 'IT Services')->update(['keywords' => 'network operations']);
        \App\Models\Department::where('name', 'Administration')->update(['keywords' => 'network operations']);

        $this->assertNull(app(DepartmentRecommender::class)->suggest('Network operations review completed.'));
    }

    public function test_fake_ai_adds_summary_and_confidence(): void
    {
        $this->seed();
        app()->instance(AiProvider::class, new FakeAiProvider());
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeTxt($emp, 'Preventive maintenance schedule transformer inspection crew assigned');

        app()->call([new ProcessDocument($doc->id), 'handle']);

        $doc->refresh();
        $this->assertEquals('Fake summary of report.', $doc->summary);
        $this->assertStringContainsString('power', $doc->keywords);
        $this->assertEquals('Maintenance Reports', $doc->suggestedCategory->name);
        $this->assertEquals(0.82, (float) $doc->classification_confidence);
    }

    public function test_manager_can_approve_employee_cannot(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $manager = User::where('email', 'manager@panelco3.local')->first();
        $doc = $this->makeTxt($emp, 'Some plain content');
        $cat = DocumentCategory::where('name', 'Memorandums')->first();

        $this->actingAs($emp)->post(route('documents.approve', $doc), ['category_id' => $cat->id])->assertForbidden();

        $this->actingAs($manager)->post(route('documents.approve', $doc), ['category_id' => $cat->id])->assertRedirect();
        $doc->refresh();
        $this->assertEquals($cat->id, $doc->category_id);
        $this->assertSame('approved', $doc->classification_review_status);
        $this->assertEquals($manager->id, $doc->classification_reviewed_by);
        $this->assertNull($doc->approved_at);
        $this->assertSame('pending', $doc->approval_status);
    }

    public function test_manager_can_correct_and_approve_category_and_department_suggestions(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $suggestedCategory = DocumentCategory::where('name', 'Maintenance Reports')->firstOrFail();
        $correctedCategory = DocumentCategory::where('name', 'Memorandums')->firstOrFail();
        $suggestedDepartment = Department::where('name', 'Administration')->firstOrFail();
        $targetDepartment = Department::where('name', 'IT Services')->firstOrFail();
        $document = $this->makeTxt($employee, 'Classification review test');
        $document->update([
            'suggested_category_id' => $suggestedCategory->id,
            'suggested_department_id' => $suggestedDepartment->id,
            'classification_confidence' => 0.82,
            'department_classification_confidence' => 0.75,
            'suggested_category_reason' => 'AI model recommendation based on extracted document text.',
            'suggested_department_reason' => 'Matched department keywords in extracted text: records',
        ]);

        $this->actingAs($manager)->post(route('documents.approve', $document), [
            'category_id' => $correctedCategory->id,
            'department_id' => $targetDepartment->id,
            'review_note' => 'Verified against the document contents.',
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame($correctedCategory->id, $document->category_id);
        $this->assertSame($targetDepartment->id, $document->department_id);
        $this->assertSame('approved', $document->classification_review_status);
        $this->assertSame($manager->id, $document->classification_reviewed_by);
        $this->assertSame('Verified against the document contents.', $document->classification_review_note);
        $this->assertNotEmpty($document->suggested_category_reason);
        $this->assertNotEmpty($document->suggested_department_reason);
        $this->assertSame(
            $suggestedCategory->id,
            AuditLog::where('action', 'document.classification_approved')
                ->where('entity_id', $document->id)->firstOrFail()->metadata['suggested_category_id']
        );
    }

    public function test_manager_cannot_assign_a_document_to_an_inaccessible_department(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $category = DocumentCategory::firstOrFail();
        $outsideDepartment = Department::create(['name' => 'Restricted Department']);
        $document = $this->makeTxt($employee, 'Classification review test');
        $document->update(['department_id' => $employee->department_id]);
        $document->update(['suggested_category_id' => $category->id]);

        $this->actingAs($manager)->post(route('documents.approve', $document), [
            'category_id' => $category->id,
            'department_id' => $outsideDepartment->id,
        ])->assertSessionHasErrors('department_id');

        $this->assertSame('pending', $document->fresh()->classification_review_status);
        $this->assertSame($employee->department_id, $document->fresh()->department_id);
    }

    public function test_manager_can_reject_suggestions_and_rejection_is_audited(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $category = DocumentCategory::firstOrFail();
        $document = $this->makeTxt($employee, 'Classification review test');
        $document->update([
            'suggested_category_id' => $category->id,
            'classification_confidence' => 0.61,
        ]);

        $this->actingAs($manager)->post(route('documents.reject-classification', $document), [
            'review_note' => 'The suggested category does not match the document.',
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame('rejected', $document->classification_review_status);
        $this->assertSame($manager->id, $document->classification_reviewed_by);
        $this->assertSame('The suggested category does not match the document.', $document->classification_review_note);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.classification_rejected',
            'entity_id' => $document->id,
        ]);

        $this->actingAs($employee)
            ->post(route('documents.reject-classification', $document))
            ->assertForbidden();
    }

    public function test_only_authorized_reviewers_see_classification_actions(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $category = DocumentCategory::firstOrFail();
        $document = $this->makeTxt($employee, 'Classification review test');
        $document->update(['suggested_category_id' => $category->id]);

        $this->actingAs($manager)->get(route('documents.show', $document))
            ->assertOk()
            ->assertSee('Approve classification');

        $this->actingAs($employee)->get(route('documents.show', $document))
            ->assertOk()
            ->assertDontSee('Approve classification');
    }

    public function test_null_provider_reports_unavailable(): void
    {
        $provider = new NullAiProvider();
        $this->assertFalse($provider->available());
        $this->assertNull($provider->analyze('anything'));
        $this->assertNull($provider->classify('anything', ['Memorandums']));
    }

    public function test_unmatched_text_falls_back_to_other_documents(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        Storage::fake('private');
        Storage::disk('private')->put('documents/r.txt', 'Xylophone quantum zebra absolutelynothingmatcheshere');

        $doc = Document::create([
            'uploaded_by' => $emp->id,
            'original_filename' => 'r.txt',
            'storage_path' => 'documents/r.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'pending',
        ]);

        app()->call([new ProcessDocument($doc->id), 'handle']);

        $doc->refresh();
        $this->assertEquals('completed', $doc->processing_status);
        $this->assertEquals('Other Documents', $doc->suggestedCategory->name);
        $this->assertNull($doc->category_id);
    }
}
