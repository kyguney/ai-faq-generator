<?php
/**
 * Property-based test for the Prompt_Builder content truncation invariant.
 *
 * Feature: prompt-builder, Property 2: Content Truncation Invariant
 *
 * For any post content string, after HTML stripping, the content portion included
 * in the prompt string SHALL have a length equal to min(length_of_stripped_content, 2000)
 * characters. Content longer than 2000 characters is cut at exactly the 2000th character
 * position; content at or below 2000 characters is included in full.
 *
 * Validates: Requirements 1.4, 1.5
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class PromptBuilderTruncationPropertyTest extends TestCase
{
    private Prompt_Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Prompt_Builder();
    }

    /**
     * **Validates: Requirements 1.4, 1.5**
     *
     * Property 2: Content Truncation Invariant.
     * For any content string, the content portion in the prompt has length equal to
     * min(stripped_length, 2000).
     */
    #[Test]
    #[DataProvider('randomContentProvider')]
    public function content_portion_length_equals_min_of_stripped_length_and_2000(string $content): void
    {
        $result = $this->builder->build('', $content, 5);

        // Simulate the same sanitization the builder does internally.
        $stripped = trim(strip_tags($content));
        $expectedLength = min(strlen($stripped), 2000);

        if ($stripped === '') {
            // Empty content after stripping means no "Content:" section in output.
            $this->assertStringNotContainsString('Content:', $result);
            return;
        }

        // Extract the content portion from the prompt.
        $contentPrefix = 'Content: ';
        $contentPos = strpos($result, $contentPrefix);
        $this->assertNotFalse(
            $contentPos,
            'Prompt should contain "Content: " prefix for non-empty stripped content'
        );

        $contentStart = $contentPos + strlen($contentPrefix);
        // Content goes to the end of the string (it's the last line).
        $contentPortion = substr($result, $contentStart);

        $this->assertSame(
            $expectedLength,
            strlen($contentPortion),
            sprintf(
                'Content portion length should be min(%d, 2000) = %d, got %d',
                strlen($stripped),
                $expectedLength,
                strlen($contentPortion)
            )
        );
    }

    /**
     * Data provider generating 100+ random strings of varying lengths (0 to 5000 chars).
     *
     * @return array<string, array{string}>
     */
    public static function randomContentProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(42);

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 .,!?-';
        $charsLen = strlen($chars);

        for ($i = 0; $i < 110; $i++) {
            // Generate random length between 0 and 5000.
            $length = mt_rand(0, 5000);
            $str = '';
            for ($j = 0; $j < $length; $j++) {
                $str .= $chars[mt_rand(0, $charsLen - 1)];
            }
            $cases["random_content_length_{$length}_case_{$i}"] = [$str];
        }

        // Additional edge cases.
        $cases['empty_string'] = [''];
        $cases['exactly_2000_chars'] = [str_repeat('a', 2000)];
        $cases['exactly_2001_chars'] = [str_repeat('b', 2001)];
        $cases['exactly_1999_chars'] = [str_repeat('c', 1999)];
        $cases['max_5000_chars'] = [str_repeat('d', 5000)];
        $cases['single_char'] = ['x'];
        $cases['whitespace_only'] = ['   '];
        $cases['html_with_long_text'] = ['<p>' . str_repeat('e', 3000) . '</p>'];
        $cases['html_only_no_text'] = ['<div><span></span></div>'];

        return $cases;
    }
}
