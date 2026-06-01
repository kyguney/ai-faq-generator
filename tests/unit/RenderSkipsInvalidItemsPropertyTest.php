<?php
/**
 * Property-based test for the FAQ Accordion block render callback.
 *
 * Feature: faq-accordion-block, Property 6: Invalid Items Are Skipped in Render
 * Validates: Requirements 2.5, 5.7, 7.5
 *
 * For any array containing a mix of valid FAQ items (non-empty question and answer)
 * and invalid items (missing keys, empty question, empty answer, non-array entries),
 * the render callback SHALL output HTML containing only the valid items,
 * skipping all invalid ones without producing errors.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// Provide WordPress function stubs needed by render.php if not already defined.
if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $data): string
    {
        // Simplified stub: strip <script> and <style> tags, allow safe HTML.
        $data = preg_replace('#<script[^>]*>.*?</script>#is', '', $data);
        $data = preg_replace('#<style[^>]*>.*?</style>#is', '', $data);
        return $data;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Load the render function.
require_once dirname(__DIR__, 2) . '/blocks/faq-accordion/render.php';

use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\render_faq_accordion_block;

class RenderSkipsInvalidItemsPropertyTest extends TestCase
{
    /**
     * **Validates: Requirements 2.5, 5.7, 7.5**
     *
     * Property 6: Invalid Items Are Skipped in Render.
     * For any array containing a mix of valid FAQ items and invalid items,
     * the render callback outputs HTML containing only the valid items,
     * skipping all invalid ones without producing PHP errors.
     */
    #[Test]
    #[DataProvider('mixedValidAndInvalidItemsProvider')]
    public function render_skips_invalid_items_and_renders_only_valid_ones(
        array $items,
        array $expectedValidItems
    ): void {
        // Set up error tracking to ensure no PHP errors/warnings are produced.
        $errors = [];
        set_error_handler(function (int $errno, string $errstr) use (&$errors): bool {
            $errors[] = ['errno' => $errno, 'errstr' => $errstr];
            return true;
        });

        try {
            $output = render_faq_accordion_block(['items' => $items]);
        } finally {
            restore_error_handler();
        }

        // No PHP errors or warnings should be produced.
        $this->assertEmpty(
            $errors,
            'render_faq_accordion_block() must not produce PHP errors/warnings. Got: '
            . json_encode($errors)
        );

        // Verify output is a string.
        $this->assertIsString($output);

        if (empty($expectedValidItems)) {
            // If no valid items, output should be the wrapper div only (no <details> elements).
            $this->assertStringNotContainsString(
                '<details',
                $output,
                'Output must not contain <details> elements when no valid items exist.'
            );
            return;
        }

        // Verify each valid item appears in the output.
        foreach ($expectedValidItems as $validItem) {
            $this->assertStringContainsString(
                $validItem['question'],
                $output,
                "Valid question '{$validItem['question']}' must appear in the rendered output."
            );
            $this->assertStringContainsString(
                $validItem['answer'],
                $output,
                "Valid answer '{$validItem['answer']}' must appear in the rendered output."
            );
        }

        // Count the number of <details> elements — must match valid item count.
        $detailsCount = substr_count($output, '<details class="faq-accordion-item">');
        $this->assertSame(
            count($expectedValidItems),
            $detailsCount,
            'Number of rendered <details> elements must equal the number of valid items.'
        );

        // Verify invalid items do NOT appear in the output.
        foreach ($items as $item) {
            if (!is_array($item)) {
                // Non-array entries should not appear as rendered content.
                if (is_string($item) && !empty(trim($item))) {
                    // Only check if the string is not a substring of a valid item.
                    $isPartOfValid = false;
                    foreach ($expectedValidItems as $validItem) {
                        if (
                            str_contains($validItem['question'], $item) ||
                            str_contains($validItem['answer'], $item)
                        ) {
                            $isPartOfValid = true;
                            break;
                        }
                    }
                    if (!$isPartOfValid) {
                        $this->assertStringNotContainsString(
                            (string) $item,
                            $output,
                            "Non-array scalar entry '{$item}' must not appear in rendered output."
                        );
                    }
                }
                continue;
            }

            // Check if this item is invalid (not in expectedValidItems).
            $isValid = false;
            foreach ($expectedValidItems as $validItem) {
                if (
                    isset($item['question']) && isset($item['answer']) &&
                    is_string($item['question']) && is_string($item['answer']) &&
                    $item['question'] === $validItem['question'] &&
                    $item['answer'] === $validItem['answer']
                ) {
                    $isValid = true;
                    break;
                }
            }

            if (!$isValid && is_array($item)) {
                // For invalid items with string question/answer values, verify they don't appear.
                if (isset($item['question']) && is_string($item['question']) && !empty(trim($item['question']))) {
                    $questionInValid = false;
                    foreach ($expectedValidItems as $validItem) {
                        if (str_contains($validItem['question'], $item['question'])) {
                            $questionInValid = true;
                            break;
                        }
                    }
                    if (!$questionInValid) {
                        $this->assertStringNotContainsString(
                            $item['question'],
                            $output,
                            "Invalid item question '{$item['question']}' must not appear in rendered output."
                        );
                    }
                }
            }
        }
    }

    /**
     * Data provider generating 110+ arrays with a mix of valid and invalid FAQ items.
     *
     * @return array<string, array{array<mixed>, array<int, array{question: string, answer: string}>}>
     */
    public static function mixedValidAndInvalidItemsProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(98765);

        // Generate 110 random mixed arrays.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(2, 12);
            $items = [];
            $expectedValid = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $choice = mt_rand(0, 12);

                if ($choice <= 4) {
                    // Generate a valid item (~38% chance).
                    $question = self::generateNonEmptyString(mt_rand(5, 60));
                    $answer = self::generateNonEmptyString(mt_rand(5, 100));
                    $items[] = ['question' => $question, 'answer' => $answer];
                    $expectedValid[] = ['question' => $question, 'answer' => $answer];
                } else {
                    // Generate an invalid item.
                    $items[] = self::generateInvalidItem($choice);
                }
            }

            $cases["random_mixed_{$i}"] = [$items, $expectedValid];
        }

        // Edge case: all items invalid.
        $cases['all_items_invalid'] = [
            [
                ['question' => '', 'answer' => 'valid answer'],
                ['answer' => 'no question key'],
                'scalar_string',
                42,
                null,
                ['question' => 'has question', 'answer' => ''],
                ['question' => '', 'answer' => ''],
            ],
            [],
        ];

        // Edge case: single valid item among many invalid.
        $cases['single_valid_among_invalid'] = [
            [
                null,
                ['question' => '', 'answer' => 'empty question'],
                ['question' => 'Only Valid Q', 'answer' => 'Only Valid A'],
                42,
                'string_entry',
                ['answer' => 'missing question'],
            ],
            [['question' => 'Only Valid Q', 'answer' => 'Only Valid A']],
        ];

        // Edge case: all items valid.
        $cases['all_items_valid'] = [
            [
                ['question' => 'Q1', 'answer' => 'A1'],
                ['question' => 'Q2', 'answer' => 'A2'],
                ['question' => 'Q3', 'answer' => 'A3'],
            ],
            [
                ['question' => 'Q1', 'answer' => 'A1'],
                ['question' => 'Q2', 'answer' => 'A2'],
                ['question' => 'Q3', 'answer' => 'A3'],
            ],
        ];

        // Edge case: non-array entries (scalars, null, booleans).
        $cases['non_array_entries_skipped'] = [
            [
                'just a string',
                42,
                true,
                false,
                null,
                3.14,
                ['question' => 'Valid Q', 'answer' => 'Valid A'],
            ],
            [['question' => 'Valid Q', 'answer' => 'Valid A']],
        ];

        // Edge case: items with missing question key.
        $cases['missing_question_key'] = [
            [
                ['answer' => 'Only answer here'],
                ['question' => 'Has both', 'answer' => 'Complete item'],
                ['answer' => 'Another without question'],
            ],
            [['question' => 'Has both', 'answer' => 'Complete item']],
        ];

        // Edge case: items with missing answer key.
        $cases['missing_answer_key'] = [
            [
                ['question' => 'Only question here'],
                ['question' => 'Has both', 'answer' => 'Complete item'],
                ['question' => 'Another without answer'],
            ],
            [['question' => 'Has both', 'answer' => 'Complete item']],
        ];

        // Edge case: items with empty string values.
        $cases['empty_string_values'] = [
            [
                ['question' => '', 'answer' => 'valid answer'],
                ['question' => 'valid question', 'answer' => ''],
                ['question' => '', 'answer' => ''],
                ['question' => 'Good Q', 'answer' => 'Good A'],
            ],
            [['question' => 'Good Q', 'answer' => 'Good A']],
        ];

        // Edge case: items with null values for question/answer.
        $cases['null_values_for_keys'] = [
            [
                ['question' => null, 'answer' => 'valid answer'],
                ['question' => 'valid question', 'answer' => null],
                ['question' => null, 'answer' => null],
                ['question' => 'Real Q', 'answer' => 'Real A'],
            ],
            [['question' => 'Real Q', 'answer' => 'Real A']],
        ];

        // Edge case: items with numeric values for question/answer.
        $cases['numeric_values_for_keys'] = [
            [
                ['question' => 123, 'answer' => 'valid answer'],
                ['question' => 'valid question', 'answer' => 456],
                ['question' => 0, 'answer' => 0],
                ['question' => 'Num Q', 'answer' => 'Num A'],
            ],
            [['question' => 'Num Q', 'answer' => 'Num A']],
        ];

        // Edge case: items with array values for question/answer.
        $cases['array_values_for_keys'] = [
            [
                ['question' => ['nested'], 'answer' => 'valid answer'],
                ['question' => 'valid question', 'answer' => ['nested']],
                ['question' => 'Array Q', 'answer' => 'Array A'],
            ],
            [['question' => 'Array Q', 'answer' => 'Array A']],
        ];

        // Edge case: items with boolean values.
        $cases['boolean_values_for_keys'] = [
            [
                ['question' => true, 'answer' => 'valid answer'],
                ['question' => 'valid question', 'answer' => false],
                ['question' => 'Bool Q', 'answer' => 'Bool A'],
            ],
            [['question' => 'Bool Q', 'answer' => 'Bool A']],
        ];

        // Edge case: alternating valid and invalid items preserves order.
        $cases['alternating_preserves_order'] = [
            [
                ['question' => 'First', 'answer' => 'A1'],
                'invalid_scalar',
                ['question' => 'Second', 'answer' => 'A2'],
                null,
                ['question' => 'Third', 'answer' => 'A3'],
                ['answer' => 'missing question'],
                ['question' => 'Fourth', 'answer' => 'A4'],
            ],
            [
                ['question' => 'First', 'answer' => 'A1'],
                ['question' => 'Second', 'answer' => 'A2'],
                ['question' => 'Third', 'answer' => 'A3'],
                ['question' => 'Fourth', 'answer' => 'A4'],
            ],
        ];

        return $cases;
    }

    /**
     * Generate a non-empty random string without leading/trailing whitespace.
     */
    private static function generateNonEmptyString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $charsLength = strlen($chars);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        return $result;
    }

    /**
     * Generate an invalid FAQ item based on the choice value.
     *
     * @return mixed An invalid entry (not a valid FAQ item).
     */
    private static function generateInvalidItem(int $choice): mixed
    {
        return match ($choice) {
            5  => ['answer' => self::generateNonEmptyString(mt_rand(5, 40))],                          // Missing question key
            6  => ['question' => self::generateNonEmptyString(mt_rand(5, 40))],                        // Missing answer key
            7  => ['question' => '', 'answer' => self::generateNonEmptyString(mt_rand(5, 40))],        // Empty question
            8  => ['question' => self::generateNonEmptyString(mt_rand(5, 40)), 'answer' => ''],        // Empty answer
            9  => 'scalar_string_' . mt_rand(1, 1000),                                                 // Scalar string
            10 => mt_rand(1, 1000),                                                                     // Scalar integer
            11 => null,                                                                                  // Null entry
            12 => ['question' => null, 'answer' => self::generateNonEmptyString(mt_rand(5, 40))],      // Null question value
            default => ['question' => mt_rand(1, 100), 'answer' => self::generateNonEmptyString(mt_rand(5, 40))], // Non-string question
        };
    }
}
