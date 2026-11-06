<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_public_welcome_page_with_employee_login_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Answers grounded in real records')
            ->assertSee('role-based document access')
            ->assertSee('Skip to main content')
            ->assertSee('id="main-content"', false)
            ->assertSee('Grounded chat')
            ->assertDontSee('~200k')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('name="description"', false);
    }

    public function test_authenticated_user_is_redirected_to_dashboard_from_root(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('home'));
    }
}
