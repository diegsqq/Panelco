<?php

namespace App\Services\Ai;

/**
 * Default provider: AI is not configured. All methods return null so the
 * system clearly reports unavailability instead of fabricating results.
 */
class NullAiProvider implements AiProvider
{
    public function available(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'none';
    }

    public function analyze(string $text): ?array
    {
        return null;
    }

    public function classify(string $text, array $categoryNames): ?array
    {
        return null;
    }

    public function answer(string $question, array $passages): ?string
    {
        return null;
    }
}
