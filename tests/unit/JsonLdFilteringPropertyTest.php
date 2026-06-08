<?php
/**
 * Property-based test for the JSON_LD_Generator service.
 *
 * Feature: faqpage-jsonld-generator, Property 2: Invalid Item Filtering
 * Validates: Requirements 2.6, 3.1, 3.3, 3.4
 *
 * For any FAQ meta array containing a mix of valid and invalid items (missing keys,
 * empty strings, whitespace-only values, non-string values), the `mainEntity` array
 * SHALL contain exactly the valid items — its length equals the count of items where
 * both `trim(question)` and `trim(answer)` are non-empty, and no invalid item appears
 * in the output.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdFilteringPropertyTest extends TestCase
{
    private JSON_LD_Generator $generator;

    protected function setUp(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $afg_test_is_singular = true;
        $afg_test_post_meta_values = [];
        $afg_test_current_post_id = 42;

        $this->generator = new JSON_LD_Generator();
    }

    protected function tearDown(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $afg_test_is_singular = true;
        $afg_test_post_meta_values = [];
        $afg_test_current_post_id = 42;
    }

    /**
     * **Validates: Requirements 2.6, 3.1, 3.3, 3.4**
     *
     * Property 2: Invalid Item Filtering.
     * For any FAQ meta array containing a mix of valid and invalid items,
     * the mainEntity array length equals the count of valid items,
     * and no invalid item appears in the output.
     */
    #[Test]
    #[DataProvider('mixedValidAndInvalidItemsProvider')]
    public function mainEntity_contains_only_valid_items_and_correct_count(
        array $inputItems,
        int $expectedValidCount
    ): void {
        global $afg_test_post_meta_values;

        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = json_encode($inputItems, JSON_UNESCAPED_UNICODE);

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        if ($expectedValidCount === 0) {
            // No valid items — should produce no output at all.
            $this->assertSame('', $output, 'No valid items should produce no script tag output.');
            return;
        }

        // Extract JSON from script tag.
        $this->assertStringContainsString(
            '<script type="application/ld+json">',
            $output,
            'Output should contain a JSON-LD script tag when valid items exist.'
        );

        $json_content = preg_replace(
            '/^<script type="application\/ld\+json">(.+)<\/script>\n?$/s',
            '$1',
            $output
        );

        // Un-escape script tags for JSON parsing.
        $json_content = str_replace('<\\/script', '</script', $json_content);

        $schema = json_decode($json_content, true);
        $this->assertNotNull($schema, 'JSON-LD output should be valid JSON.');
        $this->assertArrayHasKey('mainEntity', $schema);

        // Assert mainEntity length equals count of valid items (Requirement 3.3).
        $this->assertCount(
            $expectedValidCount,
            $schema['mainEntity'],
            sprintf(
                'mainEntity count must equal valid item count. Expected %d, got %d.',
                $expectedValidCount,
                count($schema['mainEntity'])
            )
        );

        // Assert no invalid item appears in output (Requirements 2.6, 3.4).
        // Valid items have non-empty trimmed question and answer strings.
        // Collect the question names from the output to verify no invalid content leaked through.
        foreach ($schema['mainEntity'] as $index => $questionObj) {
            $this->assertNotEmpty(
                trim($questionObj['name'] ?? ''),
                sprintf('Question name at index %d must be non-empty after trim.', $index)
            );
            $this->assertNotEmpty(
                trim($questionObj['acceptedAnswer']['text'] ?? ''),
                sprintf('Answer text at index %d must be non-empty after trim.', $index)
            );
        }
    }

    /**
     * Data provider generating 110+ arrays mixing valid and invalid FAQ items.
     *
     * @return array<string, array{array<int, mixed>, int}>
     */
    public static function mixedValidAndInvalidItemsProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(98765);

        // Generate 110 random mixed arrays.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(2, 10);
            $items = [];
            $validCount = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                // Randomly decide if this item should be valid or invalid.
                $makeValid = (mt_rand(0, 2) === 0); // ~33% valid items

                if ($makeValid) {
                    $items[] = self::generateValidItem();
                    $validCount++;
                } else {
                    $items[] = self::generateInvalidItem();
                }
            }

            $cases["random_mixed_array_{$i}"] = [$items, $validCount];
        }

        // ─── Edge cases ──────────────────────────────────────────────────────

        // All invalid items — no output expected.
        $allInvalid = [
            ['question' => '', 'answer' => 'Valid answer'],
            ['answer' => 'Missing question key'],
            ['question' => 'Missing answer key'],
            ['question' => '   ', 'answer' => 'Whitespace question'],
            ['question' => 'Good question', 'answer' => "\t\n  "],
        ];
        $cases['all_invalid_items'] = [$allInvalid, 0];

        // Single valid item among many invalid.
        $singleValid = [
            ['question' => '', 'answer' => 'A'],
            ['question' => 'Valid Q?', 'answer' => 'Valid A.'],
            null,
            'not an array',
            42,
        ];
        $cases['single_valid_among_invalid'] = [$singleValid, 1];

        // Non-string values for question and answer.
        $nonStringValues = [
            ['question' => 123, 'answer' => 'numeric question'],
            ['question' => 'Good Q', 'answer' => ['array', 'answer']],
            ['question' => null, 'answer' => 'null question'],
            ['question' => true, 'answer' => 'bool question'],
            ['question' => 'Real question?', 'answer' => 'Real answer.'],
        ];
        $cases['non_string_values'] = [$nonStringValues, 1];

        // Non-array items in the input array.
        $nonArrayItems = [
            'just a string',
            42,
            null,
            true,
            ['question' => 'Valid?', 'answer' => 'Yes!'],
            3.14,
        ];
        $cases['non_array_items_mixed'] = [$nonArrayItems, 1];

        // Empty string question with valid answer.
        $emptyQuestion = [
            ['question' => '', 'answer' => 'This answer has no question'],
            ['question' => 'This is valid?', 'answer' => 'Yes it is.'],
        ];
        $cases['empty_string_question'] = [$emptyQuestion, 1];

        // Whitespace-only strings.
        $whitespaceOnly = [
            ['question' => '   ', 'answer' => 'Whitespace question'],
            ['question' => 'Whitespace answer', 'answer' => "\t\n  "],
            ['question' => "  \t ", 'answer' => " \n "],
            ['question' => 'Both valid?', 'answer' => 'Both valid.'],
        ];
        $cases['whitespace_only_strings'] = [$whitespaceOnly, 1];

        // Mix of all invalid types with multiple valid items.
        $comprehensiveMix = [
            ['question' => 'Q1?', 'answer' => 'A1.'],             // valid
            ['question' => '', 'answer' => 'empty q'],             // invalid: empty question
            ['question' => 'Q2?', 'answer' => 'A2.'],             // valid
            ['answer' => 'no question key'],                        // invalid: missing question key
            ['question' => 'no answer key'],                        // invalid: missing answer key
            ['question' => '  ', 'answer' => 'ws q'],              // invalid: whitespace question
            ['question' => 'Q3?', 'answer' => '   '],              // invalid: whitespace answer
            ['question' => 42, 'answer' => 'numeric q'],           // invalid: non-string question
            ['question' => 'Q4?', 'answer' => null],               // invalid: null answer
            ['question' => 'Q5?', 'answer' => 'A5.'],             // valid
        ];
        $cases['comprehensive_mix'] = [$comprehensiveMix, 3];

        return $cases;
    }

    /**
     * Generate a valid FAQ item with non-empty question and answer strings.
     *
     * @return array{question: string, answer: string}
     */
    private static function generateValidItem(): array
    {
        return [
            'question' => self::generateNonEmptyString(mt_rand(5, 80)),
            'answer' => self::generateNonEmptyString(mt_rand(10, 200)),
        ];
    }

    /**
     * Generate an invalid FAQ item using one of several invalid patterns.
     *
     * @return mixed An invalid item (could be array with bad keys, non-array, etc.)
     */
    private static function generateInvalidItem(): mixed
    {
        $invalidType = mt_rand(0, 7);

        return match ($invalidType) {
            // Missing 'question' key.
            0 => ['answer' => self::generateNonEmptyString(mt_rand(5, 50))],
            // Missing 'answer' key.
            1 => ['question' => self::generateNonEmptyString(mt_rand(5, 50))],
            // Empty string question.
            2 => ['question' => '', 'answer' => self::generateNonEmptyString(mt_rand(5, 50))],
            // Empty string answer.
            3 => ['question' => self::generateNonEmptyString(mt_rand(5, 50)), 'answer' => ''],
            // Whitespace-only question.
            4 => ['question' => str_repeat(' ', mt_rand(1, 5)), 'answer' => self::generateNonEmptyString(mt_rand(5, 50))],
            // Whitespace-only answer.
            5 => ['question' => self::generateNonEmptyString(mt_rand(5, 50)), 'answer' => "\t\n" . str_repeat(' ', mt_rand(0, 3))],
            // Non-string question value (int, null, bool, array).
            6 => ['question' => self::generateNonStringValue(), 'answer' => self::generateNonEmptyString(mt_rand(5, 50))],
            // Non-array item (string, int, null, bool).
            7 => self::generateNonArrayValue(),
        };
    }

    /**
     * Generate a random non-string value for testing non-string question/answer fields.
     */
    private static function generateNonStringValue(): mixed
    {
        return match (mt_rand(0, 3)) {
            0 => mt_rand(0, 1000),
            1 => null,
            2 => (bool) mt_rand(0, 1),
            3 => ['nested' => 'array'],
        };
    }

    /**
     * Generate a non-array value for testing non-array items in the input.
     */
    private static function generateNonArrayValue(): mixed
    {
        return match (mt_rand(0, 3)) {
            0 => self::generateNonEmptyString(mt_rand(3, 20)),
            1 => mt_rand(0, 1000),
            2 => null,
            3 => (bool) mt_rand(0, 1),
        };
    }

    /**
     * Generate a random non-empty string that is non-empty after trim.
     */
    private static function generateNonEmptyString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_()';
        $charsLength = strlen($chars);

        // Start with a letter to ensure non-empty after trim.
        $result = $chars[mt_rand(0, 25)];

        for ($i = 1; $i < $length - 1; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        // End with a non-whitespace character.
        if ($length > 1) {
            $result .= $chars[mt_rand(0, 25)];
        }

        return $result;
    }
}
