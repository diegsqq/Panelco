<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_scopes_documents_categories_and_conversations_by_role(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $anotherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $employeeCategory = DocumentCategory::create(['name' => 'Employee Records']);
        $suggestedCategory = DocumentCategory::create(['name' => 'Suggested Records']);
        $adminCategory = DocumentCategory::create(['name' => 'Admin Records']);

        $this->createDocument($employee, 'Employee record', [
            'category_id' => $employeeCategory->id,
        ]);
        $this->createDocument($employee, 'Suggested record', [
            'suggested_category_id' => $suggestedCategory->id,
        ]);
        $this->createDocument($admin, 'Shared department record', [
            'department_id' => $employee->department_id,
            'category_id' => $adminCategory->id,
        ]);
        $this->createDocument($anotherEmployee, 'Other employee record');

        Conversation::create(['user_id' => $employee->id, 'title' => 'Employee private chat']);
        Conversation::create(['user_id' => $admin->id, 'title' => 'Admin private chat']);

        $this->actingAs($employee)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Employee record')
            ->assertSee('Suggested record')
            ->assertDontSee('Shared department record')
            ->assertDontSee('Other employee record')
            ->assertSee('Employee Records')
            ->assertSee('Suggested Records')
            ->assertDontSee('Admin Records')
            ->assertSee('Employee private chat')
            ->assertDontSee('Admin private chat')
            ->assertSee('1 docs');

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Employee record')
            ->assertSee('Shared department record')
            ->assertSee('Other employee record')
            ->assertSee('Employee Records')
            ->assertSee('Suggested Records')
            ->assertSee('Admin Records')
            ->assertDontSee('Employee private chat')
            ->assertSee('Admin private chat')
            ->assertSee('1 docs');
    }

    public function test_department_distribution_shows_all_departments_for_every_role(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $it = $employee->department;
        $administration = $admin->department;

        $this->createDocument($employee, 'Assigned employee document', [
            'department_id' => $it->id,
        ]);
        $this->createDocument($employee, 'Suggested employee document', [
            'suggested_department_id' => $administration->id,
        ]);
        $this->createDocument($otherEmployee, 'Other employee document', [
            'department_id' => $administration->id,
        ]);
        $this->createDocument($otherEmployee, 'No department document');

        $expected = [
            $administration->name => 2,
            $it->name => 1,
        ];

        // Employees see organization-wide department totals, same as admins,
        // and documents without a department never show an "Unassigned" row.
        $response = $this->actingAs($employee)->get(route('dashboard'));
        $response
            ->assertOk()
            ->assertDontSee('Unassigned')
            ->assertViewHas('departmentDistribution', function ($distribution) use ($expected) {
                return $distribution->pluck('total', 'department_name')->all() === $expected;
            });

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Unassigned')
            ->assertViewHas('departmentDistribution', function ($distribution) use ($expected) {
                return $distribution->pluck('total', 'department_name')->all() === $expected;
            });
    }

    public function test_upload_trend_and_document_activity_follow_dashboard_document_visibility(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2026, 11, 5)->startOfDay());
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $currentMonthDocument = $this->createDocument($employee, 'November employee report', [
            'created_at' => now()->subDays(2),
        ]);
        $previousMonthDocument = $this->createDocument($employee, 'October employee report', [
        ]);
        Document::whereKey($previousMonthDocument->id)->update([
            'created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(3),
        ]);
        $previousMonthDocument->refresh();
        $restrictedDocument = $this->createDocument($otherEmployee, 'Private activity document', [
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $employee->id,
            'action' => 'document.uploaded',
            'entity_type' => Document::class,
            'entity_id' => $currentMonthDocument->id,
        ]);
        AuditLog::create([
            'user_id' => $otherEmployee->id,
            'action' => 'document.uploaded',
            'entity_type' => Document::class,
            'entity_id' => $restrictedDocument->id,
        ]);
        AuditLog::create([
            'user_id' => $employee->id,
            'action' => 'user.password_changed',
            'entity_type' => User::class,
            'entity_id' => $employee->id,
            'metadata' => ['secret' => 'must not appear on dashboard'],
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard'));
        $response
            ->assertOk()
            ->assertSee('Document upload trend')
            ->assertSee('Recent document activity')
            ->assertSee('November employee report')
            ->assertDontSee('Private activity document')
            ->assertDontSee('must not appear on dashboard');

        $this->assertSame(
            [0, 0, 0, 0, 1, 1],
            $response->viewData('monthlyUploads')->pluck('count')->all()
        );

        $this->travelBack();
    }

    public function test_most_accessed_documents_counts_successful_access_events_with_visibility_scope(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $visibleDocument = $this->createDocument($employee, 'Popular employee document');
        $hiddenDocument = $this->createDocument($otherEmployee, 'Private popular document');

        foreach ([
            ['document' => $visibleDocument, 'action' => 'document.viewed'],
            ['document' => $visibleDocument, 'action' => 'document.downloaded'],
            ['document' => $visibleDocument, 'action' => 'document.version_downloaded'],
            ['document' => $visibleDocument, 'action' => 'document.viewed'],
            ['document' => $hiddenDocument, 'action' => 'document.viewed'],
            ['document' => $visibleDocument, 'action' => 'document.uploaded'],
        ] as $event) {
            AuditLog::create([
                'action' => $event['action'],
                'entity_type' => Document::class,
                'entity_id' => $event['document']->id,
            ]);
        }

        $employeeResponse = $this->actingAs($employee)->get(route('dashboard'));
        $employeeResponse
            ->assertOk()
            ->assertSee('Most accessed documents')
            ->assertSee('Popular employee document')
            ->assertDontSee('Private popular document');
        $this->assertSame(
            [['document' => $visibleDocument->id, 'access_count' => 4]],
            $employeeResponse->viewData('mostAccessedDocuments')
                ->map(fn (array $item) => [
                    'document' => $item['document']->id,
                    'access_count' => $item['access_count'],
                ])
                ->all()
        );

        $adminResponse = $this->actingAs($admin)->get(route('dashboard'));
        $this->assertSame(
            ['Popular employee document', 'Private popular document'],
            $adminResponse->viewData('mostAccessedDocuments')
                ->pluck('document.title')
                ->all()
        );
    }

    public function test_pending_approvals_dashboard_preview_is_manager_scoped(): void
    {
        $this->seed();
        $operations = $this->createDepartment('Operations');
        $finance = $this->createDepartment('Finance');
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'department_id' => $operations->id,
        ]);
        $uploader = User::factory()->create(['department_id' => $operations->id]);
        $visible = $this->createDocument($uploader, 'Operations approval', [
            'department_id' => $operations->id,
            'approval_status' => 'pending',
        ]);
        $unassigned = $this->createDocument($uploader, 'Unassigned approval', [
            'department_id' => null,
            'approval_status' => 'pending',
        ]);
        $restricted = $this->createDocument($uploader, 'Finance approval', [
            'department_id' => $finance->id,
            'approval_status' => 'pending',
        ]);
        $this->createDocument($uploader, 'Already approved', [
            'department_id' => $operations->id,
            'approval_status' => 'approved',
        ]);
        $this->createDocument($uploader, 'Still processing', [
            'department_id' => $operations->id,
            'processing_status' => 'processing',
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));
        $response
            ->assertOk()
            ->assertSee('Pending approvals')
            ->assertSee('Operations approval')
            ->assertSee('Unassigned approval')
            ->assertDontSee('Finance approval')
            ->assertDontSee('Already approved')
            ->assertDontSee('Still processing');
        $this->assertSame(2, $response->viewData('pendingApprovalCount'));
        $this->assertSame(
            [$visible->id, $unassigned->id],
            $response->viewData('pendingApprovals')->pluck('id')->all()
        );

        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $employeeResponse = $this->actingAs($employee)->get(route('dashboard'));
        $employeeResponse->assertOk()->assertDontSee('Pending approvals');
        $this->assertSame(0, $employeeResponse->viewData('pendingApprovalCount'));
    }

    public function test_pending_classification_review_preview_is_manager_scoped_and_excludes_empty_suggestions(): void
    {
        $this->seed();
        $operations = $this->createDepartment('Classification Operations');
        $finance = $this->createDepartment('Classification Finance');
        $category = DocumentCategory::create(['name' => 'Suggested Category']);
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'department_id' => $operations->id,
        ]);
        $uploader = User::factory()->create(['department_id' => $operations->id]);
        $visible = $this->createDocument($uploader, 'Review visible suggestion', [
            'department_id' => $operations->id,
            'approval_status' => 'approved',
            'suggested_category_id' => $category->id,
            'classification_review_status' => 'pending',
        ]);
        $unassigned = $this->createDocument($uploader, 'Review unassigned suggestion', [
            'department_id' => null,
            'approval_status' => 'approved',
            'suggested_department_id' => $operations->id,
            'classification_review_status' => 'pending',
        ]);
        $this->createDocument($uploader, 'Private finance suggestion', [
            'department_id' => $finance->id,
            'approval_status' => 'approved',
            'suggested_category_id' => $category->id,
            'classification_review_status' => 'pending',
        ]);
        $this->createDocument($uploader, 'No classification suggestion', [
            'department_id' => $operations->id,
            'approval_status' => 'approved',
            'classification_review_status' => 'pending',
        ]);
        $this->createDocument($uploader, 'Already reviewed suggestion', [
            'department_id' => $operations->id,
            'approval_status' => 'approved',
            'suggested_category_id' => $category->id,
            'classification_review_status' => 'approved',
        ]);
        $this->createDocument($uploader, 'Unprocessed suggestion', [
            'department_id' => $operations->id,
            'approval_status' => 'approved',
            'suggested_category_id' => $category->id,
            'processing_status' => 'processing',
            'classification_review_status' => 'pending',
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));
        $response
            ->assertOk()
            ->assertSee('Classification suggestions to review')
            ->assertSee('Review visible suggestion')
            ->assertSee('Review unassigned suggestion')
            ->assertDontSee('Private finance suggestion')
            ->assertDontSee('No classification suggestion')
            ->assertDontSee('Already reviewed suggestion')
            ->assertDontSee('Unprocessed suggestion');
        $this->assertSame(2, $response->viewData('pendingClassificationReviewCount'));
        $this->assertSame(
            [$visible->id, $unassigned->id],
            $response->viewData('pendingClassificationReviews')->pluck('id')->all()
        );

        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $employeeResponse = $this->actingAs($employee)->get(route('dashboard'));
        $employeeResponse->assertOk()->assertDontSee('Classification suggestions to review');
        $this->assertSame(0, $employeeResponse->viewData('pendingClassificationReviewCount'));
    }

    private function createDepartment(string $name): Department
    {
        return Department::create(['name' => $name]);
    }

    private function createDocument(User $uploader, string $title, array $attributes = []): Document
    {
        return Document::create(array_merge([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'original_filename' => $title.'.txt',
            'storage_path' => 'documents/'.str_replace(' ', '-', strtolower($title)).'.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'processing_status' => 'completed',
        ], $attributes));
    }
}
