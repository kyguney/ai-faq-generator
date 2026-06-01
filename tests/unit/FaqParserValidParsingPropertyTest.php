<?php
/**
 * Property-based test for the Faq_Parser service.
 *
 * Feature: faq-response-parser, Property 1: Valid FAQ Parsing Round-Trip
 * Validates: Requirements 1.1, 1.2, 1.3, 3.6
 *
 * For any valid JSON array of objects where each object contains non-empty-after-trim
 * `question` and `answer` string values (and possibly additional keys), parse() SHALL
 * return an array of the same length, in the same order, where each item contains only
 * the `question` and `answer` keys with their trimmed values matching the originals.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;

class FaqParserValidParsingPropertyTest extends TestCase
{
    private Faq_Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Faq_Parser();
    }

    /**
     * **Validates: Requirements 1.1, 1.2, 1.3, 3.6**
     *
     * Property 1: Valid FAQ Parsing Round-Trip.
     * For any valid JSON array of objects with non-empty question/answer strings
     * (and possibly extra keys), parse() returns an array of the same length,
     * in the same order, containing only question and answer keys with trimmed values.
     */
    #[Test]
    #[DataProvider('validFaqArrayProvider')]
    public function parse_returns_correct_items_for_valid_json_arrays(
        array $inputItems,
        string $json
    ): void {
        $result = $this->parser->parse($json);

        // Output length matches input valid item count.
        $this->assertCount(
            count($inputItems),
            $result,
            sprintf(
                'parse() must return the same number of items as valid input items. '
                . 'Expected %d, got %d.',
                count($inputItems),
                count($result)
            )
        );

        // Order preserved, only question/answer keys retained, values trimmed.
        foreach ($inputItems as $index => $inputItem) {
            $outputItem = $result[$index];

            // Only question and answer keys retained (Requirement 3.6).
            $this->assertSame(
                ['question', 'answer'],
                array_keys($outputItem),
                sprintf(
                    'Item at index %d must contain only "question" and "answer" keys.',
                    $index
                )
            );

            // Values are trimmed versions of the originals.
            $this->assertSame(
                trim($inputItem['question']),
                $outputItem['question'],
                sprintf(
                    'Question at index %d must be trimmed. Expected "%s", got "%s".',
                    $index,
                    trim($inputItem['question']),
                    $outputItem['question']
                )
            );

            $this->assertSame(
                trim($inputItem['answer']),
                $outputItem['answer'],
                sprintf(
                    'Answer at index %d must be trimmed. Expected "%s", got "%s".',
                    $index,
                    trim($inputItem['answer']),
                    $outputItem['answer']
                )
            );
        }
    }

    /**
     * Data provider generating 110+ random valid FAQ JSON arrays.
     *
     * @return array<string, array{array<int, array<string, mixed>>, string}>
     */
    public static function validFaqArrayProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(54321);

        // Generate 110 random valid FAQ arrays.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(1, 8);
            $items = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $item = self::generateValidFaqItem();

                // Randomly add extra keys (Requirement 3.6 — extra keys discarded).
                if (mt_rand(0, 2) === 0) {
                    $item = self::addExtraKeys($item);
                }

                $items[] = $item;
            }

            $json = json_encode($items, JSON_UNESCAPED_UNICODE);

            // Store only question/answer for assertion (strip extra keys).
            $expectedItems = array_map(function (array $item): array {
                return [
                    'question' => $item['question'],
                    'answer' => $item['answer'],
                ];
            }, $items);

            $cases["random_valid_array_{$i}"] = [$expectedItems, $json];
        }

        // Edge case: single item array (Requirement 1.3).
        $singleItem = [['question' => 'What is PHP?', 'answer' => 'A programming language.']];
        $cases['single_item_array'] = [$singleItem, json_encode($singleItem)];

        // Edge case: single item with extra keys.
        $singleWithExtra = [['question' => 'What is PHP?', 'answer' => 'A language.', 'id' => 1, 'category' => 'tech']];
        $expectedSingle = [['question' => 'What is PHP?', 'answer' => 'A language.']];
        $cases['single_item_with_extra_keys'] = [$expectedSingle, json_encode($singleWithExtra)];

        // Edge case: many items (10 items).
        $manyItems = [];
        for ($i = 0; $i < 10; $i++) {
            $manyItems[] = ['question' => "Question {$i}?", 'answer' => "Answer {$i}."];
        }
        $cases['ten_items_array'] = [$manyItems, json_encode($manyItems)];

        // Edge case: items with unicode content.
        $unicodeItems = [
            ['question' => 'Ünïcödé soru nedir?', 'answer' => 'Cevap: Ünïcödé desteklenir.'],
            ['question' => '日本語の質問', 'answer' => '日本語の回答'],
        ];
        $cases['unicode_content'] = [$unicodeItems, json_encode($unicodeItems, JSON_UNESCAPED_UNICODE)];

        // Edge case: items with special characters.
        $specialItems = [
            ['question' => 'What about "quotes" & <tags>?', 'answer' => 'They are preserved as-is.'],
        ];
        $cases['special_characters'] = [$specialItems, json_encode($specialItems)];

        // Edge case: items with internal whitespace preserved.
        $whitespaceItems = [
            ['question' => 'What  is   this?', 'answer' => "Answer with\ttabs and  spaces."],
        ];
        $cases['internal_whitespace_preserved'] = [$whitespaceItems, json_encode($whitespaceItems)];

        // Edge case: items with many extra keys.
        $manyExtraKeys = [[
            'id' => 42,
            'question' => 'Core question?',
            'answer' => 'Core answer.',
            'category' => 'general',
            'priority' => 1,
            'tags' => ['a', 'b'],
            'metadata' => ['source' => 'ai'],
        ]];
        $expectedManyExtra = [['question' => 'Core question?', 'answer' => 'Core answer.']];
        $cases['many_extra_keys_discarded'] = [$expectedManyExtra, json_encode($manyExtraKeys)];

        return $cases;
    }

    /**
     * Generate a random valid FAQ item with non-empty question and answer.
     *
     * @return array{question: string, answer: string}
     */
    private static function generateValidFaqItem(): array
    {
        return [
            'question' => self::generateNonEmptyString(mt_rand(5, 150)),
            'answer' => self::generateNonEmptyString(mt_rand(10, 500)),
        ];
    }

    /**
     * Generate a random non-empty string that is non-empty after trim.
     */
    private static function generateNonEmptyString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_()[]{}@#$%&*';
        $charsLength = strlen($chars);

        // Ensure at least one non-whitespace character at the start.
        $result = $chars[mt_rand(0, 25)]; // Start with a letter.

        for ($i = 1; $i < $length - 1; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        // End with a non-whitespace character.
        if ($length > 1) {
            $result .= $chars[mt_rand(0, 25)];
        }

        return $result;
    }

    /**
     * Add random extra keys to a FAQ item (to test Requirement 3.6).
     *
     * @param array{question: string, answer: string} $item
     * @return array<string, mixed>
     */
    private static function addExtraKeys(array $item): array
    {
        $extraKeys = ['id', 'category', 'priority', 'source', 'timestamp', 'tags', 'metadata'];
        $numExtra = mt_rand(1, 4);

        for ($i = 0; $i < $numExtra; $i++) {
            $key = $extraKeys[mt_rand(0, count($extraKeys) - 1)];
            $item[$key] = match (mt_rand(0, 3)) {
                0 => mt_rand(1, 1000),
                1 => self::generateNonEmptyString(mt_rand(3, 20)),
                2 => (bool) mt_rand(0, 1),
                3 => ['nested' => 'value'],
            };
        }

        return $item;
    }
}
