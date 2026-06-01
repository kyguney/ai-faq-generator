<?php
/**
 * Property-based test for the Faq_Parser service.
 *
 * Feature: faq-response-parser, Property 2: Invalid JSON Returns Empty Array
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4
 *
 * For any string that is not valid JSON, or that decodes to a non-array type
 * (object, string, number, boolean, null), or that is empty/whitespace-only,
 * parse() SHALL return an empty array.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;

class FaqParserInvalidJsonPropertyTest extends TestCase
{
    private Faq_Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Faq_Parser();
    }

    /**
     * **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
     *
     * Property 2: Invalid JSON Returns Empty Array.
     * For any string that is not valid JSON, or that decodes to a non-array type,
     * or that is empty/whitespace-only, parse() returns an empty array.
     */
    #[Test]
    #[DataProvider('invalidJsonProvider')]
    public function parse_returns_empty_array_for_invalid_json(string $input): void
    {
        $result = $this->parser->parse($input);

        $this->assertSame(
            [],
            $result,
            sprintf(
                'parse() must return [] for invalid JSON input. '
                . 'Input (first 100 chars): "%s"',
                mb_substr($input, 0, 100)
            )
        );
    }

    /**
     * Data provider generating 110+ random invalid JSON strings.
     *
     * Categories:
     * - Random text (not JSON at all)
     * - Truncated valid JSON (cut at random positions)
     * - Non-array JSON types (objects, strings, numbers, booleans, null)
     * - Empty and whitespace-only strings
     * - HTML content
     * - XML content
     *
     * @return array<string, array{string}>
     */
    public static function invalidJsonProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(54321);

        // --- Category 1: Random text strings (30 cases) ---
        for ($i = 0; $i < 30; $i++) {
            $length = mt_rand(1, 500);
            $text = self::generateRandomText($length);
            $cases["random_text_{$i}"] = [$text];
        }

        // --- Category 2: Truncated valid JSON (25 cases) ---
        for ($i = 0; $i < 25; $i++) {
            $validJson = self::generateValidFaqJson();
            $cutPosition = mt_rand(1, max(1, strlen($validJson) - 1));
            $truncated = substr($validJson, 0, $cutPosition);
            $cases["truncated_json_{$i}"] = [$truncated];
        }

        // --- Category 3: Non-array JSON types (25 cases) ---
        // JSON objects
        for ($i = 0; $i < 7; $i++) {
            $object = self::generateRandomJsonObject();
            $cases["json_object_{$i}"] = [$object];
        }

        // JSON strings
        for ($i = 0; $i < 6; $i++) {
            $string = json_encode(self::generateRandomText(mt_rand(1, 100)));
            $cases["json_string_{$i}"] = [$string];
        }

        // JSON numbers
        for ($i = 0; $i < 4; $i++) {
            $number = match ($i) {
                0 => '0',
                1 => '42',
                2 => '-17.5',
                3 => '3.14159e10',
            };
            $cases["json_number_{$i}"] = [$number];
        }

        // JSON booleans
        $cases['json_boolean_true'] = ['true'];
        $cases['json_boolean_false'] = ['false'];

        // JSON null
        $cases['json_null'] = ['null'];

        // Nested objects (valid JSON but not arrays)
        for ($i = 0; $i < 5; $i++) {
            $nested = self::generateNestedJsonObject();
            $cases["json_nested_object_{$i}"] = [$nested];
        }

        // --- Category 4: Empty and whitespace-only strings (15 cases) ---
        $cases['empty_string'] = [''];
        $cases['single_space'] = [' '];
        $cases['multiple_spaces'] = ['     '];
        $cases['single_tab'] = ["\t"];
        $cases['multiple_tabs'] = ["\t\t\t"];
        $cases['single_newline'] = ["\n"];
        $cases['multiple_newlines'] = ["\n\n\n"];
        $cases['carriage_return'] = ["\r"];
        $cases['crlf'] = ["\r\n"];
        $cases['mixed_whitespace_1'] = [" \t \n \r\n "];
        $cases['mixed_whitespace_2'] = ["\t\t\n\n  \r\n\t"];
        $cases['mixed_whitespace_3'] = ["   \t\t\t   \n\n\n   "];
        $cases['unicode_whitespace'] = ["\xC2\xA0"]; // non-breaking space (not trimmed by PHP trim)
        $cases['vertical_tab'] = ["\x0B"];
        $cases['form_feed'] = ["\x0C"];

        // --- Category 5: HTML content (10 cases) ---
        $htmlSnippets = [
            '<html><body><p>Hello World</p></body></html>',
            '<div class="faq"><h2>Question?</h2><p>Answer.</p></div>',
            '<!DOCTYPE html><html><head><title>FAQ</title></head></html>',
            '<ul><li>Item 1</li><li>Item 2</li></ul>',
            '<table><tr><td>Q</td><td>A</td></tr></table>',
            '<script>alert("xss")</script>',
            '<p>What is PHP?</p><p>A programming language.</p>',
            '<!-- comment --><div>content</div>',
            '<br/><hr/><img src="test.jpg"/>',
            '<form action="/submit"><input type="text" name="q"/></form>',
        ];
        foreach ($htmlSnippets as $idx => $html) {
            $cases["html_content_{$idx}"] = [$html];
        }

        // --- Category 6: XML content (8 cases) ---
        $xmlSnippets = [
            '<?xml version="1.0"?><faqs><faq><question>Q1</question><answer>A1</answer></faq></faqs>',
            '<root><item key="question">Q</item><item key="answer">A</item></root>',
            '<?xml version="1.0" encoding="UTF-8"?><data/>',
            '<rss version="2.0"><channel><title>FAQ Feed</title></channel></rss>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="50"/></svg>',
            '<note><to>User</to><from>AI</from><body>FAQ data</body></note>',
            '<?xml version="1.0"?><array><element>not json</element></array>',
            '<config><setting name="faq_count">5</setting></config>',
        ];
        foreach ($xmlSnippets as $idx => $xml) {
            $cases["xml_content_{$idx}"] = [$xml];
        }

        // --- Category 7: Almost-valid JSON with syntax errors (10 cases) ---
        $cases['missing_closing_bracket'] = ['[{"question":"Q","answer":"A"}'];
        $cases['missing_opening_bracket'] = ['{"question":"Q","answer":"A"}]'];
        $cases['trailing_comma'] = ['[{"question":"Q","answer":"A"},]'];
        $cases['single_quotes'] = ["[{'question':'Q','answer':'A'}]"];
        $cases['unquoted_keys'] = ['[{question:"Q",answer:"A"}]'];
        $cases['missing_colon'] = ['[{"question" "Q","answer":"A"}]'];
        $cases['double_comma'] = ['[{"question":"Q",,"answer":"A"}]'];
        $cases['missing_value'] = ['[{"question":,"answer":"A"}]'];
        $cases['extra_closing_brace'] = ['[{"question":"Q","answer":"A"}}]'];
        $cases['backslash_error'] = ['[{"question":"Q\\","answer":"A"}]'];

        return $cases;
    }

    /**
     * Generate random text that is not valid JSON.
     */
    private static function generateRandomText(int $length): string
    {
        if ($length === 0) {
            return '';
        }

        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_\'"()@#$%&*<>/\\~`+=;:';
        $charsLength = strlen($chars);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        return $result;
    }

    /**
     * Generate a valid FAQ JSON string (used as source for truncation).
     */
    private static function generateValidFaqJson(): string
    {
        $itemCount = mt_rand(1, 5);
        $items = [];

        for ($i = 0; $i < $itemCount; $i++) {
            $items[] = [
                'question' => 'Question ' . self::generateRandomText(mt_rand(10, 50)),
                'answer' => 'Answer ' . self::generateRandomText(mt_rand(20, 100)),
            ];
        }

        return json_encode($items);
    }

    /**
     * Generate a random JSON object (not an array).
     */
    private static function generateRandomJsonObject(): string
    {
        $keys = ['question', 'answer', 'title', 'content', 'id', 'name', 'value'];
        $obj = [];
        $keyCount = mt_rand(1, 4);

        for ($i = 0; $i < $keyCount; $i++) {
            $key = $keys[mt_rand(0, count($keys) - 1)];
            $obj[$key] = self::generateRandomText(mt_rand(5, 30));
        }

        return json_encode($obj);
    }

    /**
     * Generate a nested JSON object (valid JSON but not an array).
     */
    private static function generateNestedJsonObject(): string
    {
        $structures = [
            ['faqs' => [['question' => 'Q1', 'answer' => 'A1']]],
            ['data' => ['items' => []], 'count' => 0],
            ['result' => 'success', 'payload' => ['question' => 'Q', 'answer' => 'A']],
            ['error' => false, 'message' => null],
            ['nested' => ['deep' => ['deeper' => 'value']]],
        ];

        return json_encode($structures[mt_rand(0, count($structures) - 1)]);
    }
}
