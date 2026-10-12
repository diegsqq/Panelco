<?php

namespace App\Services\Ai;

interface AiProvider
{
    public function available(): bool;

    public function name(): string;

    /**
     * @return array{summary: ?string, keywords: list<string>}|null
     */
    public function analyze(string $text): ?array;

    /**
     * @param  list<array{title: string, page: ?int, excerpt: string}>  $passages
     */
    public function answer(string $question, array $passages): ?string;

    /**
     * @param  list<string>  $categoryNames
     *
     * @return array{category: ?string, confidence: ?float}|null
     */
    public function classify(string $text, array $categoryNames): ?array;
}
