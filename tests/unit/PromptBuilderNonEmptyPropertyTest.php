<?php
/**
 * Property-based test for the Prompt_Builder service.
 *
 * Feature: prompt-builder, Property 6: Non-Empty Output
 *
 * For any valid inputs (post title as string, post content as string, FAQ count as
 * integer in [1, 20] or null), the Prompt_Builder SHALL return a non-empty string
 * (minimum 1 character).
 *
 * Validates: Requirements 5.2
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class PromptBuilderNonEmptyPropertyTest extends TestCase
{
    private Prompt_Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Prompt_Builder();
    }

    /**
     * **Validates: Requirements 5.2**
     *
     * Property 6: Non-Empty Output.
     * For any valid inputs, build() always returns a non-empty string.
     */
    #[Test]
    #[DataProvider('validInputProvider')]
    public function build_always_returns_non_empty_string(string $title, string $content, ?int $faqCount): void
    {
        $result = $this->builder->build($title, $content, $faqCount);

        $this->assertIsString($result);
        $this->assertNotEmpty($result, 'build() must return a non-empty string for valid inputs');
        $this->assertTrue(strlen($result) > 0, 'build() result must have length > 0');
    }

    /**
     * Data provider generating 100+ random valid inputs including edge cases.
     *
     * Generates combinations of:
     * - Random titles (empty, whitespace-only, HTML-only, normal text)
     * - Random content (empty, whitespace-only, HTML-only, very long, normal text)
     * - Random FAQ counts (null, 1-20, boundary values)
     *
     * @return array<string, array{string, string, int|null}>
     */
    public static function validInputProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(42);

        // Generate 100 random valid input combinations.
        for ($i = 0; $i < 100; $i++) {
            $title = self::generateRandomTitle();
            $content = self::generateRandomContent();
            $faqCount = self::generateRandomFaqCount();

            $cases["random_input_{$i}"] = [$title, $content, $faqCount];
        }

        // Edge cases beyond the 100 minimum.
        $edgeCases = [
            'empty_title_empty_content_null_count' => ['', '', null],
            'empty_title_empty_content_min_count' => ['', '', 1],
            'empty_title_empty_content_max_count' => ['', '', 20],
            'empty_title_normal_content_null_count' => ['', 'Some content here', null],
            'normal_title_empty_content_null_count' => ['My Title', '', null],
            'whitespace_title_whitespace_content' => ['   ', "  \t\n  ", null],
            'html_only_title_html_only_content' => ['<div></div>', '<p><span></span></p>', 5],
            'html_title_with_text' => ['<h1>Hello World</h1>', '<p>Content here</p>', 10],
            'very_long_content' => ['Title', str_repeat('a', 5000), 3],
            'single_char_title' => ['X', 'Y', 1],
            'unicode_content' => ['Başlık', 'İçerik metni burada', 7],
            'special_chars_title' => ['Title & "quotes" <angles>', 'Content with <b>bold</b>', 15],
            'newlines_in_content' => ["Title\nWith\nNewlines", "Content\nWith\nNewlines", 20],
            'tabs_in_content' => ["Title\tWith\tTabs", "Content\tWith\tTabs", null],
            'mixed_whitespace_html' => ['  <br>  ', '  <hr>  ', 5],
            'numeric_title' => ['12345', '67890', 10],
            'boundary_count_1' => ['Title', 'Content', 1],
            'boundary_count_20' => ['Title', 'Content', 20],
            'mid_count' => ['Title', 'Content', 10],
            'null_count_with_content' => ['Title', 'Content', null],
        ];

        return array_merge($cases, $edgeCases);
    }

    /**
     * Generate a random title string including edge cases.
     */
    private static function generateRandomTitle(): string
    {
        $type = mt_rand(0, 5);

        return match ($type) {
            0 => '',                                          // empty
            1 => str_repeat(' ', mt_rand(1, 10)),            // whitespace-only
            2 => self::generateRandomHtmlOnly(),              // HTML-only
            3 => self::generateRandomText(mt_rand(1, 100)),  // normal text
            4 => '<h1>' . self::generateRandomText(mt_rand(5, 50)) . '</h1>', // HTML with text
            5 => self::generateRandomText(mt_rand(1, 20)) . ' & "special" <chars>',
            default => 'Fallback Title',
        };
    }

    /**
     * Generate a random content string including edge cases.
     */
    private static function generateRandomContent(): string
    {
        $type = mt_rand(0, 6);

        return match ($type) {
            0 => '',                                            // empty
            1 => str_repeat(' ', mt_rand(1, 20)),              // whitespace-only
            2 => self::generateRandomHtmlOnly(),                // HTML-only
            3 => self::generateRandomText(mt_rand(1, 500)),    // normal text
            4 => self::generateRandomText(mt_rand(2001, 5000)), // very long text
            5 => '<p>' . self::generateRandomText(mt_rand(10, 200)) . '</p>', // HTML with text
            6 => "\t\n" . str_repeat(' ', mt_rand(1, 5)),      // mixed whitespace
            default => 'Fallback content',
        };
    }

    /**
     * Generate a random valid FAQ count (null or 1-20).
     */
    private static function generateRandomFaqCount(): ?int
    {
        $type = mt_rand(0, 3);

        return match ($type) {
            0 => null,
            1 => 1,                    // minimum boundary
            2 => 20,                   // maximum boundary
            3 => mt_rand(1, 20),       // random valid value
            default => null,
        };
    }

    /**
     * Generate a random HTML-only string (no text content).
     */
    private static function generateRandomHtmlOnly(): string
    {
        $tags = ['<div></div>', '<span></span>', '<p></p>', '<br>', '<hr>', '<img src="x">'];
        $count = mt_rand(1, 4);
        $html = '';

        for ($i = 0; $i < $count; $i++) {
            $html .= $tags[mt_rand(0, count($tags) - 1)];
        }

        return $html;
    }

    /**
     * Generate a random text string of specified length.
     */
    private static function generateRandomText(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 .,!?-';
        $text = '';

        for ($i = 0; $i < $length; $i++) {
            $text .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $text;
    }
}
