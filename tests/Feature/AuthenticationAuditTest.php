<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Document;
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
            'employee_id' => '0001',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'employee_id' => $user->employee_id,
            'password' => 'wrong-secret',
        ])->assertSessionHasErrors('password');

        $log = AuditLog::where('action', 'auth.login_failed')->firstOrFail();
        $this->assertSame($user->id, $log->entity_id);
        $this->assertSame($user->employee_id, $log->metadata['identifier']);
        $this->assertArrayNotHasKey('password', $log->metadata);
        $this->assertArrayHasKey('ip', $log->metadata);
        $this->assertArrayHasKey('user_agent', $log->metadata);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee($user->employee_id)
            ->assertSee($log->metadata['ip']);
    }

    public function test_repeated_failed_logins_record_a_lockout_event(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('login'), [
                'employee_id' => '9999',
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
            'employee_id' => '0001',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'employee_id' => $user->employee_id,
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

    public function test_security_page_shows_only_the_current_users_authentication_history(): void
    {
        $user = User::factory()->create(['email' => 'history@example.test', 'employee_id' => '0001']);
        $otherUser = User::factory()->create(['email' => 'other@example.test', 'employee_id' => '0002']);

        $ownSuccess = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.login_succeeded',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['ip' => '192.0.2.10', 'user_agent' => 'Example Browser'],
        ]);
        AuditLog::create([
            'action' => 'auth.login_failed',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['identifier' => $user->employee_id, 'ip' => '192.0.2.11'],
        ]);
        AuditLog::create([
            'action' => 'auth.login_locked_out',
            'metadata' => ['identifier' => $user->employee_id, 'ip' => '192.0.2.12'],
        ]);
        AuditLog::create([
            'user_id' => $otherUser->id,
            'action' => 'auth.login_succeeded',
            'entity_type' => User::class,
            'entity_id' => $otherUser->id,
            'metadata' => ['ip' => '198.51.100.5', 'user_agent' => 'Private Browser'],
        ]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'document.downloaded',
            'entity_type' => Document::class,
            'entity_id' => 999,
            'metadata' => ['filename' => 'private-document.pdf'],
        ]);

        $this->actingAs($user)->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Recent sign-in activity')
            ->assertSee('Signed in successfully')
            ->assertSee('Failed sign-in attempt')
            ->assertSee('Sign-in temporarily locked')
            ->assertSee('192.0.2.10')
            ->assertDontSee('Private Browser')
            ->assertDontSee('198.51.100.5')
            ->assertDontSee('private-document.pdf');

        $this->actingAs($otherUser)->get(route('settings.security'))
            ->assertOk()
            ->assertDontSee('192.0.2.10')
            ->assertDontSee($user->email);
        $this->assertNotNull($ownSuccess->id);
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
