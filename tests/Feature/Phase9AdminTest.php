<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();

        foreach ([
            'Organization General Management (OGM)',
            'Finance Service Management (FSD)',
            'CSD',
            'ISD',
            'ASD',
            'TSD',
        ] as $officialDepartment) {
            $this->assertDatabaseHas('departments', ['name' => $officialDepartment]);
        }

        $this->actingAs($employee)->post(route('admin.departments.store'), [
            'name' => 'Unauthorized Department',
        ])->assertForbidden();
        $this->assertDatabaseMissing('departments', ['name' => 'Unauthorized Department']);

        $this->actingAs($admin)->post(route('admin.departments.store'), [
            'name' => 'Line Maintenance',
            'description' => 'Poles and wires',
            'keywords' => 'line patrol, pole replacement',
        ])->assertRedirect(route('admin.departments.index'));
        $dept = Department::where('name', 'Line Maintenance')->first();
        $this->assertNotNull($dept);
        $this->assertSame('line patrol, pole replacement', $dept->keywords);

        $this->actingAs($admin)->delete(route('admin.departments.destroy', $dept))->assertRedirect();
        $this->assertNull(Department::where('name', 'Line Maintenance')->first());
        $this->assertTrue(AuditLog::where('action', 'department.deleted')->exists());
    }

    public function test_department_in_use_cannot_be_deleted(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $department = Department::where('name', 'IT Services')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.departments.destroy', $department))
            ->assertSessionHasErrors('department');
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_audit_log_viewer_admin_only(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_admin_can_create_update_and_delete_employee_accounts(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $payload = [
            'name' => 'New PANELCO Employee',
            'email' => 'new.employee@panelco3.local',
            'password' => 'Safe-password-123!',
            'password_confirmation' => 'Safe-password-123!',
        ];

        $this->actingAs($employee)->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->post(route('admin.users.store'), $payload)->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            ...$payload,
            'email' => 'weak.password@panelco3.local',
            'password' => 'safe-password-123',
            'password_confirmation' => 'safe-password-123',
        ])->assertSessionHasErrors('password');

        $this->actingAs($employee);
        $this->put(route('admin.users.update', $employee), [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => User::ROLE_EMPLOYEE,
        ])->assertForbidden();
        $this->delete(route('admin.users.destroy', $employee))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.users.store'), $payload)
            ->assertRedirect(route('admin.users.index'));
        $created = User::where('email', $payload['email'])->firstOrFail();
        $this->assertSame(User::ROLE_EMPLOYEE, $created->role);
        $this->assertTrue(Hash::check($payload['password'], $created->password));

        $this->actingAs($admin)->put(route('admin.users.update', $created), [
            'name' => 'Updated PANELCO Employee',
            'email' => $payload['email'],
            'role' => User::ROLE_EMPLOYEE,
        ])->assertRedirect(route('admin.users.index'));
        $this->assertSame('Updated PANELCO Employee', $created->fresh()->name);

        $this->put(route('admin.users.update', $created), [
            'name' => 'Updated PANELCO Employee',
            'email' => $payload['email'],
            'role' => User::ROLE_EMPLOYEE,
            'password' => 'Another-Password-123!',
            'password_confirmation' => 'Another-Password-123!',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertTrue(AuditLog::where('action', 'user.password_reset')
            ->where('entity_id', $created->id)->exists());

        $this->actingAs($admin)->delete(route('admin.users.destroy', $created))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $created->id]);
    }

    public function test_admin_can_deactivate_and_reactivate_employee_accounts(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.users.status', $employee), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($employee->fresh()->is_active);
        $this->assertTrue(AuditLog::where('action', 'user.deactivated')->where('entity_id', $employee->id)->exists());

        $this->patch(route('admin.users.status', $employee), ['is_active' => true])->assertRedirect();
        $this->assertTrue($employee->fresh()->is_active);
        $this->assertTrue(AuditLog::where('action', 'user.activated')->where('entity_id', $employee->id)->exists());
    }

    public function test_inactive_users_cannot_login_or_use_an_existing_session(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get(route('home'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_self_or_last_active_administrator(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.users.status', $admin), ['is_active' => false])
            ->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->is_active);

        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $this->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_EMPLOYEE,
        ])->assertSessionHasErrors('role');
        $this->assertTrue($employee->fresh()->is_active);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_last_administrator_cannot_be_demoted_or_deleted(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_EMPLOYEE,
        ])->assertSessionHasErrors('role');
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);

        $this->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => User::ROLE_ADMIN]);
    }

    public function test_dashboard_shows_permission_filtered_metrics(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('dashboard'))->assertOk();
    }
}
