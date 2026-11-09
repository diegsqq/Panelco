<?php

namespace Tests\Unit;

use App\Services\DocumentContentSanitizer;
use PHPUnit\Framework\TestCase;

class DocumentContentSanitizerTest extends TestCase
{
    private DocumentContentSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new DocumentContentSanitizer();
    }

    public function test_keeps_bold_italic_and_underline_formatting(): void
    {
        $clean = $this->sanitizer->sanitize('<p>Plain <strong>bold</strong> <em>italic</em> <u>under</u> <s>strike</s></p>');

        $this->assertStringContainsString('<strong>bold</strong>', $clean);
        $this->assertStringContainsString('<em>italic</em>', $clean);
        $this->assertStringContainsString('<u>under</u>', $clean);
        $this->assertStringContainsString('<s>strike</s>', $clean);
        $this->assertStringContainsString('Plain', $clean);
    }

    public function test_keeps_tables_headings_and_lists(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<h2>Title</h2><table><thead><tr><th>A</th></tr></thead><tbody><tr><td colspan="2">B</td></tr></tbody></table><ul><li>one</li></ul>'
        );

        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<th>A</th>', $clean);
        $this->assertStringContainsString('<td colspan="2">B</td>', $clean);
        $this->assertStringContainsString('<li>one</li>', $clean);
    }

    public function test_keeps_images_and_strips_event_handlers(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<img src="/library/3/content/images/abc.png" alt="logo" width="120" onerror="alert(1)">'
        );

        $this->assertStringContainsString('src="/library/3/content/images/abc.png"', $clean);
        $this->assertStringContainsString('alt="logo"', $clean);
        $this->assertStringContainsString('width="120"', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
    }

    public function test_drops_script_style_and_embedded_content_entirely(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<p>keep</p><script>alert(1)</script><style>body{display:none}</style><iframe src="https://evil.test"></iframe>'
        );

        $this->assertStringContainsString('keep', $clean);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('alert', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringNotContainsString('display:none', $clean);
    }

    public function test_strips_event_handler_attributes_from_allowed_tags(): void
    {
        $clean = $this->sanitizer->sanitize('<p onclick="steal()">text</p><a href="/ok" onmouseover="steal()">link</a>');

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onmouseover', $clean);
        $this->assertStringNotContainsString('steal', $clean);
        $this->assertStringContainsString('href="/ok"', $clean);
    }

    public function test_blocks_javascript_and_data_links(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<a href="javascript:alert(1)">bad</a> <a href="data:text/html;base64,PHNjcmlwdD4=">bad2</a> <a href="https://panelco3.local/ok">good</a>'
        );

        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('data:text/html', $clean);
        $this->assertStringContainsString('href="https://panelco3.local/ok"', $clean);
    }

    public function test_allows_base64_image_sources_only(): void
    {
        $clean = $this->sanitizer->sanitize('<img src="data:image/png;base64,iVBORw0KGgo="><img src="data:text/html;base64,PHNjcmlwdD4=">');

        $this->assertStringContainsString('data:image/png;base64,iVBORw0KGgo=', $clean);
        $this->assertStringNotContainsString('data:text/html', $clean);
    }

    public function test_unwraps_unknown_tags_but_keeps_their_text(): void
    {
        $clean = $this->sanitizer->sanitize('<article>kept text</article>');

        $this->assertStringContainsString('kept text', $clean);
        $this->assertStringNotContainsString('article', $clean);
    }

    public function test_style_attribute_is_filtered_to_safe_properties(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<p style="color: red; text-align: center; background-image: url(javascript:alert(1)); behavior: url(x)">styled</p>'
        );

        $this->assertStringContainsString('color: red', $clean);
        $this->assertStringContainsString('text-align: center', $clean);
        $this->assertStringNotContainsString('background-image', $clean);
        $this->assertStringNotContainsString('url(', $clean);
        $this->assertStringNotContainsString('behavior', $clean);
    }

    public function test_rejects_non_numeric_dimensions(): void
    {
        $clean = $this->sanitizer->sanitize('<img src="/library/1/content/images/a.png" width="expression(alert(1))" height="50%">');

        $this->assertStringNotContainsString('width=', $clean);
        $this->assertStringContainsString('height="50%"', $clean);
    }

    public function test_sanitizes_empty_and_null_input_to_empty_string(): void
    {
        $this->assertSame('', $this->sanitizer->sanitize(null));
        $this->assertSame('', $this->sanitizer->sanitize('   '));
    }

    public function test_multibyte_characters_survive_sanitization(): void
    {
        $clean = $this->sanitizer->sanitize('<p>Tagalog — kontratang panlipunan ₱1,250.00, mañana</p>');

        $this->assertStringContainsString('kontratang panlipunan ₱1,250.00, mañana', $clean);
    }

    public function test_keeps_only_allowed_class_names(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<span class="sd-page-token evil another">token</span><p class="foo">text</p>'
        );

        $this->assertStringContainsString('class="sd-page-token"', $clean);
        $this->assertStringNotContainsString('evil', $clean);
        $this->assertStringNotContainsString('class="foo"', $clean);
    }

    public function test_to_plain_text_flattens_markup(): void
    {
        $text = $this->sanitizer->toPlainText('<p>Hello <strong>world</strong></p>');

        $this->assertSame('Hello world', $text);
    }
}
