<?php

namespace Tests\Feature;

use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryAccessLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_create_page_shares_access_control(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $other = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($user)->get(route('categories.create'))
            ->assertOk()
            ->assertSee('id="access_level"', false)
            ->assertSee('data-bs-target="#shareAccessModal"', false)
            ->assertSee('name="access_level" value="all_employees"', false)
            ->assertSee('id="sharedUserIds"', false)
            ->assertSeeText($other->name);
    }

    public function test_store_persists_access_level_and_shared_users(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $shared = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($owner)->post(route('categories.store'), [
            'name' => 'Private Folder',
            'access_level' => 'private',
            'user_ids' => [$shared->id],
        ])->assertRedirect();

        $category = DocumentCategory::where('name', 'Private Folder')->firstOrFail();

        $this->assertSame('private', $category->access_level);
        $this->assertSame($owner->id, (int) $category->created_by);
        $this->assertTrue($category->authorizedUsers->contains($shared->id));
    }

    public function test_admin_edit_page_keeps_current_access_level_and_shares(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $shared = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $category = DocumentCategory::create([
            'name' => 'Private Folder',
            'access_level' => 'private',
            'created_by' => $admin->id,
        ]);
        $category->authorizedUsers()->sync([$shared->id]);

        $this->actingAs($admin)->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('name="access_level" value="private"', false)
            ->assertSee('value="'.$shared->id.'" selected', false);
    }

    public function test_private_and_link_folders_are_listed_only_for_allowed_users(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $other = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $private = DocumentCategory::create([
            'name' => 'Private Folder',
            'access_level' => 'private',
            'created_by' => $owner->id,
        ]);
        $link = DocumentCategory::create([
            'name' => 'Link Folder',
            'access_level' => 'link',
            'created_by' => $owner->id,
        ]);
        $shared = DocumentCategory::create([
            'name' => 'Shared Folder',
            'access_level' => 'private',
            'created_by' => $owner->id,
        ]);
        $shared->authorizedUsers()->sync([$other->id]);
        $all = DocumentCategory::create([
            'name' => 'Open Folder',
            'access_level' => 'all_employees',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($other)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('folders.show', $shared).'"', false)
            ->assertSee('href="'.route('folders.show', $all).'"', false)
            ->assertDontSee('href="'.route('folders.show', $private).'"', false)
            ->assertDontSee('href="'.route('folders.show', $link).'"', false);

        $this->get(route('documents.library'))
            ->assertOk()
            ->assertSee('href="'.route('folders.show', $shared).'"', false)
            ->assertSee('href="'.route('folders.show', $all).'"', false)
            ->assertDontSee('href="'.route('folders.show', $private).'"', false)
            ->assertDontSee('href="'.route('folders.show', $link).'"', false);

        $this->get(route('folders.show', $private))->assertForbidden();
        $this->get(route('folders.show', $link))->assertOk();
        $this->get(route('folders.show', $shared))->assertOk();

        $this->actingAs($owner)->get(route('folders.show', $private))->assertOk();
        $this->actingAs($admin)->get(route('folders.show', $private))->assertOk();
    }
}
