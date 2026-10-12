<?php

namespace Tests\Unit;

use App\Services\DetailExtractor;
use PHPUnit\Framework\TestCase;

class DetailExtractorTest extends TestCase
{
    public function test_extracts_action_sentences(): void
    {
        $text = 'Please be informed. The damaged transformer was replaced by the crew on September 2. The weather was nice. Connectivity was restored after the affected connection was checked within 2 hours.';
        $details = (new DetailExtractor())->extract($text);

        $this->assertNotEmpty($details);
        $joined = strtolower(implode(' ', $details));
        $this->assertStringContainsString('transformer was replaced', $joined);
        $this->assertStringContainsString('connectivity was restored', $joined);
    }

    public function test_returns_empty_when_nothing_actionable(): void
    {
        $this->assertSame([], (new DetailExtractor())->extract('Nice weather today indeed.'));
    }
}
