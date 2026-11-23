<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_does_not_delay_page_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('@keyframes p3-fade', false)
            ->assertDontSee('Theme swap delay', false)
            ->assertSee("localStorage.setItem('p3-theme'", false);
    }
}
