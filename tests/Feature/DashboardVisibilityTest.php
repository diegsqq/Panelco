<?php

namespace Tests\Feature;

use App\Models\Conversation;
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
        $this->actingAs($employee)->get(route('dashboard'))
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

    private function createDocument(User $uploader, string $title, array $attributes = []): Document
    {
        return Document::create(array_merge([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'original_filename' => $title . '.txt',
            'storage_path' => 'documents/' . str_replace(' ', '-', strtolower($title)) . '.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'processing_status' => 'completed',
        ], $attributes));
    }
}
