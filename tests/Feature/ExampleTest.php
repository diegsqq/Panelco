<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_root_shows_public_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Answers grounded in real records');
    }
}
