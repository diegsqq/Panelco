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
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertSee('Know your records.')
            ->assertSee('Trust every answer.')
            ->assertSee('Answers you can trace.')
            ->assertSee('role-based document access')
            ->assertSee('Skip to main content')
            ->assertSee('id="main-content"', false)
            ->assertSee('Grounded chat')
            ->assertSee('aria-label="Main navigation"', false)
            ->assertSee('Sample inspection report')
            ->assertSee('Example source citation')
            ->assertSee('animation:document-float 6s ease-in-out infinite', false)
            ->assertSee('animation:hero-color-drift 18s ease-in-out infinite alternate', false)
            ->assertSee('.hero::before{content:"SMART";top:4.7rem;left:4%', false)
            ->assertSee('.hero::after{content:"DOCS";top:11.4rem;right:3%', false)
            ->assertSee('animation:smart-drift 1.8s ease-in-out infinite alternate', false)
            ->assertSee('animation:docs-drift 1.8s ease-in-out infinite alternate', false)
            ->assertSee('@keyframes smart-drift', false)
            ->assertSee('@keyframes docs-drift', false)
            ->assertSee('@media(prefers-reduced-motion:reduce)', false)
            ->assertSee('animation:none!important', false)
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

    public function test_dashboard_keeps_accessible_mobile_navigation_available(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('id="sidebarToggle"', false)
            ->assertSee('class="p3-skip-link" href="#main-content">Skip to main content</a>', false)
            ->assertSee('id="main-content" tabindex="-1"', false)
            ->assertSee('aria-controls="p3Sidebar"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-label="Primary navigation"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('aria-controls="settingsMenu"', false)
            ->assertSee('aria-controls="settingsMenu" aria-expanded="false"', false)
            ->assertSee('sidebar.inert = mobileNav.matches && !open;', false)
            ->assertSee("e.key === 'Escape'", false)
            ->assertSee('@media (prefers-reduced-motion: reduce)', false)
            ->assertSee('animation-duration: .01ms !important;', false)
            ->assertSee('transition-duration: .01ms !important;', false)
            ->assertSee('scroll-behavior: auto !important;', false)
            ->assertSee('body:has(#p3Dashboard) .p3-topbar { display: flex !important; }', false);
    }

    public function test_production_session_cookie_defaults_to_https_only(): void
    {
        $this->assertSame(
            env('APP_ENV', 'production') === 'production',
            config('session.secure')
        );
    }

    public function test_login_password_visibility_toggle_has_accessible_state(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="pwToggle" aria-label="Show password" aria-controls="password" aria-pressed="false"', false)
            ->assertSee('toggle.setAttribute(\'aria-pressed\', show ? \'true\' : \'false\');', false)
            ->assertSee('<i class="bi bi-eye" aria-hidden="true"></i>', false);
    }
}
