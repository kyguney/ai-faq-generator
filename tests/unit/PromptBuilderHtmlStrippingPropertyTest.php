<?php
/**
 * Property-based test for HTML tag stripping in Prompt_Builder.
 *
 * Feature: prompt-builder, Property 1: HTML Tag Stripping
 *
 * For any post title or post content string containing HTML tags, the Prompt_Builder
 * output SHALL NOT contain any of those HTML tags — only the text content extracted
 * from within the tags shall appear in the prompt string.
 *
 * Validates: Requirements 1.2, 1.3
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class PromptBuilderHtmlStrippingPropertyTest extends TestCase
{
    private Prompt_Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Prompt_Builder();
    }

    /**
     * **Validates: Requirements 1.2, 1.3**
     *
     * Property 1: HTML Tag Stripping.
     * For any input string wrapped in HTML tags, the build() output must not
     * contain any HTML tags (no '<' or '>' characters from tags remain).
     */
    #[Test]
    #[DataProvider('htmlWrappedStringsProvider')]
    public function html_tags_are_stripped_from_output(string $htmlTitle, string $htmlContent): void
    {
        $result = $this->builder->build($htmlTitle, $htmlContent);

        // Assert no HTML tags remain in the output.
        $this->assertDoesNotMatchRegularExpression(
            '/<[a-z\/!][^>]*>/i',
            $result,
            "Output should not contain any HTML tags. Got: {$result}"
        );
    }

    /**
     * Data provider generating 100+ random strings wrapped in random HTML tags.
     *
     * @return array<string, array{string, string}>
     */
    public static function htmlWrappedStringsProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(12345);

        $tags = [
            'p', 'div', 'span', 'a', 'strong', 'em', 'b', 'i', 'u',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li',
            'table', 'tr', 'td', 'th', 'thead', 'tbody',
            'script', 'style',
            'blockquote', 'pre', 'code',
            'img', 'br', 'hr',
            'section', 'article', 'header', 'footer', 'nav', 'main',
            'form', 'input', 'button', 'select', 'textarea',
        ];

        for ($i = 0; $i < 110; $i++) {
            $titleText = self::generateRandomText(mt_rand(3, 30));
            $contentText = self::generateRandomText(mt_rand(10, 100));

            $htmlTitle = self::wrapInRandomHtml($titleText, $tags);
            $htmlContent = self::wrapInRandomHtml($contentText, $tags);

            $cases["random_html_{$i}"] = [$htmlTitle, $htmlContent];
        }

        // Additional edge cases with specific tag patterns.
        $edgeCases = [
            'script_tag' => [
                '<script>alert("xss")</script>Title',
                '<script type="text/javascript">var x = 1;</script>Content here',
            ],
            'style_tag' => [
                '<style>.red{color:red}</style>Styled Title',
                '<style type="text/css">body{margin:0}</style>Styled Content',
            ],
            'nested_tags' => [
                '<div><p><strong>Nested Title</strong></p></div>',
                '<div><ul><li><a href="http://example.com">Link</a></li></ul></div>',
            ],
            'self_closing_tags' => [
                'Title with<br/>break',
                'Content with<img src="test.jpg" alt="test"/>image',
            ],
            'attributes_with_angles' => [
                '<a href="http://example.com" title="Click > here">Link Title</a>',
                '<div class="container" data-value="<test>">Content</div>',
            ],
            'malformed_html' => [
                '<p>Unclosed paragraph title',
                '<div><span>Unclosed tags content',
            ],
            'html_entities_mixed' => [
                '<p>&amp; Title &lt;not a tag&gt;</p>',
                '<div>&quot;Content&quot; with <em>emphasis</em></div>',
            ],
            'deeply_nested' => [
                '<div><div><div><div><div>Deep Title</div></div></div></div></div>',
                '<table><tbody><tr><td><p><strong>Deep Content</strong></p></td></tr></tbody></table>',
            ],
            'multiple_tags_inline' => [
                '<b>Bold</b> and <i>italic</i> title',
                '<span>First</span><span>Second</span><span>Third</span> content',
            ],
            'html_comments' => [
                '<!-- comment -->Title after comment',
                '<!-- hidden -->Content<!-- another --> here',
            ],
        ];

        return array_merge($cases, $edgeCases);
    }

    /**
     * Generate random text of approximately the given length.
     */
    private static function generateRandomText(int $length): string
    {
        $words = [
            'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur',
            'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor',
            'incididunt', 'labore', 'dolore', 'magna', 'aliqua',
            'enim', 'minim', 'veniam', 'quis', 'nostrud', 'exercitation',
            'ullamco', 'laboris', 'nisi', 'aliquip', 'commodo', 'consequat',
            'duis', 'aute', 'irure', 'reprehenderit', 'voluptate',
            'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur',
        ];

        $text = '';
        while (strlen($text) < $length) {
            $word = $words[mt_rand(0, count($words) - 1)];
            $text .= ($text === '' ? '' : ' ') . $word;
        }

        return substr($text, 0, $length);
    }

    /**
     * Wrap text in one or more random HTML tags.
     */
    private static function wrapInRandomHtml(string $text, array $tags): string
    {
        $wrapCount = mt_rand(1, 3);

        for ($i = 0; $i < $wrapCount; $i++) {
            $tag = $tags[mt_rand(0, count($tags) - 1)];
            $attributes = self::generateRandomAttributes($tag);

            // Self-closing tags get inserted before the text.
            if (in_array($tag, ['img', 'br', 'hr', 'input'], true)) {
                $text = "<{$tag}{$attributes}/>" . $text;
            } else {
                $text = "<{$tag}{$attributes}>{$text}</{$tag}>";
            }
        }

        return $text;
    }

    /**
     * Generate random HTML attributes for a given tag.
     */
    private static function generateRandomAttributes(string $tag): string
    {
        $attributes = '';

        switch ($tag) {
            case 'a':
                $attributes = ' href="http://example.com/page' . mt_rand(1, 100) . '"';
                if (mt_rand(0, 1)) {
                    $attributes .= ' title="Link ' . mt_rand(1, 50) . '"';
                }
                break;
            case 'img':
                $attributes = ' src="image' . mt_rand(1, 50) . '.jpg" alt="Image ' . mt_rand(1, 50) . '"';
                break;
            case 'div':
            case 'span':
            case 'p':
                if (mt_rand(0, 1)) {
                    $attributes = ' class="class-' . mt_rand(1, 20) . '"';
                }
                break;
            case 'input':
                $attributes = ' type="text" value="val' . mt_rand(1, 50) . '"';
                break;
            case 'script':
                if (mt_rand(0, 1)) {
                    $attributes = ' type="text/javascript"';
                }
                break;
            case 'style':
                if (mt_rand(0, 1)) {
                    $attributes = ' type="text/css"';
                }
                break;
        }

        return $attributes;
    }
}
