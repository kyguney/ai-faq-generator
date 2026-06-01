<?php
/**
 * Property-based test for the Prompt_Builder FAQ count clamping.
 *
 * Feature: prompt-builder, Property 3: FAQ Count Clamping
 *
 * For any integer FAQ count value, the number included in the prompt string SHALL equal
 * clamp(faq_count, 1, 20) — values below 1 become 1, values above 20 become 20, and
 * values within [1, 20] are used as-is. When FAQ count is null, the value 5 SHALL be used.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3**
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class PromptBuilderFaqCountPropertyTest extends TestCase
{
    private Prompt_Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Prompt_Builder();
    }

    /**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * Property 3: FAQ Count Clamping.
     * For any integer FAQ count value, the prompt contains "Generate exactly N" where
     * N = max(1, min(20, faq_count)). When null, N = 5.
     */
    #[Test]
    #[DataProvider('faqCountProvider')]
    public function faq_count_is_clamped_to_valid_range(?int $faqCount, int $expectedCount): void
    {
        $result = $this->builder->build('Test', 'Content', $faqCount);

        preg_match('/Generate exactly (\d+)/', $result, $matches);

        $this->assertNotEmpty($matches, 'Prompt must contain "Generate exactly N" pattern');
        $this->assertSame(
            $expectedCount,
            (int) $matches[1],
            sprintf(
                'FAQ count %s should be clamped to %d, got %s',
                $faqCount === null ? 'null' : (string) $faqCount,
                $expectedCount,
                $matches[1]
            )
        );
    }

    /**
     * Data provider generating 100+ random integers from -100 to 100, plus null values.
     *
     * @return array<string, array{?int, int}>
     */
    public static function faqCountProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(12345);

        // Generate 100 random integer cases from -100 to 100.
        for ($i = 0; $i < 100; $i++) {
            $value = mt_rand(-100, 100);
            $expected = max(1, min(20, $value));
            $cases["random_int_{$i}_value_{$value}"] = [$value, $expected];
        }

        // Add null cases (FAQ count defaults to 5).
        $cases['null_value_1'] = [null, 5];
        $cases['null_value_2'] = [null, 5];
        $cases['null_value_3'] = [null, 5];

        // Boundary cases.
        $cases['boundary_min_minus_1'] = [0, 1];
        $cases['boundary_min'] = [1, 1];
        $cases['boundary_max'] = [20, 20];
        $cases['boundary_max_plus_1'] = [21, 20];
        $cases['extreme_negative'] = [-100, 1];
        $cases['extreme_positive'] = [100, 20];
        $cases['mid_range'] = [10, 10];

        return $cases;
    }
}
