<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\Ai\AiProvider;
use App\Services\OnlyOffice\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnlyOfficeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'onlyoffice-test-secret';

    private function configureOnlyOffice(): void
    {
        config()->set([
            'onlyoffice.enabled' => true,
            'onlyoffice.url' => 'http://127.0.0.1:8080',
            'onlyoffice.internal_url' => 'http://127.0.0.1:8000',
            'onlyoffice.jwt_secret' => self::SECRET,
            'onlyoffice.jwt_header' => 'Authorization',
        ]);
    }

    private function useEchoAiProvider(): void
    {
        $this->app->bind(AiProvider::class, fn () => new class implements AiProvider
        {
            public function available(): bool { return true; }
            public function name(): string { return 'echo'; }
            public function analyze(string $text): ?array { return null; }
            public function classify(string $text, array $categoryNames): ?array { return null; }

            public function answer(string $question, array $passages): ?string
            {
                return implode("\n", array_column($passages, 'excerpt'));
            }
        });
    }

    private function makeDocument(?User $user = null): Document
    {
        $user = $user ?? User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        return Document::create([
            'uploaded_by' => $user->id,
            'title' => 'Daily Time Record',
            'original_filename' => 'Daily Time Record.docx',
            'storage_path' => 'documents/daily-time-record.docx',
            'file_hash' => 'hash-' . uniqid(),
            'current_version_uploaded_by' => $user->id,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 12,
            'processing_status' => 'completed',
        ]);
    }

    public function test_editor_route_is_hidden_until_onlyoffice_is_enabled(): void
    {
        $this->configureOnlyOffice();
        config()->set('onlyoffice.enabled', false);

        $document = $this->makeDocument();

        $this->actingAs(User::findOrFail($document->uploaded_by))
            ->get(route('documents.onlyoffice', $document))
            ->assertNotFound();
    }

    public function test_owner_can_open_the_onlyoffice_editor(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->actingAs(User::findOrFail($document->uploaded_by))
            ->get(route('documents.onlyoffice', $document))
            ->assertOk()
            ->assertSee('/web-apps/apps/api/documents/api.js', false)
            ->assertSee('DocsAPI.DocEditor', false)
            ->assertSee('"documentType":"word"', false)
            ->assertSee('"mode":"edit"', false)
            ->assertSee('"chat":true', false)
            ->assertSee('"uiTheme":"theme-white"', false);
    }

    public function test_onlyoffice_uses_dark_theme_when_app_theme_is_dark(): void
    {
        $this->configureOnlyOffice();
        $user = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'theme' => 'dark',
        ]);
        $document = $this->makeDocument($user);

        $this->actingAs($user)
            ->get(route('documents.onlyoffice', $document))
            ->assertOk()
            ->assertSee('"uiTheme":"theme-night"', false)
            ->assertDontSee('"uiTheme":"theme-white"', false);
    }

    public function test_edit_content_route_opens_onlyoffice_when_enabled(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->actingAs(User::findOrFail($document->uploaded_by))
            ->get(route('documents.edit-content', $document))
            ->assertOk()
            ->assertSee('/web-apps/apps/api/documents/api.js', false)
            ->assertSee('DocsAPI.DocEditor', false)
            ->assertDontSee('/content/word', false);
    }

    public function test_view_query_opens_onlyoffice_in_read_only_mode(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->actingAs(User::findOrFail($document->uploaded_by))
            ->get(route('documents.edit-content', $document) . '?view=1')
            ->assertOk()
            ->assertSee('"mode":"view"', false)
            ->assertSee('"edit":false', false)
            ->assertSee('"autosave":false', false)
            ->assertDontSee('"callbackUrl"', false);
    }

    public function test_word_editor_route_is_removed(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('documents.edit-word'));
    }

    public function test_file_endpoint_serves_the_original_docx_with_a_valid_token(): void
    {
        $this->configureOnlyOffice();
        Storage::fake('private');
        Storage::disk('private')->put('documents/daily-time-record.docx', 'docx bytes');

        $document = $this->makeDocument();
        $token = Jwt::sign([
            'purpose' => 'file',
            'doc' => $document->id,
            'uid' => $document->uploaded_by,
            'exp' => now()->addHour()->timestamp,
        ], self::SECRET);

        $this->get(route('onlyoffice.file', $document) . '?token=' . urlencode($token))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertContent('docx bytes');
    }

    public function test_file_endpoint_rejects_a_missing_token(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->get(route('onlyoffice.file', $document))->assertForbidden();
    }

    public function test_callback_rejects_a_missing_token(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->postJson(route('onlyoffice.callback', $document), [
            'status' => 4,
            'key' => 'test-key',
        ])->assertForbidden();
    }

    public function test_callback_with_a_valid_token_is_acknowledged(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $token = Jwt::sign([
            'purpose' => 'callback',
            'doc' => $document->id,
            'exp' => now()->addDay()->timestamp,
        ], self::SECRET);

        $this->postJson(route('onlyoffice.callback', $document) . '?token=' . urlencode($token), [
            'status' => 4,
            'key' => 'test-key',
        ])->assertOk()->assertJson(['error' => 0]);
    }

    public function test_editor_config_exposes_signed_lightning_ai_chat_settings(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->actingAs(User::findOrFail($document->uploaded_by))
            ->get(route('documents.onlyoffice', $document))
            ->assertOk()
            ->assertSee('"lightningAi"', false)
            ->assertSee('"color":"#00A868"', false)
            ->assertSee('onlyoffice\\/ask', false);
    }

    public function test_ai_chat_endpoint_rejects_a_missing_token(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->post(route('onlyoffice.ask', $document), [
            'question' => 'hello',
        ])->assertForbidden();
    }

    public function test_ai_chat_preflight_returns_cors_headers(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->options(route('onlyoffice.ask', $document))
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:8080')
            ->assertHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->assertHeader('Access-Control-Allow-Headers', 'Content-Type');
    }

    public function test_ai_chat_endpoint_answers_with_a_valid_token(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();
        $token = Jwt::sign([
            'purpose' => 'ask',
            'doc' => $document->id,
            'uid' => $document->uploaded_by,
            'exp' => now()->addDay()->timestamp,
        ], self::SECRET);

        $this->post(route('onlyoffice.ask', $document) . '?token=' . urlencode($token), [
            'question' => 'hello',
        ])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:8080')
            ->assertSee('Lightning', false);
    }

    public function test_ai_chat_reads_the_full_current_edited_document(): void
    {
        $this->configureOnlyOffice();
        $this->useEchoAiProvider();
        $document = $this->makeDocument();
        $tailMarker = 'TAIL_MARKER_7731';

        $document->update([
            'content' => '<p>' . str_repeat('alpha beta gamma ', 1200) . '</p>'
                . '<p>' . $tailMarker . '</p>',
        ]);

        $token = Jwt::sign([
            'purpose' => 'ask',
            'doc' => $document->id,
            'uid' => $document->uploaded_by,
            'exp' => now()->addDay()->timestamp,
        ], self::SECRET);

        $this->post(route('onlyoffice.ask', $document) . '?token=' . urlencode($token), [
            'question' => 'Summarize the whole document',
        ])
            ->assertOk()
            ->assertSee($tailMarker, false);
    }

    public function test_ai_chat_reads_all_indexed_chunks_when_content_is_not_edited(): void
    {
        $this->configureOnlyOffice();
        $this->useEchoAiProvider();
        $document = $this->makeDocument();
        $tailMarker = 'CHUNK_SEVEN_MARKER';

        foreach (range(1, 8) as $index) {
            $document->chunks()->create([
                'chunk_index' => $index,
                'content' => $index === 7
                    ? $tailMarker . ' ' . str_repeat('delta ', 100)
                    : str_repeat('filler ', 100),
                'page_number' => $index,
            ]);
        }

        $token = Jwt::sign([
            'purpose' => 'ask',
            'doc' => $document->id,
            'uid' => $document->uploaded_by,
            'exp' => now()->addDay()->timestamp,
        ], self::SECRET);

        $this->post(route('onlyoffice.ask', $document) . '?token=' . urlencode($token), [
            'question' => 'What is in this document',
        ])
            ->assertOk()
            ->assertSee($tailMarker, false);
    }

    public function test_ai_chat_rejects_a_token_for_a_user_without_document_access(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();
        $outsider = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $token = Jwt::sign([
            'purpose' => 'ask',
            'doc' => $document->id,
            'uid' => $outsider->id,
            'exp' => now()->addDay()->timestamp,
        ], self::SECRET);

        $this->post(route('onlyoffice.ask', $document) . '?token=' . urlencode($token), [
            'question' => 'Summarize the document',
        ])->assertForbidden();
    }

    public function test_ai_chat_replaces_a_bare_refusal_with_closest_document_text(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();
        $marker = 'CLOSEST_TEXT_MARKER_42';

        $document->update([
            'content' => '<p>Opening paragraph about daily activities.</p>'
                . '<p>' . $marker . ' is recorded in the final section.</p>',
        ]);

        $this->app->bind(AiProvider::class, fn () => new class implements AiProvider
        {
            public function available(): bool { return true; }
            public function name(): string { return 'fake'; }
            public function analyze(string $text): ?array { return null; }
            public function classify(string $text, array $categoryNames): ?array { return null; }

            public function answer(string $question, array $passages): ?string
            {
                return "I couldn't verify that information from the available documents.";
            }
        });

        $token = Jwt::sign([
            'purpose' => 'ask',
            'doc' => $document->id,
            'uid' => $document->uploaded_by,
            'exp' => now()->addDay()->timestamp,
        ], self::SECRET);

        $response = $this->post(route('onlyoffice.ask', $document) . '?token=' . urlencode($token), [
            'question' => 'What does it say about ' . $marker,
        ]);

        $response->assertOk()
            ->assertDontSee("I couldn't verify that information", false)
            ->assertSee('closest relevant text', false)
            ->assertSee($marker, false);
    }
}
