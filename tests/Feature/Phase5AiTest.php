<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Services\Ai\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FakeAiProvider implements AiProvider
{
    public function available(): bool { return true; }
    public function name(): string { return 'fake'; }
    public function analyze(string $text): ?array
    {
        return ['summary' => 'Fake summary of report.', 'keywords' => ['power', 'zone 4']];
    }
    public function classify(string $text, array $categoryNames): ?array
    {
        $pick = in_array('Maintenance Reports', $categoryNames, true) ? 'Maintenance Reports' : null;

        return ['category' => $pick, 'confidence' => 0.82];
    }

    public function answer(string $question, array $passages): ?string
    {
        return 'Fake generated answer.';
    }
}

class Phase5AiTest extends TestCase
{
    use RefreshDatabase;

    private function makeTxt(User $uploader, string $text): Document
    {
        Storage::fake('private');
        Storage::disk('private')->put('documents/r.txt', $text);

        return Document::create([
            'uploaded_by' => $uploader->id,
            'original_filename' => 'r.txt',
            'storage_path' => 'documents/r.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($text),
            'processing_status' => 'pending',
        ]);
    }

    public function test_rule_suggestion_without_ai(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeTxt($emp, 'Preventive maintenance schedule transformer inspection crew assigned');

        app()->call([new ProcessDocument($doc->id), 'handle']);

        $doc->refresh();
        $this->assertEquals('completed', $doc->processing_status);
        $this->assertNull($doc->summary);
        $this->assertNull($doc->classification_confidence);
        $this->assertEquals('Maintenance Reports', $doc->suggestedCategory->name);
        $this->assertNull($doc->category_id);
    }

    public function test_fake_ai_adds_summary_and_confidence(): void
    {
        $this->seed();
        app()->instance(AiProvider::class, new FakeAiProvider());
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->makeTxt($emp, 'Preventive maintenance schedule transformer inspection crew assigned');

        app()->call([new ProcessDocument($doc->id), 'handle']);

        $doc->refresh();
        $this->assertEquals('Fake summary of report.', $doc->summary);
        $this->assertStringContainsString('power', $doc->keywords);
        $this->assertEquals('Maintenance Reports', $doc->suggestedCategory->name);
        $this->assertEquals(0.82, (float) $doc->classification_confidence);
    }

    public function test_manager_can_approve_employee_cannot(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $manager = User::where('email', 'manager@panelco3.local')->first();
        $doc = $this->makeTxt($emp, 'Some plain content');
        $cat = DocumentCategory::where('name', 'Memorandums')->first();

        $this->actingAs($emp)->post(route('documents.approve', $doc), ['category_id' => $cat->id])->assertForbidden();

        $this->actingAs($manager)->post(route('documents.approve', $doc), ['category_id' => $cat->id])->assertRedirect();
        $doc->refresh();
        $this->assertEquals($cat->id, $doc->category_id);
        $this->assertNotNull($doc->approved_at);
        $this->assertEquals($manager->id, $doc->approved_by);
    }

    public function test_null_provider_reports_unavailable(): void
    {
        $provider = app(AiProvider::class);
        $this->assertFalse($provider->available());
        $this->assertNull($provider->analyze('anything'));
        $this->assertNull($provider->classify('anything', ['Memorandums']));
    }

    public function test_unmatched_text_falls_back_to_other_documents(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        Storage::fake('private');
        Storage::disk('private')->put('documents/r.txt', 'Xylophone quantum zebra absolutelynothingmatcheshere');

        $doc = Document::create([
            'uploaded_by' => $emp->id,
            'original_filename' => 'r.txt',
            'storage_path' => 'documents/r.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => 'pending',
        ]);

        app()->call([new ProcessDocument($doc->id), 'handle']);

        $doc->refresh();
        $this->assertEquals('completed', $doc->processing_status);
        $this->assertEquals('Other Documents', $doc->suggestedCategory->name);
        $this->assertNull($doc->category_id);
    }
}
