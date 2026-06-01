<?php
/**
 * Property-based test for the Prompt_Builder service.
 *
 * Feature: prompt-builder, Property 7: Content Inclusion
 *
 * For any non-empty post title and non-empty post content (after sanitization),
 * the prompt string SHALL contain both the sanitized title text and the sanitized
 * (possibly truncated) content text.
 *
 * **Validates: Requirements 1.1**
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class PromptBuilderInclusionPropertyTest extends TestCase
{
    private Prompt_Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Prompt_Builder();
    }

    /**
     * **Validates: Requirements 1.1**
     *
     * Property 7: Content Inclusion.
     * For any non-empty title and non-empty content (plain text), the prompt
     * SHALL contain both the sanitized title and the sanitized (possibly truncated) content.
     */
    #[Test]
    #[DataProvider('nonEmptyTitleContentProvider')]
    public function prompt_contains_sanitized_title_and_content(string $title, string $content): void
    {
        $result = $this->builder->build($title, $content, 5);

        // The title should appear in the prompt as-is (plain text, no HTML).
        $this->assertStringContainsString(
            $title,
            $result,
            "Prompt must contain the sanitized title: \"{$title}\""
        );

        // For content longer than 2000 chars, only the first 2000 chars are included.
        if (strlen($content) > 2000) {
            $expectedContent = substr($content, 0, 2000);
        } else {
            $expectedContent = $content;
        }

        $this->assertStringContainsString(
            $expectedContent,
            $result,
            'Prompt must contain the sanitized (possibly truncated) content'
        );
    }

    /**
     * Data provider generating 100+ random non-empty title/content pairs.
     *
     * Titles and content are plain text (no HTML) to ensure they remain
     * non-empty after sanitization.
     *
     * @return array<string, array{string, string}>
     */
    public static function nonEmptyTitleContentProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(77);

        for ($i = 0; $i < 100; $i++) {
            $title = self::generateRandomPlainText(mt_rand(3, 80));
            $content = self::generateRandomPlainText(mt_rand(10, 300));
            $cases["random_pair_{$i}"] = [$title, $content];
        }

        // Additional cases with long content (exceeding 2000 char limit).
        for ($i = 0; $i < 5; $i++) {
            $title = self::generateRandomPlainText(mt_rand(5, 50));
            $content = self::generateRandomPlainText(mt_rand(2001, 3000));
            $cases["long_content_{$i}"] = [$title, $content];
        }

        // Edge cases with minimal non-empty strings.
        $cases['single_char_title_and_content'] = ['A', 'B'];
        $cases['short_title_long_content'] = ['Hi', str_repeat('x', 2500)];
        $cases['long_title_short_content'] = [str_repeat('T', 80), 'Short content here.'];
        $cases['exactly_2000_char_content'] = ['Title', str_repeat('c', 2000)];
        $cases['exactly_2001_char_content'] = ['Title', str_repeat('d', 2001)];

        return $cases;
    }

    /**
     * Generate a random plain text string of the given length.
     *
     * Uses only alphanumeric characters and spaces to ensure the string
     * remains non-empty after sanitization (no HTML, no whitespace-only).
     */
    private static function generateRandomPlainText(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 ';
        $charsLen = strlen($chars);
        $result = '';

        // Ensure first character is not a space (to avoid trim removing it).
        $nonSpace = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result .= $nonSpace[mt_rand(0, strlen($nonSpace) - 1)];

        for ($i = 1; $i < $length - 1; $i++) {
            $result .= $chars[mt_rand(0, $charsLen - 1)];
        }

        // Ensure last character is not a space (to avoid trim removing it).
        if ($length > 1) {
            $result .= $nonSpace[mt_rand(0, strlen($nonSpace) - 1)];
        }

        return $result;
    }
}
