<?php
/**
 * Property-based test for the Faq_Parser service.
 *
 * Feature: faq-response-parser, Property 5: Markdown Fence Stripping Enables Parsing
 * Validates: Requirements 5.1, 5.2, 5.3
 *
 * For any valid FAQ JSON string, wrapping it in markdown code fences (with or without
 * a language identifier, with or without surrounding whitespace) SHALL produce the same
 * parse result as the unwrapped JSON string.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;

class FaqParserFenceStrippingPropertyTest extends TestCase
{
    private Faq_Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Faq_Parser();
    }

    /**
     * **Validates: Requirements 5.1, 5.2, 5.3**
     *
     * Property 5: Markdown Fence Stripping Enables Parsing.
     * For any valid FAQ JSON string, wrapping it in markdown code fences
     * (with or without a language identifier, with or without surrounding whitespace)
     * produces the same parse result as the unwrapped JSON string.
     */
    #[Test]
    #[DataProvider('fenceStrippingProvider')]
    public function fenced_input_produces_same_result_as_unwrapped(
        string $rawJson,
        string $fencedJson
    ): void {
        $unwrappedResult = $this->parser->parse($rawJson);
        $fencedResult = $this->parser->parse($fencedJson);

        $this->assertSame(
            $unwrappedResult,
            $fencedResult,
            sprintf(
                'Fenced input must produce same result as unwrapped input. '
                . 'Raw JSON length: %d, Fenced length: %d',
                strlen($rawJson),
                strlen($fencedJson)
            )
        );
    }

    /**
     * Data provider generating 110+ random valid FAQ JSON strings wrapped in various fence styles.
     *
     * @return array<string, array{string, string}>
     */
    public static function fenceStrippingProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(54321);

        // Generate 110 random cases with various fence styles.
        for ($i = 0; $i < 110; $i++) {
            $faqItems = self::generateRandomFaqItems(mt_rand(1, 5));
            $rawJson = json_encode($faqItems, JSON_UNESCAPED_UNICODE);
            $fencedJson = self::wrapInFence($rawJson, $i);

            $cases["random_fenced_{$i}"] = [$rawJson, $fencedJson];
        }

        // Edge cases: single item array with different fence styles.
        $singleItem = json_encode([['question' => 'What is PHP?', 'answer' => 'A programming language.']]);

        $cases['single_item_fence_json'] = [
            $singleItem,
            "```json\n{$singleItem}\n```",
        ];

        $cases['single_item_fence_plain'] = [
            $singleItem,
            "```\n{$singleItem}\n```",
        ];

        $cases['single_item_fence_JSON_uppercase'] = [
            $singleItem,
            "```JSON\n{$singleItem}\n```",
        ];

        $cases['single_item_fence_leading_whitespace'] = [
            $singleItem,
            "  \n```json\n{$singleItem}\n```\n  ",
        ];

        $cases['single_item_fence_trailing_newlines'] = [
            $singleItem,
            "\n\n```json\n{$singleItem}\n```\n\n",
        ];

        // Edge cases: multiple items with fence variations.
        $multiItems = json_encode([
            ['question' => 'Q1', 'answer' => 'A1'],
            ['question' => 'Q2', 'answer' => 'A2'],
            ['question' => 'Q3', 'answer' => 'A3'],
        ]);

        $cases['multi_item_fence_javascript'] = [
            $multiItems,
            "```javascript\n{$multiItems}\n```",
        ];

        $cases['multi_item_fence_text'] = [
            $multiItems,
            "```text\n{$multiItems}\n```",
        ];

        $cases['multi_item_fence_with_spaces_around'] = [
            $multiItems,
            "   ```json\n{$multiItems}\n```   ",
        ];

        $cases['multi_item_fence_tabs_around'] = [
            $multiItems,
            "\t```json\n{$multiItems}\n```\t",
        ];

        // Edge cases: items with extra keys (should be stripped by parser).
        $extraKeysItems = json_encode([
            ['question' => 'Q?', 'answer' => 'A!', 'id' => 1, 'category' => 'general'],
        ]);

        $cases['extra_keys_fence_json'] = [
            $extraKeysItems,
            "```json\n{$extraKeysItems}\n```",
        ];

        $cases['extra_keys_fence_plain'] = [
            $extraKeysItems,
            "```\n{$extraKeysItems}\n```",
        ];

        return $cases;
    }

    /**
     * Generate random FAQ items with non-empty question and answer values.
     *
     * @param int $count Number of items to generate.
     * @return array<int, array<string, string>>
     */
    private static function generateRandomFaqItems(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $item = [
                'question' => self::generateRandomNonEmptyString(mt_rand(5, 100)),
                'answer' => self::generateRandomNonEmptyString(mt_rand(10, 200)),
            ];

            // Occasionally add extra keys to test that they are stripped.
            if (mt_rand(0, 3) === 0) {
                $item['id'] = mt_rand(1, 1000);
            }
            if (mt_rand(0, 4) === 0) {
                $item['category'] = self::generateRandomNonEmptyString(mt_rand(3, 20));
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Generate a random non-empty string (guaranteed non-empty after trim).
     */
    private static function generateRandomNonEmptyString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_';
        $charsLength = strlen($chars);

        // Ensure first and last characters are non-whitespace.
        $nonWhitespace = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.,!?-_';
        $nonWsLength = strlen($nonWhitespace);

        if ($length <= 2) {
            $result = '';
            for ($i = 0; $i < max(1, $length); $i++) {
                $result .= $nonWhitespace[mt_rand(0, $nonWsLength - 1)];
            }
            return $result;
        }

        $result = $nonWhitespace[mt_rand(0, $nonWsLength - 1)];
        for ($i = 1; $i < $length - 1; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }
        $result .= $nonWhitespace[mt_rand(0, $nonWsLength - 1)];

        return $result;
    }

    /**
     * Wrap a JSON string in markdown fences with various styles based on index.
     */
    private static function wrapInFence(string $json, int $index): string
    {
        $style = $index % 8;

        return match ($style) {
            // With language identifier "json".
            0 => "```json\n{$json}\n```",
            // Without language identifier.
            1 => "```\n{$json}\n```",
            // With uppercase "JSON" language identifier.
            2 => "```JSON\n{$json}\n```",
            // With "javascript" language identifier.
            3 => "```javascript\n{$json}\n```",
            // With leading whitespace (spaces).
            4 => "  ```json\n{$json}\n```  ",
            // With leading/trailing newlines.
            5 => "\n```json\n{$json}\n```\n",
            // With mixed whitespace around fences.
            6 => " \n ```json\n{$json}\n``` \n ",
            // With "text" language identifier and trailing whitespace.
            7 => "```text\n{$json}\n```   ",
        };
    }
}
