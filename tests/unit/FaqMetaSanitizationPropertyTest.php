<?php
/**
 * Property-based test for FAQ meta sanitization round-trip.
 *
 * Feature: generate-button, Property 3: FAQ meta sanitization round-trip
 * Validates: Requirements 6.2
 *
 * For any valid JSON string representing an array of objects where each object
 * contains a "question" key and an "answer" key with non-empty string values,
 * the sanitize callback SHALL return the value unchanged.
 *
 * For any string that is not valid JSON, or valid JSON that does not represent
 * an array of {question, answer} objects, the sanitize callback SHALL return
 * an empty string.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Loader;

class FaqMetaSanitizationPropertyTest extends TestCase
{
    private Loader $loader;

    protected function setUp(): void
    {
        $this->loader = new Loader();
    }

    /**
     * **Validates: Requirements 6.2**
     *
     * Property 3: Valid FAQ JSON passes through unchanged.
     * For any valid JSON string representing an array of {question, answer} objects,
     * the sanitize callback returns the value unchanged.
     */
    #[Test]
    #[DataProvider('validFaqJsonProvider')]
    public function valid_faq_json_passes_through_unchanged(string $json): void
    {
        $result = $this->loader->sanitize_faq_meta($json);

        $this->assertSame(
            $json,
            $result,
            'Valid FAQ JSON should pass through the sanitize callback unchanged.'
        );
    }

    /**
     * **Validates: Requirements 6.2**
     *
     * Property 3: Invalid or non-conforming input returns empty string.
     * For any string that is not valid JSON, or valid JSON that does not represent
     * an array of {question, answer} objects, the sanitize callback returns empty string.
     */
    #[Test]
    #[DataProvider('invalidFaqJsonProvider')]
    public function invalid_faq_json_returns_empty_string(string $input): void
    {
        $result = $this->loader->sanitize_faq_meta($input);

        $this->assertSame(
            '',
            $result,
            "Invalid or non-conforming input should return empty string. Input: {$input}"
        );
    }

    /**
     * Data provider generating 100+ valid FAQ JSON strings.
     *
     * Each is a JSON-encoded array of objects with "question" and "answer" string keys.
     *
     * @return array<string, array{string}>
     */
    public static function validFaqJsonProvider(): array
    {
        $cases = [];

        mt_srand(12345);

        // Generate 100 random valid FAQ arrays.
        for ($i = 0; $i < 100; $i++) {
            $faqCount = mt_rand(1, 10);
            $faqs = [];

            for ($j = 0; $j < $faqCount; $j++) {
                $faqs[] = [
                    'question' => self::generateRandomString(mt_rand(5, 100)),
                    'answer'   => self::generateRandomString(mt_rand(10, 200)),
                ];
            }

            $json = json_encode($faqs, JSON_UNESCAPED_UNICODE);
            $cases["valid_faq_array_{$i}"] = [$json];
        }

        // Edge cases: single item array.
        $cases['single_item'] = [json_encode([['question' => 'Q?', 'answer' => 'A.']])];

        // Edge cases: items with special characters.
        $cases['special_chars'] = [json_encode([
            ['question' => 'What is "JSON"?', 'answer' => "It's a data format with <tags> & symbols."],
        ])];

        // Edge cases: items with unicode.
        $cases['unicode_content'] = [json_encode([
            ['question' => 'Qu\'est-ce que c\'est?', 'answer' => 'C\'est une réponse avec des accents.'],
            ['question' => '这是什么？', 'answer' => '这是一个答案。'],
        ], JSON_UNESCAPED_UNICODE)];

        // Edge cases: items with extra keys (still valid since question and answer exist).
        // The sanitize_faq_meta implementation only checks that question and answer
        // exist and are strings - extra keys are allowed.
        $cases['extra_keys'] = [json_encode([
            ['question' => 'Q?', 'answer' => 'A.', 'extra' => 'value'],
        ])];

        // Edge case: empty array is valid (vacuously satisfies the constraint).
        $cases['empty_array'] = ['[]'];

        return $cases;
    }

    /**
     * Data provider generating 100+ invalid or non-conforming inputs.
     *
     * @return array<string, array{string}>
     */
    public static function invalidFaqJsonProvider(): array
    {
        $cases = [];

        mt_srand(67890);

        // ─── Random invalid JSON strings (not parseable) ─────────────────────
        for ($i = 0; $i < 35; $i++) {
            $invalidJson = self::generateRandomInvalidJson($i);
            $cases["invalid_json_{$i}"] = [$invalidJson];
        }

        // ─── Valid JSON but wrong structure (not an array of {question, answer}) ─
        for ($i = 0; $i < 35; $i++) {
            $nonConforming = self::generateNonConformingJson($i);
            $cases["non_conforming_{$i}"] = [$nonConforming];
        }

        // ─── Valid JSON arrays with missing or wrong-typed keys ──────────────
        for ($i = 0; $i < 35; $i++) {
            $malformedItems = self::generateMalformedItemsJson($i);
            $cases["malformed_items_{$i}"] = [$malformedItems];
        }

        // ─── Specific edge cases ─────────────────────────────────────────────
        $edgeCases = [
            'empty_string'          => [''],
            'plain_text'            => ['hello world'],
            'number_string'         => ['42'],
            'null_string'           => ['null'],
            'true_string'           => ['true'],
            'false_string'          => ['false'],
            'string_json'           => ['"just a string"'],
            'nested_array'          => ['[[1,2,3]]'],
            'object_not_array'      => ['{"question":"Q","answer":"A"}'],
            'array_of_strings'      => ['["a","b","c"]'],
            'array_of_numbers'      => ['[1,2,3]'],
            'array_of_nulls'        => ['[null,null]'],
            'missing_question'      => [json_encode([['answer' => 'A']])],
            'missing_answer'        => [json_encode([['question' => 'Q']])],
            'question_is_int'       => [json_encode([['question' => 123, 'answer' => 'A']])],
            'answer_is_int'         => [json_encode([['question' => 'Q', 'answer' => 456]])],
            'question_is_null'      => [json_encode([['question' => null, 'answer' => 'A']])],
            'answer_is_null'        => [json_encode([['question' => 'Q', 'answer' => null]])],
            'question_is_array'     => [json_encode([['question' => ['nested'], 'answer' => 'A']])],
            'answer_is_bool'        => [json_encode([['question' => 'Q', 'answer' => true]])],
            'associative_array'     => [json_encode(['key' => ['question' => 'Q', 'answer' => 'A']])],
            'mixed_valid_invalid'   => [json_encode([
                ['question' => 'Q', 'answer' => 'A'],
                ['not_question' => 'X', 'answer' => 'B'],
            ])],
            'truncated_json'        => ['[{"question":"Q","answer"'],
            'trailing_comma'        => ['[{"question":"Q","answer":"A"},]'],
            'single_brace'          => ['{'],
            'single_bracket'        => ['['],
        ];

        return array_merge($cases, $edgeCases);
    }

    /**
     * Generate a random string of given length.
     */
    private static function generateRandomString(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 .,!?-_()';
        $str = '';
        $charsLen = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)];
        }

        return $str;
    }

    /**
     * Generate a random invalid JSON string (not parseable).
     */
    private static function generateRandomInvalidJson(int $seed): string
    {
        $strategies = [
            // Random garbage text.
            fn() => self::generateRandomString(mt_rand(5, 50)),
            // Truncated JSON.
            fn() => substr(json_encode(['question' => 'Q', 'answer' => 'A']), 0, mt_rand(3, 15)),
            // JSON with syntax errors.
            fn() => '{question: "Q", answer: "A"}',
            // Unquoted keys.
            fn() => '[{question: "Q", answer: "A"}]',
            // Single quotes instead of double.
            fn() => "[{'question': 'Q', 'answer': 'A'}]",
            // Trailing commas.
            fn() => '[{"question":"Q","answer":"A",}]',
            // Missing colons.
            fn() => '[{"question" "Q", "answer" "A"}]',
            // Random bytes.
            fn() => chr(mt_rand(128, 255)) . chr(mt_rand(128, 255)) . chr(mt_rand(128, 255)),
        ];

        $strategy = $strategies[$seed % count($strategies)];
        return $strategy();
    }

    /**
     * Generate valid JSON that doesn't conform to the FAQ structure.
     */
    private static function generateNonConformingJson(int $seed): string
    {
        $strategies = [
            // Plain string.
            fn() => json_encode(self::generateRandomString(mt_rand(5, 30))),
            // Number.
            fn() => json_encode(mt_rand(-1000, 1000)),
            // Boolean.
            fn() => json_encode((bool) mt_rand(0, 1)),
            // Null.
            fn() => 'null',
            // Flat object (not array).
            fn() => json_encode(['question' => 'Q', 'answer' => 'A']),
            // Array of primitives.
            fn() => json_encode(array_map(fn() => mt_rand(0, 100), range(1, mt_rand(1, 5)))),
            // Array of strings.
            fn() => json_encode(array_map(fn() => self::generateRandomString(5), range(1, 3))),
            // Nested arrays.
            fn() => json_encode([[['deep' => 'value']]]),
            // Associative array (not sequential).
            fn() => json_encode(['a' => ['question' => 'Q', 'answer' => 'A']]),
            // Array with a single non-object item.
            fn() => json_encode([self::generateRandomString(5)]),
        ];

        $strategy = $strategies[$seed % count($strategies)];
        return $strategy();
    }

    /**
     * Generate JSON arrays where items have malformed question/answer structure.
     */
    private static function generateMalformedItemsJson(int $seed): string
    {
        $strategies = [
            // Missing question key.
            fn() => json_encode([['answer' => 'A', 'other' => 'val']]),
            // Missing answer key.
            fn() => json_encode([['question' => 'Q', 'other' => 'val']]),
            // Question is not a string.
            fn() => json_encode([['question' => mt_rand(0, 100), 'answer' => 'A']]),
            // Answer is not a string.
            fn() => json_encode([['question' => 'Q', 'answer' => mt_rand(0, 100)]]),
            // Question is null.
            fn() => json_encode([['question' => null, 'answer' => 'A']]),
            // Answer is null.
            fn() => json_encode([['question' => 'Q', 'answer' => null]]),
            // Question is array.
            fn() => json_encode([['question' => ['nested'], 'answer' => 'A']]),
            // Answer is object.
            fn() => json_encode([['question' => 'Q', 'answer' => ['nested' => true]]]),
            // Question is boolean.
            fn() => json_encode([['question' => true, 'answer' => 'A']]),
            // Item is a string, not an object.
            fn() => json_encode(['not an object']),
            // Item is a number.
            fn() => json_encode([42]),
            // Item is null.
            fn() => json_encode([null]),
            // Empty object in array.
            fn() => json_encode([[]]),
            // Mixed: one valid, one invalid.
            fn() => json_encode([
                ['question' => 'Q', 'answer' => 'A'],
                ['wrong_key' => 'X'],
            ]),
        ];

        $strategy = $strategies[$seed % count($strategies)];
        return $strategy();
    }
}
