<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\MessageSource;
use App\Models\User;
use App\Services\Ai\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeChatAiProvider implements AiProvider
{
    public function available(): bool { return true; }
    public function name(): string { return 'fake-chat'; }
    public function analyze(string $text): ?array { return null; }
    public function classify(string $text, array $categoryNames): ?array { return null; }
    public function answer(string $question, array $passages): ?string
    {
        return 'Generated answer from ' . count($passages) . ' passages.';
    }
}

class Phase7ChatTest extends TestCase
{
    use RefreshDatabase;

    private function completedDoc(User $uploader, string $title, string $text): Document
    {
        $doc = Document::create([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'original_filename' => $title . '.txt',
            'storage_path' => 'documents/x.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($text),
            'department_id' => $uploader->department_id,
            'processing_status' => 'completed',
            'extracted_text' => $text,
        ]);
        $doc->chunks()->create(['chunk_index' => 0, 'content' => $text, 'page_number' => 2]);

        return $doc;
    }

    public function test_empty_library_message(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $response = $this->actingAs($emp)->post(route('chat.store'), ['question' => 'Find the latest maintenance report']);

        $response->assertRedirect();
        $conv = Conversation::where('user_id', $emp->id)->first();
        $this->assertNotNull($conv);
        $assistant = $conv->messages()->where('role', 'assistant')->first();
        $this->assertEquals(
            'No documents have been uploaded yet. Please upload a document before asking questions about stored files.',
            $assistant->content
        );
        $this->assertEquals(0, MessageSource::count());
    }

    public function test_no_match_message(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $this->completedDoc($emp, 'Holiday Memo', 'Please be informed about the holiday schedule');

        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'damaged transformer substation explosion']);

        $assistant = Conversation::first()->messages()->where('role', 'assistant')->first();
        $this->assertStringContainsString("couldn't find relevant information", $assistant->content);
        $this->assertEquals(0, MessageSource::count());
    }

    public function test_extractive_answer_cites_real_chunks(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = $this->completedDoc($emp, 'Transformer Report', 'The damaged transformer was replaced by the crew');

        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'What happened to the damaged transformer?']);

        $assistant = Conversation::first()->messages()->where('role', 'assistant')->first();
        $this->assertStringContainsString('damaged transformer was replaced', strtolower($assistant->content));
        $this->assertStringContainsString('Transformer Report', $assistant->content);

        $sources = MessageSource::all();
        $this->assertGreaterThanOrEqual(1, $sources->count());
        foreach ($sources as $src) {
            $this->assertEquals($doc->id, $src->document_id);
            $this->assertNotNull($src->document_chunk_id);
            $this->assertEquals(2, $src->page_number);
        }
    }

    public function test_generative_answer_with_ai(): void
    {
        $this->seed();
        app()->instance(AiProvider::class, new FakeChatAiProvider());
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $this->completedDoc($emp, 'Transformer Report', 'The damaged transformer was replaced by the crew');

        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'damaged transformer?']);

        $assistant = Conversation::first()->messages()->where('role', 'assistant')->first();
        $this->assertStringContainsString('Generated answer from', $assistant->content);
        $this->assertGreaterThanOrEqual(1, MessageSource::count());
    }

    public function test_pending_title_match_message(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        Document::create([
            'uploaded_by' => $emp->id,
            'title' => 'Transformer inspection notes',
            'original_filename' => 't.txt',
            'storage_path' => 'documents/t.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
            'department_id' => $emp->department_id,
            'processing_status' => 'pending',
        ]);

        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'transformer inspection notes?']);

        $assistant = Conversation::first()->messages()->where('role', 'assistant')->first();
        $this->assertStringContainsString('still being processed', $assistant->content);
    }

    public function test_conversation_isolation(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $manager = User::where('email', 'manager@panelco3.local')->first();
        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'anything here?']);
        $conv = Conversation::first();

        $this->actingAs($manager)->get(route('chat.show', $conv))->assertForbidden();
        $this->actingAs($manager)->post(route('chat.ask', $conv), ['question' => 'hi'])->assertForbidden();
    }

    public function test_greetings_answered_without_search(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'hello']);
        $assistant = Conversation::first()->messages()->where('role', 'assistant')->first();
        $this->assertStringContainsString('Lightning', $assistant->content);
        $this->assertEquals(0, MessageSource::count());

        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'Who are you?']);
        $assistant = Conversation::orderByDesc('id')->first()->messages()->where('role', 'assistant')->first();
        $this->assertStringContainsString('authorized to access', $assistant->content);
    }

    public function test_vague_question_asks_for_clarification(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'what?']);
        $assistant = Conversation::first()->messages()->where('role', 'assistant')->first();
        $this->assertStringContainsString('more specific', $assistant->content);
    }

    public function test_follow_up_inherits_previous_context(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();
        $doc = Document::create([
            'uploaded_by' => $emp->id,
            'title' => 'Transformer Report',
            'original_filename' => 't.txt',
            'storage_path' => 'documents/x.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'department_id' => $emp->department_id,
            'processing_status' => 'completed',
            'extracted_text' => 'The damaged transformer was replaced by the crew',
        ]);
        $doc->chunks()->create(['chunk_index' => 0, 'content' => 'The damaged transformer was replaced by the crew', 'page_number' => 1]);

        $this->actingAs($emp)->post(route('chat.store'), ['question' => 'Tell me about the damaged transformer']);
        $conv = Conversation::first();
        // Bare follow-up with no searchable terms of its own still finds evidence via context.
        $this->actingAs($emp)->post(route('chat.ask', $conv), ['question' => 'and the dates?']);

        $last = $conv->messages()->where('role', 'assistant')->latest('id')->first();
        $this->assertStringContainsString('transformer', strtolower($last->content));
    }

    public function test_chat_index_shows_suggestion_chips(): void
    {
        $this->seed();
        $emp = User::where('email', 'employee@panelco3.local')->first();

        $this->actingAs($emp)->get(route('chat.index', ['q' => 'Summarize it']))
            ->assertOk()
            ->assertSee('What corrective actions were recorded?');
    }
}
