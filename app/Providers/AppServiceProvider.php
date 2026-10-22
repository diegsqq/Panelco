<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\User;
use App\Policies\DocumentPolicy;
use App\Services\Ai\AiProvider;
use App\Services\Ai\NullAiProvider;
use App\Services\Ai\OpenAiCompatibleProvider;
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
        if (! empty($settings['allowed_extensions'])) {
            $exts = array_values(array_filter(array_map('trim', explode(',', $settings['allowed_extensions']))));
            if ($exts !== []) {
                config(['smartdocs.upload.allowed_extensions' => $exts]);
            }
        }

        Gate::policy(Document::class, DocumentPolicy::class);

        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('manage-documents', fn (User $user) => $user->isManager());

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
