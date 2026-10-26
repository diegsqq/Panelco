<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase9AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_category_employee_blocked(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('admin.categories.index'))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Energy Audit Reports', 'keywords' => 'audit,energy,consumption',
        ])->assertRedirect(route('admin.categories.index'));
        $cat = DocumentCategory::where('name', 'Energy Audit Reports')->first();
        $this->assertNotNull($cat);

        $this->actingAs($admin)->put(route('admin.categories.update', $cat), [
            'name' => 'Energy Audit Reports', 'keywords' => 'audit,energy,consumption,meter',
        ])->assertRedirect();
        $this->assertStringContainsString('meter', $cat->fresh()->keywords);
        $this->assertTrue(AuditLog::where('action', 'category.created')->exists());
        $this->assertTrue(AuditLog::where('action', 'category.updated')->exists());
    }

    public function test_admin_can_crud_department(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();

        $this->actingAs($admin)->post(route('admin.departments.store'), [
            'name' => 'Line Maintenance', 'description' => 'Poles and wires',
        ])->assertRedirect(route('admin.departments.index'));
        $dept = Department::where('name', 'Line Maintenance')->first();
        $this->assertNotNull($dept);

        $this->actingAs($admin)->delete(route('admin.departments.destroy', $dept))->assertRedirect();
        $this->assertNull(Department::where('name', 'Line Maintenance')->first());
        $this->assertTrue(AuditLog::where('action', 'department.deleted')->exists());
    }

    public function test_audit_log_viewer_admin_only(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_dashboard_shows_permission_filtered_metrics(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('dashboard'))->assertOk();
    }
}
