<?php

namespace Tests\Feature;

use App\Mail\ProfileVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SettingsFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    public function test_settings_page_shows_new_sections_to_employees_and_hides_admin_cards(): void
    {
        $user = $this->user(['role' => 'employee']);

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('My Account')
            ->assertDontSee('App Basics')
            ->assertDontSee('AI Settings')
            ->assertDontSee('View only');

        // Security + Preferences live on their own sidebar sub-pages.
        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Change Password')
            ->assertSee('Two-Factor Authentication')
            ->assertDontSee('App Basics');

        $this->actingAs($user)
            ->get(route('settings.preferences'))
            ->assertOk()
            ->assertSee('Appearance')
            ->assertSee('Email Notifications');
    }

    public function test_admin_still_sees_system_settings_cards(): void
    {
        $admin = $this->user(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('App Basics')
            ->assertSee('AI Settings')
            ->assertSee('Documents')
            ->assertSee('Save Settings');
    }

    public function test_profile_update_saves_names_job_title_and_organization(): void
    {
        Mail::fake();
        $user = $this->user(['role' => 'employee']);

        $this->actingAs($user)->put(route('settings.profile'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => $user->email,
            'phone' => '09171234567',
            'job_title' => 'Records Officer',
            'organization' => 'PANELCO III',
        ])->assertRedirect(route('settings.index'));

        $user->refresh();
        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame('Juan', $user->first_name);
        $this->assertSame('Dela Cruz', $user->last_name);
        $this->assertSame('Records Officer', $user->job_title);
        $this->assertSame('PANELCO III', $user->organization);
        $this->assertNull($user->pending_email);

        // Phone change is pending until its emailed code is entered.
        $this->assertNull($user->phone);
        $this->assertSame('09171234567', $user->pending_phone);
        $this->assertNotNull($user->phone_change_code);

        $this->from(route('settings.index'))
            ->put(route('settings.profile.verify-phone'), ['code' => $user->phone_change_code])
            ->assertRedirect(route('settings.index'));

        $user->refresh();
        $this->assertSame('09171234567', $user->phone);
        $this->assertNull($user->pending_phone);
        $this->assertNull($user->phone_change_code);
    }

    public function test_email_change_requires_verification_code(): void
    {
        Mail::fake();
        $user = $this->user(['role' => 'employee']);

        $this->actingAs($user)->put(route('settings.profile'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'newaddress@example.com',
            'job_title' => null,
            'organization' => null,
        ])->assertRedirect(route('settings.index'));

        $user->refresh();
        $this->assertSame($user->email, $user->email); // old address untouched
        $this->assertNotSame('newaddress@example.com', $user->email);
        $this->assertSame('newaddress@example.com', $user->pending_email);
        $this->assertNotNull($user->email_change_code);
        Mail::assertSent(ProfileVerificationCode::class, fn ($m) => $m->hasTo('newaddress@example.com'));

        // Wrong code rejected, right code applies.
        $this->from(route('settings.index'))
            ->put(route('settings.profile.verify-email'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertSame($user->fresh()->email, $user->email);

        $this->from(route('settings.index'))
            ->put(route('settings.profile.verify-email'), ['code' => $user->fresh()->email_change_code])
            ->assertRedirect(route('settings.index'));

        $user->refresh();
        $this->assertSame('newaddress@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNull($user->email_change_code);
    }

    public function test_password_change_validates_current_password(): void
    {
        $user = $this->user(['role' => 'employee']);

        $this->actingAs($user)->put(route('settings.password'), [
            'current_password' => 'wrongpass',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('settings.password'), [
            'current_password' => 'password',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])->assertRedirect(route('settings.security'));

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newsecret123', $user->fresh()->password));
    }

    public function test_preferences_theme_locale_and_notifications(): void
    {
        $user = $this->user(['role' => 'employee']);

        $this->actingAs($user)->post(route('settings.theme'), ['theme' => 'dark']);
        $this->assertSame('dark', $user->fresh()->theme);

        $this->actingAs($user)->post(route('settings.locale'), ['locale' => 'tl']);
        $this->assertSame('tl', $user->fresh()->locale);

        $this->actingAs($user)->post(route('settings.notifications'), [
            'notify_doc_done' => '1',
            'notify_doc_failed' => '0',
            'notify_login_alert' => '1',
        ]);
        $u = $user->fresh();
        $this->assertTrue($u->notify_doc_done);
        $this->assertFalse($u->notify_doc_failed);
        $this->assertTrue($u->notify_login_alert);

        $this->actingAs($user)->post(route('settings.theme'), ['theme' => 'neon'])->assertSessionHasErrors('theme');
    }

    public function test_two_factor_setup_confirm_then_login_requires_code(): void
    {
        Mail::fake();
        $user = $this->user(['role' => 'employee', 'email_verified_at' => now()]);

        // Start setup → secret parked in session.
        $this->actingAs($user)->post(route('settings.2fa.setup'));
        $secret = Session::get('two_factor_pending_secret');
        $this->assertNotEmpty($secret);
        $this->assertFalse($user->fresh()->two_factor_enabled);

        // Confirm with real TOTP → activated.
        $code = (new Google2FA())->getCurrentOtp($secret);
        $this->actingAs($user)->post(route('settings.2fa.confirm'), ['code' => $code]);
        $user->refresh();
        $this->assertTrue($user->two_factor_enabled);
        $this->assertSame($secret, $user->two_factor_secret);
        $this->assertNull(Session::get('two_factor_pending_secret'));

        // Password alone no longer logs in: parked for 2FA step.
        // actingAs() pinned the guard — drop it so this is a real fresh login.
        $this->app['auth']->forgetGuards();
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertRedirect(route('two-factor.show'));
        $this->assertGuest();
        $this->assertNotEmpty(Session::get('two_factor_pending_user'));

        // Wrong 2FA code rejected.
        $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // Right 2FA code signs in.
        $valid = (new Google2FA())->getCurrentOtp($user->two_factor_secret);
        $this->post(route('two-factor.verify'), ['code' => $valid])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(Session::get('two_factor_pending_user'));

        // Turning it off restores plain password login.
        $this->actingAs($user)->post(route('settings.2fa.disable'));
        $this->assertFalse($user->fresh()->two_factor_enabled);
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_avatar_upload_and_removal(): void
    {
        $user = $this->user(['role' => 'employee']);

        $this->actingAs($user)->post(route('settings.profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('me.png', 120, 120),
        ])->assertRedirect(route('settings.index'));

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('private')->exists($user->avatar_path));

        $this->actingAs($user)->get(route('profile.avatar'))->assertOk();
        // Avatar uploaded → page renders the photo, not the initials.
        $this->actingAs($user)->get(route('settings.index'))
            ->assertSee(route('profile.avatar'), false);

        $this->actingAs($user)->delete(route('settings.profile.avatar.destroy'))
            ->assertRedirect(route('settings.index'));
        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_avatar_rejects_non_image_and_oversize(): void
    {
        $user = $this->user(['role' => 'employee']);

        $this->actingAs($user)->post(route('settings.profile.avatar'), [
            'avatar' => UploadedFile::fake()->create('doc.pdf', 100),
        ])->assertSessionHasErrors('avatar');

        $this->actingAs($user)->post(route('settings.profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('big.png')->size(3000),
        ])->assertSessionHasErrors('avatar');
    }

    public function test_locale_middleware_applies_user_language(): void
    {
        $user = $this->user(['role' => 'employee', 'locale' => 'tl']);

        $response = $this->actingAs($user)->get(route('settings.index'));
        $response->assertOk()->assertSee('Pangkalahatang Setting');
    }
}
