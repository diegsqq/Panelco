<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_help_guide_from_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('help'))
            ->assertOk()
            ->assertSee('SmartDocs user guide')
            ->assertSee('Upload a document')
            ->assertSee('Find and review documents')
            ->assertSee('Ask Lightning')
            ->assertSee('Account and security')
            ->assertSee('href="'.route('documents.upload').'"', false)
            ->assertSee('href="'.route('documents.library').'"', false)
            ->assertSee('href="'.route('chat.index').'"', false)
            ->assertSee('href="'.route('help').'"', false);
    }

    public function test_guest_must_sign_in_to_open_help_guide(): void
    {
        $this->get(route('help'))
            ->assertRedirect(route('login'));
    }
}
