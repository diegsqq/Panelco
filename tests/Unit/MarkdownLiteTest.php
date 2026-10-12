<?php

namespace Tests\Unit;

use App\Support\MarkdownLite;
use PHPUnit\Framework\TestCase;

class MarkdownLiteTest extends TestCase
{
    public function test_bold_and_paragraphs(): void
    {
        $html = (string) MarkdownLite::render("Hello **world**\n\nSecond line");
        $this->assertStringContainsString('<strong>world</strong>', $html);
        $this->assertStringContainsString('<p', $html);
    }

    public function test_lists(): void
    {
        $html = (string) MarkdownLite::render("- one\n- two\n1. first\n2. second");
        $this->assertStringContainsString('<ul', $html);
        $this->assertStringContainsString('<ol', $html);
    }

    public function test_escapes_html(): void
    {
        $html = (string) MarkdownLite::render('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
