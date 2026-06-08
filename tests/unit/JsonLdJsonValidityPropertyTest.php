<?php
/**
 * Property-based test for the JSON_LD_Generator service.
 *
 * Feature: faqpage-jsonld-generator, Property 7: JSON Validity with Special Characters
 * Validates: Requirements 6.2
 *
 * For any FAQ content containing double quotes, backslashes, or control characters
 * (U+0000 through U+001F), the JSON content within the script tag (after un-escaping
 * `<\/script`) SHALL be valid JSON per RFC 8259.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdJsonValidityPropertyTest extends TestCase
{
    private JSON_LD_Generator $generator;

    protected function setUp(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];

        $this->generator = new JSON_LD_Generator();
    }

    protected function tearDown(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values;

        $afg_test_is_singular = true;
        $afg_test_post_meta_values = [];
    }

    /**
     * **Validates: Requirements 6.2**
     *
     * Property 7: JSON Validity with Special Characters.
     * For any FAQ content containing double quotes, backslashes, or control characters,
     * the JSON content within the script tag (after un-escaping `<\/script`) SHALL be
     * valid JSON per RFC 8259.
     */
    #[Test]
    #[DataProvider('specialCharacterFaqProvider')]
    public function output_json_is_valid_with_special_characters(array $faqItems): void
    {
        global $afg_test_post_meta_values;

        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = json_encode(
            $faqItems,
            JSON_UNESCAPED_UNICODE
        );

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        // The output should not be empty since we have valid FAQ items.
        $this->assertNotEmpty($output, 'Expected script tag output for valid FAQ items.');

        // Extract the JSON content from between the script tags.
        $pattern = '/<script type="application\/ld\+json">(.*?)<\/script>/s';
        $matched = preg_match($pattern, $output, $matches);
        $this->assertSame(1, $matched, 'Expected output to contain a script tag.');

        $jsonContent = $matches[1];

        // Un-escape `<\/script` back to `</script` (the generator escapes it for safety).
        $jsonContent = str_replace('<\\/script', '</script', $jsonContent);

        // Assert json_decode() succeeds — valid JSON per RFC 8259.
        $decoded = json_decode($jsonContent, true);
        $this->assertNotNull(
            $decoded,
            sprintf(
                'JSON content must be valid after un-escaping. json_last_error: %s. Content: %s',
                json_last_error_msg(),
                substr($jsonContent, 0, 200)
            )
        );

        // Also assert json_last_error() === JSON_ERROR_NONE.
        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'json_last_error() must be JSON_ERROR_NONE for valid JSON output.'
        );
    }

    /**
     * Data provider generating 110+ FAQ item sets containing special characters
     * that require JSON escaping: double quotes, backslashes, and control characters.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>}>
     */
    public static function specialCharacterFaqProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(77665);

        // Generate 110 randomized test cases with special characters.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(1, 5);
            $items = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $charType = mt_rand(0, 3);
                $question = self::generateTextWithSpecialChars($charType);
                $answer = self::generateTextWithSpecialChars(mt_rand(0, 3));

                $items[] = [
                    'question' => $question,
                    'answer'   => $answer,
                ];
            }

            $cases["random_special_chars_{$i}"] = [$items];
        }

        // Edge case: double quotes in question.
        $cases['double_quotes_in_question'] = [[
            ['question' => 'What is "WordPress"?', 'answer' => 'A content management system.'],
        ]];

        // Edge case: nested double quotes.
        $cases['nested_double_quotes'] = [[
            ['question' => 'He said "she said ""hello"" to me"', 'answer' => 'Nested "quotes" are tricky.'],
        ]];

        // Edge case: backslashes.
        $cases['backslashes'] = [[
            ['question' => 'What is C:\\path\\to\\file?', 'answer' => 'A Windows path with \\ backslashes.'],
        ]];

        // Edge case: double backslashes.
        $cases['double_backslashes'] = [[
            ['question' => 'How to write \\\\ in code?', 'answer' => 'Use \\\\ to escape.'],
        ]];

        // Edge case: tab characters.
        $cases['tab_characters'] = [[
            ['question' => "What\tabout\ttabs?", 'answer' => "Tabs\tare\tcontrol\tcharacters."],
        ]];

        // Edge case: newline characters.
        $cases['newline_characters'] = [[
            ['question' => "Question with\nnewline?", 'answer' => "Answer with\nnewline and\rcarriage return."],
        ]];

        // Edge case: null character (U+0000) — note: json_encode removes null bytes.
        $cases['null_character'] = [[
            ['question' => "Question with \x00 null?", 'answer' => "Answer with \x00 null byte."],
        ]];

        // Edge case: mixed control characters.
        $cases['mixed_control_characters'] = [[
            ['question' => "Q with \x01\x02\x03 control", 'answer' => "A with \x04\x05\x06 chars."],
        ]];

        // Edge case: combination of all special chars.
        $cases['all_special_chars_combined'] = [[
            [
                'question' => "What about \"quotes\" and \\slashes and \ttabs\nand \x01 control?",
                'answer'   => "All \"these\" \\ special \t chars \n are \x1F handled.",
            ],
        ]];

        // Edge case: quotes and backslash adjacent.
        $cases['quotes_backslash_adjacent'] = [[
            ['question' => 'What is \\"escaped quote\\"?', 'answer' => 'A \\"pattern\\" in JSON.'],
        ]];

        // Edge case: form feed and vertical tab.
        $cases['form_feed_vertical_tab'] = [[
            ['question' => "Form\x0Cfeed question?", 'answer' => "Vertical\x0Btab answer."],
        ]];

        // Edge case: all control characters U+0000 through U+001F in answer.
        $controlCharsAnswer = 'Answer with: ';
        for ($c = 0; $c <= 0x1F; $c++) {
            $controlCharsAnswer .= chr($c);
        }
        $cases['all_control_chars_0x00_to_0x1F'] = [[
            ['question' => 'All control chars?', 'answer' => $controlCharsAnswer],
        ]];

        return $cases;
    }

    /**
     * Generate text containing specific types of special characters.
     *
     * @param int $charType 0=double quotes, 1=backslashes, 2=control chars, 3=combination
     */
    private static function generateTextWithSpecialChars(int $charType): string
    {
        $prefix = self::generateRandomText(mt_rand(3, 20));
        $suffix = self::generateRandomText(mt_rand(3, 20));

        $specialContent = match ($charType) {
            0 => self::generateDoubleQuoteContent(),
            1 => self::generateBackslashContent(),
            2 => self::generateControlCharContent(),
            3 => self::generateCombinedContent(),
            default => self::generateDoubleQuoteContent(),
        };

        return $prefix . ' ' . $specialContent . ' ' . $suffix;
    }

    /**
     * Generate content with double quotes.
     */
    private static function generateDoubleQuoteContent(): string
    {
        $patterns = [
            '"quoted text"',
            'say "hello"',
            '""',
            'nested "inner "deep" value" end',
            '"start',
            'end"',
            'mid"dle',
            '"multiple" "quotes" "here"',
        ];

        return $patterns[mt_rand(0, count($patterns) - 1)];
    }

    /**
     * Generate content with backslashes.
     */
    private static function generateBackslashContent(): string
    {
        $patterns = [
            'C:\\Users\\path',
            '\\\\server\\share',
            'back\\slash',
            '\\',
            '\\\\',
            'path\\to\\file.txt',
            'regex: \\d+\\.\\w+',
            'end\\',
        ];

        return $patterns[mt_rand(0, count($patterns) - 1)];
    }

    /**
     * Generate content with control characters (U+0000 through U+001F).
     */
    private static function generateControlCharContent(): string
    {
        $controlChars = [
            "\x00", // NULL
            "\x01", // SOH
            "\x02", // STX
            "\x08", // BS
            "\x09", // TAB
            "\x0A", // LF
            "\x0B", // VT
            "\x0C", // FF
            "\x0D", // CR
            "\x1B", // ESC
            "\x1F", // US
        ];

        $numChars = mt_rand(1, 4);
        $result = '';

        for ($i = 0; $i < $numChars; $i++) {
            $result .= $controlChars[mt_rand(0, count($controlChars) - 1)];
            $result .= self::generateRandomText(mt_rand(2, 8));
        }

        return $result;
    }

    /**
     * Generate content combining double quotes, backslashes, and control characters.
     */
    private static function generateCombinedContent(): string
    {
        $parts = [
            self::generateDoubleQuoteContent(),
            self::generateBackslashContent(),
            self::generateControlCharContent(),
        ];

        shuffle($parts);

        return implode(' ', $parts);
    }

    /**
     * Generate random text content (letters, digits, spaces).
     */
    private static function generateRandomText(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 ';
        $charsLength = strlen($chars);
        $result = '';

        // Start with a letter to ensure non-empty after trim.
        $result .= $chars[mt_rand(0, 25)];

        for ($i = 1; $i < $length - 1; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        // End with a letter.
        if ($length > 1) {
            $result .= $chars[mt_rand(0, 25)];
        }

        return $result;
    }
}
