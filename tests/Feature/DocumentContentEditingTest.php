<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentContentEditingTest extends TestCase
{
    use RefreshDatabase;

    private function makeDocument(User $uploader): Document
    {
        return Document::create([
            'uploaded_by' => $uploader->id,
            'title' => 'Board resolution',
            'original_filename' => 'resolution.txt',
            'storage_path' => 'documents/2026/11/resolution.txt',
            'file_hash' => 'hash-' . uniqid(),
            'current_version_uploaded_by' => $uploader->id,
            'mime_type' => 'text/plain',
            'file_size' => 1024,
            'processing_status' => 'completed',
        ]);
    }

    public function test_owner_can_open_the_content_editor(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->get(route('documents.edit-content', $document))
            ->assertOk()
            ->assertSee('docBody')
            ->assertSee('docHeader')
            ->assertSee('docFooter');

        // The metadata edit page links across to the content editor.
        $this->actingAs($employee)
            ->get(route('documents.edit', $document))
            ->assertOk()
            ->assertSee(route('documents.edit-content', $document), false);
    }

    public function test_unrelated_employee_cannot_open_the_content_editor(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);
        $stranger = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($stranger)
            ->get(route('documents.edit-content', $document))
            ->assertForbidden();
    }

    public function test_autosave_persists_content_header_and_footer(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->putJson(route('documents.update-content', $document), [
                'content' => '<p>Body with <strong>bold</strong>, <em>italic</em> and <u>underline</u>.</p>',
                'header' => '<b>PANELCO III</b>',
                'footer' => '<i>Page footer</i>',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $document->refresh();
        $this->assertStringContainsString('<strong>bold</strong>', (string) $document->content);
        $this->assertStringContainsString('<em>italic</em>', (string) $document->content);
        $this->assertStringContainsString('<u>underline</u>', (string) $document->content);
        $this->assertStringContainsString('<b>PANELCO III</b>', (string) $document->content_header);
        $this->assertStringContainsString('<i>Page footer</i>', (string) $document->content_footer);
        $this->assertNotNull($document->content_updated_at);
        $this->assertSame($employee->id, $document->content_updated_by);
    }

    public function test_autosave_sanitizes_dangerous_markup_before_storing(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->putJson(route('documents.update-content', $document), [
                'content' => '<p onclick="steal()">hello</p><script>alert(1)</script>'
                    . '<img src="/library/1/content/images/a.png" onerror="alert(2)">'
                    . '<a href="javascript:alert(3)">click</a>',
                'header' => '<style>body{display:none}</style>Header',
                'footer' => 'Footer<iframe src="https://evil.test"></iframe>',
            ])
            ->assertOk();

        $document->refresh();
        $stored = (string) $document->content;

        $this->assertStringContainsString('hello', $stored);
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('alert(1)', $stored);
        $this->assertStringNotContainsString('onclick', $stored);
        $this->assertStringNotContainsString('onerror', $stored);
        $this->assertStringNotContainsString('javascript:', $stored);
        $this->assertStringNotContainsString('iframe', (string) $document->content_footer);
        $this->assertStringNotContainsString('display:none', (string) $document->content_header);
        $this->assertStringNotContainsString('style', (string) $document->content_header);
    }

    public function test_autosave_rejects_oversized_content(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->putJson(route('documents.update-content', $document), [
                'content' => str_repeat('a', 400001),
            ])
            ->assertStatus(422);

        $this->assertNull($document->refresh()->content);
    }

    public function test_autosave_is_denied_without_update_permission(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);
        $stranger = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($stranger)
            ->putJson(route('documents.update-content', $document), ['content' => '<p>hijacked</p>'])
            ->assertForbidden();

        $this->assertNull($document->refresh()->content);
    }

    public function test_first_save_writes_an_audit_log_entry(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->putJson(route('documents.update-content', $document), ['content' => '<p>first</p>'])
            ->assertOk();

        $this->assertTrue(
            AuditLog::where('action', 'document.content_updated')
                ->where('entity_id', $document->id)
                ->where('user_id', $employee->id)
                ->exists()
        );
    }

    public function test_editor_image_upload_returns_a_private_url_that_respects_permissions(): void
    {
        $this->seed();
        Storage::fake('private');
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $response = $this->actingAs($employee)
            ->post(route('documents.content-images', $document), [
                'image' => UploadedFile::fake()->image('logo.png', 40, 40),
            ])
            ->assertCreated();

        $url = $response->json('url');
        $this->assertNotEmpty($url);

        $this->actingAs($employee)->get($url)->assertOk();

        $stranger = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $this->actingAs($stranger)->get($url)->assertForbidden();
    }

    public function test_image_upload_rejects_unsupported_files(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->postJson(route('documents.content-images', $document), [
                'image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422);
    }

    public function test_saved_content_is_rendered_on_the_document_page(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);
        $document->update([
            'content' => '<p>Quarterly <strong>operational</strong> report body</p>',
            'content_header' => '<b>PANELCO III</b>',
            'content_footer' => '<i>Confidential</i>',
            'content_updated_at' => now(),
            'content_updated_by' => $employee->id,
        ]);

        $this->actingAs($employee)
            ->get(route('documents.show', $document))
            ->assertOk()
            ->assertSee('Quarterly')
            ->assertSee('operational')
            ->assertSee('PANELCO III')
            ->assertSee('Confidential')
            ->assertSee(route('documents.edit-content', $document), false);
    }

    public function test_documents_without_content_do_not_show_the_content_section(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->get(route('documents.show', $document))
            ->assertOk()
            ->assertDontSee('id="document-content"', false);
    }

    public function test_guests_are_redirected_from_the_editor(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->get(route('documents.edit-content', $document))->assertRedirect();
        $this->put(route('documents.update-content', $document), ['content' => '<p>x</p>'])->assertRedirect();
    }

    public function test_autosave_persists_page_layout(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->putJson(route('documents.update-content', $document), [
                'content' => '<p>x</p>',
                'page_paper' => 'letter',
                'page_orientation' => 'landscape',
                'page_margins' => 'narrow',
            ])
            ->assertOk();

        $document->refresh();
        $this->assertSame('letter', $document->page_paper);
        $this->assertSame('landscape', $document->page_orientation);
        $this->assertSame('narrow', $document->page_margins);
    }

    public function test_autosave_rejects_invalid_page_layout(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->putJson(route('documents.update-content', $document), [
                'content' => '<p>x</p>',
                'page_paper' => 'tabloid',
            ])
            ->assertStatus(422);
    }

    public function test_editor_page_lists_templates(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->get(route('documents.edit-content', $document))
            ->assertOk()
            ->assertSee('Memorandum')
            ->assertSee('Office Order');
    }

    public function test_export_docx_returns_409_without_content(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);

        $this->actingAs($employee)
            ->get(route('documents.content-docx', $document))
            ->assertStatus(409);
    }

    public function test_export_docx_returns_a_word_file_containing_the_text(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);
        $document->update([
            'content' => '<p>Hello <strong>World</strong></p>',
            'content_updated_at' => now(),
            'content_updated_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)
            ->get(route('documents.content-docx', $document));
        $response->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'sd-docx');
        file_put_contents($tmp, $response->getContent());

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmp) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertIsString($xml);
        $this->assertStringContainsString('Hello', $xml);
        $this->assertStringContainsString('World', $xml);
    }

    public function test_export_pdf_returns_a_pdf_file(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);
        $document->update([
            'content' => '<p>Hello <strong>World</strong></p>',
            'content_updated_at' => now(),
            'content_updated_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)
            ->get(route('documents.content-pdf', $document));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_export_selection_pdf_returns_a_pdf_file(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);
        $document->update([
            'content' => '<p>Hello <strong>World</strong></p>',
            'content_updated_at' => now(),
            'content_updated_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)
            ->postJson(route('documents.content-pdf-selection', $document), [
                'html' => '<p>Selected paragraph</p>',
            ]);
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_export_is_denied_without_download_permission(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->makeDocument($employee);
        $document->update([
            'content' => '<p>x</p>',
            'content_updated_at' => now(),
            'content_updated_by' => $employee->id,
        ]);
        $stranger = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($stranger)->get(route('documents.content-docx', $document))->assertForbidden();
        $this->actingAs($stranger)->get(route('documents.content-pdf', $document))->assertForbidden();
    }
}
