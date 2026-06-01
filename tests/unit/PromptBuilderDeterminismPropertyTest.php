<?php
/**
 * Property-based test for the Prompt_Builder service.
 *
 * Feature: prompt-builder, Property 5: Deterministic Output
 * Validates: Requirements 5.1, 5.4
 *
 * For any combination of post title, post content, and FAQ count, calling build()
 * multiple times with the same arguments SHALL return a byte-for-byte identical
 * prompt string on every invocation, regardless of call order or number of prior invocations.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class PromptBuilderDeterminismPropertyTest extends TestCase
{
    private Prompt_Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Prompt_Builder();
    }

    /**
     * **Validates: Requirements 5.1, 5.4**
     *
     * Property 5: Deterministic Output.
     * For any combination of post title, post content, and FAQ count,
     * calling build() twice with the same arguments returns byte-for-byte identical output.
     */
    #[Test]
    #[DataProvider('deterministicInputProvider')]
    public function build_returns_identical_output_for_same_inputs(
        string $title,
        string $content,
        ?int $faqCount
    ): void {
        $result1 = $this->builder->build($title, $content, $faqCount);
        $result2 = $this->builder->build($title, $content, $faqCount);

        $this->assertSame(
            $result1,
            $result2,
            sprintf(
                'build() must return byte-for-byte identical output for same inputs. '
                . 'Title: "%s", Content length: %d, FAQ count: %s',
                mb_substr($title, 0, 50),
                strlen($content),
                $faqCount === null ? 'null' : (string) $faqCount
            )
        );
    }

    /**
     * Data provider generating 100+ random title/content/count combinations.
     *
     * @return array<string, array{string, string, int|null}>
     */
    public static function deterministicInputProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(12345);

        // Generate 100 random combinations.
        for ($i = 0; $i < 100; $i++) {
            $title = self::generateRandomString(mt_rand(0, 200));
            $content = self::generateRandomString(mt_rand(0, 3000));
            $faqCount = self::generateRandomFaqCount();

            $cases["random_combination_{$i}"] = [$title, $content, $faqCount];
        }

        // Edge cases: empty strings.
        $cases['empty_title_and_content'] = ['', '', null];
        $cases['empty_title_with_content'] = ['', 'Some content here', 5];
        $cases['empty_content_with_title'] = ['A Title', '', 10];
        $cases['both_empty_with_null_count'] = ['', '', null];
        $cases['both_empty_with_count_1'] = ['', '', 1];
        $cases['both_empty_with_count_20'] = ['', '', 20];

        // Edge cases: very long strings.
        $cases['very_long_title'] = [str_repeat('A', 500), 'Short content', 5];
        $cases['very_long_content'] = ['Short title', str_repeat('B', 5000), 10];
        $cases['both_very_long'] = [str_repeat('C', 500), str_repeat('D', 5000), 15];

        // Edge cases: null count.
        $cases['null_count_with_normal_inputs'] = ['My Title', 'My content', null];
        $cases['null_count_with_empty_title'] = ['', 'Content only', null];
        $cases['null_count_with_empty_content'] = ['Title only', '', null];

        // Edge cases: boundary counts.
        $cases['count_zero_clamped'] = ['Title', 'Content', 0];
        $cases['count_one_minimum'] = ['Title', 'Content', 1];
        $cases['count_twenty_maximum'] = ['Title', 'Content', 20];
        $cases['count_twenty_one_clamped'] = ['Title', 'Content', 21];

        // Edge cases: special characters.
        $cases['special_chars_in_title'] = ['Title with "quotes" & <tags>', 'Normal content', 5];
        $cases['special_chars_in_content'] = ['Normal title', 'Content with "quotes" & <tags> and \n newlines', 5];
        $cases['unicode_content'] = ['Ünïcödé Tïtlé', 'Cöntënt wïth spëcïal chàrs', 5];
        $cases['newlines_and_tabs'] = ["Title\nwith\nnewlines", "Content\twith\ttabs", 5];

        // Edge cases: whitespace-only strings.
        $cases['whitespace_title'] = ['   ', 'Content', 5];
        $cases['whitespace_content'] = ['Title', "  \t\n  ", 5];
        $cases['both_whitespace'] = ['   ', "  \t\n  ", null];

        // Edge cases: HTML content.
        $cases['html_in_title'] = ['<h1>Title</h1>', 'Content', 5];
        $cases['html_in_content'] = ['Title', '<p>Paragraph</p><div>Block</div>', 5];
        $cases['html_only_no_text'] = ['<span></span>', '<div><br></div>', 5];

        return $cases;
    }

    /**
     * Generate a random string of specified length.
     */
    private static function generateRandomString(int $length): string
    {
        if ($length === 0) {
            return '';
        }

        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_\'"()[]{}@#$%&*';
        $charsLength = strlen($chars);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        return $result;
    }

    /**
     * Generate a random FAQ count value including null and boundary values.
     */
    private static function generateRandomFaqCount(): ?int
    {
        $choice = mt_rand(0, 10);

        return match (true) {
            $choice === 0 => null,
            $choice === 1 => 0,
            $choice === 2 => 1,
            $choice === 3 => 20,
            $choice === 4 => 21,
            default => mt_rand(-10, 30),
        };
    }
}
