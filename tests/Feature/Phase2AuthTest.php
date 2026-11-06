<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class Phase2AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_roles_exist(): void
    {
        $this->seed();
        $this->assertEquals(3, User::count());
        $this->assertTrue(User::where('email', 'admin@panelco3.local')->first()->isAdmin());
        $this->assertEquals('document_manager', User::where('email', 'manager@panelco3.local')->first()->role);
        $this->assertEquals('employee', User::where('email', 'employee@panelco3.local')->first()->role);
    }

    public function test_employee_cannot_access_admin_users(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $this->actingAs($emp)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_access_admin_users(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_document_policy_owner_and_admin(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $admin = User::where('email', 'admin@panelco3.local')->first();
        $other = User::factory()->create(['role' => 'employee']);

        $doc = Document::create([
            'uploaded_by' => $emp->id,
            'original_filename' => 'test.pdf',
            'storage_path' => 'private/documents/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 123,
            'processing_status' => 'pending',
        ]);

        $this->assertTrue($emp->can('view', $doc));
        $this->assertTrue($admin->can('view', $doc));
        $this->assertFalse($other->can('view', $doc));
        $doc->update(['department_id' => $emp->department_id]);
        $other->update(['department_id' => null]);
        $other->departments()->detach();
        $this->assertFalse($other->can('view', $doc->fresh()));
    }

    public function test_register_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_password_reset_forms_have_accessible_labels_guidance_and_error_targets(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('for="email"', false)
            ->assertSee('id="email" type="email"', false);

        $this->get(route('password.reset', ['token' => 'sample-token']))
            ->assertOk()
            ->assertSee('for="password"', false)
            ->assertSee('for="password-confirm"', false)
            ->assertSee('Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.')
            ->assertSee('aria-describedby="passwordHelp"', false);

        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['password' => 'The password is invalid.']));

        $this->withSession(['errors' => $errors])
            ->get(route('password.reset', ['token' => 'sample-token']))
            ->assertOk()
            ->assertSee('aria-describedby="passwordHelp passwordError"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('id="passwordError"', false);
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
