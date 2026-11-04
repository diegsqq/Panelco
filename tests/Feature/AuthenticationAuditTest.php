<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthenticationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_is_audited_without_storing_the_password(): void
    {
        $user = User::factory()->create([
            'email' => 'employee@example.test',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-secret',
        ])->assertSessionHasErrors('email');

        $log = AuditLog::where('action', 'auth.login_failed')->firstOrFail();
        $this->assertSame($user->id, $log->entity_id);
        $this->assertSame($user->email, $log->metadata['identifier']);
        $this->assertArrayNotHasKey('password', $log->metadata);
        $this->assertArrayHasKey('ip', $log->metadata);
        $this->assertArrayHasKey('user_agent', $log->metadata);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee($log->metadata['ip']);
    }

    public function test_repeated_failed_logins_record_a_lockout_event(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('login'), [
                'email' => 'unknown@example.test',
                'password' => 'wrong-secret',
            ]);
        }

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_locked_out',
        ]);
    }

    public function test_successful_login_and_logout_are_audited(): void
    {
        $user = User::factory()->create([
            'email' => 'employee@example.test',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login_succeeded',
        ]);

        $this->post(route('logout'))->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logged_out',
        ]);
    }

    public function test_invalid_two_factor_code_is_audited_without_recording_the_code(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => 'secret',
        ]);
        $google2fa = \Mockery::mock(Google2FA::class);
        $google2fa->shouldReceive('verifyKey')->once()->andReturnFalse();
        $this->app->instance(Google2FA::class, $google2fa);

        $this->withSession(['two_factor_pending_user' => $user->id])
            ->post(route('two-factor.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $log = AuditLog::where('action', 'auth.two_factor_failed')->firstOrFail();
        $this->assertSame($user->id, $log->entity_id);
        $this->assertArrayNotHasKey('code', $log->metadata);
    }
}
