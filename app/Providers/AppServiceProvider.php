<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\User;
use App\Policies\DocumentPolicy;
use App\Services\Ai\AiProvider;
use App\Services\Ai\NullAiProvider;
use App\Services\Ai\OpenAiCompatibleProvider;
use App\Listeners\AuthenticationAuditListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $settings = \App\Models\Setting::allSettings();

        if (! empty($settings['app_name'])) {
            config(['app.name' => $settings['app_name']]);
        }
        if (! empty($settings['ai_model'])) {
            config(['smartdocs.ai.model' => $settings['ai_model']]);
        }
        if (! empty($settings['upload_max_kb'])) {
            config(['smartdocs.upload.max_kb' => (int) $settings['upload_max_kb']]);
        }
        $sessionTimeout = filter_var($settings['session_timeout_minutes'] ?? null, FILTER_VALIDATE_INT);
        if ($sessionTimeout !== false && $sessionTimeout >= 5 && $sessionTimeout <= 1440) {
            config(['session.lifetime' => $sessionTimeout]);
        }
        if (! empty($settings['allowed_extensions'])) {
            $exts = array_values(array_filter(array_map('trim', explode(',', $settings['allowed_extensions']))));
            if ($exts !== []) {
                config(['smartdocs.upload.allowed_extensions' => $exts]);
            }
        }

        Gate::policy(Document::class, DocumentPolicy::class);

        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('manage-documents', fn (User $user) => $user->isManager());

        Event::listen(Login::class, [AuthenticationAuditListener::class, 'login']);
        Event::listen(Failed::class, [AuthenticationAuditListener::class, 'failed']);
        Event::listen(Lockout::class, [AuthenticationAuditListener::class, 'lockedOut']);
        Event::listen(Logout::class, [AuthenticationAuditListener::class, 'logout']);

        $this->app->singleton(AiProvider::class, function () {
            $provider = (string) config('smartdocs.ai.provider', 'none');
            $key = (string) env('AI_API_KEY', '');
            $model = (string) config('smartdocs.ai.model', '');

            if ($provider === 'openai-compatible' && $key !== '' && $model !== '') {
                return new OpenAiCompatibleProvider(
                    (string) env('AI_BASE_URL', 'https://api.openai.com/v1'),
                    $key,
                    $model
                );
            }

            return new NullAiProvider();
        });
    }
}
