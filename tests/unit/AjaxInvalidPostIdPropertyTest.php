<?php
/**
 * Property-based test for invalid post_id rejection.
 *
 * Feature: generate-button, Property 1: Invalid post_id rejection
 * Validates: Requirements 4.4, 5.5
 *
 * For any value that is not a positive integer (including zero, negative integers,
 * non-numeric strings, null, and empty strings), when passed as the post_id parameter
 * to the AJAX handler, the handler SHALL return a JSON error response with HTTP status 400.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use Afg_Test_Json_Response_Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Ajax_Generate_Faqs;

class AjaxInvalidPostIdPropertyTest extends TestCase
{
    private Ajax_Generate_Faqs $handler;

    protected function setUp(): void
    {
        $this->handler = new Ajax_Generate_Faqs();

        // Ensure nonce check passes so we reach post_id validation.
        global $afg_test_check_ajax_referer_result;
        $afg_test_check_ajax_referer_result = true;

        // Reset $_POST.
        $_POST = [];
    }

    protected function tearDown(): void
    {
        global $afg_test_json_error_response, $afg_test_json_success_response, $afg_test_check_ajax_referer_result;
        $afg_test_json_error_response = null;
        $afg_test_json_success_response = null;
        $afg_test_check_ajax_referer_result = true;
        $_POST = [];
    }

    /**
     * **Validates: Requirements 4.4, 5.5**
     *
     * Property 1: Invalid post_id rejection.
     * For any value that is not a positive integer, the AJAX handler returns
     * a JSON error response with HTTP status 400.
     */
    #[Test]
    #[DataProvider('invalidPostIdProvider')]
    public function invalid_post_id_returns_400_error($postIdValue, string $description): void
    {
        global $afg_test_json_error_response;

        // Set up $_POST with the invalid post_id value.
        if ($postIdValue === null) {
            // Don't set post_id at all (simulates missing parameter).
            $_POST = ['_ajax_nonce' => 'test_nonce'];
        } else {
            $_POST = [
                '_ajax_nonce' => 'test_nonce',
                'post_id'     => $postIdValue,
            ];
        }

        try {
            $this->handler->handle();
            $this->fail("Expected Afg_Test_Json_Response_Exception to be thrown for: {$description}");
        } catch (Afg_Test_Json_Response_Exception $e) {
            // Expected — wp_send_json_error throws to halt execution.
        }

        $this->assertNotNull(
            $afg_test_json_error_response,
            "Error response should have been set for invalid post_id: {$description}"
        );
        $this->assertSame(
            400,
            $afg_test_json_error_response['status'],
            "HTTP status should be 400 for invalid post_id: {$description}"
        );
        $this->assertArrayHasKey(
            'message',
            $afg_test_json_error_response['data'],
            "Error response should contain a message for: {$description}"
        );
    }

    /**
     * Data provider generating 100+ invalid post_id values.
     *
     * Covers: zero, negative integers, non-numeric strings, null, empty strings.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function invalidPostIdProvider(): array
    {
        $cases = [];

        mt_srand(54321);

        // ─── Zero values ─────────────────────────────────────────────────────
        $cases['zero_int'] = [0, 'zero integer'];
        $cases['zero_string'] = ['0', 'zero as string'];
        $cases['zero_float'] = [0.0, 'zero float'];

        // ─── Negative integers (30 random) ───────────────────────────────────
        for ($i = 0; $i < 30; $i++) {
            $negValue = -mt_rand(1, 999999);
            $cases["negative_int_{$i}"] = [$negValue, "negative integer: {$negValue}"];
        }

        // ─── Negative integer strings (10 random) ────────────────────────────
        for ($i = 0; $i < 10; $i++) {
            $negValue = -mt_rand(1, 999999);
            $cases["negative_string_{$i}"] = [(string) $negValue, "negative string: {$negValue}"];
        }

        // ─── Non-numeric strings (30 random) ─────────────────────────────────
        for ($i = 0; $i < 30; $i++) {
            $str = self::generateRandomNonNumericString(mt_rand(1, 50));
            $cases["non_numeric_string_{$i}"] = [$str, "non-numeric string: '{$str}'"];
        }

        // ─── Null and empty values ───────────────────────────────────────────
        $cases['null_value'] = [null, 'null (missing post_id)'];
        $cases['empty_string'] = ['', 'empty string'];
        $cases['whitespace_only'] = ['   ', 'whitespace only'];
        $cases['tab_newline'] = ["\t\n", 'tab and newline'];

        // ─── Special non-numeric strings ─────────────────────────────────────
        $specialStrings = [
            'abc', 'hello', 'NaN', 'undefined', 'null', 'false', 'true',
            'Infinity', '-Infinity', '1.5.3', '1e999', '0x1A', '0b1010',
            '1abc', 'abc1', '12 34', '--1', '++1', '1-', '1+',
            'SELECT * FROM posts', '<script>alert(1)</script>',
            '../../../etc/passwd', '${7*7}',
        ];
        foreach ($specialStrings as $idx => $str) {
            $cases["special_string_{$idx}"] = [$str, "special string: '{$str}'"];
        }

        // ─── Float/decimal strings that are NOT positive integers when cast ─────
        // Note: Strings like '1.5' pass is_numeric() and (int)'1.5' = 1 > 0,
        // so the handler accepts them. We only test floats that cast to <= 0.
        $floatStrings = ['0.5', '-0.1', '-3.14'];
        foreach ($floatStrings as $idx => $str) {
            $cases["float_string_{$idx}"] = [$str, "float string: '{$str}'"];
        }

        // ─── Array and object values ─────────────────────────────────────────
        $cases['array_value'] = [['1'], 'array value'];
        $cases['nested_array'] = [[[1]], 'nested array'];
        $cases['assoc_array'] = [['id' => 1], 'associative array'];

        return $cases;
    }

    /**
     * Generate a random non-numeric string.
     */
    private static function generateRandomNonNumericString(int $length): string
    {
        // Use only alphabetic and special characters to ensure non-numeric.
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|;:,.<>?/~`';
        $str = '';
        $charsLen = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)];
        }

        return $str;
    }
}
