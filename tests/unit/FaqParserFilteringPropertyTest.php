<?php
/**
 * Property-based test for the Faq_Parser service.
 *
 * Feature: faq-response-parser, Property 3: Invalid Items Filtered, Valid Items Preserved in Order
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.7
 *
 * For any JSON array containing a mix of valid FAQ items and invalid entries
 * (missing keys, non-string values, whitespace-only values, scalar entries, nested arrays),
 * parse() SHALL return only the valid items in their original relative order,
 * with sequential zero-based integer keys.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;

class FaqParserFilteringPropertyTest extends TestCase
{
    private Faq_Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Faq_Parser();
    }

    /**
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.7**
     *
     * Property 3: Invalid Items Filtered, Valid Items Preserved in Order.
     * For any JSON array containing a mix of valid FAQ items and invalid entries,
     * parse() returns only the valid items in their original relative order,
     * with sequential zero-based integer keys.
     */
    #[Test]
    #[DataProvider('mixedItemsProvider')]
    public function parse_filters_invalid_items_and_preserves_valid_items_in_order(
        string $json,
        array $expectedItems
    ): void {
        $result = $this->parser->parse($json);

        $this->assertSame(
            $expectedItems,
            $result,
            'parse() must return only valid items in original relative order with zero-based keys.'
        );

        // Verify zero-based sequential keys
        if (count($result) > 0) {
            $this->assertSame(
                range(0, count($result) - 1),
                array_keys($result),
                'Result keys must be zero-based and sequential.'
            );
        }
    }

    /**
     * Data provider generating 110+ random arrays mixing valid FAQ items with invalid entries.
     *
     * @return array<string, array{string, array<int, array{question: string, answer: string}>}>
     */
    public static function mixedItemsProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(54321);

        // Generate 110 random mixed arrays.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(2, 10);
            $items = [];
            $expectedValid = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $choice = mt_rand(0, 10);

                if ($choice <= 3) {
                    // Generate a valid item (roughly 36% chance).
                    $question = self::generateNonEmptyString(mt_rand(3, 80));
                    $answer = self::generateNonEmptyString(mt_rand(3, 200));
                    $item = ['question' => $question, 'answer' => $answer];

                    // Optionally add extra keys (valid items with extra keys are still valid).
                    if (mt_rand(0, 2) === 0) {
                        $item['extra_key'] = 'extra_value_' . $j;
                    }

                    $items[] = $item;
                    $expectedValid[] = ['question' => trim($question), 'answer' => trim($answer)];
                } else {
                    // Generate an invalid item.
                    $items[] = self::generateInvalidItem($choice);
                }
            }

            $json = json_encode($items, JSON_UNESCAPED_UNICODE);
            $cases["random_mixed_{$i}"] = [$json, $expectedValid];
        }

        // Edge case: all items invalid.
        $allInvalid = [
            ['question' => 123, 'answer' => 'valid answer'],
            ['answer' => 'no question key'],
            'scalar_string',
            42,
            null,
            ['question' => '', 'answer' => 'empty question'],
        ];
        $cases['all_items_invalid'] = [json_encode($allInvalid), []];

        // Edge case: single valid item among many invalid.
        $singleValid = [
            ['question' => null, 'answer' => 'invalid'],
            ['question' => 'Valid Q?', 'answer' => 'Valid A.'],
            ['question' => '   ', 'answer' => 'whitespace question'],
            true,
        ];
        $cases['single_valid_among_invalid'] = [
            json_encode($singleValid),
            [['question' => 'Valid Q?', 'answer' => 'Valid A.']],
        ];

        // Edge case: valid items with extra keys are preserved (only question/answer returned).
        $extraKeys = [
            ['question' => 'Q1', 'answer' => 'A1', 'id' => 1, 'category' => 'general'],
            ['question' => 'Q2', 'answer' => 'A2', 'metadata' => ['nested' => true]],
        ];
        $cases['valid_items_with_extra_keys'] = [
            json_encode($extraKeys),
            [['question' => 'Q1', 'answer' => 'A1'], ['question' => 'Q2', 'answer' => 'A2']],
        ];

        // Edge case: nested indexed arrays as entries.
        $nestedArrays = [
            [['nested', 'array']],
            ['question' => 'Valid?', 'answer' => 'Yes'],
            [[1, 2, 3]],
        ];
        $cases['nested_indexed_arrays_filtered'] = [
            json_encode($nestedArrays),
            [['question' => 'Valid?', 'answer' => 'Yes']],
        ];

        // Edge case: scalar entries mixed with valid items.
        $scalarMix = [
            'just a string',
            42,
            true,
            false,
            null,
            ['question' => 'First Q', 'answer' => 'First A'],
            3.14,
            ['question' => 'Second Q', 'answer' => 'Second A'],
        ];
        $cases['scalars_mixed_with_valid'] = [
            json_encode($scalarMix),
            [
                ['question' => 'First Q', 'answer' => 'First A'],
                ['question' => 'Second Q', 'answer' => 'Second A'],
            ],
        ];

        // Edge case: items with non-string question/answer values.
        $nonStringValues = [
            ['question' => 123, 'answer' => 'string answer'],
            ['question' => 'string question', 'answer' => 456],
            ['question' => true, 'answer' => false],
            ['question' => null, 'answer' => null],
            ['question' => ['array'], 'answer' => 'string'],
            ['question' => 'Valid Q', 'answer' => 'Valid A'],
        ];
        $cases['non_string_values_filtered'] = [
            json_encode($nonStringValues),
            [['question' => 'Valid Q', 'answer' => 'Valid A']],
        ];

        // Edge case: whitespace-only strings are invalid.
        $whitespaceOnly = [
            ['question' => '   ', 'answer' => 'valid answer'],
            ['question' => "\t\n", 'answer' => 'valid answer'],
            ['question' => 'valid question', 'answer' => '   '],
            ['question' => 'valid question', 'answer' => "\r\n"],
            ['question' => 'Real Q', 'answer' => 'Real A'],
        ];
        $cases['whitespace_only_values_filtered'] = [
            json_encode($whitespaceOnly),
            [['question' => 'Real Q', 'answer' => 'Real A']],
        ];

        // Edge case: empty string values are invalid.
        $emptyStrings = [
            ['question' => '', 'answer' => 'valid answer'],
            ['question' => 'valid question', 'answer' => ''],
            ['question' => '', 'answer' => ''],
            ['question' => 'Good Q', 'answer' => 'Good A'],
        ];
        $cases['empty_string_values_filtered'] = [
            json_encode($emptyStrings),
            [['question' => 'Good Q', 'answer' => 'Good A']],
        ];

        // Edge case: order preservation with alternating valid/invalid.
        $alternating = [
            ['question' => 'First', 'answer' => 'A1'],
            'invalid_scalar',
            ['question' => 'Second', 'answer' => 'A2'],
            null,
            ['question' => 'Third', 'answer' => 'A3'],
            ['answer' => 'missing question'],
            ['question' => 'Fourth', 'answer' => 'A4'],
        ];
        $cases['alternating_valid_invalid_preserves_order'] = [
            json_encode($alternating),
            [
                ['question' => 'First', 'answer' => 'A1'],
                ['question' => 'Second', 'answer' => 'A2'],
                ['question' => 'Third', 'answer' => 'A3'],
                ['question' => 'Fourth', 'answer' => 'A4'],
            ],
        ];

        // Edge case: missing question key.
        $missingQuestion = [
            ['answer' => 'Only answer here'],
            ['question' => 'Has both', 'answer' => 'Complete item'],
        ];
        $cases['missing_question_key_filtered'] = [
            json_encode($missingQuestion),
            [['question' => 'Has both', 'answer' => 'Complete item']],
        ];

        // Edge case: missing answer key.
        $missingAnswer = [
            ['question' => 'Only question here'],
            ['question' => 'Has both', 'answer' => 'Complete item'],
        ];
        $cases['missing_answer_key_filtered'] = [
            json_encode($missingAnswer),
            [['question' => 'Has both', 'answer' => 'Complete item']],
        ];

        return $cases;
    }

    /**
     * Generate a non-empty random string (no leading/trailing whitespace).
     */
    private static function generateNonEmptyString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_';
        $charsLength = strlen($chars);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        // Ensure first and last characters are not whitespace.
        $alphaChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $alphaLen = strlen($alphaChars);
        $result[0] = $alphaChars[mt_rand(0, $alphaLen - 1)];
        $result[$length - 1] = $alphaChars[mt_rand(0, $alphaLen - 1)];

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
            4 => ['answer' => self::generateNonEmptyString(mt_rand(3, 50))], // Missing question key
            5 => ['question' => self::generateNonEmptyString(mt_rand(3, 50))], // Missing answer key
            6 => ['question' => mt_rand(1, 1000), 'answer' => self::generateNonEmptyString(mt_rand(3, 50))], // Non-string question
            7 => ['question' => self::generateNonEmptyString(mt_rand(3, 50)), 'answer' => mt_rand(1, 1000)], // Non-string answer
            8 => 'scalar_string_' . mt_rand(1, 100), // Scalar string entry
            9 => mt_rand(1, 1000), // Scalar integer entry
            10 => [mt_rand(1, 10), mt_rand(1, 10), mt_rand(1, 10)], // Nested indexed array
            default => ['question' => '   ', 'answer' => self::generateNonEmptyString(mt_rand(3, 50))], // Whitespace-only question
        };
    }
}
