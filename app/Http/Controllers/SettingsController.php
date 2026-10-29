<?php

namespace App\Http\Controllers;

use App\Mail\ProfileVerificationCode;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware('role:admin')->only(['update', 'updateRetention']);
    }

    public function index(): View
    {
        return view('settings.index', [
            'settings' => Setting::allSettings(),
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
        ]);
    }

    /** Settings > Security (sidebar): change password + 2FA + retention. */
    public function security(): View
    {
        return view('settings.security', [
            'settings' => Setting::allSettings(),
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
        ]);
    }

    /** Settings > Preferences (sidebar): theme, language, notifications. */
    public function preferences(): View
    {
        return view('settings.preferences', [
            'settings' => Setting::allSettings(),
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
        ]);
    }

    /**
     * Any signed-in user updates their own contact details here
     * (Settings > General > My Account).
     *
     * Email and contact number changes are not applied directly: a
     * 6-digit code is emailed first (new address for email changes,
     * current address for phone changes) and the value only updates
     * after the code is confirmed.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:150'],
        ]);

        $firstName = trim($data['first_name']);
        $lastName = trim($data['last_name']);
        $newEmail = trim($data['email']);
        $newPhone = isset($data['phone']) && $data['phone'] !== null ? trim($data['phone']) : null;

        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->name = $firstName . ' ' . $lastName;
        $user->job_title = isset($data['job_title']) && $data['job_title'] !== null ? trim($data['job_title']) : null;
        $user->organization = isset($data['organization']) && $data['organization'] !== null ? trim($data['organization']) : null;

        $status = 'Your account details were updated.';
        $newEmailPending = false;
        $newPhonePending = false;

        // Email changed → verify at the NEW address before applying.
        if ($newEmail !== $user->email) {
            $user->pending_email = $newEmail;
            $user->email_change_code = (string) random_int(100000, 999999);
            $user->verification_expires_at = now()->addMinutes(30);
            $newEmailPending = true;
        } else {
            $user->pending_email = null;
            $user->email_change_code = null;
        }

        // Contact number changed → code goes to the CURRENT email
        // (email is the working channel until SMS is configured).
        if ($newPhone !== $user->phone) {
            $user->pending_phone = $newPhone;
            $user->phone_change_code = (string) random_int(100000, 999999);
            $user->verification_expires_at = now()->addMinutes(30);
            $newPhonePending = true;
        } else {
            $user->pending_phone = null;
            $user->phone_change_code = null;
        }

        $user->save();

        $failures = [];

        if ($newEmailPending) {
            if ($this->sendVerificationCode($user, $user->pending_email, 'Confirm your new email address for your SmartDocs account.', $user->email_change_code)) {
                $status = 'A verification code was sent to your new email address.';
            } else {
                // Mail transport broken → don't leave a pending change
                // the user can never confirm; revert and say why.
                $user->pending_email = null;
                $user->email_change_code = null;
                $user->save();
                $newEmailPending = false;
                $failures[] = 'new email';
            }
        }

        if ($newPhonePending) {
            if ($this->sendVerificationCode($user, $user->email, 'Confirm your new contact number for your SmartDocs account.', $user->phone_change_code)) {
                $status = $newEmailPending
                    ? 'Verification codes were sent for your new email and contact number.'
                    : 'A verification code was sent to your email to confirm the new contact number.';
            } else {
                $user->pending_phone = null;
                $user->phone_change_code = null;
                $user->save();
                $newPhonePending = false;
                $failures[] = 'new contact number';
            }
        }

        if (! $newEmailPending && ! $newPhonePending && $failures === []) {
            $status = 'Your account details were updated.';
        }

        if ($failures !== []) {
            $status .= ' Verification email could NOT be sent for the '
                . implode(' and ', $failures)
                . ' — it was not changed. Ask the administrator to fix the mail settings, then try again.';
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.profile_updated',
            'entity_type' => \App\Models\User::class,
            'entity_id' => $user->id,
            'metadata' => [
                'email_pending' => $newEmailPending,
                'phone_pending' => $newPhonePending,
            ],
        ]);

        return redirect()->route('settings.index')->with('status', $status);
    }

    /** Confirm a pending email change with the emailed code. */
    public function verifyEmail(Request $request): RedirectResponse
    {
        return $this->verifyProfileChange($request, 'email');
    }

    /** Confirm a pending contact number change with the emailed code. */
    public function verifyPhone(Request $request): RedirectResponse
    {
        return $this->verifyProfileChange($request, 'phone');
    }

    /** Re-send a verification code for email or phone. */
    public function resendProfileCode(Request $request): RedirectResponse
    {
        $user = $request->user();
        $type = $request->input('type');

        abort_unless(in_array($type, ['email', 'phone'], true), 400, 'Unknown verification type.');

        if ($type === 'email') {
            abort_unless($user->pending_email && $user->email_change_code, 400, 'No pending email change.');
            $user->email_change_code = (string) random_int(100000, 999999);
            $user->verification_expires_at = now()->addMinutes(30);
            $user->save();

            if (! $this->sendVerificationCode($user, $user->pending_email, 'Confirm your new email address for your SmartDocs account.', $user->email_change_code)) {
                return back()->withErrors(['code' => 'Verification email could not be sent. Ask the administrator to fix the mail settings.']);
            }

            return redirect()->route('settings.index')->with('status', 'A new verification code was sent to ' . $user->pending_email . '.');
        }

        abort_unless($user->pending_phone && $user->phone_change_code, 400, 'No pending contact number change.');
        $user->phone_change_code = (string) random_int(100000, 999999);
        $user->verification_expires_at = now()->addMinutes(30);
        $user->save();

        if (! $this->sendVerificationCode($user, $user->email, 'Confirm your new contact number for your SmartDocs account.', $user->phone_change_code)) {
            return back()->withErrors(['code' => 'Verification email could not be sent. Ask the administrator to fix the mail settings.']);
        }

        return redirect()->route('settings.index')->with('status', 'A new verification code was sent to ' . $user->email . '.');
    }

    /**
     * Sends the 6-digit code by email. Returns false instead of throwing
     * when SMTP is misconfigured, so a mail outage never breaks the page.
     */
    private function sendVerificationCode(User $user, string $to, string $purpose, string $code): bool
    {
        try {
            Mail::to($to)->send(new ProfileVerificationCode($purpose, $code));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function verifyProfileChange(Request $request, string $type): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);

        $pendingColumn = $type === 'email' ? 'pending_email' : 'pending_phone';
        $codeColumn = $type === 'email' ? 'email_change_code' : 'phone_change_code';

        if (! $user->{$pendingColumn} || ! $user->{$codeColumn}) {
            return back()->withErrors(['code' => 'No pending ' . ($type === 'email' ? 'email' : 'contact number') . ' change to verify.']);
        }

        if ($user->verification_expires_at === null || $user->verification_expires_at->isPast()) {
            $user->update([$pendingColumn => null, $codeColumn => null, 'verification_expires_at' => null]);

            return back()->withErrors(['code' => 'That code has expired. Please save your details again to get a new code.']);
        }

        if (! hash_equals($user->{$codeColumn}, trim($data['code']))) {
            return back()->withErrors(['code' => 'Incorrect verification code.']);
        }

        if ($type === 'email') {
            $user->email = $user->pending_email;
        } else {
            $user->phone = $user->pending_phone;
        }

        $user->update([
            $pendingColumn => null,
            $codeColumn => null,
            'verification_expires_at' => null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $type === 'email' ? 'user.email_verified' : 'user.phone_verified',
            'entity_type' => \App\Models\User::class,
            'entity_id' => $user->id,
            'metadata' => [],
        ]);

        return redirect()->route('settings.index')->with('status', ($type === 'email' ? 'Email address' : 'Contact number') . ' verified and updated.');
    }

    /**
     * Avatar upload: stored on the private disk, shown only to the
     * signed-in owner (topbar + My Account).
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $file = $request->file('avatar');
        $path = $file->storeAs('avatars', $user->id . '.' . strtolower($file->getClientOriginalExtension()), 'private');

        if ($user->avatar_path) {
            Storage::disk('private')->delete($user->avatar_path);
        }
        $user->update(['avatar_path' => $path]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.avatar_updated',
            'entity_type' => \App\Models\User::class,
            'entity_id' => $user->id,
            'metadata' => [],
        ]);

        return redirect()->route('settings.index');
    }

    public function destroyAvatar(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->avatar_path) {
            Storage::disk('private')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return redirect()->route('settings.index')->with('status', 'Profile picture removed.');
    }

    /** Streams the signed-in user's own avatar (inline). */
    public function showAvatar(): StreamedResponse
    {
        $user = auth()->user();

        abort_unless($user->avatar_path, 404);
        abort_unless(Storage::disk('private')->exists($user->avatar_path), 404);

        return Storage::disk('private')->response($user->avatar_path, 'avatar.' . pathinfo($user->avatar_path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, max-age=3600'], 'inline');
    }

    /** Change password: current + new + confirm. All users. */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => $data['password']]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.password_changed',
            'entity_type' => \App\Models\User::class,
            'entity_id' => $user->id,
            'metadata' => [],
        ]);

        return redirect()->route('settings.security')->with('status', 'Password changed successfully.');
    }

    /** Step 1 of 2FA: generate a secret (kept in session until confirmed). */
    public function twoFactorSetup(Request $request): RedirectResponse
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        session(['two_factor_pending_secret' => $secret]);

        return redirect()->route('settings.security')->with('status', 'Scan the QR code with Google Authenticator, then enter the 6-digit code to activate.');
    }

    /** Step 2 of 2FA: verify one code, then store the secret for good. */
    public function twoFactorConfirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = session('two_factor_pending_secret');

        abort_unless($secret, 400, 'No pending 2FA setup. Start setup first.');

        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);

        if (! app(Google2FA::class)->verifyKey($secret, trim($data['code']))) {
            return back()->withErrors(['code' => 'Incorrect code. Check the current code in Google Authenticator and try again.']);
        }

        $user->update(['two_factor_enabled' => true, 'two_factor_secret' => $secret]);
        session()->forget('two_factor_pending_secret');

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.2fa_enabled',
            'entity_type' => \App\Models\User::class,
            'entity_id' => $user->id,
            'metadata' => [],
        ]);

        return redirect()->route('settings.security')->with('status', 'Two-factor authentication is now ON.');
    }

    public function twoFactorDisable(): RedirectResponse
    {
        $user = auth()->user();

        $user->update(['two_factor_enabled' => false, 'two_factor_secret' => null]);
        session()->forget('two_factor_pending_secret');

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.2fa_disabled',
            'entity_type' => \App\Models\User::class,
            'entity_id' => $user->id,
            'metadata' => [],
        ]);

        return redirect()->route('settings.security')->with('status', 'Two-factor authentication turned OFF.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $data = $request->validate(['theme' => ['required', 'in:light,dark']]);
        $request->user()->update(['theme' => $data['theme']]);

        return redirect()->back();
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'in:en,tl']]);
        $request->user()->update(['locale' => $data['locale']]);

        return redirect()->back()->with('status', 'Language set to ' . ($data['locale'] === 'tl' ? 'Tagalog' : 'English') . '.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'notify_doc_done' => $request->boolean('notify_doc_done'),
            'notify_doc_failed' => $request->boolean('notify_doc_failed'),
            'notify_login_alert' => $request->boolean('notify_login_alert'),
        ]);

        return redirect()->back()->with('status', 'Notification preferences saved.');
    }

    public function update(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('settings')) {
            return back()->withErrors(['settings' => 'Settings table missing. Run: php artisan migrate']);
        }

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:100'],

            'ai_model' => ['nullable', 'string', 'max:100'],
            'ai_temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'ai_max_tokens' => ['required', 'integer', 'min:64', 'max:8192'],
            'ai_chat_enabled' => ['nullable', 'in:1'],
            'ai_autofill_enabled' => ['nullable', 'in:1'],
            'ai_suggest_enabled' => ['nullable', 'in:1'],

            'upload_max_kb' => ['required', 'integer', 'min:1024', 'max:102400'],
            'allowed_extensions' => ['required', 'string', 'max:200'],
            'failed_cleanup_days' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        Setting::setMany([
            'app_name' => $data['app_name'],
            'support_email' => $data['support_email'] ?? '',

            'ai_model' => $data['ai_model'] ?? '',
            'ai_temperature' => (string) $data['ai_temperature'],
            'ai_max_tokens' => (string) $data['ai_max_tokens'],
            'ai_chat_enabled' => $request->has('ai_chat_enabled') ? '1' : '0',
            'ai_autofill_enabled' => $request->has('ai_autofill_enabled') ? '1' : '0',
            'ai_suggest_enabled' => $request->has('ai_suggest_enabled') ? '1' : '0',

            'upload_max_kb' => (string) $data['upload_max_kb'],
            'allowed_extensions' => strtolower(preg_replace('/\s+/', '', $data['allowed_extensions'])),
            'failed_cleanup_days' => (string) $data['failed_cleanup_days'],
        ]);

        return redirect()->route('settings.preferences')->with('status', 'Settings saved successfully.');
    }

    /** Settings > Security > Session and audit retention (admin only). */
    public function updateRetention(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('settings')) {
            return back()->withErrors(['settings' => 'Settings table missing. Run: php artisan migrate']);
        }

        $data = $request->validate([
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'audit_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        Setting::setMany([
            'session_timeout_minutes' => (string) $data['session_timeout_minutes'],
            'audit_retention_days' => (string) $data['audit_retention_days'],
        ]);

        return redirect()->route('settings.security')->with('status', 'Retention settings saved.');
    }
}
