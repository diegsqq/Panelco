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
        $this->middleware('role:admin')->only('update');
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

            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'audit_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
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

            'session_timeout_minutes' => (string) $data['session_timeout_minutes'],
            'audit_retention_days' => (string) $data['audit_retention_days'],
        ]);

        return redirect()->route('settings.index')->with('status', 'Settings saved successfully.');
    }
}
