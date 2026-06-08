<?php
/**
 * Property-based test for the JSON_LD_Generator service.
 *
 * Feature: faqpage-jsonld-generator, Property 4: Invalid Input Produces No Output
 * Validates: Requirements 1.2, 1.3
 *
 * For any string that is not a valid JSON-encoded array of objects (including
 * malformed JSON, non-array JSON values, arrays of non-objects, and
 * empty/whitespace strings), the generator SHALL produce no script tag output.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdInvalidInputPropertyTest extends TestCase
{
    private JSON_LD_Generator $generator;

    protected function setUp(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $this->generator = new JSON_LD_Generator();
        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];
    }

    protected function tearDown(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];
    }

    /**
     * **Validates: Requirements 1.2, 1.3**
     *
     * Property 4: Invalid Input Produces No Output.
     * For any string that is not a valid JSON-encoded array of objects with
     * valid FAQ items, the generator SHALL produce no script tag output.
     */
    #[Test]
    #[DataProvider('invalidMetaProvider')]
    public function output_schema_produces_no_output_for_invalid_meta(string $invalidMeta): void
    {
        global $afg_test_post_meta_values;

        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = $invalidMeta;

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        $this->assertSame(
            '',
            $output,
            sprintf(
                'output_schema() must produce no output for invalid meta: %s',
                strlen($invalidMeta) > 80
                    ? substr($invalidMeta, 0, 80) . '...'
                    : $invalidMeta
            )
        );
    }

    /**
     * Data provider generating 120+ invalid meta strings across multiple categories.
     *
     * @return array<string, array{string}>
     */
    public static function invalidMetaProvider(): array
    {
        $cases = [];

        mt_srand(98765);

        // ─── Category 1: Empty and whitespace-only strings (10 cases) ────────
        $cases['empty_string'] = [''];
        $cases['single_space'] = [' '];
        $cases['multiple_spaces'] = ['     '];
        $cases['tab_only'] = ["\t"];
        $cases['newline_only'] = ["\n"];
        $cases['carriage_return'] = ["\r"];
        $cases['mixed_whitespace_1'] = [" \t\n\r "];
        $cases['mixed_whitespace_2'] = ["\t\t\t\n\n"];
        $cases['mixed_whitespace_3'] = ["  \r\n  \t  "];
        $cases['unicode_whitespace'] = ["\xC2\xA0"]; // Non-breaking space (UTF-8)

        // ─── Category 2: Malformed JSON strings (30 cases) ───────────────────
        $cases['random_text'] = ['not json at all'];
        $cases['single_brace_open'] = ['{'];
        $cases['single_brace_close'] = ['}'];
        $cases['single_bracket_open'] = ['['];
        $cases['single_bracket_close'] = [']'];
        $cases['truncated_array'] = ['[{"question":"test"'];
        $cases['trailing_comma_array'] = ['[{"question":"q","answer":"a"},]'];
        $cases['trailing_comma_object'] = ['[{"question":"q","answer":"a",}]'];
        $cases['missing_colon'] = ['[{"question" "value"}]'];
        $cases['single_quotes'] = ["[{'question': 'q', 'answer': 'a'}]"];
        $cases['unquoted_keys'] = ['[{question:"q",answer:"a"}]'];
        $cases['unclosed_string'] = ['[{"question":"unclosed value}]'];
        $cases['double_comma'] = ['[{"question":"q","answer":"a"},,{"question":"q2","answer":"a2"}]'];
        $cases['null_byte'] = ["\x00"];
        $cases['binary_data'] = ["\x89PNG\r\n\x1a\n"];
        $cases['html_fragment'] = ['<div>not json</div>'];
        $cases['xml_fragment'] = ['<?xml version="1.0"?><root/>'];
        $cases['javascript_code'] = ['var x = [1,2,3];'];
        $cases['php_code'] = ['<?php echo "hello"; ?>'];
        $cases['csv_data'] = ["question,answer\nq1,a1\nq2,a2"];

        // Generate 10 more random malformed JSON strings
        for ($i = 0; $i < 10; $i++) {
            $randomChars = '';
            $length = mt_rand(5, 50);
            for ($j = 0; $j < $length; $j++) {
                $randomChars .= chr(mt_rand(32, 126));
            }
            $cases["random_malformed_{$i}"] = [$randomChars];
        }

        // ─── Category 3: Non-array JSON values (20 cases) ───────────────────
        $cases['json_null'] = ['null'];
        $cases['json_true'] = ['true'];
        $cases['json_false'] = ['false'];
        $cases['json_integer_zero'] = ['0'];
        $cases['json_integer_positive'] = ['42'];
        $cases['json_integer_negative'] = ['-7'];
        $cases['json_float'] = ['3.14'];
        $cases['json_string_empty'] = ['""'];
        $cases['json_string_hello'] = ['"hello world"'];
        $cases['json_string_with_brackets'] = ['"[not an array]"'];
        $cases['json_object_empty'] = ['{}'];
        $cases['json_object_simple'] = ['{"key":"value"}'];
        $cases['json_object_with_question'] = ['{"question":"q","answer":"a"}'];
        $cases['json_object_nested'] = ['{"data":[{"question":"q","answer":"a"}]}'];
        $cases['json_large_number'] = ['99999999999999999999'];
        $cases['json_scientific'] = ['1.5e10'];
        $cases['json_negative_float'] = ['-0.001'];

        // Generate 3 more random non-array JSON values
        for ($i = 0; $i < 3; $i++) {
            $value = match (mt_rand(0, 2)) {
                0 => json_encode(mt_rand(-1000, 1000)),
                1 => json_encode(self::randomString(mt_rand(1, 30))),
                2 => json_encode(['key' . $i => 'value' . $i]),
            };
            $cases["random_non_array_{$i}"] = [$value];
        }

        // ─── Category 4: Arrays of non-objects (20 cases) ────────────────────
        $cases['array_of_strings'] = [json_encode(['hello', 'world', 'foo'])];
        $cases['array_of_integers'] = [json_encode([1, 2, 3, 4, 5])];
        $cases['array_of_nulls'] = [json_encode([null, null, null])];
        $cases['array_of_booleans'] = [json_encode([true, false, true])];
        $cases['array_of_floats'] = [json_encode([1.1, 2.2, 3.3])];
        $cases['array_mixed_primitives'] = [json_encode([1, 'two', true, null, 3.14])];
        $cases['array_of_arrays'] = [json_encode([[1, 2], [3, 4]])];
        $cases['array_nested_arrays'] = [json_encode([['a', 'b'], ['c', 'd'], ['e', 'f']])];
        $cases['array_single_null'] = [json_encode([null])];
        $cases['array_single_string'] = [json_encode(['just a string'])];
        $cases['array_single_integer'] = [json_encode([99])];
        $cases['array_single_boolean'] = [json_encode([false])];

        // Generate 8 randomized arrays of non-objects
        for ($i = 0; $i < 8; $i++) {
            $items = [];
            $count = mt_rand(1, 6);
            for ($j = 0; $j < $count; $j++) {
                $items[] = match (mt_rand(0, 4)) {
                    0 => mt_rand(-100, 100),
                    1 => self::randomString(mt_rand(1, 20)),
                    2 => (bool) mt_rand(0, 1),
                    3 => null,
                    4 => [mt_rand(1, 10), mt_rand(1, 10)],
                };
            }
            $cases["random_non_object_array_{$i}"] = [json_encode($items)];
        }

        // ─── Category 5: Arrays of objects missing required keys (20 cases) ──
        $cases['objects_no_question_key'] = [json_encode([['answer' => 'a1'], ['answer' => 'a2']])];
        $cases['objects_no_answer_key'] = [json_encode([['question' => 'q1'], ['question' => 'q2']])];
        $cases['objects_empty_keys'] = [json_encode([['foo' => 'bar'], ['baz' => 'qux']])];
        $cases['objects_numeric_keys'] = [json_encode([[0 => 'a', 1 => 'b']])];
        $cases['objects_question_only_empty'] = [json_encode([['question' => '', 'answer' => 'valid']])];
        $cases['objects_answer_only_empty'] = [json_encode([['question' => 'valid', 'answer' => '']])];
        $cases['objects_both_empty'] = [json_encode([['question' => '', 'answer' => '']])];
        $cases['objects_question_whitespace'] = [json_encode([['question' => '   ', 'answer' => 'valid']])];
        $cases['objects_answer_whitespace'] = [json_encode([['question' => 'valid', 'answer' => '   ']])];
        $cases['objects_both_whitespace'] = [json_encode([['question' => '  ', 'answer' => "\t\n"]])];
        $cases['objects_question_tabs'] = [json_encode([['question' => "\t\t", 'answer' => 'valid']])];
        $cases['objects_answer_newlines'] = [json_encode([['question' => 'valid', 'answer' => "\n\n"]])];
        $cases['objects_question_null'] = [json_encode([['question' => null, 'answer' => 'valid']])];
        $cases['objects_answer_null'] = [json_encode([['question' => 'valid', 'answer' => null]])];
        $cases['objects_question_integer'] = [json_encode([['question' => 42, 'answer' => 'valid']])];
        $cases['objects_answer_boolean'] = [json_encode([['question' => 'valid', 'answer' => true]])];
        $cases['objects_question_array'] = [json_encode([['question' => ['nested'], 'answer' => 'valid']])];
        $cases['objects_answer_object'] = [json_encode([['question' => 'valid', 'answer' => ['key' => 'val']]])];

        // Generate 2 randomized arrays with all-invalid objects
        for ($i = 0; $i < 2; $i++) {
            $items = [];
            $count = mt_rand(2, 5);
            for ($j = 0; $j < $count; $j++) {
                $items[] = self::generateInvalidObject();
            }
            $cases["random_invalid_objects_{$i}"] = [json_encode($items)];
        }

        // ─── Category 6: Empty JSON array (Requirement 1.2) ─────────────────
        $cases['empty_array'] = ['[]'];

        // ─── Category 7: Additional edge cases (10+ cases) ───────────────────
        $cases['deeply_nested'] = [json_encode([[[[['question' => 'q', 'answer' => 'a']]]]])];
        $cases['array_with_empty_object'] = [json_encode([new \stdClass()])];
        $cases['unicode_but_invalid'] = ['["\\u0000"]'];
        $cases['bom_prefix'] = ["\xEF\xBB\xBF" . '[]'];
        $cases['array_of_empty_objects'] = [json_encode([(object)[], (object)[]])];
        $cases['all_items_question_empty_after_trim'] = [
            json_encode([
                ['question' => '   ', 'answer' => 'answer1'],
                ['question' => "\t", 'answer' => 'answer2'],
                ['question' => "\n", 'answer' => 'answer3'],
            ]),
        ];
        $cases['all_items_answer_empty_after_trim'] = [
            json_encode([
                ['question' => 'question1', 'answer' => '   '],
                ['question' => 'question2', 'answer' => "\t"],
                ['question' => 'question3', 'answer' => "\n"],
            ]),
        ];
        $cases['all_items_missing_question'] = [
            json_encode([
                ['text' => 'q1', 'answer' => 'a1'],
                ['title' => 'q2', 'answer' => 'a2'],
            ]),
        ];
        $cases['all_items_missing_answer'] = [
            json_encode([
                ['question' => 'q1', 'response' => 'a1'],
                ['question' => 'q2', 'reply' => 'a2'],
            ]),
        ];

        return $cases;
    }

    /**
     * Generate a random string of printable ASCII characters.
     */
    private static function randomString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $result;
    }

    /**
     * Generate a random object that does NOT qualify as a valid FAQ item.
     *
     * @return array<string, mixed>
     */
    private static function generateInvalidObject(): array
    {
        return match (mt_rand(0, 5)) {
            // Missing question key
            0 => ['answer' => self::randomString(mt_rand(5, 30))],
            // Missing answer key
            1 => ['question' => self::randomString(mt_rand(5, 30))],
            // Empty question
            2 => ['question' => '', 'answer' => self::randomString(mt_rand(5, 30))],
            // Empty answer
            3 => ['question' => self::randomString(mt_rand(5, 30)), 'answer' => ''],
            // Whitespace question
            4 => ['question' => '   ', 'answer' => self::randomString(mt_rand(5, 30))],
            // Whitespace answer
            5 => ['question' => self::randomString(mt_rand(5, 30)), 'answer' => "  \t\n  "],
        };
    }
}
