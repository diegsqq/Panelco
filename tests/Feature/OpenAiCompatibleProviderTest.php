<?php

namespace Tests\Feature;

use App\Services\Ai\OpenAiCompatibleProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiCompatibleProviderTest extends TestCase
{
    public function test_answer_instructions_support_grounded_summary_and_corrections(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"answer":"Document Summary\\nA supported summary."}']],
                ],
            ]),
        ]);

        $provider = new OpenAiCompatibleProvider('https://ai.example.test/v1', 'test-key', 'test-model');
        $answer = $provider->answer('Summarize and correct the action', [
            ['title' => 'Maintenance Report', 'page' => 1, 'excerpt' => 'The crew repaired the damaged transformer.'],
        ]);

        $this->assertSame("Document Summary\nA supported summary.", $answer);
        Http::assertSent(function (Request $request): bool {
            $system = $request->data()['messages'][0]['content'];

            return str_contains($system, 'Document Summary, Important Details, and Corrective Actions')
                && str_contains($system, 'correct wording while preserving the original meaning')
                && str_contains($system, 'Never invent replacement facts');
        });
    }
}
