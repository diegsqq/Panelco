<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UploadSuggester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
