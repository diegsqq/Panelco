<?php

namespace Tests\Feature;

use App\Mail\DocumentApprovalNotification;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_approve_a_completed_document_and_the_decision_is_audited(): void
    {
        Mail::fake();
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $document = $this->createDocument($employee);

        $this->actingAs($manager)->post(route('documents.approve-document', $document), [
            'review_note' => 'Verified against the signed copy.',
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame('approved', $document->approval_status);
        $this->assertSame($manager->id, $document->approved_by);
        $this->assertSame($manager->id, $document->approval_reviewed_by);
        $this->assertSame('Verified against the signed copy.', $document->approval_review_note);
        $this->assertNotNull($document->approved_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.approved',
            'entity_id' => $document->id,
        ]);
        Mail::assertSent(DocumentApprovalNotification::class, function ($mail) use ($document, $manager) {
            return $mail->document->is($document)
                && $mail->approved
                && $mail->reviewerName === $manager->name
                && $mail->reviewNote === 'Verified against the signed copy.';
        });
    }

    public function test_rejection_requires_a_reason_and_records_reviewer_and_audit(): void
    {
        Mail::fake();
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $document = $this->createDocument($employee);

        $this->actingAs($manager)
            ->post(route('documents.reject-document', $document))
            ->assertSessionHasErrors('review_note');
        $this->assertSame('pending', $document->fresh()->approval_status);

        $this->post(route('documents.reject-document', $document), [
            'review_note' => 'The required signature is missing.',
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame('rejected', $document->approval_status);
        $this->assertSame($manager->id, $document->approval_reviewed_by);
        $this->assertSame('The required signature is missing.', $document->approval_review_note);
        $this->assertNull($document->approved_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.rejected',
            'entity_id' => $document->id,
            'metadata' => json_encode(['review_note' => 'The required signature is missing.']),
        ]);
        Mail::assertSent(DocumentApprovalNotification::class, function ($mail) use ($document) {
            return $mail->document->is($document)
                && ! $mail->approved
                && $mail->reviewNote === 'The required signature is missing.';
        });
    }

    public function test_employees_cannot_approve_or_reject_and_incomplete_documents_cannot_be_reviewed(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $document = $this->createDocument($employee);

        $this->actingAs($otherEmployee)
            ->post(route('documents.approve-document', $document))
            ->assertForbidden();
        $this->post(route('documents.reject-document', $document), ['review_note' => 'No access'])
            ->assertForbidden();

        $document->update(['processing_status' => 'processing']);
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $this->actingAs($manager)
            ->post(route('documents.approve-document', $document))
            ->assertSessionHasErrors('approval');
        $this->assertSame('pending', $document->fresh()->approval_status);
    }

    public function test_only_authorized_reviewers_see_document_approval_controls(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $document = $this->createDocument($employee);

        $this->actingAs($manager)->get(route('documents.show', $document))
            ->assertOk()
            ->assertSee('Document approval')
            ->assertSee('Approve document')
            ->assertSee('Reject document');

        $this->actingAs($otherEmployee)->get(route('documents.show', $document))
            ->assertForbidden();

        $this->actingAs($employee)->get(route('documents.show', $document))
            ->assertOk()
            ->assertSee('Pending')
            ->assertDontSee('Approve document')
            ->assertDontSee('Reject document');
    }

    public function test_approval_queue_is_manager_only_and_limited_to_authorized_completed_documents(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $pending = $this->createDocument($employee, ['title' => 'Waiting for approval']);
        $this->createDocument($employee, ['title' => 'Already approved', 'approval_status' => 'approved']);
        $this->createDocument($employee, ['title' => 'Still processing', 'processing_status' => 'processing']);
        $restrictedUploader = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $restrictedDepartment = Department::create(['name' => 'Private Review Department']);
        $restricted = $this->createDocument($restrictedUploader, [
            'title' => 'Restricted waiting document',
            'department_id' => $restrictedDepartment->id,
        ]);
        $this->assertNotNull($pending);
        $this->assertNotNull($restricted);

        $this->actingAs($employee)->get(route('documents.approvals'))->assertForbidden();
        $this->actingAs($manager)->get(route('documents.approvals'))
            ->assertOk()
            ->assertSee('Waiting for approval')
            ->assertDontSee('Already approved')
            ->assertDontSee('Still processing')
            ->assertDontSee('Restricted waiting document');
    }

    public function test_classification_review_queue_is_manager_only_and_scoped_to_actionable_suggestions(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $category = DocumentCategory::firstOrFail();
        $visible = $this->createDocument($employee, [
            'title' => 'Visible classification suggestion',
            'suggested_category_id' => $category->id,
            'classification_review_status' => 'pending',
        ]);
        $visibleDepartmentSuggestion = $this->createDocument($employee, [
            'title' => 'Visible department suggestion',
            'suggested_department_id' => $employee->department_id,
            'classification_review_status' => 'pending',
        ]);
        $this->createDocument($employee, [
            'title' => 'No suggestion to review',
            'classification_review_status' => 'pending',
        ]);
        $this->createDocument($employee, [
            'title' => 'Already reviewed classification',
            'suggested_category_id' => $category->id,
            'classification_review_status' => 'approved',
        ]);
        $this->createDocument($employee, [
            'title' => 'Unprocessed classification',
            'suggested_category_id' => $category->id,
            'processing_status' => 'processing',
            'classification_review_status' => 'pending',
        ]);
        $restrictedUploader = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $restrictedDepartment = Department::create(['name' => 'Private Classification Review']);
        $this->createDocument($restrictedUploader, [
            'title' => 'Restricted classification suggestion',
            'department_id' => $restrictedDepartment->id,
            'suggested_category_id' => $category->id,
            'classification_review_status' => 'pending',
        ]);

        $this->actingAs($employee)->get(route('documents.classification-reviews'))
            ->assertForbidden();

        $response = $this->actingAs($manager)->get(route('documents.classification-reviews'));
        $response
            ->assertOk()
            ->assertSee('Classification review queue')
            ->assertSee('Visible classification suggestion')
            ->assertSee('Visible department suggestion')
            ->assertDontSee('No suggestion to review')
            ->assertDontSee('Already reviewed classification')
            ->assertDontSee('Unprocessed classification')
            ->assertDontSee('Restricted classification suggestion');
        $this->assertSame(
            [$visible->id, $visibleDepartmentSuggestion->id],
            $response->viewData('documents')->pluck('id')->all()
        );
    }

    public function test_classification_approval_does_not_approve_the_document(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $document = $this->createDocument($employee, [
            'suggested_category_id' => DocumentCategory::firstOrFail()->id,
        ]);

        $this->actingAs($manager)->post(route('documents.approve', $document), [
            'category_id' => $document->suggested_category_id,
        ])->assertRedirect();

        $this->assertSame('approved', $document->fresh()->classification_review_status);
        $this->assertSame('pending', $document->fresh()->approval_status);
        $this->assertNull($document->fresh()->approved_at);
    }

    public function test_uploader_can_opt_out_of_approval_decision_emails(): void
    {
        Mail::fake();
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $manager = User::where('email', 'manager@panelco3.local')->firstOrFail();
        $employee->update(['notify_doc_approval' => false]);
        $document = $this->createDocument($employee);

        $this->actingAs($manager)->post(route('documents.approve-document', $document))
            ->assertRedirect();

        $this->assertSame('approved', $document->fresh()->approval_status);
        Mail::assertNothingSent();
    }

    private function createDocument(User $uploader, array $attributes = []): Document
    {
        return Document::create(array_merge([
            'uploaded_by' => $uploader->id,
            'original_filename' => 'approval-document.txt',
            'storage_path' => 'documents/approval-document.txt',
            'mime_type' => 'text/plain',
            'file_size' => 64,
            'processing_status' => 'completed',
        ], $attributes));
    }
}
