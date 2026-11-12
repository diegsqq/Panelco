<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityRemainingTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_index_binds_labels_to_inputs_and_labels_verification_code_input(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'pending_email' => 'verify@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('for="settingsFirstName"', false)
            ->assertSee('id="settingsFirstName"', false)
            ->assertSee('for="settingsLastName"', false)
            ->assertSee('id="settingsLastName"', false)
            ->assertSee('for="settingsEmail"', false)
            ->assertSee('id="settingsEmail"', false)
            ->assertSee('for="settingsPhone"', false)
            ->assertSee('id="settingsPhone"', false)
            ->assertSee('for="settingsJobTitle"', false)
            ->assertSee('id="settingsJobTitle"', false)
            ->assertSee('for="settingsOrganization"', false)
            ->assertSee('id="settingsOrganization"', false)
            ->assertSee('aria-label="6-digit verification code"', false);
    }

    public function test_settings_preferences_labels_and_keyboard_operable_ai_pills(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('settings.preferences'))
            ->assertOk()
            ->assertSee('aria-label="Interface language"', false)
            ->assertSee('for="settingsAppName"', false)
            ->assertSee('id="settingsAppName"', false)
            ->assertSee('for="settingsAiModel"', false)
            ->assertSee('id="settingsAiModel"', false)
            ->assertSee('for="settingsUploadMaxKb"', false)
            ->assertSee('id="settingsUploadMaxKb"', false)
            ->assertSee('role="checkbox"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('id="aiChatPill"', false);
    }

    public function test_admin_users_index_modal_and_labels_are_accessible(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('role="dialog" aria-modal="true" aria-labelledby="newUserModalLabel"', false)
            ->assertSee('id="newUserModalLabel"', false)
            ->assertSee('for="newUserName"', false)
            ->assertSee('id="newUserName"', false)
            ->assertSee('for="newUserIdNumber"', false)
            ->assertSee('id="newUserIdNumber"', false)
            ->assertSee('for="newUserRole"', false)
            ->assertSee('id="newUserRole"', false)
            ->assertSee('data-pw-target="newUserPw"', false)
            ->assertSee('data-pw-target="newUserPwConfirm"', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('for="newUserDepartment"', false)
            ->assertSee('id="newUserDepartment"', false)
            ->assertSee('<th scope="col">Name</th>', false)
            ->assertSee('<span class="visually-hidden">Actions</span>', false);
    }

    public function test_admin_users_create_binds_labels_to_inputs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('for="userName"', false)
            ->assertSee('id="userName"', false)
            ->assertSee('for="userEmployeeId"', false)
            ->assertSee('id="userEmployeeId"', false)
            ->assertSee('for="standalonePw"', false)
            ->assertSee('id="standalonePw"', false)
            ->assertSee('data-pw-target="standalonePw"', false)
            ->assertSee('data-pw-target="standalonePwConfirm"', false)
            ->assertSee('for="userRole"', false)
            ->assertSee('id="userRole"', false)
            ->assertSee('for="userDepartment"', false)
            ->assertSee('id="userDepartment"', false);
    }

    public function test_documents_trash_uses_column_scopes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('documents.trash'))
            ->assertOk()
            ->assertSee('<th scope="col">Document</th>', false)
            ->assertSee('<th scope="col" class="text-end">Action</th>', false);
    }

    public function test_admin_documents_retention_review_uses_column_scopes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.documents.retention-review'))
            ->assertOk()
            ->assertSee('<th scope="col">Document</th>', false)
            ->assertSee('<th scope="col">Status</th>', false);
    }
}
