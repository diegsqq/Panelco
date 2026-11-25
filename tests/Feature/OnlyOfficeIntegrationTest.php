<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
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

    private function makeDocument(): Document
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

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
            ->assertSee('"mode":"edit"', false);
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
            ->assertSee(route('documents.edit-word', $document), false);
    }

    public function test_content_editor_links_to_onlyoffice_when_enabled(): void
    {
        $this->configureOnlyOffice();
        $document = $this->makeDocument();

        $this->actingAs(User::findOrFail($document->uploaded_by))
            ->get(route('documents.edit-word', $document))
            ->assertOk()
            ->assertSee(route('documents.onlyoffice', $document), false);
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
}
