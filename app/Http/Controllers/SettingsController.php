<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(): View
    {
        return view('settings.index', [
            'settings' => Setting::allSettings(),
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('settings')) {
            return back()->withErrors(['settings' => 'Settings table missing. Run: php artisan migrate']);
        }

        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],

            // Admin-only fields
            'app_name' => ['nullable', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:100'],
            'ai_model' => ['nullable', 'string', 'max:100'],
            'ai_temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'ai_max_tokens' => ['nullable', 'integer', 'min:64', 'max:8192'],
            'ai_chat_enabled' => ['nullable', 'in:1'],
            'ai_autofill_enabled' => ['nullable', 'in:1'],
            'ai_suggest_enabled' => ['nullable', 'in:1'],
            'upload_max_kb' => ['nullable', 'integer', 'min:1024', 'max:102400'],
            'allowed_extensions' => ['nullable', 'string', 'max:200'],
            'failed_cleanup_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'session_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'audit_retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        // Only admin can update system settings
        if (! $user->isAdmin()) {
            // Remove admin-only fields for regular users
            unset($data['app_name'], $data['support_email'], $data['ai_model'], $data['ai_temperature'], $data['ai_max_tokens'], $data['ai_chat_enabled'], $data['ai_autofill_enabled'], $data['ai_suggest_enabled'], $data['upload_max_kb'], $data['allowed_extensions'], $data['failed_cleanup_days'], $data['session_timeout_minutes'], $data['audit_retention_days']);
        }

        // Update user profile fields
        if (! empty($data['name'])) {
            $user->name = $data['name'];
        }
        if (! empty($data['email'])) {
            $user->email = $data['email'];
            // Also update the setting if it exists
            Setting::set('support_email', $data['email'] ?? '');
        }
        $user->save();

        // Update password if provided
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->save();
        }

        // Update system settings only for admins
        if ($user->isAdmin()) {
            Setting::setMany([
                'app_name' => $data['app_name'] ?? '',
                'support_email' => $data['support_email'] ?? '',

                'ai_model' => $data['ai_model'] ?? '',
                'ai_temperature' => $data['ai_temperature'] ?? '0.2',
                'ai_max_tokens' => $data['ai_max_tokens'] ?? '1024',
                'ai_chat_enabled' => $request->has('ai_chat_enabled') ? '1' : '0',
                'ai_autofill_enabled' => $request->has('ai_autofill_enabled') ? '1' : '0',
                'ai_suggest_enabled' => $request->has('ai_suggest_enabled') ? '1' : '0',

                'upload_max_kb' => $data['upload_max_kb'] ?? '51200',
                'allowed_extensions' => strtolower(preg_replace('/\s+/', '', $data['allowed_extensions'] ?? '')),
                'failed_cleanup_days' => $data['failed_cleanup_days'] ?? '0',

                'session_timeout_minutes' => $data['session_timeout_minutes'] ?? '120',
                'audit_retention_days' => $data['audit_retention_days'] ?? '90',
            ]);
        }

        return redirect()->route('settings.index')->with('status', 'Profile updated successfully.');
    }
}
