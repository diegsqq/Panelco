<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\UploadSuggester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_requires_auth(): void
    {
        $this->post(route('documents.suggest'), [])->assertRedirect('/login');
    }

    public function test_upload_page_renders_with_suggest_url(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('documents.upload'))->assertOk()
            ->assertSee('suggestUrl')
            ->assertSee('id="dropZone"', false)
            ->assertSee('id="badgeTitle"', false);
    }

    public function test_suggest_rejects_missing_file(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->postJson(route('documents.suggest'), [])
            ->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_suggest_fills_title_category_and_date_from_txt(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $content = "Maintenance Report - Feeder 3\n"
            . "Date: January 15, 2024\n"
            . "Preventive maintenance conducted. Transformer inspection completed and repair performed by the crew.";

        $res = $this->actingAs($emp)->postJson(route('documents.suggest'), [
            'file' => UploadedFile::fake()->createWithContent('feeder3.txt', $content),
        ]);

        $res->assertOk()
            ->assertJsonPath('title', 'Maintenance Report - Feeder 3')
            ->assertJsonPath('document_date', '2024-01-15')
            ->assertJsonPath('text_found', true);
        $this->assertNotNull($res->json('category_id'));

        $catName = \App\Models\DocumentCategory::find($res->json('category_id'))->name;
        $this->assertEquals('Maintenance Reports', $catName);
    }

    public function test_suggest_falls_back_to_filename_when_unreadable(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $res = $this->actingAs($emp)->postJson(route('documents.suggest'), [
            'file' => UploadedFile::fake()->create('quarterly_report.pdf', 10, 'application/pdf'),
        ]);

        $res->assertOk()
            ->assertJsonPath('text_found', false)
            ->assertJsonPath('document_date', null)
            ->assertJsonPath('category_id', null)
            ->assertJsonPath('title', 'Quarterly Report');
    }

    public function test_suggest_uses_file_date_when_text_has_no_date(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $hint = strtotime('2025-03-04') * 1000; // browser sends milliseconds

        $res = $this->actingAs($emp)->postJson(route('documents.suggest'), [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'Some notes with no stated date inside.'),
            'file_last_modified' => $hint,
        ]);

        $res->assertOk()->assertJsonPath('document_date', '2025-03-04');
    }

    public function test_suggest_file_date_beats_date_found_in_text(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $hint = strtotime('2025-03-04') * 1000;

        $res = $this->actingAs($emp)->postJson(route('documents.suggest'), [
            'file' => UploadedFile::fake()->createWithContent('memo.txt', "Memorandum\nDated January 15, 2024\nBody text here."),
            'file_last_modified' => $hint,
        ]);

        $res->assertOk()->assertJsonPath('document_date', '2025-03-04');
    }

    public function test_store_saves_file_date_when_document_date_left_empty(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $hint = strtotime('2025-03-04') * 1000;

        $this->actingAs($emp)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('plain.txt', 'No date in this text at all.'),
            'file_last_modified' => $hint,
        ])->assertRedirect(route('documents.library'));

        $doc = Document::where('original_filename', 'plain.txt')->first();
        $this->assertNotNull($doc);
        $this->assertEquals('2025-03-04', substr((string) $doc->document_date, 0, 10));
    }

    public function test_store_keeps_explicit_document_date_over_file_date(): void
    {
        $this->seed();
        Storage::fake('private');
        Queue::fake();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $hint = strtotime('2025-03-04') * 1000;

        $this->actingAs($emp)->post(route('documents.store'), [
            'file' => UploadedFile::fake()->createWithContent('plain2.txt', 'No date in this text at all.'),
            'file_last_modified' => $hint,
            'document_date' => '2024-01-15',
        ])->assertRedirect(route('documents.library'));

        $doc = Document::where('original_filename', 'plain2.txt')->first();
        $this->assertEquals('2024-01-15', substr((string) $doc->document_date, 0, 10));
    }

    public function test_docx_metadata_creation_date_read_without_client_hint(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'doc') . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE);
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0"?><cp:coreProperties xmlns:cp="x" xmlns:dcterms="y"><dcterms:created xsi:type="dcterms:W3CDTF">2024-06-10T08:30:00Z</dcterms:created></cp:coreProperties>');
        $zip->close();

        $file = new \Illuminate\Http\UploadedFile($tmp, 'report.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        try {
            $date = app(\App\Services\UploadSuggester::class)->creationDate($file);
            $this->assertEquals('2024-06-10', $date);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_suggest_rejects_disallowed_mime(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->postJson(route('documents.suggest'), [
            'file' => UploadedFile::fake()->create('evil.exe', 10, 'application/x-msdownload'),
        ])->assertStatus(422);
    }

    public function test_date_parsing_variants(): void
    {
        $this->seed();
        $svc = app(UploadSuggester::class);
        $ref = new \ReflectionMethod(UploadSuggester::class, 'detectDate');

        $cases = [
            'Received January 15, 2024 by the crew.' => '2024-01-15',
            'Dated 15-Jan-2024 for the inspection.' => '2024-01-15',
            'Serial 01/15/2024 logged by the crew.' => '2024-01-15',
            'Reference 2024-01-15 in the log book.' => '2024-01-15',
            'Reported 25 December 2023 during the outage.' => '2023-12-25',
            'No date anywhere in this text at all.' => null,
            'Invalid 02/30/2021 date should fail checkdate.' => null,
        ];

        foreach ($cases as $text => $expected) {
            $this->assertEquals($expected, $ref->invoke($svc, $text), "Failed on: {$text}");
        }
    }
}
