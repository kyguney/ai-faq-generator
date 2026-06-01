<?php
/**
 * Property-based test for the Faq_Parser service.
 *
 * Feature: faq-response-parser, Property 6: Return Type Invariant
 * Validates: Requirements 6.1, 6.2, 6.4
 *
 * For any input string whatsoever (valid JSON, invalid JSON, binary data, empty string,
 * extremely long strings), parse() SHALL never throw an exception and SHALL always return
 * a value of type array<int, array{question: string, answer: string}> with zero-based
 * sequential integer keys.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;

class FaqParserReturnTypePropertyTest extends TestCase
{
    private Faq_Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Faq_Parser();
    }

    /**
     * **Validates: Requirements 6.1, 6.2, 6.4**
     *
     * Property 6: Return Type Invariant.
     * For any input string, parse() never throws and always returns an array
     * with zero-based sequential int keys where each element has exactly
     * `question` and `answer` string keys.
     */
    #[Test]
    #[DataProvider('returnTypeInvariantProvider')]
    public function parse_never_throws_and_always_returns_valid_typed_array(string $input): void
    {
        $exception = null;

        try {
            $result = $this->parser->parse($input);
        } catch (\Throwable $e) {
            $exception = $e;
            $result = null;
        }

        $this->assertNull(
            $exception,
            sprintf(
                'parse() must never throw an exception. Got %s: %s for input (first 100 chars): "%s"',
                $exception !== null ? get_class($exception) : '',
                $exception !== null ? $exception->getMessage() : '',
                mb_substr($input, 0, 100)
            )
        );

        $this->assertIsArray(
            $result,
            sprintf(
                'parse() must always return an array. Input (first 100 chars): "%s"',
                mb_substr($input, 0, 100)
            )
        );

        // Assert zero-based sequential integer keys.
        $keys = array_keys($result);
        $expectedKeys = range(0, count($result) - 1);

        if (count($result) > 0) {
            $this->assertSame(
                $expectedKeys,
                $keys,
                sprintf(
                    'Result array must have zero-based sequential integer keys. Got keys: [%s]',
                    implode(', ', $keys)
                )
            );
        }

        // Assert each element has exactly 'question' and 'answer' string keys.
        foreach ($result as $index => $item) {
            $this->assertIsArray(
                $item,
                sprintf('Element at index %d must be an array.', $index)
            );

            $itemKeys = array_keys($item);
            sort($itemKeys);

            $this->assertSame(
                ['answer', 'question'],
                $itemKeys,
                sprintf(
                    'Element at index %d must have exactly "question" and "answer" keys. Got: [%s]',
                    $index,
                    implode(', ', $itemKeys)
                )
            );

            $this->assertIsString(
                $item['question'],
                sprintf('Element at index %d "question" value must be a string.', $index)
            );

            $this->assertIsString(
                $item['answer'],
                sprintf('Element at index %d "answer" value must be a string.', $index)
            );
        }
    }

    /**
     * Data provider generating 110+ random strings of diverse types.
     *
     * @return array<string, array{string}>
     */
    public static function returnTypeInvariantProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(67890);

        // --- Valid FAQ JSON (20 cases) ---
        for ($i = 0; $i < 20; $i++) {
            $itemCount = mt_rand(1, 10);
            $items = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $items[] = [
                    'question' => self::generateRandomText(mt_rand(5, 100)),
                    'answer' => self::generateRandomText(mt_rand(10, 200)),
                ];
            }

            $cases["valid_faq_json_{$i}"] = [json_encode($items)];
        }

        // --- Invalid JSON strings (20 cases) ---
        for ($i = 0; $i < 20; $i++) {
            $cases["invalid_json_{$i}"] = [self::generateRandomText(mt_rand(1, 500))];
        }

        // --- Truncated JSON (10 cases) ---
        for ($i = 0; $i < 10; $i++) {
            $validJson = json_encode([
                ['question' => 'Q' . $i, 'answer' => 'A' . $i],
                ['question' => 'Q' . ($i + 1), 'answer' => 'A' . ($i + 1)],
            ]);
            $cutPoint = mt_rand(1, strlen($validJson) - 2);
            $cases["truncated_json_{$i}"] = [substr($validJson, 0, $cutPoint)];
        }

        // --- Binary-like data (15 cases) ---
        for ($i = 0; $i < 15; $i++) {
            $length = mt_rand(10, 500);
            $binary = '';

            for ($j = 0; $j < $length; $j++) {
                $binary .= chr(mt_rand(0, 255));
            }

            $cases["binary_data_{$i}"] = [$binary];
        }

        // --- Empty and whitespace-only strings (10 cases) ---
        $cases['empty_string'] = [''];
        $cases['single_space'] = [' '];
        $cases['multiple_spaces'] = ['     '];
        $cases['tab_only'] = ["\t"];
        $cases['newline_only'] = ["\n"];
        $cases['carriage_return'] = ["\r"];
        $cases['mixed_whitespace_1'] = [" \t\n\r "];
        $cases['mixed_whitespace_2'] = ["\n\n\n\t\t\t   "];
        $cases['mixed_whitespace_3'] = [str_repeat(" \t\n", 50)];
        $cases['mixed_whitespace_4'] = [str_repeat("\r\n", 100)];

        // --- Extremely long strings (10 cases) ---
        $cases['long_random_text'] = [self::generateRandomText(10000)];
        $cases['long_repeated_char'] = [str_repeat('x', 15000)];
        $cases['long_json_like'] = [str_repeat('{"question":"q","answer":"a"},', 500)];
        $cases['long_brackets'] = [str_repeat('[', 10000)];
        $cases['long_braces'] = [str_repeat('{', 10000)];
        $cases['long_valid_faq_json'] = [json_encode(array_map(
            fn($k) => ['question' => "Question {$k}", 'answer' => "Answer {$k}"],
            range(1, 200)
        ))];
        $cases['long_null_bytes'] = [str_repeat("\0", 10000)];
        $cases['long_mixed_binary'] = [str_repeat("\x00\xFF\x7F\x80", 3000)];
        $cases['long_unicode'] = [str_repeat('日本語テスト', 2000)];
        $cases['long_emoji'] = [str_repeat('🎉🚀💡', 3500)];

        // --- HTML content (5 cases) ---
        $cases['html_simple'] = ['<html><body><p>Hello</p></body></html>'];
        $cases['html_with_script'] = ['<script>alert("xss")</script>'];
        $cases['html_table'] = ['<table><tr><td>Q</td><td>A</td></tr></table>'];
        $cases['html_entities'] = ['&lt;div&gt;&amp;nbsp;&lt;/div&gt;'];
        $cases['html_complex'] = ['<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><div class="faq"><h2>FAQ</h2></div></body></html>'];

        // --- XML content (5 cases) ---
        $cases['xml_simple'] = ['<?xml version="1.0"?><faqs><faq><q>Question</q><a>Answer</a></faq></faqs>'];
        $cases['xml_malformed'] = ['<?xml version="1.0"?><faqs><faq><q>Unclosed'];
        $cases['xml_with_cdata'] = ['<![CDATA[Some raw content]]>'];
        $cases['xml_namespace'] = ['<ns:root xmlns:ns="http://example.com"><ns:item/></ns:root>'];
        $cases['xml_declaration_only'] = ['<?xml version="1.0" encoding="UTF-8"?>'];

        // --- Null bytes and control characters (5 cases) ---
        $cases['null_byte_single'] = ["\0"];
        $cases['null_bytes_in_json'] = ["[\0{\"question\":\0\"Q\",\"answer\":\"A\"}]"];
        $cases['control_chars'] = ["\x01\x02\x03\x04\x05\x06\x07"];
        $cases['bell_and_backspace'] = ["\x07\x08\x0B\x0C"];
        $cases['escape_sequences'] = ["\x1B[31mRed text\x1B[0m"];

        // --- Non-array JSON types (5 cases) ---
        $cases['json_object'] = ['{"question": "Q", "answer": "A"}'];
        $cases['json_string'] = ['"just a string"'];
        $cases['json_number'] = ['42'];
        $cases['json_boolean'] = ['true'];
        $cases['json_null'] = ['null'];

        // --- Markdown-fenced content (5 cases) ---
        $cases['fenced_valid_json'] = ["```json\n[{\"question\":\"Q\",\"answer\":\"A\"}]\n```"];
        $cases['fenced_invalid_json'] = ["```\nnot json at all\n```"];
        $cases['fenced_empty'] = ["```json\n\n```"];
        $cases['partial_fence_opening'] = ["```json\n[{\"question\":\"Q\",\"answer\":\"A\"}]"];
        $cases['partial_fence_closing'] = ["[{\"question\":\"Q\",\"answer\":\"A\"}]\n```"];

        return $cases;
    }

    /**
     * Generate random text of specified length using printable characters.
     */
    private static function generateRandomText(int $length): string
    {
        if ($length === 0) {
            return '';
        }

        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_\'"()[]{}@#$%&*:;/\\~`+=<>';
        $charsLength = strlen($chars);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        return $result;
    }
}
